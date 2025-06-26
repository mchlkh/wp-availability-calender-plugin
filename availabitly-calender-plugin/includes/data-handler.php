<?php
/**
 * Data Handler for Availability Calendar Plugin
 * 
 * Provides secure interfaces for accessing availability data
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
 * Class YCP_Data_Handler
 * 
 * Handles all data operations for the availability calendar plugin
 * with proper security measures and validation
 */
class YCP_Data_Handler {
    
    /**
     * Nonce action for AJAX requests
     */
    const NONCE_ACTION = 'ycp_availability_data_nonce';
    
    /**
     * Capability required to access availability data
     */
    const REQUIRED_CAPABILITY = 'read';
    
    /**
     * Initialize the data handler
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Register AJAX actions
        add_action('wp_ajax_ycp_get_availability_data', [$this, 'handle_get_availability_data']);
        add_action('wp_ajax_nopriv_ycp_get_availability_data', [$this, 'handle_get_availability_data']);
        add_action('wp_ajax_ycp_get_compact_availability_data', [$this, 'handle_get_compact_availability_data']);
        add_action('wp_ajax_nopriv_ycp_get_compact_availability_data', [$this, 'handle_get_compact_availability_data']);
        
        // Register REST API routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        
        // Register shortcode
        add_shortcode('ycp_availability', [$this, 'render_availability_shortcode']);
        
        // Register global functions
        add_action('init', [$this, 'register_availability_functions']);
    }
    
    /**
     * Register REST API routes for availability data
     */
    public function register_rest_routes(): void {
        register_rest_route('ycp/v1', '/availability/(?P<professional_id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_availability_rest'],
            'permission_callback' => [$this, 'check_rest_permissions'],
            'args' => [
                'professional_id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                ],
                'date_from' => [
                    'validate_callback' => function($param) {
                        return $this->validate_date_format($param);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'date_to' => [
                    'validate_callback' => function($param) {
                        return $this->validate_date_format($param);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        register_rest_route('ycp/v1', '/availability', [
            'methods' => 'GET',
            'callback' => [$this, 'get_all_availability_rest'],
            'permission_callback' => [$this, 'check_rest_permissions'],
            'args' => [
                'date' => [
                    'validate_callback' => function($param) {
                        return $this->validate_date_format($param);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limit' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    },
                    'sanitize_callback' => 'absint',
                    'default' => 50,
                ],
            ],
        ]);
    }
    
    /**
     * Check permissions for REST API access
     */
    public function check_rest_permissions(): bool {
        // Allow public access for availability data
        // You can restrict this further if needed
        return true;
    }
    
    /**
     * Handle AJAX request for availability data
     */
    public function handle_get_availability_data(): void {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => 'Security check failed'], 403);
        }
        
        // Check user permissions
        if (!current_user_can(self::REQUIRED_CAPABILITY)) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }
        
        $professional_id = isset($_POST['professional_id']) ? absint($_POST['professional_id']) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field($_POST['date']) : '';
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        try {
            if ($professional_id > 0) {
                $data = $this->get_professional_availability($professional_id, $date_from, $date_to);
            } else {
                $data = $this->get_availability_by_date($date);
            }
            
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Handle AJAX request for compact availability data
     */
    public function handle_get_compact_availability_data(): void {
        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => 'Security check failed'], 403);
        }
        
        // Check user permissions
        if (!current_user_can(self::REQUIRED_CAPABILITY)) {
            wp_send_json_error(['message' => 'Insufficient permissions'], 403);
        }
        
        $professional_id = isset($_POST['professional_id']) ? absint($_POST['professional_id']) : 0;
        $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
        
        if ($professional_id <= 0) {
            wp_send_json_error(['message' => 'Professional ID is required'], 400);
        }
        
        try {
            $data = $this->get_professional_availability_compact($professional_id, $date_from, $date_to, true);
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * REST API endpoint for getting availability by professional ID
     */
    public function get_availability_rest(WP_REST_Request $request): WP_REST_Response {
        $professional_id = $request->get_param('professional_id');
        $date_from = $request->get_param('date_from');
        $date_to = $request->get_param('date_to');
        
        try {
            $data = $this->get_professional_availability($professional_id, $date_from, $date_to);
            return new WP_REST_Response($data, 200);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * REST API endpoint for getting all availability data
     */
    public function get_all_availability_rest(WP_REST_Request $request): WP_REST_Response {
        $date = $request->get_param('date');
        $limit = $request->get_param('limit');
        
        try {
            $data = $this->get_availability_by_date($date, $limit);
            return new WP_REST_Response($data, 200);
        } catch (Exception $e) {
            return new WP_REST_Response(['error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Get availability data for a specific professional
     * 
     * @param int $professional_id The professional's post ID
     * @param string $date_from Optional start date (Y-m-d format)
     * @param string $date_to Optional end date (Y-m-d format)
     * @return array Availability data
     * @throws Exception If professional not found or invalid data
     */
    public function get_professional_availability(int $professional_id, string $date_from = '', string $date_to = ''): array {
        // Validate professional exists
        $professional = get_post($professional_id);
        if (!$professional || $professional->post_type !== 'ycp_professional') {
            throw new Exception('Professional not found');
        }
        
        // Get availability dates
        $available_dates = get_post_meta($professional_id, '_ycp_available_dates', true);
        $date_array = $this->parse_dates_string($available_dates);
        
        // Filter by date range if provided
        if (!empty($date_from) || !empty($date_to)) {
            $date_array = $this->filter_dates_by_range($date_array, $date_from, $date_to);
        }
        
        // Get additional professional data
        $profile_url = get_post_meta($professional_id, '_ycp_profile_url', true);
        $description = get_post_meta($professional_id, '_ycp_description', true);
        $thumbnail_id = get_post_thumbnail_id($professional_id);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
        
        return [
            'id' => $professional_id,
            'name' => $professional->post_title,
            'available_dates' => $date_array,
            'profile_url' => esc_url($profile_url),
            'description' => esc_html($description),
            'thumbnail_url' => esc_url($thumbnail_url),
            'is_available_today' => in_array(date('Y-m-d'), $date_array),
            'total_available_days' => count($date_array),
        ];
    }
    
    /**
     * Get availability data for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @param int $limit Maximum number of professionals to return
     * @return array Array of available professionals
     */
    public function get_availability_by_date(string $date = '', int $limit = 50): array {
        $date = !empty($date) ? $date : date('Y-m-d');
        
        if (!$this->validate_date_format($date)) {
            throw new Exception('Invalid date format. Use Y-m-d format.');
        }
        
        $args = [
            'post_type' => 'ycp_professional',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
        ];
        
        $query = new WP_Query($args);
        $available_professionals = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $professional_id = get_the_ID();
                $available_dates = get_post_meta($professional_id, '_ycp_available_dates', true);
                $date_array = $this->parse_dates_string($available_dates);
                
                if (in_array($date, $date_array)) {
                    $profile_url = get_post_meta($professional_id, '_ycp_profile_url', true);
                    $description = get_post_meta($professional_id, '_ycp_description', true);
                    $thumbnail_id = get_post_thumbnail_id($professional_id);
                    $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                    
                    $available_professionals[] = [
                        'id' => $professional_id,
                        'name' => get_the_title(),
                        'profile_url' => esc_url($profile_url),
                        'description' => esc_html($description),
                        'thumbnail_url' => esc_url($thumbnail_url),
                        'is_available_today' => in_array(date('Y-m-d'), $date_array),
                    ];
                }
            }
        }
        
        wp_reset_postdata();
        
        return [
            'date' => $date,
            'available_professionals' => $available_professionals,
            'count' => count($available_professionals),
        ];
    }
    
    /**
     * Get all professionals with their availability data
     * 
     * @param int $limit Maximum number of professionals to return
     * @return array Array of all professionals with availability
     */
    public function get_all_professionals_availability(int $limit = 100): array {
        $args = [
            'post_type' => 'ycp_professional',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
        ];
        
        $query = new WP_Query($args);
        $professionals = [];
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $professional_id = get_the_ID();
                $available_dates = get_post_meta($professional_id, '_ycp_available_dates', true);
                $date_array = $this->parse_dates_string($available_dates);
                
                $profile_url = get_post_meta($professional_id, '_ycp_profile_url', true);
                $description = get_post_meta($professional_id, '_ycp_description', true);
                $thumbnail_id = get_post_thumbnail_id($professional_id);
                $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
                
                $professionals[] = [
                    'id' => $professional_id,
                    'name' => get_the_title(),
                    'available_dates' => $date_array,
                    'profile_url' => esc_url($profile_url),
                    'description' => esc_html($description),
                    'thumbnail_url' => esc_url($thumbnail_url),
                    'is_available_today' => in_array(date('Y-m-d'), $date_array),
                    'total_available_days' => count($date_array),
                ];
            }
        }
        
        wp_reset_postdata();
        
        return [
            'professionals' => $professionals,
            'count' => count($professionals),
        ];
    }
    
    /**
     * Parse dates string into array
     * 
     * @param string $dates_string Comma-separated dates
     * @return array Array of dates
     */
    private function parse_dates_string(string $dates_string): array {
        if (empty($dates_string)) {
            return [];
        }
        
        $dates = array_map('trim', explode(',', $dates_string));
        return array_filter($dates, function($date) {
            return $this->validate_date_format($date);
        });
    }
    
    /**
     * Filter dates by range
     * 
     * @param array $dates Array of dates
     * @param string $date_from Start date
     * @param string $date_to End date
     * @return array Filtered dates
     */
    private function filter_dates_by_range(array $dates, string $date_from, string $date_to): array {
        return array_filter($dates, function($date) use ($date_from, $date_to) {
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$date_obj) {
                return false;
            }
            
            $from_valid = empty($date_from) || $date_obj >= DateTime::createFromFormat('Y-m-d', $date_from);
            $to_valid = empty($date_to) || $date_obj <= DateTime::createFromFormat('Y-m-d', $date_to);
            
            return $from_valid && $to_valid;
        });
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string
     * @return bool True if valid
     */
    private function validate_date_format(string $date): bool {
        if (empty($date)) {
            return false;
        }
        
        $date_obj = DateTime::createFromFormat('Y-m-d', $date);
        return $date_obj && $date_obj->format('Y-m-d') === $date;
    }
    
    /**
     * Render availability shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function render_availability_shortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'professional_id' => 0,
            'date' => '',
            'show_all' => 'false',
            'limit' => 10,
            'template' => 'list', // list, grid, calendar
        ], $atts, 'ycp_availability_data');
        
        try {
            if (!empty($atts['professional_id'])) {
                $data = $this->get_professional_availability(absint($atts['professional_id']));
                return $this->render_professional_availability($data, $atts['template']);
            } elseif ($atts['show_all'] === 'true') {
                $data = $this->get_all_professionals_availability(absint($atts['limit']));
                return $this->render_all_availability($data, $atts['template']);
            } else {
                $data = $this->get_availability_by_date($atts['date'], absint($atts['limit']));
                return $this->render_date_availability($data, $atts['template']);
            }
        } catch (Exception $e) {
            return '<p class="ycp-error">Error: ' . esc_html($e->getMessage()) . '</p>';
        }
    }
    
    /**
     * Render professional availability HTML
     */
    private function render_professional_availability(array $data, string $template): string {
        ob_start();
        ?>
        <div class="ycp-availability-data professional-<?php echo esc_attr($data['id']); ?>">
            <h3><?php echo esc_html($data['name']); ?></h3>
            <div class="ycp-availability-info">
                <p><strong>Available Days:</strong> <?php echo esc_html($data['total_available_days']); ?></p>
                <p><strong>Available Today:</strong> <?php echo $data['is_available_today'] ? 'Yes' : 'No'; ?></p>
                <?php if (!empty($data['description'])): ?>
                    <p><strong>Description:</strong> <?php echo esc_html($data['description']); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($data['available_dates'])): ?>
                <div class="ycp-available-dates">
                    <h4>Available Dates:</h4>
                    <ul>
                        <?php foreach ($data['available_dates'] as $date): ?>
                            <li><?php echo esc_html(date('F j, Y', strtotime($date))); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render all availability HTML
     */
    private function render_all_availability(array $data, string $template): string {
        ob_start();
        ?>
        <div class="ycp-availability-data all-professionals">
            <h3>All Professionals (<?php echo esc_html($data['count']); ?>)</h3>
            <?php foreach ($data['professionals'] as $professional): ?>
                <div class="ycp-professional-item">
                    <h4><?php echo esc_html($professional['name']); ?></h4>
                    <p>Available Days: <?php echo esc_html($professional['total_available_days']); ?></p>
                    <p>Available Today: <?php echo $professional['is_available_today'] ? 'Yes' : 'No'; ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render date availability HTML
     */
    private function render_date_availability(array $data, string $template): string {
        ob_start();
        ?>
        <div class="ycp-availability-data date-<?php echo esc_attr($data['date']); ?>">
            <h3>Available on <?php echo esc_html(date('F j, Y', strtotime($data['date']))); ?> (<?php echo esc_html($data['count']); ?>)</h3>
            <?php if (!empty($data['available_professionals'])): ?>
                <?php foreach ($data['available_professionals'] as $professional): ?>
                    <div class="ycp-professional-item">
                        <h4><?php echo esc_html($professional['name']); ?></h4>
                        <?php if (!empty($professional['description'])): ?>
                            <p><?php echo esc_html($professional['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No professionals available on this date.</p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Register global functions for direct PHP access
     */
    public function register_availability_functions(): void {
        // This allows other plugins/themes to access availability data directly
        if (!function_exists('ycp_get_professional_availability')) {
            function ycp_get_professional_availability(int $professional_id, string $date_from = '', string $date_to = '') {
                global $ycp_data_handler;
                if (!$ycp_data_handler) {
                    $ycp_data_handler = new YCP_Data_Handler();
                }
                return $ycp_data_handler->get_professional_availability($professional_id, $date_from, $date_to);
            }
        }
        
        if (!function_exists('ycp_get_availability_by_date')) {
            function ycp_get_availability_by_date(string $date = '', int $limit = 50) {
                global $ycp_data_handler;
                if (!$ycp_data_handler) {
                    $ycp_data_handler = new YCP_Data_Handler();
                }
                return $ycp_data_handler->get_availability_by_date($date, $limit);
            }
        }
        
        if (!function_exists('ycp_get_all_professionals_availability')) {
            function ycp_get_all_professionals_availability(int $limit = 100) {
                global $ycp_data_handler;
                if (!$ycp_data_handler) {
                    $ycp_data_handler = new YCP_Data_Handler();
                }
                return $ycp_data_handler->get_all_professionals_availability($limit);
            }
        }
    }
    
    /**
     * Get availability data for a specific professional with compact date formatting
     * 
     * @param int $professional_id The professional's post ID
     * @param string $date_from Optional start date (Y-m-d format)
     * @param string $date_to Optional end date (Y-m-d format)
     * @param bool $compact_format Whether to format dates in compact ranges
     * @return array Availability data
     * @throws Exception If professional not found or invalid data
     */
    public function get_professional_availability_compact(int $professional_id, string $date_from = '', string $date_to = '', bool $compact_format = true): array {
        // Get the basic availability data
        $data = $this->get_professional_availability($professional_id, $date_from, $date_to);
        
        if ($compact_format && !empty($data['available_dates'])) {
            $data['available_dates_formatted'] = $this->format_dates_compact($data['available_dates']);
            $data['available_dates_ranges'] = $this->get_date_ranges($data['available_dates']);
        }
        
        return $data;
    }
    
    /**
     * Format dates in compact ranges (e.g., "25.06. - 27.06.")
     * 
     * @param array $dates Array of dates in Y-m-d format
     * @return array Array of formatted date ranges
     */
    private function format_dates_compact(array $dates): array {
        if (empty($dates)) {
            return [];
        }
        
        // Sort dates
        sort($dates);
        
        $ranges = [];
        $current_range_start = null;
        $current_range_end = null;
        
        foreach ($dates as $date) {
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$date_obj) {
                continue;
            }
            
            if ($current_range_start === null) {
                // Start new range
                $current_range_start = $date_obj;
                $current_range_end = $date_obj;
            } else {
                // Check if this date is consecutive
                $diff = $current_range_end->diff($date_obj)->days;
                if ($diff == 1) {
                    // Consecutive date, extend range
                    $current_range_end = $date_obj;
                } else {
                    // Non-consecutive, save current range and start new one
                    $ranges[] = $this->format_date_range($current_range_start, $current_range_end);
                    $current_range_start = $date_obj;
                    $current_range_end = $date_obj;
                }
            }
        }
        
        // Add the last range
        if ($current_range_start !== null) {
            $ranges[] = $this->format_date_range($current_range_start, $current_range_end);
        }
        
        return $ranges;
    }
    
    /**
     * Get date ranges with start and end dates
     * 
     * @param array $dates Array of dates in Y-m-d format
     * @return array Array of date range objects
     */
    private function get_date_ranges(array $dates): array {
        if (empty($dates)) {
            return [];
        }
        
        // Sort dates
        sort($dates);
        
        $ranges = [];
        $current_range_start = null;
        $current_range_end = null;
        
        foreach ($dates as $date) {
            $date_obj = DateTime::createFromFormat('Y-m-d', $date);
            if (!$date_obj) {
                continue;
            }
            
            if ($current_range_start === null) {
                // Start new range
                $current_range_start = $date_obj;
                $current_range_end = $date_obj;
            } else {
                // Check if this date is consecutive
                $diff = $current_range_end->diff($date_obj)->days;
                if ($diff == 1) {
                    // Consecutive date, extend range
                    $current_range_end = $date_obj;
                } else {
                    // Non-consecutive, save current range and start new one
                    $ranges[] = [
                        'start' => $current_range_start->format('Y-m-d'),
                        'end' => $current_range_end->format('Y-m-d'),
                        'start_formatted' => $current_range_start->format('d.m.'),
                        'end_formatted' => $current_range_end->format('d.m.'),
                        'display' => $this->format_date_range($current_range_start, $current_range_end),
                        'days_count' => $current_range_end->diff($current_range_start)->days + 1
                    ];
                    $current_range_start = $date_obj;
                    $current_range_end = $date_obj;
                }
            }
        }
        
        // Add the last range
        if ($current_range_start !== null) {
            $ranges[] = [
                'start' => $current_range_start->format('Y-m-d'),
                'end' => $current_range_end->format('Y-m-d'),
                'start_formatted' => $current_range_start->format('d.m.'),
                'end_formatted' => $current_range_end->format('d.m.'),
                'display' => $this->format_date_range($current_range_start, $current_range_end),
                'days_count' => $current_range_end->diff($current_range_start)->days + 1
            ];
        }
        
        return $ranges;
    }
    
    /**
     * Format a date range for display
     * 
     * @param DateTime $start Start date
     * @param DateTime $end End date
     * @return string Formatted date range
     */
    private function format_date_range(DateTime $start, DateTime $end): string {
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            // Single date
            return $start->format('d.m.');
        } else {
            // Date range
            return $start->format('d.m.') . ' - ' . $end->format('d.m.');
        }
    }
}

// Initialize the data handler
global $ycp_data_handler;
$ycp_data_handler = new YCP_Data_Handler();
