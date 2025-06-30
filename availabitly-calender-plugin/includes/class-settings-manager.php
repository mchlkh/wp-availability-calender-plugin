<?php
/**
 * Availability Calendar Plugin - Settings Manager
 * 
 * Handles all plugin settings, admin pages, and configuration management
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
 * Class YCP_Settings_Manager
 * 
 * Manages plugin settings, admin pages, and configuration
 */
class YCP_Settings_Manager {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'availability-calendar-plugin';
    
    /**
     * Settings page slug
     */
    const SETTINGS_PAGE_SLUG = 'ycp-availability-calendar';
    
    /**
     * Color settings page slug
     */
    const COLOR_SETTINGS_PAGE_SLUG = 'ycp-color-settings';
    
    /**
     * Settings option name
     */
    const SETTINGS_OPTION = 'ycp_calendar_settings';
    
    /**
     * Color settings option name
     */
    const COLOR_OPTIONS = 'ycp_color_options';
    
    /**
     * Initialize the settings manager
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Admin menu registration
        add_action('admin_menu', [$this, 'register_admin_menus']);
        
        // Settings registration
        add_action('admin_init', [$this, 'register_settings']);
        
        // Admin scripts and styles
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        
        // Custom CSS output
        add_action('wp_head', [$this, 'output_custom_css']);
    }
    
    /**
     * Register admin menu pages
     */
    public function register_admin_menus(): void {
        // Main settings page
        add_submenu_page(
            'edit.php?post_type=availabilities',
            __('Calendar Settings', self::TEXT_DOMAIN),
            __('Calendar Settings', self::TEXT_DOMAIN),
            'manage_options',
            self::SETTINGS_PAGE_SLUG,
            [$this, 'render_settings_page']
        );
        
        // Color settings page
        add_submenu_page(
            'edit.php?post_type=ycp_professional',
            __('Calendar Colors', self::TEXT_DOMAIN),
            __('Calendar Colors', self::TEXT_DOMAIN),
            'manage_options',
            self::COLOR_SETTINGS_PAGE_SLUG,
            [$this, 'render_color_settings_page']
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings(): void {
        $this->register_general_settings();
        $this->register_color_settings();
    }
    
    /**
     * Register general settings
     */
    private function register_general_settings(): void {
        // General settings section
        add_settings_section(
            'ycp_general_section',
            __('Calendar Display Settings', self::TEXT_DOMAIN),
            [$this, 'render_general_section_callback'],
            self::SETTINGS_PAGE_SLUG
        );
        
        // Register settings fields
        $this->register_general_settings_fields();
    }
    
    /**
     * Register general settings fields
     */
    private function register_general_settings_fields(): void {
        $settings_fields = [
            'ycp_default_month_count' => [
                'title' => __('Default Number of Months', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_number_field'],
                'args' => [
                    'min' => 1,
                    'max' => 12,
                    'default' => 1,
                    'desc' => __('Number of months to display by default', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_mobile_behavior' => [
                'title' => __('Mobile Display Behavior', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_select_field'],
                'args' => [
                    'options' => [
                        'responsive' => __('Responsive (adapts to screen)', self::TEXT_DOMAIN),
                        'compact' => __('Compact (single month)', self::TEXT_DOMAIN),
                        'full' => __('Full (same as desktop)', self::TEXT_DOMAIN)
                    ],
                    'default' => 'responsive',
                    'desc' => __('How the calendar behaves on mobile devices', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_calendar_primary_color' => [
                'title' => __('Calendar Primary Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#1E90FF',
                    'desc' => __('Primary color for calendar elements', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_calendar_text_color' => [
                'title' => __('Calendar Text Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#333333',
                    'desc' => __('Text color for calendar content', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_show_today_button' => [
                'title' => __('Show Today Button', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_checkbox_field'],
                'args' => [
                    'default' => true,
                    'desc' => __('Display a button to jump to today\'s date', self::TEXT_DOMAIN)
                ]
            ]
        ];
        
        foreach ($settings_fields as $field_id => $field_config) {
            register_setting(self::SETTINGS_PAGE_SLUG, $field_id);
            add_settings_field(
                $field_id,
                $field_config['title'],
                $field_config['callback'],
                self::SETTINGS_PAGE_SLUG,
                'ycp_general_section',
                array_merge(['field_id' => $field_id], $field_config['args'])
            );
        }
    }
    
    /**
     * Register color settings
     */
    private function register_color_settings(): void {
        // Color settings section
        add_settings_section(
            'ycp_color_section',
            __('Advanced Color Settings', self::TEXT_DOMAIN),
            [$this, 'render_color_section_callback'],
            self::COLOR_SETTINGS_PAGE_SLUG
        );
        
        // Register color settings
        register_setting(
            self::COLOR_SETTINGS_PAGE_SLUG,
            self::COLOR_OPTIONS,
            [$this, 'sanitize_color_options']
        );
        
        // Register color fields
        $this->register_color_settings_fields();
    }
    
    /**
     * Register color settings fields
     */
    private function register_color_settings_fields(): void {
        $color_fields = [
            'ycp_use_theme_colors' => [
                'title' => __('Use Theme Colors', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_checkbox_field'],
                'args' => [
                    'desc' => __('When checked, the calendar will inherit colors from the theme instead of using custom colors.', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_primary_color' => [
                'title' => __('Primary Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#1E90FF',
                    'desc' => __('Main color for selected dates', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_primary_dark' => [
                'title' => __('Primary Dark Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#0060C0',
                    'desc' => __('Darker variant of primary color', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_primary_contrast' => [
                'title' => __('Primary Contrast Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#FFFFFF',
                    'desc' => __('Text color on primary background', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_secondary_color' => [
                'title' => __('Secondary Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#9ECBFF',
                    'desc' => __('Color for highlights', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_secondary_light' => [
                'title' => __('Secondary Light Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#E6F2FF',
                    'desc' => __('Light variant for hover effects', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_accent_color' => [
                'title' => __('Accent Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#FF6B00',
                    'desc' => __('Accent color for special highlights', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_accent_contrast' => [
                'title' => __('Accent Contrast Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#FFFFFF',
                    'desc' => __('Text color on accent background', self::TEXT_DOMAIN)
                ]
            ],
            'ycp_month_year_color' => [
                'title' => __('Month and Year Text Color', self::TEXT_DOMAIN),
                'callback' => [$this, 'render_color_field'],
                'args' => [
                    'default' => '#333333',
                    'desc' => __('Text color for month and year in the calendar header', self::TEXT_DOMAIN)
                ]
            ]
        ];
        
        foreach ($color_fields as $field_id => $field_config) {
            add_settings_field(
                $field_id,
                $field_config['title'],
                $field_config['callback'],
                self::COLOR_SETTINGS_PAGE_SLUG,
                'ycp_color_section',
                array_merge(['field_id' => $field_id], $field_config['args'])
            );
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets(string $hook): void {
        // Only load on our settings pages
        if (!in_array($hook, [
            'availabilities_page_' . self::SETTINGS_PAGE_SLUG,
            'ycp_professional_page_' . self::COLOR_SETTINGS_PAGE_SLUG
        ])) {
            return;
        }
        
        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Enqueue custom admin styles
        wp_enqueue_style(
            'ycp-admin-style',
            plugin_dir_url(dirname(__FILE__)) . 'admin/admin.css',
            [],
            '1.0.0'
        );
        
        // Enqueue custom admin scripts
        wp_enqueue_script(
            'ycp-admin-script',
            plugin_dir_url(dirname(__FILE__)) . 'admin/admin.js',
            ['jquery', 'wp-color-picker'],
            '1.0.0',
            true
        );
        
        // Localize script
        wp_localize_script('ycp-admin-script', 'ycp_admin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ycp_admin_nonce')
        ]);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Calendar Settings', self::TEXT_DOMAIN) . '</h1>';
        echo '<form method="post" action="options.php">';
        
        settings_fields(self::SETTINGS_PAGE_SLUG);
        do_settings_sections(self::SETTINGS_PAGE_SLUG);
        
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Render color settings page
     */
    public function render_color_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Calendar Color Settings', self::TEXT_DOMAIN) . '</h1>';
        echo '<form method="post" action="options.php">';
        
        settings_fields(self::COLOR_SETTINGS_PAGE_SLUG);
        do_settings_sections(self::COLOR_SETTINGS_PAGE_SLUG);
        
        submit_button();
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Render general section callback
     */
    public function render_general_section_callback(): void {
        echo '<p>' . esc_html__('Configure how your availability calendar is displayed and behaves.', self::TEXT_DOMAIN) . '</p>';
    }
    
    /**
     * Render color section callback
     */
    public function render_color_section_callback(): void {
        echo '<p>' . esc_html__('Customize the colors for your availability calendar. Leave any field empty to use the default color.', self::TEXT_DOMAIN) . '</p>';
        echo '<p>' . esc_html__('Click the color field to open the color picker and select your preferred color.', self::TEXT_DOMAIN) . '</p>';
    }
    
    /**
     * Render number field
     */
    public function render_number_field(array $args): void {
        $field_id = $args['field_id'];
        $value = get_option($field_id, $args['default'] ?? '');
        $min = $args['min'] ?? 0;
        $max = $args['max'] ?? 999;
        
        echo '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" min="' . esc_attr($min) . '" max="' . esc_attr($max) . '" class="regular-text">';
        
        if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
    
    /**
     * Render select field
     */
    public function render_select_field(array $args): void {
        $field_id = $args['field_id'];
        $value = get_option($field_id, $args['default'] ?? '');
        $options = $args['options'] ?? [];
        
        echo '<select id="' . esc_attr($field_id) . '" name="' . esc_attr($field_id) . '">';
        foreach ($options as $option_value => $option_label) {
            $selected = selected($value, $option_value, false);
            echo '<option value="' . esc_attr($option_value) . '" ' . $selected . '>' . esc_html($option_label) . '</option>';
        }
        echo '</select>';
        
        if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
    
    /**
     * Render color field
     */
    public function render_color_field(array $args): void {
        $field_id = $args['field_id'];
        $options = get_option(self::COLOR_OPTIONS, []);
        $value = isset($options[$field_id]) ? $options[$field_id] : ($args['default'] ?? '');
        
        echo '<input type="text" class="ycp-color-picker" id="' . esc_attr($field_id) . '" name="' . esc_attr(self::COLOR_OPTIONS) . '[' . esc_attr($field_id) . ']" value="' . esc_attr($value) . '" data-default-color="' . esc_attr($args['default'] ?? '') . '">';
        
        if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
    
    /**
     * Render checkbox field
     */
    public function render_checkbox_field(array $args): void {
        $field_id = $args['field_id'];
        $options = get_option(self::COLOR_OPTIONS, []);
        $value = isset($options[$field_id]) ? $options[$field_id] : ($args['default'] ?? false);
        
        echo '<input type="checkbox" id="' . esc_attr($field_id) . '" name="' . esc_attr(self::COLOR_OPTIONS) . '[' . esc_attr($field_id) . ']" value="1" ' . checked($value, true, false) . '>';
        echo '<label for="' . esc_attr($field_id) . '">' . esc_html__('Enable', self::TEXT_DOMAIN) . '</label>';
        
        if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
    
    /**
     * Sanitize color options
     */
    public function sanitize_color_options(array $input): array {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            if ($key === 'ycp_use_theme_colors') {
                $sanitized[$key] = (bool) $value;
            } else {
                // Sanitize color values
                $sanitized[$key] = sanitize_hex_color($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Output custom CSS based on settings
     */
    public function output_custom_css(): void {
        $options = get_option(self::COLOR_OPTIONS, []);
        
        // Only output custom CSS if we have options and we're not using theme colors
        if (empty($options) || (isset($options['ycp_use_theme_colors']) && $options['ycp_use_theme_colors'])) {
            return;
        }
        
        $css = '<style id="ycp-custom-colors">';
        $css .= ':root {';
        
        // Map options to CSS variables
        $color_mappings = [
            'ycp_primary_color' => '--admin-primary-color',
            'ycp_primary_dark' => '--admin-primary-dark',
            'ycp_primary_contrast' => '--admin-primary-contrast',
            'ycp_secondary_color' => '--admin-secondary-color',
            'ycp_secondary_light' => '--admin-secondary-light',
            'ycp_accent_color' => '--admin-accent-color',
            'ycp_accent_contrast' => '--admin-accent-contrast',
            'ycp_month_year_color' => '--admin-month-year-color'
        ];
        
        foreach ($color_mappings as $option_key => $css_var) {
            if (!empty($options[$option_key])) {
                $css .= $css_var . ': ' . esc_attr($options[$option_key]) . ';';
            }
        }
        
        $css .= '}';
        $css .= '</style>';
        
        echo $css;
    }
    
    /**
     * Get a setting value
     */
    public function get_setting(string $key, $default = null) {
        return get_option($key, $default);
    }
    
    /**
     * Get color setting value
     */
    public function get_color_setting(string $key, $default = null) {
        $options = get_option(self::COLOR_OPTIONS, []);
        return isset($options[$key]) ? $options[$key] : $default;
    }
    
    /**
     * Update a setting value
     */
    public function update_setting(string $key, $value): bool {
        return update_option($key, $value);
    }
    
    /**
     * Update a color setting value
     */
    public function update_color_setting(string $key, $value): bool {
        $options = get_option(self::COLOR_OPTIONS, []);
        $options[$key] = $value;
        return update_option(self::COLOR_OPTIONS, $options);
    }
    
    /**
     * Get all settings
     */
    public function get_all_settings(): array {
        return [
            'general' => [
                'default_month_count' => $this->get_setting('ycp_default_month_count', 1),
                'mobile_behavior' => $this->get_setting('ycp_mobile_behavior', 'responsive'),
                'primary_color' => $this->get_setting('ycp_calendar_primary_color', '#1E90FF'),
                'text_color' => $this->get_setting('ycp_calendar_text_color', '#333333'),
                'show_today_button' => $this->get_setting('ycp_show_today_button', true)
            ],
            'colors' => [
                'use_theme_colors' => $this->get_color_setting('ycp_use_theme_colors', false),
                'primary_color' => $this->get_color_setting('ycp_primary_color', '#1E90FF'),
                'primary_dark' => $this->get_color_setting('ycp_primary_dark', '#0060C0'),
                'primary_contrast' => $this->get_color_setting('ycp_primary_contrast', '#FFFFFF'),
                'secondary_color' => $this->get_color_setting('ycp_secondary_color', '#9ECBFF'),
                'secondary_light' => $this->get_color_setting('ycp_secondary_light', '#E6F2FF'),
                'accent_color' => $this->get_color_setting('ycp_accent_color', '#FF6B00'),
                'accent_contrast' => $this->get_color_setting('ycp_accent_contrast', '#FFFFFF'),
                'month_year_color' => $this->get_color_setting('ycp_month_year_color', '#333333')
            ]
        ];
    }
} 