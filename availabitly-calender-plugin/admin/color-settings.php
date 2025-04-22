<?php
/**
 * Calendar Color Settings
 * 
 * Adds color customization options to the WordPress admin for the calendar.
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register calendar color settings
 */
if (!function_exists('ycp_register_color_settings')) {
    function ycp_register_color_settings() {
        // Register as a submenu under Availabilities instead of Settings
        if (function_exists('add_submenu_page')) {
            add_submenu_page(
                'edit.php?post_type=ycp_professional', // Parent slug (Correct post type)
                'Calendar Colors',                    // Page title
                'Calendar Colors',                    // Menu title
                'manage_options',                     // Capability
                'ycp-color-settings',                 // Menu slug
                'ycp_render_color_settings'           // Callback function
            );
        }

        // Register settings
        if (function_exists('register_setting')) {
            register_setting(
                'ycp_color_settings',        // Option group
                'ycp_color_options',         // Option name
                'ycp_sanitize_color_options' // Sanitize callback
            );
        }

        // Add settings section
        if (function_exists('add_settings_section')) {
            add_settings_section(
                'ycp_color_section',         // ID
                'Calendar Color Settings',   // Title
                'ycp_color_section_callback',// Callback
                'ycp-color-settings'         // Page
            );
        }

        // Add settings fields for primary colors
        if (function_exists('add_settings_field')) {
            add_settings_field(
                'ycp_primary_color',         // ID
                'Primary Color',             // Title
                'ycp_color_field_callback',  // Callback
                'ycp-color-settings',        // Page
                'ycp_color_section',         // Section
                [
                    'label_for' => 'ycp_primary_color',
                    'desc' => 'Main color for selected dates (default: #1E90FF)'
                ]
            );

            add_settings_field(
                'ycp_primary_dark',
                'Primary Dark Color',
                'ycp_color_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_primary_dark',
                    'desc' => 'Darker variant of primary color (default: #0060C0)'
                ]
            );

            add_settings_field(
                'ycp_primary_contrast',
                'Primary Contrast Color',
                'ycp_color_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_primary_contrast',
                    'desc' => 'Text color on primary background (default: white)'
                ]
            );

            // Add settings fields for secondary colors
            add_settings_field(
                'ycp_secondary_color',
                'Secondary Color',
                'ycp_color_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_secondary_color',
                    'desc' => 'Color for highlights (default: #9ECBFF)'
                ]
            );

            add_settings_field(
                'ycp_secondary_light',
                'Secondary Light Color',
                'ycp_color_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_secondary_light',
                    'desc' => 'Light variant for hover effects (default: #E6F2FF)'
                ]
            );

            // Add settings fields for accent colors
            add_settings_field(
                'ycp_accent_color',
                'Accent Color',
                'ycp_color_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_accent_color',
                    'desc' => 'Accent color for special highlights (default: #FF6B00)'
                ]
            );

            add_settings_field(
                'ycp_accent_contrast',
                'Accent Contrast Color',
                'ycp_color_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_accent_contrast',
                    'desc' => 'Text color on accent background (default: white)'
                ]
            );
            
            // Add settings field for month and year text color
            add_settings_field(
                'ycp_month_year_color',
                'Month and Year Text Color',
                'ycp_color_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_month_year_color',
                    'desc' => 'Text color for month and year in the calendar header (default: #333333)'
                ]
            );

            // Add a setting for "Use theme colors" toggle
            add_settings_field(
                'ycp_use_theme_colors',
                'Use Theme Colors',
                'ycp_checkbox_field_callback',
                'ycp-color-settings',
                'ycp_color_section',
                [
                    'label_for' => 'ycp_use_theme_colors',
                    'desc' => 'When checked, the calendar will inherit colors from the theme instead of using custom colors.'
                ]
            );
        }
    }
}
// Use admin_menu instead of admin_init to add under Availabilities
add_action('admin_menu', 'ycp_register_color_settings');

/**
 * Section callback
 */
if (!function_exists('ycp_color_section_callback')) {
    function ycp_color_section_callback() {
        echo '<p>Customize the colors for your availability calendar. Leave any field empty to use the default color.</p>';
        echo '<p>Click the color field to open the color picker and select your preferred color.</p>';
    }
}

/**
 * Color field callback
 */
