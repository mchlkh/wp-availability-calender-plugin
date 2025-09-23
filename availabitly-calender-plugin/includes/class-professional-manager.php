<?php
/**
 * Professional Manager Class
 * 
 * Manages professional data and availability information
 * 
 * @package AvailabilityCalendarPlugin
 * @since 1.0.0
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class YCP_Professional_Manager
 * 
 * Manages professional data storage and retrieval
 */
class YCP_Professional_Manager {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'availability-calendar-plugin';
    
    /**
     * Database table name
     */
    const TABLE_NAME = 'ycp_professionals';
    
    /**
     * Nonce action for security
     */
    const NONCE_ACTION = 'ycp_professional_nonce';
    
    /**
     * Initialize the professional manager
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_ycp_save_professional', [$this, 'ajax_save_professional']);
        add_action('wp_ajax_ycp_delete_professional', [$this, 'ajax_delete_professional']);
        add_action('wp_ajax_ycp_get_professional', [$this, 'ajax_get_professional']);
        add_action('wp_ajax_ycp_get_day_details', [$this, 'ajax_get_day_details']);
        add_action('wp_ajax_ycp_save_day_details', [$this, 'ajax_save_day_details']);
        add_action('wp_ajax_ycp_get_locations', [$this, 'ajax_get_locations']);
    }
    
    /**
     * Create database table for professionals
     */
    public static function create_table(): void {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            profile_url varchar(500),
            image_url varchar(500),
            available_dates text,
            display_order int NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_menu_page(
            __('Professionals', self::TEXT_DOMAIN),
            __('Professionals', self::TEXT_DOMAIN),
            'manage_options',
            'ycp-professionals',
            [$this, 'render_admin_page'],
            'dashicons-businessperson',
            20
        );
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page(): void {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Manage Professionals', self::TEXT_DOMAIN); ?></h1>
            
            <div class="ycp-admin-container">
                <!-- Professional Form -->
                <div class="ycp-form-section">
                    <h2><?php esc_html_e('Add/Edit Professional', self::TEXT_DOMAIN); ?></h2>
                    <form id="ycp-professional-form" method="post">
                        <?php wp_nonce_field(self::NONCE_ACTION, 'ycp_nonce'); ?>
                        <input type="hidden" id="ycp_professional_id" name="professional_id" value="">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="ycp_name"><?php esc_html_e('Name:', self::TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="ycp_name" name="name" class="regular-text" required>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ycp_description"><?php esc_html_e('Description:', self::TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <textarea id="ycp_description" name="description" rows="3" class="large-text"></textarea>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ycp_profile_url"><?php esc_html_e('Profile URL:', self::TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="ycp_profile_url" name="profile_url" class="regular-text">
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ycp_image_url"><?php esc_html_e('Image URL:', self::TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <input type="url" id="ycp_image_url" name="image_url" class="regular-text">
                                    <button type="button" id="ycp_upload_image" class="button"><?php esc_html_e('Upload Image', self::TEXT_DOMAIN); ?></button>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="ycp_available_dates"><?php esc_html_e('Available Dates:', self::TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="ycp_available_dates" name="available_dates" class="regular-text" readonly>
                                    <p class="description"><?php esc_html_e('Click to select dates', self::TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="ycp_day_details_date"><?php esc_html_e('Per-Day Details:', self::TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                                        <input type="text" id="ycp_day_details_date" class="regular-text" placeholder="YYYY-MM-DD" style="max-width:200px;" />
                                        <select id="ycp_day_floor" style="max-width:220px; min-width:200px;"></select>
                                        <select id="ycp_day_room" style="max-width:220px; min-width:200px;"></select>
                                        <button type="button" id="ycp_load_day_details" class="button"><?php esc_html_e('Load', self::TEXT_DOMAIN); ?></button>
                                        <button type="button" id="ycp_save_day_details" class="button button-primary"><?php esc_html_e('Save Day Details', self::TEXT_DOMAIN); ?></button>
                                    </div>
                                    <p class="description"><?php esc_html_e('Pick a date and set room and floor for that specific day. Lists are sourced from Rooms & Floors.', self::TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="ycp_display_order"><?php esc_html_e('Display Order:', self::TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="ycp_display_order" name="display_order" class="small-text" value="0" min="0" step="1">
                                    <p class="description"><?php esc_html_e('Lower numbers appear first.', self::TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary"><?php esc_html_e('Save Professional', self::TEXT_DOMAIN); ?></button>
                            <button type="button" id="ycp_reset_form" class="button"><?php esc_html_e('Reset Form', self::TEXT_DOMAIN); ?></button>
                        </p>
                    </form>
                </div>
                
                <!-- Professionals List -->
                <div class="ycp-list-section">
                    <h2><?php esc_html_e('Current Professionals', self::TEXT_DOMAIN); ?></h2>
                    
                    <div class="ycp-info-box">
                        <p><strong><?php esc_html_e('How to use Professional IDs:', self::TEXT_DOMAIN); ?></strong></p>
                        <p><?php esc_html_e('Use the ID numbers shown below in your shortcodes like this:', self::TEXT_DOMAIN); ?></p>
                        <code>[ycp_availability_data professional_id="11" show_title="false" separator=" | " css_class="ycp-availability-compact modern"]</code>
                    </div>
                    
                    <div id="ycp-professionals-list">
                        <?php $this->render_professionals_list(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php
    }
    
    /**
     * Render professionals list
     */
    private function render_professionals_list(): void {
        $professionals = $this->get_all_professionals();
        
        if (empty($professionals)) {
            echo '<p>' . esc_html__('No professionals found.', self::TEXT_DOMAIN) . '</p>';
            return;
        }
        
        foreach ($professionals as $professional) {
            ?>
            <div class="ycp-professional-item" data-id="<?php echo esc_attr($professional['id']); ?>">
                <h3>
                    <?php echo esc_html($professional['name']); ?>
                    <span class="ycp-professional-id">(ID: <?php echo esc_html($professional['id']); ?>)</span>
                </h3>
                <p><em><?php esc_html_e('Order', self::TEXT_DOMAIN); ?>: <?php echo isset($professional['display_order']) ? intval($professional['display_order']) : 0; ?></em></p>
                
                <?php if (!empty($professional['image_url'])): ?>
                    <img src="<?php echo esc_url($professional['image_url']); ?>" class="ycp-image-preview" alt="">
                <?php endif; ?>
                
                <?php if (!empty($professional['description'])): ?>
                    <p><?php echo esc_html($professional['description']); ?></p>
                <?php endif; ?>
                
                <?php if (!empty($professional['available_dates'])): ?>
                    <p><strong><?php esc_html_e('Available Dates:', self::TEXT_DOMAIN); ?></strong> <?php echo esc_html($professional['available_dates']); ?></p>
                <?php endif; ?>
                
                <div class="ycp-professional-actions">
                    <button type="button" class="button ycp-edit-professional" data-id="<?php echo esc_attr($professional['id']); ?>">
                        <?php esc_html_e('Edit', self::TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="button button-link-delete ycp-delete-professional" data-id="<?php echo esc_attr($professional['id']); ?>">
                        <?php esc_html_e('Delete', self::TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
            <?php
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts(string $hook): void {
        if ($hook !== 'toplevel_page_ycp-professionals') {
            return;
        }
        
        // Admin styles
        wp_enqueue_style(
            'ycp-admin-style',
            plugin_dir_url(dirname(__FILE__)) . 'admin/admin.css',
            [],
            '1.0.0'
        );
        
        // Enqueue Flatpickr for date selection
        wp_enqueue_script(
            'flatpickr', 
            'https://cdn.jsdelivr.net/npm/flatpickr', 
            [], 
            null, 
            true
        );
        
        wp_enqueue_style(
            'flatpickr-style', 
            'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css'
        );
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Enqueue custom admin script
        wp_enqueue_script(
            'ycp-admin', 
            plugin_dir_url(dirname(__FILE__)) . 'admin/admin.js', 
            ['jquery', 'flatpickr'], 
            '1.0.0', 
            true
        );
        
        // Localize script
        wp_localize_script('ycp-admin', 'ycp_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION)
        ]);
    }
    
    /**
     * AJAX handler for saving professional
     */
    public function ajax_save_professional(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', self::TEXT_DOMAIN)]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', self::TEXT_DOMAIN)]);
        }
        
        $professional_id = intval($_POST['professional_id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $profile_url = sanitize_url($_POST['profile_url'] ?? '');
        $image_url = sanitize_url($_POST['image_url'] ?? '');
        $available_dates = sanitize_text_field($_POST['available_dates'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        
        if (empty($name)) {
            wp_send_json_error(['message' => __('Name is required.', self::TEXT_DOMAIN)]);
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'profile_url' => $profile_url,
            'image_url' => $image_url,
            'available_dates' => $available_dates,
            'display_order' => $display_order
        ];
        
        if ($professional_id > 0) {
            $result = $this->update_professional($professional_id, $data);
        } else {
            $result = $this->create_professional($data);
        }
        
        if (!$result) {
            wp_send_json_error(['message' => __('Failed to save professional.', self::TEXT_DOMAIN)]);
        }

        // Sync selected dates to per-day availability table
        require_once plugin_dir_path(__FILE__) . 'class-availability-repository.php';
        $repo = new YCP_Availability_Repository();
        $dates = $this->parse_dates_string($available_dates);
        $target_id = $professional_id > 0 ? $professional_id : $this->get_last_insert_id();
        if ($target_id > 0) {
            $repo->sync_dates($target_id, $dates);
        }

        wp_send_json_success([
            'message' => __('Professional saved successfully.', self::TEXT_DOMAIN),
            'professionals' => $this->get_all_professionals()
        ]);
    }
    
    /**
     * AJAX handler for deleting professional
     */
    public function ajax_delete_professional(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', self::TEXT_DOMAIN)]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', self::TEXT_DOMAIN)]);
        }
        
        $professional_id = intval($_POST['professional_id'] ?? 0);
        
        if ($professional_id <= 0) {
            wp_send_json_error(['message' => __('Invalid professional ID.', self::TEXT_DOMAIN)]);
        }
        
        $result = $this->delete_professional($professional_id);
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Professional deleted successfully.', self::TEXT_DOMAIN),
                'professionals' => $this->get_all_professionals()
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to delete professional.', self::TEXT_DOMAIN)]);
        }
    }
    
    /**
     * AJAX handler for getting professional
     */
    public function ajax_get_professional(): void {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', self::TEXT_DOMAIN)]);
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', self::TEXT_DOMAIN)]);
        }
        
        $professional_id = intval($_POST['professional_id'] ?? 0);
        
        if ($professional_id <= 0) {
            wp_send_json_error(['message' => __('Invalid professional ID.', self::TEXT_DOMAIN)]);
        }
        
        $professional = $this->get_professional($professional_id);
        
        if ($professional) {
            wp_send_json_success($professional);
        } else {
            wp_send_json_error(['message' => __('Professional not found.', self::TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Get room/floor for a specific day
     */
    public function ajax_get_day_details(): void {
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', self::TEXT_DOMAIN)]);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', self::TEXT_DOMAIN)]);
        }
        $professional_id = intval($_POST['professional_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');
        if ($professional_id <= 0 || empty($date)) {
            wp_send_json_error(['message' => __('Invalid input.', self::TEXT_DOMAIN)]);
        }

        require_once plugin_dir_path(__FILE__) . 'class-availability-repository.php';
        $repo = new YCP_Availability_Repository();
        $details = $repo->get_day_details($professional_id, $date);
        wp_send_json_success($details);
    }

    /**
     * AJAX: Save room/floor for a specific day (upsert)
     */
    public function ajax_save_day_details(): void {
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', self::TEXT_DOMAIN)]);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', self::TEXT_DOMAIN)]);
        }
        $professional_id = intval($_POST['professional_id'] ?? 0);
        $date = sanitize_text_field($_POST['date'] ?? '');
        $room = isset($_POST['room']) ? sanitize_text_field($_POST['room']) : null;
        $floor = isset($_POST['floor']) ? sanitize_text_field($_POST['floor']) : null;
        // Normalize empty strings to null to represent "no selection"
        if ($room !== null) {
            $room = trim((string) $room);
            if ($room === '') { $room = null; }
        }
        if ($floor !== null) {
            $floor = trim((string) $floor);
            if ($floor === '') { $floor = null; }
        }
        if ($professional_id <= 0 || empty($date)) {
            wp_send_json_error(['message' => __('Invalid input.', self::TEXT_DOMAIN)]);
        }

        require_once plugin_dir_path(__FILE__) . 'class-availability-repository.php';
        $repo = new YCP_Availability_Repository();
        $repo->upsert_availability($professional_id, $date, $room, $floor);
        wp_send_json_success(['message' => __('Day details saved.', self::TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Get floors and rooms lists for dropdowns
     */
    public function ajax_get_locations(): void {
        if (!wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed.', self::TEXT_DOMAIN)]);
        }
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions.', self::TEXT_DOMAIN)]);
        }
        global $wpdb;
        $floors_table = $wpdb->prefix . 'ycp_floors';
        $rooms_table = $wpdb->prefix . 'ycp_rooms';
        $floors = $wpdb->get_results("SELECT id, name, url FROM $floors_table ORDER BY name ASC", ARRAY_A) ?: [];
        $rooms = $wpdb->get_results("SELECT id, name FROM $rooms_table ORDER BY name ASC", ARRAY_A) ?: [];
        wp_send_json_success(['floors' => $floors, 'rooms' => $rooms]);
    }
    
    /**
     * Create a new professional
     */
    public function create_professional(array $data): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            ['%s', '%s', '%s', '%s', '%s', '%d']
        );
        
        return $result !== false;
    }

    /**
     * Get last insert id from wpdb
     */
    private function get_last_insert_id(): int {
        global $wpdb;
        return (int) $wpdb->insert_id;
    }
    
    /**
     * Update an existing professional
     */
    public function update_professional(int $id, array $data): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $result = $wpdb->update(
            $table_name,
            $data,
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s', '%d'],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a professional
     */
    public function delete_professional(int $id): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $result = $wpdb->delete(
            $table_name,
            ['id' => $id],
            ['%d']
        );
        
        return $result !== false;
    }
    
    /**
     * Get a single professional
     */
    public function get_professional(int $id): ?array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $professional = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id),
            ARRAY_A
        );
        
        return $professional ?: null;
    }
    
    /**
     * Get all professionals
     */
    public function get_all_professionals(): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $professionals = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY display_order ASC, name ASC",
            ARRAY_A
        );
        
        return $professionals ?: [];
    }
    
    /**
     * Get professionals available on a specific date
     */
    public function get_professionals_for_date(string $date): array {
        $all_professionals = $this->get_all_professionals();
        $available_professionals = [];
        
        foreach ($all_professionals as $professional) {
            $available_dates = $this->parse_dates_string($professional['available_dates']);
            
            if (in_array($date, $available_dates, true)) {
                $available_professionals[] = $this->format_professional_for_frontend($professional);
            }
        }
        
        return $available_professionals;
    }
    
    /**
     * Format professional data for frontend display
     */
    public function format_professional_for_frontend(array $professional): array {
        $today = date('Y-m-d');
        $available_dates = $this->parse_dates_string($professional['available_dates']);
        
        return [
            'id' => $professional['id'],
            'name' => $professional['name'],
            'image' => $this->get_image_html($professional['image_url']),
            'url' => $professional['profile_url'],
            'description' => $professional['description'],
            'available_dates' => $available_dates,
            'is_available_today' => in_array($today, $available_dates, true)
        ];
    }
    
    /**
     * Get image HTML for frontend
     */
    private function get_image_html(?string $image_url): string {
        if (empty($image_url)) {
            return '<img src="' . plugin_dir_url(dirname(__FILE__)) . 'public/assets/default-avatar.png" alt="Default Avatar" />';
        }
        
        return '<img src="' . esc_url($image_url) . '" alt="Professional Photo" />';
    }
    
    /**
     * Parse dates string into array
     */
    private function parse_dates_string(string $dates_string): array {
        if (empty($dates_string)) {
            return [];
        }
        
        $dates = array_map('trim', explode(',', $dates_string));
        return array_filter($dates, function($date) {
            return !empty($date) && $this->is_valid_date($date);
        });
    }
    
    /**
     * Validate date format
     */
    private function is_valid_date(string $date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
} 