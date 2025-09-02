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
     * Custom post type name
     */
    const POST_TYPE = 'ycp_professional';
    
    /**
     * Meta field keys
     */
    const META_AVAILABLE_DATES = '_ycp_available_dates';
    const META_PROFILE_URL = '_ycp_profile_url';
    const META_DESCRIPTION = '_ycp_description';
    
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
        // Register AJAX handlers
        add_action('wp_ajax_ycp_get_availability_data', [$this, 'handle_get_availability_data']);
        add_action('wp_ajax_nopriv_ycp_get_availability_data', [$this, 'handle_get_availability_data']);
        add_action('wp_ajax_ycp_get_compact_availability_data', [$this, 'handle_get_compact_availability_data']);
        add_action('wp_ajax_nopriv_ycp_get_compact_availability_data', [$this, 'handle_get_compact_availability_data']);
        
        // Register global functions
        add_action('init', [$this, 'register_availability_functions']);
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
     * Get availability data for a specific professional
     * 
     * @param int $professional_id The professional's database ID
     * @param string $date_from Optional start date (Y-m-d format)
     * @param string $date_to Optional end date (Y-m-d format)
     * @return array Availability data
     * @throws Exception If professional not found or invalid data
     */
    public function get_professional_availability(int $professional_id, string $date_from = '', string $date_to = ''): array {
        global $wpdb;
        
        // Get professional from custom database table
        $table_name = $wpdb->prefix . 'ycp_professionals';
        $professional = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $professional_id),
            ARRAY_A
        );
        
        if (!$professional) {
            // Get all available IDs for debugging
            $all_ids = $wpdb->get_col("SELECT id FROM $table_name ORDER BY id ASC");
            $available_ids_str = !empty($all_ids) ? implode(', ', $all_ids) : 'none';
            
            throw new Exception(sprintf(
                'Professional with ID %d not found. Available IDs: %s. Total professionals: %d',
                $professional_id,
                $available_ids_str,
                count($all_ids)
            ));
        }
        
        // Get availability dates from the database
        $available_dates = $professional['available_dates'] ?? '';
        $date_array = $this->parse_dates_string($available_dates);
        
        // Filter by date range if provided
        if (!empty($date_from) || !empty($date_to)) {
            $date_array = $this->filter_dates_by_range($date_array, $date_from, $date_to);
        }
        
        // Get professional data
        $professional_data = $this->get_professional_meta_data_from_db($professional);
        
        return array_merge($professional_data, [
            'available_dates' => $date_array,
            'is_available_today' => in_array(date('Y-m-d'), $date_array),
            'total_available_days' => count($date_array),
        ]);
    }
    
    /**
     * Get availability data for a specific date
     * 
     * @param string $date Date in Y-m-d format
     * @param int $limit Maximum number of professionals to return
     * @return array Array of available professionals
     */
    public function get_availability_by_date(string $date = '', int $limit = 50): array {
        global $wpdb;
        
        $date = !empty($date) ? $date : date('Y-m-d');
        
        if (!$this->validate_date_format($date)) {
            throw new Exception('Invalid date format. Use Y-m-d format.');
        }
        
        // Get all professionals from database
        $table_name = $wpdb->prefix . 'ycp_professionals';
        $professionals = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY display_order ASC, name ASC LIMIT $limit",
            ARRAY_A
        );
        
        $available_professionals = [];
        
        foreach ($professionals as $professional) {
            $available_dates = $professional['available_dates'] ?? '';
            $date_array = $this->parse_dates_string($available_dates);
            
            if (in_array($date, $date_array)) {
                $professional_data = $this->get_professional_meta_data_from_db($professional);
                $available_professionals[] = array_merge($professional_data, [
                    'is_available_today' => in_array(date('Y-m-d'), $date_array),
                ]);
            }
        }
        
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
        global $wpdb;
        
        // Get all professionals from database
        $table_name = $wpdb->prefix . 'ycp_professionals';
        $professionals = $wpdb->get_results(
            "SELECT * FROM $table_name ORDER BY display_order ASC, name ASC LIMIT $limit",
            ARRAY_A
        );
        
        $formatted_professionals = [];
        
        foreach ($professionals as $professional) {
            $available_dates = $professional['available_dates'] ?? '';
            $date_array = $this->parse_dates_string($available_dates);
            
            $professional_data = $this->get_professional_meta_data_from_db($professional);
            $formatted_professionals[] = array_merge($professional_data, [
                'available_dates' => $date_array,
                'is_available_today' => in_array(date('Y-m-d'), $date_array),
                'total_available_days' => count($date_array),
            ]);
        }
        
        return [
            'professionals' => $formatted_professionals,
            'count' => count($formatted_professionals),
        ];
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
     * Get professional meta data from database
     * 
     * @param array $professional Professional data from database
     * @return array Professional meta data
     */
    private function get_professional_meta_data_from_db(array $professional): array {
        return [
            'id' => $professional['id'],
            'name' => $professional['name'],
            'profile_url' => esc_url($professional['profile_url'] ?? ''),
            'description' => esc_html($professional['description'] ?? ''),
            'thumbnail_url' => esc_url($professional['image_url'] ?? ''),
        ];
    }
    
    /**
     * Get professional meta data
     * 
     * @param int $professional_id The professional's post ID
     * @return array Professional meta data
     */
    private function get_professional_meta_data(int $professional_id): array {
        $profile_url = get_post_meta($professional_id, self::META_PROFILE_URL, true);
        $description = get_post_meta($professional_id, self::META_DESCRIPTION, true);
        $thumbnail_id = get_post_thumbnail_id($professional_id);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'medium') : '';
        
        return [
            'id' => $professional_id,
            'name' => get_the_title($professional_id),
            'profile_url' => esc_url($profile_url),
            'description' => esc_html($description),
            'thumbnail_url' => esc_url($thumbnail_url),
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
     * @param string $date Date string to validate
     * @return bool True if valid date format
     */
    private function validate_date_format(string $date): bool {
        if (empty($date)) {
            return true; // Allow empty dates
        }
        
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
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
        
        $ranges = $this->get_date_ranges($dates);
        return array_column($ranges, 'display');
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
                    $ranges[] = $this->create_date_range_object($current_range_start, $current_range_end);
                    $current_range_start = $date_obj;
                    $current_range_end = $date_obj;
                }
            }
        }
        
        // Add the last range
        if ($current_range_start !== null) {
            $ranges[] = $this->create_date_range_object($current_range_start, $current_range_end);
        }
        
        return $ranges;
    }
    
    /**
     * Create a date range object
     * 
     * @param DateTime $start Start date
     * @param DateTime $end End date
     * @return array Date range object
     */
    private function create_date_range_object(DateTime $start, DateTime $end): array {
        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
            'start_formatted' => $start->format('d.m.'),
            'end_formatted' => $end->format('d.m.'),
            'display' => $this->format_date_range($start, $end),
            'days_count' => $end->diff($start)->days + 1
        ];
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
        
        if (!function_exists('ycp_debug_professional')) {
            function ycp_debug_professional(int $professional_id) {
                global $ycp_data_handler;
                if (!$ycp_data_handler) {
                    $ycp_data_handler = new YCP_Data_Handler();
                }
                return $ycp_data_handler->debug_professional($professional_id);
            }
        }
    }
    
    /**
     * Debug method to check if professional exists and get basic info
     * 
     * @param int $professional_id The professional's database ID
     * @return array Debug information
     */
    public function debug_professional(int $professional_id): array {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ycp_professionals';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        // Get professional data
        $professional = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $professional_id),
            ARRAY_A
        );
        
        // Get all professional IDs for reference
        $all_ids = $wpdb->get_col("SELECT id FROM $table_name ORDER BY id ASC");
        
        return [
            'table_exists' => $table_exists,
            'table_name' => $table_name,
            'requested_id' => $professional_id,
            'professional_found' => !empty($professional),
            'professional_data' => $professional,
            'all_available_ids' => $all_ids,
            'total_professionals' => count($all_ids),
        ];
    }
}

// Initialize the data handler
global $ycp_data_handler;
$ycp_data_handler = new YCP_Data_Handler();
