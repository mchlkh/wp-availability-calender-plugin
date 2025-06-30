<?php
/**
 * AJAX Handler Class
 * 
 * Handles all AJAX endpoints and shortcode rendering for the availability calendar
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
 * Class YCP_Ajax_Handler
 * 
 * Manages AJAX endpoints and shortcode rendering for the availability calendar
 */
class YCP_Ajax_Handler {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'availability-calendar-plugin';
    
    /**
     * Professional manager instance
     */
    private $professional_manager;
    
    /**
     * Data handler instance
     */
    private $data_handler;
    
    /**
     * Display handler instance
     */
    private $display_handler;
    
    /**
     * Initialize the AJAX handler
     */
    public function __construct(YCP_Professional_Manager $professional_manager, YCP_Data_Handler $data_handler, YCP_Display_Handler $display_handler) {
        $this->professional_manager = $professional_manager;
        $this->data_handler = $data_handler;
        $this->display_handler = $display_handler;
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // AJAX handlers for professionals
        add_action('wp_ajax_ycp_get_professionals', [$this, 'get_professionals_by_date']);
        add_action('wp_ajax_nopriv_ycp_get_professionals', [$this, 'get_professionals_by_date']);
        add_action('wp_ajax_ycp_get_all_professionals', [$this, 'get_all_professionals']);
        add_action('wp_ajax_nopriv_ycp_get_all_professionals', [$this, 'get_all_professionals']);
        
        // Shortcode registration
        add_shortcode('ycp_calendar', [$this->display_handler, 'render_calendar_shortcode']);
        add_shortcode('ycp_today_simple', [$this->display_handler, 'render_today_simple_shortcode']);
        add_shortcode('ycp_availability_data', [$this->display_handler, 'render_availability_data_shortcode']);
    }
    
    /**
     * AJAX handler for getting professionals by date
     */
    public function get_professionals_by_date(): void {
        try {
            $date = $this->validate_date_parameter();
            $professionals = $this->professional_manager->get_professionals_for_date($date);
            $found_professionals = !empty($professionals);
            
            $output = $this->display_handler->render_professionals_list($professionals);
            $output .= $this->display_handler->render_availability_data_attributes($found_professionals, $date);
            
            echo $output;
            wp_die();
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * AJAX handler for getting all professionals
     */
    public function get_all_professionals(): void {
        try {
            $professionals_data = $this->professional_manager->get_all_professionals();
            $professionals = [];
            
            foreach ($professionals_data as $professional) {
                $professionals[] = $this->professional_manager->format_professional_for_frontend($professional);
            }
            
            $output = $this->display_handler->render_professionals_list($professionals);
            echo $output;
            wp_die();
            
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Validate date parameter from AJAX request
     */
    private function validate_date_parameter(): string {
        $date = sanitize_text_field($_GET['date'] ?? '');
        
        if (empty($date)) {
            throw new Exception(__('Date parameter is required.', self::TEXT_DOMAIN));
        }
        
        if (!$this->is_valid_date($date)) {
            throw new Exception(__('Invalid date format. Expected YYYY-MM-DD.', self::TEXT_DOMAIN));
        }
        
        return $date;
    }
    
    /**
     * Check if date is valid
     */
    private function is_valid_date(string $date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
} 