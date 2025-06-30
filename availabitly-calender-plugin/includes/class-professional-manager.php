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
        $this->create_table();
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
    }
    
    /**
     * Create database table for professionals
     */
    private function create_table(): void {
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
                    <div id="ycp-professionals-list">
                        <?php $this->render_professionals_list(); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .ycp-admin-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 20px;
        }
        
        .ycp-form-section, .ycp-list-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .ycp-professional-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .ycp-professional-item h3 {
            margin: 0 0 10px 0;
        }
        
        .ycp-professional-actions {
            margin-top: 10px;
        }
        
        .ycp-professional-actions .button {
            margin-right: 5px;
        }
        
        .ycp-image-preview {
            max-width: 100px;
            max-height: 100px;
            margin-top: 10px;
        }
        </style>
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
                <h3><?php echo esc_html($professional['name']); ?></h3>
                
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
        
        if (empty($name)) {
            wp_send_json_error(['message' => __('Name is required.', self::TEXT_DOMAIN)]);
        }
        
        $data = [
            'name' => $name,
            'description' => $description,
            'profile_url' => $profile_url,
            'image_url' => $image_url,
            'available_dates' => $available_dates
        ];
        
        if ($professional_id > 0) {
            $result = $this->update_professional($professional_id, $data);
        } else {
            $result = $this->create_professional($data);
        }
        
        if ($result) {
            wp_send_json_success([
                'message' => __('Professional saved successfully.', self::TEXT_DOMAIN),
                'professionals' => $this->get_all_professionals()
            ]);
        } else {
            wp_send_json_error(['message' => __('Failed to save professional.', self::TEXT_DOMAIN)]);
        }
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
     * Create a new professional
     */
    public function create_professional(array $data): bool {
        global $wpdb;
        
        $table_name = $wpdb->prefix . self::TABLE_NAME;
        
        $result = $wpdb->insert(
            $table_name,
            $data,
            ['%s', '%s', '%s', '%s', '%s']
        );
        
        return $result !== false;
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
            ['%s', '%s', '%s', '%s', '%s'],
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
            "SELECT * FROM $table_name ORDER BY name ASC",
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