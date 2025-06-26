<?php
/**
 * Availability Calendar Plugin - Usage Examples
 * 
 * This file contains practical examples of how to use the availability interface
 * in different scenarios within WordPress.
 * 
 * @package AvailabilityCalendarPlugin
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Example 1: Display Today's Availability in Header
 * 
 * Add this to your theme's header.php or use a hook
 */
function example_display_today_availability_in_header() {
    try {
        $today_data = ycp_get_availability_by_date();
        
        if ($today_data['count'] > 0) {
            echo '<div class="today-availability-header">';
            echo '<span class="availability-count">' . esc_html($today_data['count']) . ' professionals available today</span>';
            echo '</div>';
        }
    } catch (Exception $e) {
        // Log error but don't display to users
        error_log('Header availability error: ' . $e->getMessage());
    }
}

/**
 * Example 2: Create a Custom Availability Widget
 */
class Example_Availability_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'example_availability_widget',
            'Availability Widget',
            ['description' => 'Display professional availability']
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? $instance['title'] : 'Today\'s Availability';
        $date = !empty($instance['date']) ? $instance['date'] : date('Y-m-d');
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        try {
            $data = ycp_get_availability_by_date($date, $limit);
            
            if ($data['count'] > 0) {
                echo '<ul class="availability-list">';
                foreach ($data['available_professionals'] as $professional) {
                    echo '<li class="professional-item">';
                    echo '<h4>' . esc_html($professional['name']) . '</h4>';
                    if (!empty($professional['description'])) {
                        echo '<p>' . esc_html($professional['description']) . '</p>';
                    }
                    if (!empty($professional['profile_url'])) {
                        echo '<a href="' . esc_url($professional['profile_url']) . '" class="profile-link">View Profile</a>';
                    }
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No professionals available on this date.</p>';
            }
        } catch (Exception $e) {
            echo '<p class="error">Unable to load availability data.</p>';
            error_log('Widget availability error: ' . $e->getMessage());
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Today\'s Availability';
        $date = !empty($instance['date']) ? $instance['date'] : date('Y-m-d');
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('date'); ?>">Date (Y-m-d):</label>
            <input class="widefat" id="<?php echo $this->get_field_id('date'); ?>" 
                   name="<?php echo $this->get_field_name('date'); ?>" type="date" 
                   value="<?php echo esc_attr($date); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>">Max Professionals:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" 
                   name="<?php echo $this->get_field_name('limit'); ?>" type="number" 
                   min="1" max="50" value="<?php echo esc_attr($limit); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['date'] = !empty($new_instance['date']) ? sanitize_text_field($new_instance['date']) : '';
        $instance['limit'] = !empty($new_instance['limit']) ? absint($new_instance['limit']) : 5;
        return $instance;
    }
}

/**
 * Example 3: Add Availability to Professional Profile Pages
 */
function example_add_availability_to_profile($content) {
    // Only on professional post type
    if (get_post_type() !== 'ycp_professional') {
        return $content;
    }
    
    $professional_id = get_the_ID();
    
    try {
        $availability_data = ycp_get_professional_availability($professional_id);
        
        $availability_html = '<div class="professional-availability-section">';
        $availability_html .= '<h3>Availability Information</h3>';
        $availability_html .= '<div class="availability-stats">';
        $availability_html .= '<p><strong>Total Available Days:</strong> ' . esc_html($availability_data['total_available_days']) . '</p>';
        $availability_html .= '<p><strong>Available Today:</strong> ' . ($availability_data['is_available_today'] ? 'Yes' : 'No') . '</p>';
        $availability_html .= '</div>';
        
        if (!empty($availability_data['available_dates'])) {
            $availability_html .= '<div class="available-dates">';
            $availability_html .= '<h4>Available Dates:</h4>';
            $availability_html .= '<ul class="dates-list">';
            foreach ($availability_data['available_dates'] as $date) {
                $formatted_date = date('F j, Y', strtotime($date));
                $availability_html .= '<li>' . esc_html($formatted_date) . '</li>';
            }
            $availability_html .= '</ul>';
            $availability_html .= '</div>';
        }
        
        $availability_html .= '</div>';
        
        return $content . $availability_html;
        
    } catch (Exception $e) {
        error_log('Profile availability error: ' . $e->getMessage());
        return $content;
    }
}

