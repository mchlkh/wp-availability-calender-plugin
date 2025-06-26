<?php
/**
 * Compact Availability Display Examples
 * 
 * This file demonstrates how to use the new compact availability format
 * that displays dates in ranges like "25.06. - 27.06."
 */

// Example 1: Basic shortcode usage
// [ycp_availability_data professional_id="11"]
// Output: 25.06. - 27.06.

// Example 2: Shortcode with custom styling
// [ycp_availability_data professional_id="11" css_class="ycp-availability-compact modern"]
// Output: Same dates but with modern gradient styling

// Example 3: Shortcode with date range filter
// [ycp_availability_data professional_id="11" date_from="2024-06-01" date_to="2024-06-30"]
// Output: Only dates within June 2024

// Example 4: Shortcode without professional title
// [ycp_availability_data professional_id="11" show_title="false"]
// Output: Just the date ranges without the professional name

// Example 5: Shortcode with count display
// [ycp_availability_data professional_id="11" show_count="true"]
// Output: Date ranges plus count like "15 dates in 3 ranges"

// Example 6: Shortcode with custom separator
// [ycp_availability_data professional_id="11" separator=" | "]
// Output: Date ranges separated by pipes instead of line breaks

// Example 7: Shortcode with custom no-dates message
// [ycp_availability_data professional_id="11" no_dates_text="This professional is not available."]

// Example 8: PHP function usage
function display_professional_availability_compact($professional_id) {
    try {
        $data_handler = new YCP_Data_Handler();
        $data = $data_handler->get_professional_availability_compact($professional_id);
        
        if (empty($data['available_dates_formatted'])) {
            echo '<p>No availability dates found.</p>';
            return;
        }
        
        echo '<div class="ycp-availability-compact">';
        echo '<h4>' . esc_html($data['professional_name']) . '</h4>';
        echo '<div class="ycp-date-ranges">';
        echo implode('<br>', array_map('esc_html', $data['available_dates_formatted']));
        echo '</div>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<p class="ycp-error">Error: ' . esc_html($e->getMessage()) . '</p>';
    }
}

// Example 9: JavaScript AJAX usage
function get_availability_compact_ajax() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        // Get compact availability data via AJAX
        $.ajax({
            url: ycp_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'ycp_get_professional_availability',
                professional_id: 11,
                nonce: ycp_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.available_dates_formatted) {
                    var ranges = response.data.available_dates_formatted;
                    var display = ranges.join('<br>');
                    
                    $('#availability-display').html(
                        '<div class="ycp-availability-compact">' +
                        '<div class="ycp-date-ranges">' + display + '</div>' +
                        '</div>'
                    );
                }
            }
        });
    });
    </script>
    <?php
}

// Example 10: Custom template integration
function custom_availability_template($professional_id) {
    $data_handler = new YCP_Data_Handler();
    $data = $data_handler->get_professional_availability_compact($professional_id);
    
    if (empty($data['available_dates_ranges'])) {
        return;
    }
    
    // Custom template with detailed range information
    echo '<div class="custom-availability">';
    foreach ($data['available_dates_ranges'] as $range) {
        echo '<div class="date-range">';
        echo '<span class="range-dates">' . esc_html($range['display']) . '</span>';
        echo '<span class="range-days">(' . $range['days_count'] . ' days)</span>';
        echo '</div>';
    }
    echo '</div>';
}

// Example 11: Integration with theme templates
function theme_integration_example() {
    // In your theme's single-ycp_professional.php or similar
    $professional_id = get_the_ID();
    
    // Display compact availability
    echo do_shortcode('[ycp_availability_data professional_id="' . $professional_id . '" show_title="false"]');
    
    // Or use PHP function
    display_professional_availability_compact($professional_id);
}

// Example 12: Widget integration
function availability_widget_example() {
    // In a custom widget or sidebar
    $professional_id = 11; // Get from widget settings
    
    echo '<div class="availability-widget">';
    echo '<h3>Availability</h3>';
    echo do_shortcode('[ycp_availability_data professional_id="' . $professional_id . '" css_class="ycp-availability-compact minimal"]');
    echo '</div>';
}

// Example 13: REST API usage
function rest_api_compact_example() {
    // Using the REST API endpoint
    $response = wp_remote_get(home_url('/wp-json/ycp/v1/professional/' . $professional_id . '/availability'));
    
    if (!is_wp_error($response)) {
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($data['available_dates_formatted'])) {
            echo '<div class="ycp-availability-compact">';
            echo '<div class="ycp-date-ranges">';
            echo implode('<br>', array_map('esc_html', $data['available_dates_formatted']));
            echo '</div>';
            echo '</div>';
        }
    }
}

// Example 14: Conditional display based on availability
function conditional_availability_display($professional_id) {
    $data_handler = new YCP_Data_Handler();
    $data = $data_handler->get_professional_availability_compact($professional_id);
    
    if (!empty($data['available_dates_formatted'])) {
        echo '<div class="available-indicator">';
        echo '<span class="status available">Available</span>';
        echo '<div class="availability-dates">';
        echo implode(', ', array_map('esc_html', $data['available_dates_formatted']));
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="available-indicator">';
        echo '<span class="status unavailable">Not Available</span>';
        echo '</div>';
    }
}

// Example 15: Advanced formatting with custom CSS classes
function advanced_availability_display($professional_id) {
    $data_handler = new YCP_Data_Handler();
    $data = $data_handler->get_professional_availability_compact($professional_id);
    
    if (empty($data['available_dates_ranges'])) {
        return;
    }
    
    echo '<div class="advanced-availability">';
    foreach ($data['available_dates_ranges'] as $index => $range) {
        $css_class = ($index % 2 == 0) ? 'range-even' : 'range-odd';
        echo '<div class="date-range ' . $css_class . '">';
        echo '<span class="range-text">' . esc_html($range['display']) . '</span>';
        echo '<span class="range-duration">' . $range['days_count'] . ' day' . ($range['days_count'] > 1 ? 's' : '') . '</span>';
        echo '</div>';
    }
    echo '</div>';
}
?> 