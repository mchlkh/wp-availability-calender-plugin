<?php
/**
 * CSV Import Manager
 *
 * Provides an admin UI to import professionals and per-day availability (room, floor).
 *
 * @package AvailabilityCalendarPlugin
 * @since 1.1.0
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YCP_Import_Manager
 */
class YCP_Import_Manager {

    /** @var YCP_Availability_Repository */
    private $availability_repository;

    /** Nonce action */
    const NONCE_ACTION = 'ycp_csv_import_nonce';

    /** Capability required */
    const REQUIRED_CAP = 'manage_options';

    public function __construct(YCP_Availability_Repository $availability_repository) {
        $this->availability_repository = $availability_repository;
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_action('admin_menu', [$this, 'add_import_submenu']);
        add_action('admin_post_ycp_handle_csv_import', [$this, 'handle_csv_import']);
    }

    /**
     * Add submenu under the Professionals top-level page
     */
    public function add_import_submenu(): void {
        add_submenu_page(
            'ycp-professionals',
            __('CSV Import', 'availability-calendar-plugin'),
            __('CSV Import', 'availability-calendar-plugin'),
            self::REQUIRED_CAP,
            'ycp-csv-import',
            [$this, 'render_import_page']
        );
    }

    /**
     * Render the CSV import page
     */
    public function render_import_page(): void {
        if (!current_user_can(self::REQUIRED_CAP)) {
            wp_die(__('You do not have permission to access this page.', 'availability-calendar-plugin'));
        }

        $action = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Import Professionals and Availability (CSV/XLSX)', 'availability-calendar-plugin'); ?></h1>
            <?php if (isset($_GET['ycp_msg'])): ?>
                <?php $type = isset($_GET['ycp_msg_type']) ? sanitize_text_field((string) $_GET['ycp_msg_type']) : 'updated'; ?>
                <div class="notice notice-<?php echo esc_attr($type === 'error' ? 'error' : 'success'); ?> is-dismissible">
                    <p><?php echo esc_html((string) wp_unslash($_GET['ycp_msg'])); ?></p>
                </div>
            <?php endif; ?>
            <p><?php esc_html_e('Upload a CSV or Excel (.xlsx) file with columns: professional_id (optional), date (Y-m-d), floor_id, room_id, name. Rows for the same professional_id will replace all existing per-day availability and update the name.', 'availability-calendar-plugin'); ?></p>

            <form method="post" action="<?php echo esc_url($action); ?>" enctype="multipart/form-data">
                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_import_nonce'); ?>
                <input type="hidden" name="action" value="ycp_handle_csv_import" />

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ycp_csv_file"><?php esc_html_e('Data File', 'availability-calendar-plugin'); ?></label></th>
                        <td><input type="file" name="ycp_csv_file" id="ycp_csv_file" accept=".csv,.xlsx" required /></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ycp_format"><?php esc_html_e('Format', 'availability-calendar-plugin'); ?></label></th>
                        <td>
                            <select name="ycp_format" id="ycp_format">
                                <option value="auto"><?php esc_html_e('Auto-detect', 'availability-calendar-plugin'); ?></option>
                                <option value="csv">CSV</option>
                                <option value="xlsx">XLSX</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Start Import', 'availability-calendar-plugin')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle CSV import submission
     */
    public function handle_csv_import(): void {
        if (!current_user_can(self::REQUIRED_CAP)) {
            wp_die(__('Insufficient permissions.', 'availability-calendar-plugin'));
        }
        if (!isset($_POST['ycp_import_nonce']) || !wp_verify_nonce($_POST['ycp_import_nonce'], self::NONCE_ACTION)) {
            wp_die(__('Security check failed.', 'availability-calendar-plugin'));
        }

        if (!isset($_FILES['ycp_csv_file']) || empty($_FILES['ycp_csv_file']['tmp_name'])) {
            $this->redirect_with_message('ycp-csv-import', 'error', __('No file uploaded.', 'availability-calendar-plugin'));
        }

        $file = $_FILES['ycp_csv_file'];
        $tmp_name = $file['tmp_name'];

        // Determine format by selector/extension
        $filename = isset($file['name']) ? (string) $file['name'] : '';
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $format = isset($_POST['ycp_format']) ? strtolower((string) $_POST['ycp_format']) : 'auto';
        if ($format === 'csv' || $format === 'xlsx') {
            $ext = $format;
        }

        $by_professional = [];
        $summary = [
            'created' => 0,
            'updated' => 0,
            'availability_rows' => 0,
        ];

        if ($ext === 'xlsx') {
            require_once plugin_dir_path(__FILE__) . 'simplexlsx.class.php';
            $xlsx = SimpleXLSX::parse($tmp_name);
            if ($xlsx === null) {
                $this->redirect_with_message('ycp-csv-import', 'error', __('Unable to parse XLSX file.', 'availability-calendar-plugin'));
            }
            $rows = $xlsx->rows();
            if (empty($rows)) {
                $this->redirect_with_message('ycp-csv-import', 'error', __('Empty Excel file.', 'availability-calendar-plugin'));
            }
            $header = array_map('trim', (array) $rows[0]);
            $header_map = $this->normalize_header($header);
            for ($i = 1; $i < count($rows); $i++) {
                $row = (array) $rows[$i];
                $record = $this->map_row($header_map, $row);
                if ($record === null) { continue; }
                $this->accumulate_record($by_professional, $record);
            }
        } else {
            $handle = fopen($tmp_name, 'r');
            if ($handle === false) {
                $this->redirect_with_message('ycp-csv-import', 'error', __('Unable to read uploaded file.', 'availability-calendar-plugin'));
            }
            $header = fgetcsv($handle, 0, ',');
            if ($header === false) {
                fclose($handle);
                $this->redirect_with_message('ycp-csv-import', 'error', __('Empty CSV file.', 'availability-calendar-plugin'));
            }
            $header_map = $this->normalize_header($header);
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                $record = $this->map_row($header_map, $row);
                if ($record === null) { continue; }
                $this->accumulate_record($by_professional, $record);
            }
            fclose($handle);
        }

        global $wpdb;
        $professionals_table = $wpdb->prefix . 'ycp_professionals';

        foreach ($by_professional as $group) {
            $professional_id = (int) $group['professional_id'];
            $fields = $group['fields'];

            if ($professional_id > 0) {
                $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM $professionals_table WHERE id = %d", $professional_id));
                if ($exists <= 0) {
                    // create new with provided fields
                    $wpdb->insert(
                        $professionals_table,
                        [
                            'name' => sanitize_text_field($fields['name']),
                            'available_dates' => '',
                            'display_order' => 0,
                        ],
                        ['%s','%s','%d']
                    );
                    $professional_id = (int) $wpdb->insert_id;
                    $summary['created']++;
                } else {
                    // update name only
                    $wpdb->update(
                        $professionals_table,
                        [ 'name' => sanitize_text_field($fields['name']) ],
                        ['id' => $professional_id],
                        ['%s'],
                        ['%d']
                    );
                    $summary['updated']++;
                }
            } else {
                // Create new because no valid ID was provided
                $wpdb->insert(
                    $professionals_table,
                    [
                        'name' => sanitize_text_field($fields['name']),
                        'available_dates' => '',
                        'display_order' => 0,
                    ],
                    ['%s','%s','%d']
                );
                $professional_id = (int) $wpdb->insert_id;
                $summary['created']++;
            }

            // Replace availability rows and rebuild available_dates
            $this->availability_repository->replace_professional_availability($professional_id, $group['availability']);
            $summary['availability_rows'] += count($group['availability']);
        }

        $msg = sprintf(
            /* translators: 1: created count, 2: updated count, 3: availability rows count */
            __('Import completed. Created: %1$d, Updated: %2$d, Availability rows: %3$d', 'availability-calendar-plugin'),
            $summary['created'],
            $summary['updated'],
            $summary['availability_rows']
        );

        $this->redirect_with_message('ycp-csv-import', 'updated', $msg);
    }