/**
 * Example 4: Create a Shortcode for Date Range Availability
 */
function example_date_range_availability_shortcode($atts) {
    $atts = shortcode_atts([
        'date_from' => '',
        'date_to' => '',
        'professional_id' => 0,
        'template' => 'list'
    ], $atts);
    
    if (empty($atts['professional_id'])) {
        return '<p class="error">Professional ID is required.</p>';
    }
    
    try {
        $data = ycp_get_professional_availability(
            absint($atts['professional_id']),
            sanitize_text_field($atts['date_from']),
            sanitize_text_field($atts['date_to'])
        );
        
        $output = '<div class="date-range-availability">';
        $output .= '<h3>' . esc_html($data['name']) . ' - Availability</h3>';
        
        if (!empty($atts['date_from']) || !empty($atts['date_to'])) {
            $range_text = '';
            if (!empty($atts['date_from']) && !empty($atts['date_to'])) {
                $range_text = 'from ' . date('F j, Y', strtotime($atts['date_from'])) . 
                             ' to ' . date('F j, Y', strtotime($atts['date_to']));
            } elseif (!empty($atts['date_from'])) {
                $range_text = 'from ' . date('F j, Y', strtotime($atts['date_from']));
            } elseif (!empty($atts['date_to'])) {
                $range_text = 'until ' . date('F j, Y', strtotime($atts['date_to']));
            }
            $output .= '<p class="date-range">' . esc_html($range_text) . '</p>';
        }
        
        $output .= '<p><strong>Available Days:</strong> ' . esc_html($data['total_available_days']) . '</p>';
        
        if (!empty($data['available_dates'])) {
            $output .= '<div class="available-dates">';
            $output .= '<h4>Available Dates:</h4>';
            $output .= '<ul>';
            foreach ($data['available_dates'] as $date) {
                $output .= '<li>' . esc_html(date('F j, Y', strtotime($date))) . '</li>';
            }
            $output .= '</ul>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        
        return $output;
        
    } catch (Exception $e) {
        return '<p class="error">Error loading availability data: ' . esc_html($e->getMessage()) . '</p>';
    }
}

/**
 * Example 5: Add Availability to WooCommerce Product Pages
 */
function example_add_availability_to_product_page() {
    // Only on product pages
    if (!is_product()) {
        return;
    }
    
    // Get product ID and check if it has availability data
    $product_id = get_the_ID();
    $availability_professional_id = get_post_meta($product_id, '_availability_professional_id', true);
    
    if (empty($availability_professional_id)) {
        return;
    }
    
    try {
        $availability_data = ycp_get_professional_availability($availability_professional_id);
        
        echo '<div class="product-availability">';
        echo '<h3>Professional Availability</h3>';
        echo '<p><strong>Professional:</strong> ' . esc_html($availability_data['name']) . '</p>';
        echo '<p><strong>Available Today:</strong> ' . ($availability_data['is_available_today'] ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Total Available Days:</strong> ' . esc_html($availability_data['total_available_days']) . '</p>';
        echo '</div>';
        
    } catch (Exception $e) {
        error_log('Product availability error: ' . $e->getMessage());
    }
}

/**
 * Example 6: Create an AJAX Availability Checker
 */
function example_ajax_availability_checker() {
    ?>
    <div class="ajax-availability-checker">
        <h3>Check Availability</h3>
        <div class="checker-form">
            <label for="check-date">Select Date:</label>
            <input type="date" id="check-date" name="check-date">
            <button type="button" id="check-availability-btn">Check Availability</button>
        </div>
        <div id="availability-results"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#check-availability-btn').on('click', function() {
            const selectedDate = $('#check-date').val();
            const resultsContainer = $('#availability-results');
            
            if (!selectedDate) {
                resultsContainer.html('<p class="error">Please select a date.</p>');
                return;
            }
            
            resultsContainer.html('<p class="loading">Checking availability...</p>');
            
            $.ajax({
                url: ycp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ycp_get_availability_data',
                    nonce: ycp_ajax.nonce,
                    date: selectedDate
                },
                success: function(response) {
                    if (response.success) {
                        const data = response.data;
                        let html = '<div class="availability-results">';
                        html += '<h4>Available on ' + new Date(data.date).toLocaleDateString() + '</h4>';
                        
                        if (data.count > 0) {
                            html += '<ul class="available-professionals">';
                            data.available_professionals.forEach(function(professional) {
                                html += '<li>';
                                html += '<h5>' + professional.name + '</h5>';
                                if (professional.description) {
                                    html += '<p>' + professional.description + '</p>';
                                }
                                html += '</li>';
                            });
                            html += '</ul>';
                        } else {
                            html += '<p>No professionals available on this date.</p>';
                        }
                        
                        html += '</div>';
                        resultsContainer.html(html);
                    } else {
                        resultsContainer.html('<p class="error">Error: ' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    resultsContainer.html('<p class="error">Failed to check availability. Please try again.</p>');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Example 7: Add Availability to Admin Dashboard
 */
function example_admin_dashboard_availability_widget() {
    try {
        $today_data = ycp_get_availability_by_date();
        $all_data = ycp_get_all_professionals_availability(10);
        
        echo '<div class="dashboard-availability-widget">';
        echo '<h3>Today\'s Availability</h3>';
        echo '<p><strong>Available Today:</strong> ' . esc_html($today_data['count']) . ' professionals</p>';
        
        if ($today_data['count'] > 0) {
            echo '<ul>';
            foreach ($today_data['available_professionals'] as $professional) {
                echo '<li>' . esc_html($professional['name']) . '</li>';
            }
            echo '</ul>';
        }
        
        echo '<h3>All Professionals</h3>';
        echo '<p><strong>Total Professionals:</strong> ' . esc_html($all_data['count']) . '</p>';
        
        echo '<h4>Top Available Professionals:</h4>';
        echo '<ul>';
        foreach (array_slice($all_data['professionals'], 0, 5) as $professional) {
            echo '<li>' . esc_html($professional['name']) . ' - ' . esc_html($professional['total_available_days']) . ' days</li>';
        }
        echo '</ul>';
        
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<p class="error">Unable to load availability data.</p>';
        error_log('Dashboard availability error: ' . $e->getMessage());
    }
}

/**
 * Example 8: Create a REST API Client Function
 */
function example_rest_api_client() {
    $rest_url = rest_url('ycp/v1/availability');
    
    // Get availability for professional ID 123
    $response = wp_remote_get($rest_url . '/123');
    
    if (is_wp_error($response)) {
        error_log('REST API error: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if ($data) {
        echo '<div class="rest-api-result">';
        echo '<h3>REST API Result</h3>';
        echo '<p><strong>Professional:</strong> ' . esc_html($data['name']) . '</p>';
        echo '<p><strong>Available Days:</strong> ' . esc_html($data['total_available_days']) . '</p>';
        echo '</div>';
    }
}

// Register the example widget
add_action('widgets_init', function() {
    register_widget('Example_Availability_Widget');
});

// Add availability to professional profiles
add_filter('the_content', 'example_add_availability_to_profile');

// Register the date range shortcode
add_shortcode('ycp_date_range_availability', 'example_date_range_availability_shortcode');

// Add availability to WooCommerce products (if WooCommerce is active)
if (class_exists('WooCommerce')) {
    add_action('woocommerce_single_product_summary', 'example_add_availability_to_product_page', 25);
}

// Add availability checker to pages
add_action('wp_footer', 'example_ajax_availability_checker');

// Add dashboard widget (admin only)
if (is_admin()) {
    add_action('wp_dashboard_setup', function() {
        wp_add_dashboard_widget(
            'example_availability_dashboard_widget',
            'Availability Overview',
            'example_admin_dashboard_availability_widget'
        );
    });
} 