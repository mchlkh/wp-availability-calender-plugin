<?php
/**
 * CSV Export Manager
 *
 * Streams a CSV of professionals and per-day availability (room, floor).
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
 * Class YCP_Export_Manager
 */
class YCP_Export_Manager {

    /** Nonce action */
    const NONCE_ACTION = 'ycp_csv_export_nonce';

    /** Capability required */
    const REQUIRED_CAP = 'manage_options';

    public function __construct() {
        $this->init_hooks();
    }

    private function init_hooks(): void {
        add_action('admin_menu', [$this, 'add_export_submenu']);
        add_action('admin_post_ycp_handle_csv_export', [$this, 'handle_csv_export']);
    }

    /**
     * Add submenu under the Professionals top-level page
     */
    public function add_export_submenu(): void {
        add_submenu_page(
            'ycp-professionals',
            __('CSV Export', 'availability-calendar-plugin'),
            __('CSV Export', 'availability-calendar-plugin'),
            self::REQUIRED_CAP,
            'ycp-csv-export',
            [$this, 'render_export_page']
        );
    }

    /**
     * Render the CSV export page with a simple download form
     */
    public function render_export_page(): void {
        if (!current_user_can(self::REQUIRED_CAP)) {
            wp_die(__('You do not have permission to access this page.', 'availability-calendar-plugin'));
        }

        $action = admin_url('admin-post.php');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Export Professionals and Availability (CSV/XLSX)', 'availability-calendar-plugin'); ?></h1>
            <p><?php esc_html_e('Download professionals and per-day availability with room and floor. Columns: professional_id, date, room, floor, name.', 'availability-calendar-plugin'); ?></p>

            <form method="post" action="<?php echo esc_url($action); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_export_nonce'); ?>
                <input type="hidden" name="action" value="ycp_handle_csv_export" />
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ycp_export_format"><?php esc_html_e('Format', 'availability-calendar-plugin'); ?></label></th>
                        <td>
                            <select name="ycp_export_format" id="ycp_export_format">
                                <option value="csv">CSV</option>
                                <option value="xlsx">XLSX</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Download', 'availability-calendar-plugin')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Handle CSV export: streams a file to the browser
     */
    public function handle_csv_export(): void {
        if (!current_user_can(self::REQUIRED_CAP)) {
            wp_die(__('Insufficient permissions.', 'availability-calendar-plugin'));
        }
        if (!isset($_POST['ycp_export_nonce']) || !wp_verify_nonce($_POST['ycp_export_nonce'], self::NONCE_ACTION)) {
            wp_die(__('Security check failed.', 'availability-calendar-plugin'));
        }

        $format = isset($_POST['ycp_export_format']) ? strtolower((string) $_POST['ycp_export_format']) : 'csv';

        global $wpdb;
        $professionals_table = $wpdb->prefix . 'ycp_professionals';
        $availability_table = $wpdb->prefix . 'ycp_availability';

        $professionals = $wpdb->get_results(
            "SELECT id, name, description, profile_url, image_url, display_order FROM $professionals_table ORDER BY display_order ASC, name ASC",
            ARRAY_A
        );

        if ($format === 'xlsx') {
            // Build rows in memory then emit XLSX via a minimal generator
            $rows = [];
            $rows[] = ['professional_id','date','floor_id','room_id','name'];
            foreach ($professionals as $p) {
                $pid = (int) $p['id'];
                $availability = $wpdb->get_results(
                    $wpdb->prepare("SELECT date, room, floor FROM $availability_table WHERE professional_id = %d ORDER BY date ASC", $pid),
                    ARRAY_A
                );
                if (empty($availability)) {
                    $rows[] = [$pid,'', '', '', $p['name']];
                    continue;
                }
                foreach ($availability as $a) {
                    $rows[] = [$pid,$a['date'],$this->lookup_floor_id($a['floor']),$this->lookup_room_id($a['room']),$p['name']];
                }
            }
            $this->output_xlsx($rows);
        } else {
            $filename = 'ycp-export-' . date('Ymd-His') . '.csv';
            nocache_headers();
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $filename);
            $output = fopen('php://output', 'w');
            if ($output === false) { wp_die(__('Unable to open output stream.', 'availability-calendar-plugin')); }
            fputcsv($output, ['professional_id','date','floor_id','room_id','name']);
            foreach ($professionals as $p) {
                $pid = (int) $p['id'];
                $availability = $wpdb->get_results(
                    $wpdb->prepare("SELECT date, room, floor FROM $availability_table WHERE professional_id = %d ORDER BY date ASC", $pid),
                    ARRAY_A
                );
                if (empty($availability)) {
                    fputcsv($output, [$pid,'', '', '', $p['name']]);
                    continue;
                }
                foreach ($availability as $a) {
                    fputcsv($output, [$pid,$a['date'],$this->lookup_floor_id($a['floor']),$this->lookup_room_id($a['room']),$p['name']]);
                }
            }
            fclose($output);
            exit;
        }
    }

    private function lookup_floor_id(?string $floor_name): string {
        if (empty($floor_name)) return '';
        global $wpdb;
        $table = $wpdb->prefix . 'ycp_floors';
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE name = %s", $floor_name));
        return $id ? (string) (int) $id : '';
    }

    private function lookup_room_id(?string $room_name): string {
        if (empty($room_name)) return '';
        global $wpdb;
        $table = $wpdb->prefix . 'ycp_rooms';
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE name = %s", $room_name));
        return $id ? (string) (int) $id : '';
    }

    /**
     * Emit a minimal XLSX file from a 2D array of rows
     * Uses a basic XML approach to avoid external libs.
     */
    private function output_xlsx(array $rows): void {
        $filename = 'ycp-export-' . date('Ymd-His') . '.xlsx';
        nocache_headers();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename=' . $filename);

        // Create minimal XLSX in-memory (very basic, single sheet, shared strings)
        $sharedStrings = [];
        $sidx = [];
        $sheetRowsXml = '';
        $r = 1;
        foreach ($rows as $row) {
            $sheetRowsXml .= '<row r="' . $r . '">';
            $c = 1;
            foreach ($row as $val) {
                $val = (string) $val;
                if (!isset($sidx[$val])) { $sidx[$val] = count($sharedStrings); $sharedStrings[] = $val; }
                $col = $this->xlsxColName($c) . $r;
                $sheetRowsXml .= '<c r="' . $col . '" t="s"><v>' . $sidx[$val] . '</v></c>';
                $c++;
            }
            $sheetRowsXml .= '</row>';
            $r++;
        }

        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="' . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">'
            . implode('', array_map(function($s){ return '<si><t>' . htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</t></si>'; }, $sharedStrings))
            . '</sst>';

        $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheetRowsXml . '</sheetData></worksheet>';

        $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>';

        $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $wbRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
            . '</Relationships>';

        $contentTypesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/_rels/.rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '</Types>';

        $zip = new ZipArchive();
        $tmp = tmpfile();
        $meta = stream_get_meta_data($tmp);
        $path = $meta['uri'];
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $contentTypesXml);
        $zip->addFromString('_rels/.rels', $relsXml);
        $zip->addFromString('xl/workbook.xml', $workbookXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRelsXml);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);
        $zip->close();

        readfile($path);
        fclose($tmp);
        exit;
    }

    private function xlsxColName(int $index): string {
        $name = '';
        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }
        return $name;
    }
}