    /**
     * Normalize header names to array keys
     *
     * @param array<int, string> $header
     * @return array<string, int>
     */
    private function normalize_header(array $header): array {
        $map = [];
        foreach ($header as $idx => $cell) {
            $key = strtolower(trim((string) $cell));
            $map[$key] = (int) $idx;
        }
        return $map;
    }

    /**
     * Map row to associative array using header map
     *
     * @param array<string, int> $header_map
     * @param array<int, string> $row
     * @return array<string, string>|null
     */
    private function map_row(array $header_map, array $row): ?array {
        // Require at least date or name to proceed
        if (!isset($header_map['date']) && !isset($header_map['name'])) {
            return null;
        }
        $get = function(string $key) use ($header_map, $row) {
            if (!isset($header_map[$key])) { return null; }
            $idx = (int) $header_map[$key];
            return isset($row[$idx]) ? trim((string) $row[$idx]) : null;
        };
        return [
            'professional_id' => $get('professional_id'),
            'date' => $get('date'),
            'floor_id' => $get('floor_id'),
            'room_id' => $get('room_id'),
            'name' => $get('name'),
        ];
    }

    /**
     * Accumulate mapped record into by_professional structure
     *
     * @param array<string,array> &$by_professional
     * @param array<string,string|null> $record
     */
    private function accumulate_record(array &$by_professional, array $record): void {
        $prof_id = isset($record['professional_id']) ? absint((string) $record['professional_id']) : 0;
        $key = (string) $prof_id . '|' . ($record['name'] ?? '');
        if (!isset($by_professional[$key])) {
            $by_professional[$key] = [
                'professional_id' => $prof_id,
                'fields' => [
                    'name' => (string) ($record['name'] ?? ''),
                ],
                'availability' => [],
            ];
        }
        $by_professional[$key]['availability'][] = [
            'date' => (string) ($record['date'] ?? ''),
            'floor_id' => isset($record['floor_id']) && is_numeric($record['floor_id']) ? (int) $record['floor_id'] : null,
            'room_id' => isset($record['room_id']) && is_numeric($record['room_id']) ? (int) $record['room_id'] : null,
        ];
    }

    /**
     * Redirect back to import page with admin notice
     */
    private function redirect_with_message(string $page_slug, string $type, string $message): void {
        $url = add_query_arg([
            'page' => $page_slug,
            'ycp_msg' => rawurlencode($message),
            'ycp_msg_type' => $type,
        ], admin_url('admin.php'));
        wp_redirect($url);
        exit;
    }
}


