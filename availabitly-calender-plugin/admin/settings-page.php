<?php
/**
 * Availability Calendar Plugin - Admin Settings Page
 * 
 * Provides the admin interface for configuring the calendar plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the settings page in the admin menu
 */
function ycp_availability_calendar_add_settings_page() {
    // Use add_submenu_page instead of add_menu_page to add under existing Availabilities menu
    add_submenu_page(
        'edit.php?post_type=availabilities',  // Parent menu slug (the custom post type)
        'Calendar Settings',                  // Page title
        'Calendar Settings',                  // Menu title
        'manage_options',                     // Capability
        'ycp-availability-calendar',          // Menu slug
        'ycp_availability_calendar_render_settings_page'  // Callback function
    );
}
add_action('admin_menu', 'ycp_availability_calendar_add_settings_page');

/**
 * Register plugin settings
 */
function ycp_availability_calendar_register_settings() {
    // Register a settings section
    add_settings_section(
        'ycp_availability_calendar_general_section',
        'Calendar Display Settings',
        'ycp_availability_calendar_section_callback',
        'ycp-availability-calendar'
    );

    // Calendar default view - single/multi month
    register_setting('ycp-availability-calendar', 'ycp_default_month_count');
    add_settings_field(
        'ycp_default_month_count',
        'Default Number of Months',
        'ycp_default_month_count_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_general_section'
    );

    // Mobile behavior
    register_setting('ycp-availability-calendar', 'ycp_mobile_behavior');
    add_settings_field(
        'ycp_mobile_behavior',
        'Mobile Display Behavior',
        'ycp_mobile_behavior_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_general_section'
    );

    // Basic color settings (kept for backward compatibility)
    register_setting('ycp-availability-calendar', 'ycp_calendar_primary_color');
    add_settings_field(
        'ycp_calendar_primary_color',
        'Calendar Primary Color',
        'ycp_calendar_primary_color_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_general_section'
    );

    // Calendar text color
    register_setting('ycp-availability-calendar', 'ycp_calendar_text_color');
    add_settings_field(
        'ycp_calendar_text_color',
        'Calendar Text Color',
        'ycp_calendar_text_color_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_general_section'
    );

    // Show Today button
    register_setting('ycp-availability-calendar', 'ycp_show_today_button');
    add_settings_field(
        'ycp_show_today_button',
        'Show Today Button',
        'ycp_show_today_button_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_general_section'
    );
    
    // Add Advanced Color Settings section
    add_settings_section(
        'ycp_availability_calendar_color_section',
        'Advanced Color Settings',
        'ycp_color_settings_section_callback',
        'ycp-availability-calendar'
    );
    
    // Use theme colors toggle
    register_setting('ycp-availability-calendar', 'ycp_use_theme_colors');
    add_settings_field(
        'ycp_use_theme_colors',
        'Use Theme Colors',
        'ycp_use_theme_colors_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'desc' => 'When checked, the calendar will inherit colors from the theme instead of using custom colors.'
        ]
    );
    
    // Primary Color Group
    register_setting('ycp-availability-calendar', 'ycp_primary_color');
    add_settings_field(
        'ycp_primary_color',
        'Primary Color',
        'ycp_color_field_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'label_for' => 'ycp_primary_color',
            'desc' => 'Main color for selected dates (default: #1E90FF)'
        ]
    );
    
    register_setting('ycp-availability-calendar', 'ycp_primary_dark');
    add_settings_field(
        'ycp_primary_dark',
        'Primary Dark Color',
        'ycp_color_field_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'label_for' => 'ycp_primary_dark',
            'desc' => 'Darker variant of primary color (default: #0060C0)'
        ]
    );
    
    register_setting('ycp-availability-calendar', 'ycp_primary_contrast');
    add_settings_field(
        'ycp_primary_contrast',
        'Primary Contrast Color',
        'ycp_color_field_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'label_for' => 'ycp_primary_contrast',
            'desc' => 'Text color on primary background (default: white)'
        ]
    );
    
    // Secondary Color Group
    register_setting('ycp-availability-calendar', 'ycp_secondary_color');
    add_settings_field(
        'ycp_secondary_color',
        'Secondary Color',
        'ycp_color_field_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'label_for' => 'ycp_secondary_color',
            'desc' => 'Color for highlights (default: #9ECBFF)'
        ]
    );
    
    register_setting('ycp-availability-calendar', 'ycp_secondary_light');
    add_settings_field(
        'ycp_secondary_light',
        'Secondary Light Color',
        'ycp_color_field_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'label_for' => 'ycp_secondary_light',
            'desc' => 'Light variant for hover effects (default: #E6F2FF)'
        ]
    );
    
    // Accent Color Group
    register_setting('ycp-availability-calendar', 'ycp_accent_color');
    add_settings_field(
        'ycp_accent_color',
        'Accent Color',
        'ycp_color_field_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'label_for' => 'ycp_accent_color',
            'desc' => 'Accent color for special highlights (default: #FF6B00)'
        ]
    );
    
    register_setting('ycp-availability-calendar', 'ycp_accent_contrast');
    add_settings_field(
        'ycp_accent_contrast',
        'Accent Contrast Color',
        'ycp_color_field_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_color_section',
        [
            'label_for' => 'ycp_accent_contrast',
            'desc' => 'Text color on accent background (default: white)'
        ]
    );

    // Availability display section
    add_settings_section(
        'ycp_availability_calendar_availability_section',
        'Availability Settings',
        'ycp_availability_section_callback',
        'ycp-availability-calendar'
    );

    // Default available professionals
    register_setting('ycp-availability-calendar', 'ycp_default_professionals');
    add_settings_field(
        'ycp_default_professionals',
        'Default Available Professionals',
        'ycp_default_professionals_callback',
        'ycp-availability-calendar',
        'ycp_availability_calendar_availability_section'
    );
}
add_action('admin_init', 'ycp_availability_calendar_register_settings');

