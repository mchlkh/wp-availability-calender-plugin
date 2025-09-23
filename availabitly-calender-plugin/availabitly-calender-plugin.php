<?php

/**
 * Plugin Name: Availability Calendar Plugin
 * Plugin URI: 
 * Description: A calendar that shows available professionals.
 * Version: 1.0
 * Author: Michael Kofler-Hofer
 * Author URI: 
 * Text Domain: availability-calendar-plugin
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
 * Main plugin class for Availability Calendar Plugin
 * 
 * Handles plugin initialization and asset management.
 */
class Availability_Calendar_Plugin {
    
    /**
     * Plugin version
     */
    const VERSION = '1.0.0';
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'availability-calendar-plugin';
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Professional manager instance
     */
    private $professional_manager;
    
    /**
     * Data handler instance
     */
    private $data_handler;
    
    /**
     * Overview UI instance
     */
    private $overview_ui;
    
    /**
     * Display handler instance
     */
    private $display_handler;
    
    /**
     * AJAX handler instance
     */
    private $ajax_handler;
    
    /**
     * Settings manager instance
     */
    private $settings_manager;
    
    /**
     * REST API handler instance
     */
    private $rest_api_handler;
    
    /**
     * Get plugin instance (singleton pattern)
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the plugin
     */
    private function init(): void {
        $this->load_dependencies();
        $this->init_components();
        $this->register_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies(): void {
        require_once plugin_dir_path(__FILE__) . 'includes/class-professional-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-display-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-ajax-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/data-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/overview-ui.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-settings-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-rest-api-handler.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-availability-repository.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-import-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-export-manager.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-location-manager.php';
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components(): void {
        $this->professional_manager = new YCP_Professional_Manager();
        $this->data_handler = new YCP_Data_Handler();
        $this->overview_ui = new YCP_Overview_UI();
        $this->display_handler = new YCP_Display_Handler($this->professional_manager, $this->data_handler);
        $this->ajax_handler = new YCP_Ajax_Handler($this->professional_manager, $this->data_handler, $this->display_handler);
        $this->settings_manager = new YCP_Settings_Manager();
        $this->rest_api_handler = new YCP_REST_API_Handler($this->data_handler);
        // Initialize import manager (registers admin submenu and handlers)
        new YCP_Import_Manager(new YCP_Availability_Repository());
        // Initialize export manager
        new YCP_Export_Manager();
        // Initialize Rooms & Floors manager
        new YCP_Location_Manager();
    }
    
    /**
     * Register WordPress hooks
     */
    private function register_hooks(): void {
        // Register frontend assets; enqueue from shortcode renderers only
        add_action('init', [$this, 'register_frontend_assets']);
    }
    
    /**
     * Enqueue frontend assets
     */
    public function register_frontend_assets(): void {
        // Styles
        wp_register_style('flatpickr-style', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', [], null);
        wp_register_style('ycp-style', plugin_dir_url(__FILE__) . 'public/assets/style.css', [], self::VERSION);

        // Scripts
        wp_register_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', [], null, true);
        wp_register_script('ycp-script', plugin_dir_url(__FILE__) . 'public/assets/script.js', ['jquery', 'flatpickr'], self::VERSION, true);
        wp_register_script('ycp-availability-api', plugin_dir_url(__FILE__) . 'public/assets/availability-api.js', ['jquery'], self::VERSION, true);

        // Localize scripts with AJAX data
        $ajax_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'rest_url' => rest_url(),
            'nonce' => wp_create_nonce('ycp_availability_data_nonce')
        ];

        wp_localize_script('ycp-script', 'ycp_ajax', $ajax_data);
        wp_localize_script('ycp-availability-api', 'ycp_ajax', $ajax_data);
    }
}

// Initialize the plugin
Availability_Calendar_Plugin::get_instance();

// Activation hook: ensure DB schema exists and up to date
register_activation_hook(__FILE__, function() {
    if (class_exists('YCP_Professional_Manager')) {
        YCP_Professional_Manager::create_table();
        update_option('ycp_db_version', Availability_Calendar_Plugin::VERSION);
    } else {
        require_once plugin_dir_path(__FILE__) . 'includes/class-professional-manager.php';
        if (class_exists('YCP_Professional_Manager')) {
            YCP_Professional_Manager::create_table();
            update_option('ycp_db_version', Availability_Calendar_Plugin::VERSION);
        }
    }
    if (class_exists('YCP_Availability_Repository')) {
        YCP_Availability_Repository::create_table();
    } else {
        require_once plugin_dir_path(__FILE__) . 'includes/class-availability-repository.php';
        if (class_exists('YCP_Availability_Repository')) {
            YCP_Availability_Repository::create_table();
        }
    }
    if (class_exists('YCP_Location_Manager')) {
        YCP_Location_Manager::create_tables();
    } else {
        require_once plugin_dir_path(__FILE__) . 'includes/class-location-manager.php';
        if (class_exists('YCP_Location_Manager')) {
            YCP_Location_Manager::create_tables();
        }
    }
});
