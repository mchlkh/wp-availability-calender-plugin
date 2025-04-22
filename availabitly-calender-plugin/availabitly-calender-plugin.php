<?php

/**
 * Plugin Name: Availabitly Calendar Plugin
 * Plugin URI: 
 * Description: A calendar that shows available professionals.
 * Version: 1.0
 * Author: Michael Kofler-Hofer
 * Author URI: 
 * Text Domain: availabitly-calendar-plugin
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include admin files
require_once plugin_dir_path(__FILE__) . 'admin/color-settings.php';

add_action('init', 'ycp_register_professional_cpt');

function ycp_register_professional_cpt() {
    register_post_type('ycp_professional', [
        'labels' => [
            'name' => 'Availabilities',
            'singular_name' => 'Availability',
        ],
        'public' => true,
        'has_archive' => false,
        'show_in_rest' => true,
        'supports' => ['title', 'thumbnail'],
        'menu_icon' => 'dashicons-businessperson'
    ]);
}

// Add meta box
add_action('add_meta_boxes', 'ycp_add_professional_meta_box');
function ycp_add_professional_meta_box() {
    add_meta_box(
        'ycp_professional_details',
        'Professional Availability & Page Link',
        'ycp_render_professional_meta_box',
        'ycp_professional',
        'normal',
        'high'
    );
}

function ycp_render_professional_meta_box($post) {
    $profile_url = get_post_meta($post->ID, '_ycp_profile_url', true);
    $available_dates = get_post_meta($post->ID, '_ycp_available_dates', true);

    echo '<label for="ycp_profile_url">Profile Page URL:</label><br>';
    echo '<input type="url" name="ycp_profile_url" value="' . esc_attr($profile_url) . '" style="width: 100%;" /><br><br>';

    echo '<label for="ycp_available_dates"><strong>Available Dates:</strong></label><br>';
    echo '<input type="text" id="ycp_available_dates" name="ycp_available_dates" value="' . esc_attr($available_dates) . '" style="width: 100%;" placeholder="Select dates..." /><br>';
    echo '<small>Hold CTRL (or CMD) to select multiple dates.</small><br><br>';

    wp_nonce_field('ycp_save_professional_meta', 'ycp_professional_nonce');
}


// Save meta box data
add_action('save_post', 'ycp_save_professional_meta');
function ycp_save_professional_meta($post_id) {
    if (!isset($_POST['ycp_professional_nonce']) || !wp_verify_nonce($_POST['ycp_professional_nonce'], 'ycp_save_professional_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Save profile URL
    if (isset($_POST['ycp_profile_url'])) {
        update_post_meta($post_id, '_ycp_profile_url', esc_url_raw($_POST['ycp_profile_url']));
    }

    // Save availability
    if (isset($_POST['ycp_available_dates'])) {
        $raw_dates = sanitize_text_field($_POST['ycp_available_dates']);
        update_post_meta($post_id, '_ycp_available_dates', $raw_dates);
    }
}

add_action('admin_enqueue_scripts', 'ycp_enqueue_admin_scripts');
function ycp_enqueue_admin_scripts($hook) {
    global $post;
    if ($hook === 'post-new.php' || $hook === 'post.php') {
        if ($post->post_type === 'ycp_professional') {
            wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', [], null, true);
            wp_enqueue_style('flatpickr-style', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
            wp_enqueue_script('ycp-admin', plugin_dir_url(__FILE__) . 'admin/admin.js', ['flatpickr'], null, true);
        }
    }
}

// Register shortcode for frontend display
add_shortcode('ycp_calendar', 'ycp_render_calendar_shortcode');

function ycp_render_calendar_shortcode() {
    // Get color settings options
    $options = get_option('ycp_color_options', []);
    
    // Add custom class if we have color settings
    $container_class = '';
    if (!empty($options)) {
        if (isset($options['ycp_use_theme_colors']) && $options['ycp_use_theme_colors']) {
            $container_class = 'class="ycp-theme-color-sync"';
        } else {
            $container_class = 'class="ycp-custom-colors"';
        }
    }
    
    ob_start();
    
    // Output calendar container with appropriate class
    echo '<div id="ycp-calendar-container" ' . $container_class . '>';
    
    include plugin_dir_path(__FILE__) . 'public/frontend-display.php';
    
    echo '</div>';
    
    return ob_get_clean();
}


add_action('wp_enqueue_scripts', 'ycp_enqueue_frontend_assets');
function ycp_enqueue_frontend_assets() {
    wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', [], null, true);
    wp_enqueue_style('flatpickr-style', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css');
    
    wp_enqueue_style('ycp-style', plugin_dir_url(__FILE__) . 'public/assets/style.css');
    wp_enqueue_script('ycp-script', plugin_dir_url(__FILE__) . 'public/assets/script.js', ['jquery', 'flatpickr'], null, true);

    wp_localize_script('ycp-script', 'ycp_ajax', [
        'ajax_url' => admin_url('admin-ajax.php')
    ]);
}

/**
 * Output custom CSS variables to the frontend based on settings
 */
function ycp_output_custom_css() {
    $options = get_option('ycp_color_options', []);
    
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
add_action('wp_head', 'ycp_output_custom_css');

add_action('wp_ajax_ycp_get_professionals', 'ycp_get_professionals_by_date');
add_action('wp_ajax_nopriv_ycp_get_professionals', 'ycp_get_professionals_by_date');

function ycp_get_professionals_by_date() {
    $date = sanitize_text_field($_GET['date']);

    $args = [
        'post_type' => 'ycp_professional',
        'posts_per_page' => -1
    ];

    $query = new WP_Query($args);

    $output = '<div class="ycp-pro-list">';
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $dates = get_post_meta(get_the_ID(), '_ycp_available_dates', true);
            $date_array = array_map('trim', explode(',', $dates));

            if (in_array($date, $date_array)) {
                $name = get_the_title();
                $img = get_the_post_thumbnail(get_the_ID(), 'medium');
                $url = get_post_meta(get_the_ID(), '_ycp_profile_url', true);

                $output .= '<div class="ycp-pro">';
                
                // Check if URL exists and is not empty
                if (!empty($url)) {
                    $url = esc_url($url);
                    $output .= "<a href='{$url}'><div class='image-container'>{$img}<h4>{$name}</h4></div></a>";
                } else {
                    // No URL, render as non-clickable
                    $output .= "<div class='image-container non-clickable'>{$img}<h4>{$name}</h4></div>";
                }
                
                $output .= '</div>';
            }
        }
    } else {
        $output .= '<p>Keine Fachleute gefunden.</p>';
    }
    $output .= '</div>';

    wp_reset_postdata();
    echo $output;
    wp_die();
}