/**
 * Section description callback
 */
function ycp_availability_calendar_section_callback() {
    echo '<p>Configure how the availability calendar appears on your site.</p>';
}

/**
 * Availability section description callback
 */
function ycp_availability_section_callback() {
    echo '<p>Configure availability settings and default professional display.</p>';
}

/**
 * Default month count callback
 */
function ycp_default_month_count_callback() {
    $default_month_count = get_option('ycp_default_month_count', 2);
    ?>
    <select name="ycp_default_month_count">
        <option value="1" <?php selected($default_month_count, 1); ?>>Single Month</option>
        <option value="2" <?php selected($default_month_count, 2); ?>>Two Months</option>
        <option value="3" <?php selected($default_month_count, 3); ?>>Three Months</option>
    </select>
    <p class="description">Number of months to display on desktop. Mobile will always show one month.</p>
    <?php
}

/**
 * Mobile behavior callback
 */
function ycp_mobile_behavior_callback() {
    $mobile_behavior = get_option('ycp_mobile_behavior', 'responsive');
    ?>
    <select name="ycp_mobile_behavior">
        <option value="responsive" <?php selected($mobile_behavior, 'responsive'); ?>>Responsive (Auto-adjust)</option>
        <option value="fixed" <?php selected($mobile_behavior, 'fixed'); ?>>Fixed Size</option>
    </select>
    <p class="description">How the calendar should behave on mobile devices.</p>
    <?php
}

/**
 * Calendar primary color callback
 */
function ycp_calendar_primary_color_callback() {
    $color = get_option('ycp_calendar_primary_color', '#1E90FF');
    ?>
    <input type="color" name="ycp_calendar_primary_color" value="<?php echo esc_attr($color); ?>">
    <p class="description">Primary color for calendar highlights and buttons.</p>
    <?php
}

/**
 * Calendar text color callback
 */
function ycp_calendar_text_color_callback() {
    $color = get_option('ycp_calendar_text_color', '#333333');
    ?>
    <input type="color" name="ycp_calendar_text_color" value="<?php echo esc_attr($color); ?>">
    <p class="description">Text color for calendar dates and headers.</p>
    <?php
}

/**
 * Show Today button callback
 */
function ycp_show_today_button_callback() {
    $show_today = get_option('ycp_show_today_button', 1);
    ?>
    <input type="checkbox" name="ycp_show_today_button" value="1" <?php checked($show_today, 1); ?>>
    <p class="description">Display a "Today" button to quickly navigate to the current date.</p>
    <?php
}

/**
 * Default professionals callback
 */
function ycp_default_professionals_callback() {
    $default_pros = get_option('ycp_default_professionals', '');
    ?>
    <textarea name="ycp_default_professionals" rows="5" cols="50" class="large-text"><?php echo esc_textarea($default_pros); ?></textarea>
    <p class="description">Enter names of professionals to show by default, one per line. Leave empty to show all.</p>
    <?php
}

/**
 * Render the settings page
 */