if (!function_exists('ycp_color_field_callback')) {
    function ycp_color_field_callback($args) {
        $options = get_option('ycp_color_options', []);
        $id = $args['label_for'];
        $value = isset($options[$id]) ? $options[$id] : '';
        
        echo '<input type="text" class="ycp-color-picker" id="' . esc_attr($id) . '" name="ycp_color_options[' . esc_attr($id) . ']" value="' . esc_attr($value) . '" data-default-color="">';
        if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
}

/**
 * Checkbox field callback
 */
if (!function_exists('ycp_checkbox_field_callback')) {
    function ycp_checkbox_field_callback($args) {
        $options = get_option('ycp_color_options', []);
        $id = $args['label_for'];
        $checked = isset($options[$id]) ? $options[$id] : false;
        
        echo '<input type="checkbox" id="' . esc_attr($id) . '" name="ycp_color_options[' . esc_attr($id) . ']" ' . checked($checked, true, false) . ' value="1">';
        if (isset($args['desc'])) {
            echo '<p class="description">' . esc_html($args['desc']) . '</p>';
        }
    }
}

/**
 * Sanitize options
 */
if (!function_exists('ycp_sanitize_color_options')) {
    function ycp_sanitize_color_options($input) {
        $new_input = array();
        
        // List of color fields to sanitize
        $color_fields = array(
            'ycp_primary_color',
            'ycp_primary_dark',
            'ycp_primary_contrast',
            'ycp_secondary_color',
            'ycp_secondary_light',
            'ycp_accent_color',
            'ycp_accent_contrast',
            'ycp_month_year_color'
        );
        
        // Sanitize each color field
        foreach ($color_fields as $field) {
            if (isset($input[$field])) {
                // Check if it's a valid hex color
                if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $input[$field])) {
                    $new_input[$field] = $input[$field];
                } else if ($input[$field] === '') {
                    // Allow empty values
                    $new_input[$field] = '';
                }
            }
        }
        
        // Sanitize checkbox
        if (isset($input['ycp_use_theme_colors'])) {
            $new_input['ycp_use_theme_colors'] = (bool) $input['ycp_use_theme_colors'];
        }
        
        return $new_input;
    }
}

/**
 * Render settings page
 */
if (!function_exists('ycp_render_color_settings')) {
    function ycp_render_color_settings() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Add error/update messages
        if (isset($_GET['settings-updated'])) {
            add_settings_error('ycp_color_messages', 'ycp_color_message', 'Settings Saved', 'updated');
        }
        
        // Show settings errors
        settings_errors('ycp_color_messages');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // Output security fields
                settings_fields('ycp_color_settings');
                // Output setting sections
                do_settings_sections('ycp-color-settings');
                // Output save settings button
                submit_button('Save Settings');
                ?>
            </form>
            
            <div class="ycp-color-preview" style="margin-top: 30px;">
                <h2>Preview</h2>
                <p>This preview shows how your calendar will look with the selected colors.</p>
                <div id="ycp-calendar-preview" style="max-width: 500px; border: 1px solid #ddd; padding: 10px;">
                    <!-- Preview will be loaded here via JavaScript -->
                    <p style="text-align: center;">Loading preview...</p>
                </div>
            </div>
        </div>
        <?php
    }
}

/**
 * Enqueue required admin scripts
 */
if (!function_exists('ycp_admin_enqueue_scripts')) {
    function ycp_admin_enqueue_scripts($hook) {
        // Check if we're on the color settings page
        if ($hook == 'ycp_professional_page_ycp-color-settings' || $hook == 'availabilities_page_ycp-color-settings') {
            // Add color picker
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
            
            // Custom script
            wp_enqueue_script(
                'ycp-admin-script',
                plugin_dir_url(__FILE__) . 'js/admin-color-settings.js',
                array('jquery', 'wp-color-picker'),
                '1.0.0',
                true
            );
            
            // Debug information to help troubleshoot
            echo "<!-- Color Settings Page Detected: {$hook} -->";
        }
    }
}
add_action('admin_enqueue_scripts', 'ycp_admin_enqueue_scripts');

/**
 * Output custom CSS variables to the frontend based on settings
 */
if (!function_exists('ycp_output_custom_css')) {
    function ycp_output_custom_css() {
        $options = get_option('ycp_color_options', []);
        
        // Only output custom CSS if we're not using theme colors
        if (isset($options['ycp_use_theme_colors']) && $options['ycp_use_theme_colors']) {
            return;
        }
        
        $css = '<style id="ycp-custom-colors">';
        $css .= '#ycp-calendar-container {';
        
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
}
add_action('wp_head', 'ycp_output_custom_css');

/**
 * Add CSS class for custom colors to the calendar container
 */
if (!function_exists('ycp_add_custom_colors_class')) {
    function ycp_add_custom_colors_class($atts) {
        $options = get_option('ycp_color_options', []);
        
        // If we have custom colors and we're not using theme colors, add the class
        if (!empty($options) && (!isset($options['ycp_use_theme_colors']) || !$options['ycp_use_theme_colors'])) {
            add_filter('ycp_calendar_container_class', function($classes) {
                return $classes . ' ycp-custom-colors';
            });
        }
        
        return $atts;
    }
}
add_filter('shortcode_atts_ycp_calendar', 'ycp_add_custom_colors_class', 10, 1);

// Add a debugging admin notice to help users with the color settings
function ycp_color_settings_admin_notice() {
    // Get the current screen
    $screen = get_current_screen();
    $hook = isset($GLOBALS['hook_suffix']) ? $GLOBALS['hook_suffix'] : '';
    
    // Check if we're on the color settings page or some other admin page
    if (strpos($hook, 'ycp-color-settings') !== false || 
        (isset($_GET['page']) && $_GET['page'] === 'ycp-color-settings')) {
        
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>Calendar Colors Debug Info:</strong></p>';
        echo '<p>Current screen ID: ' . esc_html($screen->id) . '</p>';
        echo '<p>Current hook: ' . esc_html($hook) . '</p>';
        echo '<p>Settings page URL: ' . esc_url(admin_url('edit.php?post_type=ycp_professional&page=ycp-color-settings')) . '</p>';
        echo '<p>If colors are not showing up in the calendar, please check that the color picker fields below are set and saved.</p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'ycp_color_settings_admin_notice'); 