function ycp_availability_calendar_render_settings_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <?php if (isset($_GET['settings-updated'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Settings saved successfully!</p>
            </div>
        <?php endif; ?>
        
        <form method="post" action="options.php">
            <?php
            settings_fields('ycp-availability-calendar');
            do_settings_sections('ycp-availability-calendar');
            submit_button('Save Settings');
            ?>
        </form>
        
        <hr>
        
        <h2>Calendar Preview</h2>
        <div id="calendar-preview-container" style="max-width: 900px; margin: 20px auto; overflow: visible;">
            <div id="admin-calendar-preview"></div>
        </div>
        
        <script>
            // This script will initialize a preview calendar using the current settings
            document.addEventListener('DOMContentLoaded', function() {
                // Preview calendar will be initialized here with JavaScript
                // The calendar appearance will update when settings are changed
                if (typeof flatpickr === 'function') {
                    const primaryColor = '<?php echo esc_js(get_option('ycp_calendar_primary_color', '#1E90FF')); ?>';
                    const textColor = '<?php echo esc_js(get_option('ycp_calendar_text_color', '#333333')); ?>';
                    const monthCount = parseInt('<?php echo esc_js(get_option('ycp_default_month_count', 2)); ?>');
                    
                    // Apply custom styles
                    const styleEl = document.createElement('style');
                    styleEl.textContent = `
                        .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
                            background: ${primaryColor};
                            border-color: ${primaryColor};
                        }
                        .flatpickr-day.today {
                            border-color: ${primaryColor};
                        }
                        .flatpickr-months .flatpickr-month {
                            color: ${textColor};
                        }
                    `;
                    document.head.appendChild(styleEl);
                    
                    // Initialize preview calendar
                    flatpickr('#admin-calendar-preview', {
                        inline: true,
                        mode: 'multiple',
                        showMonths: monthCount,
                        static: true,
                        monthSelectorType: 'static',
                        prevArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>',
                        nextArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>'
                    });
                    
                    // Add Today button if enabled
                    if (<?php echo get_option('ycp_show_today_button', 1) ? 'true' : 'false'; ?>) {
                        setTimeout(function() {
                            const monthsContainer = document.querySelector('#admin-calendar-preview .flatpickr-months');
                            if (monthsContainer) {
                                const todayBtn = document.createElement('button');
                                todayBtn.className = 'preview-today-btn';
                                todayBtn.textContent = 'Today';
                                todayBtn.style.cssText = `
                                    position: absolute;
                                    right: 50px;
                                    top: 10px;
                                    background-color: ${primaryColor};
                                    color: white;
                                    border: none;
                                    border-radius: 0;
                                    padding: 4px 10px;
                                    font-size: 12px;
                                    cursor: pointer;
                                    z-index: 10;
                                `;
                                monthsContainer.appendChild(todayBtn);
                            }
                        }, 200);
                    }
                } else {
                    document.getElementById('admin-calendar-preview').innerHTML = 
                        '<p>Preview not available. Flatpickr library not loaded.</p>';
                }
            });
        </script>
    </div>
    <?php
}

/**
 * Output custom CSS variables to the frontend based on settings
 */
function ycp_output_calendar_css() {
    // Get the use theme colors setting
    $use_theme_colors = get_option('ycp_use_theme_colors', false);
    
    // If using theme colors, add a special class but don't output variables
    if ($use_theme_colors) {
        add_filter('ycp_calendar_container_class', function($classes) {
            return $classes . ' ycp-theme-inherit';
        });
        return;
    }
    
    // Get basic color values
    $primary_color = get_option('ycp_calendar_primary_color', '#1E90FF');
    $text_color = get_option('ycp_calendar_text_color', '#333333');
    
    // Get advanced color values
    $advanced_colors = [
        'primary_color' => get_option('ycp_primary_color', ''),
        'primary_dark' => get_option('ycp_primary_dark', ''),
        'primary_contrast' => get_option('ycp_primary_contrast', ''),
        'secondary_color' => get_option('ycp_secondary_color', ''),
        'secondary_light' => get_option('ycp_secondary_light', ''),
        'accent_color' => get_option('ycp_accent_color', ''),
        'accent_contrast' => get_option('ycp_accent_contrast', ''),
    ];
    
    // Check if we have any advanced colors set
    $has_advanced_colors = false;
    foreach ($advanced_colors as $color) {
        if (!empty($color)) {
            $has_advanced_colors = true;
            break;
        }
    }
    
    // Add a special class to the calendar container if we have any advanced colors
    if ($has_advanced_colors) {
        add_filter('ycp_calendar_container_class', function($classes) {
            return $classes . ' ycp-advanced-colors';
        });
    } else {
        // If only using basic colors, add a special class
        add_filter('ycp_calendar_container_class', function($classes) {
            return $classes . ' ycp-theme-color-sync';
        });
    }
    
    // Start building the CSS for output
    $css = '<style id="ycp-custom-colors">';
    
    // If only using basic colors, set those as CSS variables
    if (!$has_advanced_colors) {
        $css .= ':root {';
        $css .= '--ycp-primary-from-settings: ' . esc_attr($primary_color) . ';';
        $css .= '--ycp-text-from-settings: ' . esc_attr($text_color) . ';';
        $css .= '}';
    } else {
        // Using advanced colors, set them all
        $css .= '#ycp-calendar-container {';
        
        // Map settings to CSS variables
        $css_mappings = [
            'primary_color' => '--admin-primary-color',
            'primary_dark' => '--admin-primary-dark',
            'primary_contrast' => '--admin-primary-contrast',
            'secondary_color' => '--admin-secondary-color',
            'secondary_light' => '--admin-secondary-light',
            'accent_color' => '--admin-accent-color',
            'accent_contrast' => '--admin-accent-contrast',
        ];
        
        // Add each color that's set as a CSS variable
        foreach ($css_mappings as $option_key => $css_var) {
            if (!empty($advanced_colors[$option_key])) {
                $css .= $css_var . ': ' . esc_attr($advanced_colors[$option_key]) . ';';
            }
        }
        
        // Use basic colors as fallbacks for any unset advanced colors
        if (empty($advanced_colors['primary_color'])) {
            $css .= '--admin-primary-color: ' . esc_attr($primary_color) . ';';
        }
        
        $css .= '}';
    }
    
    $css .= '</style>';
    
    // Output the CSS
    echo $css;
}
add_action('wp_head', 'ycp_output_calendar_css');

/**
 * Function to add classes to the calendar container
 * This will be a hook point for other functions to add classes
 */
function ycp_get_calendar_container_class() {
    $classes = 'ycp-calendar-container';
    
    // Allow other functions to add classes
    $classes = apply_filters('ycp_calendar_container_class', $classes);
    
    return $classes;
}

/**
 * Enqueue admin scripts and styles
 */
function ycp_enqueue_admin_scripts($hook) {
    if ('toplevel_page_ycp-availability-calendar' !== $hook) {
        return;
    }

    // Enqueue color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Enqueue admin specific styles
    wp_enqueue_style(
        'ycp-admin-styles',
        plugin_dir_url(__FILE__) . 'admin.css',
        array(),
        time()
    );
    
    // Enqueue frontend styles (for the calendar base)
    wp_enqueue_style(
        'ycp-frontend-styles',
        plugin_dir_url(__FILE__) . '../public/assets/style.css',
        array(),
        time()
    );
    
    // Enqueue flatpickr
    wp_enqueue_style(
        'flatpickr-css',
        'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css',
        array(),
        '4.6.13'
    );
    
    wp_enqueue_script(
        'flatpickr-js',
        'https://cdn.jsdelivr.net/npm/flatpickr',
        array('jquery'),
        '4.6.13',
        true
    );
    
    // Enqueue admin script
    wp_enqueue_script(
        'ycp-admin-script',
        plugin_dir_url(__FILE__) . 'admin.js',
        array('jquery', 'flatpickr-js', 'wp-color-picker'),
        time(),
        true
    );
}
add_action('admin_enqueue_scripts', 'ycp_enqueue_admin_scripts');

// Add the new callbacks for advanced color settings
/**
 * Advanced color settings section callback
 */
function ycp_color_settings_section_callback() {
    echo '<p>Customize the colors for your availability calendar with these advanced options.</p>';
    echo '<p>The basic color settings above will be used as fallbacks if the advanced settings are not set.</p>';
    echo '<div class="notice notice-info inline"><p><strong>Note:</strong> If "Use Theme Colors" is checked, the calendar will inherit colors from your theme and these settings will be ignored.</p></div>';
}

/**
 * Callback for color fields
 */
function ycp_color_field_callback($args) {
    $id = $args['label_for'];
    $value = get_option($id, '');
    
    echo '<input type="text" class="ycp-color-picker" id="' . esc_attr($id) . '" name="' . esc_attr($id) . '" value="' . esc_attr($value) . '" data-default-color="">';
    if (isset($args['desc'])) {
        echo '<p class="description">' . esc_html($args['desc']) . '</p>';
    }
}

/**
 * Callback for use theme colors toggle
 */
function ycp_use_theme_colors_callback($args) {
    $use_theme_colors = get_option('ycp_use_theme_colors', false);
    
    echo '<input type="checkbox" id="ycp_use_theme_colors" name="ycp_use_theme_colors" ' . checked($use_theme_colors, true, false) . ' value="1">';
    if (isset($args['desc'])) {
        echo '<p class="description">' . esc_html($args['desc']) . '</p>';
    }
}
