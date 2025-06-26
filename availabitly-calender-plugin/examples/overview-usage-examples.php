<?php
/**
 * Availability Calendar Plugin - Overview UI Usage Examples
 * 
 * This file contains practical examples of how to use the overview UI
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
 * Example 1: Add Overview to Theme Header
 * 
 * Add this to your theme's header.php
 */
function example_add_overview_to_header() {
    if (function_exists('ycp_render_availability_overview')) {
        echo '<div class="header-availability-overview">';
        echo ycp_render_availability_overview([
            'show_today_only' => true,
            'template' => 'minimal'
        ]);
        echo '</div>';
    }
}

/**
 * Example 2: Add Overview to Sidebar
 * 
 * Add this to your theme's sidebar.php
 */
function example_add_overview_to_sidebar() {
    if (function_exists('ycp_render_availability_overview')) {
        echo '<div class="sidebar-availability-overview">';
        echo '<h3>Availability Overview</h3>';
        echo ycp_render_availability_overview([
            'view' => 'summary',
            'limit' => 10
        ]);
        echo '</div>';
    }
}

/**
 * Example 3: Create Custom Overview Widget
 */
class Example_Overview_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'example_overview_widget',
            'Availability Overview Widget',
            ['description' => 'Display availability overview']
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        $title = !empty($instance['title']) ? $instance['title'] : 'Availability Overview';
        $view = !empty($instance['view']) ? $instance['view'] : 'summary';
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 10;
        
        echo $args['before_title'] . esc_html($title) . $args['after_title'];
        
        if (function_exists('ycp_render_availability_overview')) {
            echo ycp_render_availability_overview([
                'view' => $view,
                'limit' => $limit
            ]);
        }
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Availability Overview';
        $view = !empty($instance['view']) ? $instance['view'] : 'summary';
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 10;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
                   name="<?php echo $this->get_field_name('title'); ?>" type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('view'); ?>">View:</label>
            <select class="widefat" id="<?php echo $this->get_field_id('view'); ?>" 
                    name="<?php echo $this->get_field_name('view'); ?>">
                <option value="summary" <?php selected($view, 'summary'); ?>>Summary</option>
                <option value="calendar" <?php selected($view, 'calendar'); ?>>Calendar</option>
                <option value="list" <?php selected($view, 'list'); ?>>List</option>
                <option value="chart" <?php selected($view, 'chart'); ?>>Chart</option>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>">Limit:</label>
            <input class="widefat" id="<?php echo $this->get_field_id('limit'); ?>" 
                   name="<?php echo $this->get_field_name('limit'); ?>" type="number" 
                   min="1" max="50" value="<?php echo esc_attr($limit); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['view'] = !empty($new_instance['view']) ? sanitize_text_field($new_instance['view']) : 'summary';
        $instance['limit'] = !empty($new_instance['limit']) ? absint($new_instance['limit']) : 10;
        return $instance;
    }
}

/**
 * Example 4: Add Overview to Page Templates
 */
function example_add_overview_to_page_template() {
    // Only on specific pages
    if (is_page('availability') || is_page('schedule')) {
        echo '<div class="page-availability-overview">';
        echo '<h2>Availability Overview</h2>';
        
        // Calendar view
        echo '<div class="calendar-section">';
        echo '<h3>Calendar View</h3>';
        echo do_shortcode('[ycp_availability_calendar date_range="60" highlight_today="true"]');
        echo '</div>';
        
        // Summary view
        echo '<div class="summary-section">';
        echo '<h3>Summary</h3>';
        echo do_shortcode('[ycp_availability_summary auto_refresh="true"]');
        echo '</div>';
        
        echo '</div>';
    }
}

/**
 * Example 5: Custom Overview with Data
 */
function example_custom_overview_with_data() {
    if (function_exists('ycp_get_overview_data')) {
        $data = ycp_get_overview_data(['limit' => 20]);
        
        if (!empty($data)) {
            echo '<div class="custom-overview">';
            echo '<h3>Custom Availability Overview</h3>';
            
            // Statistics
            echo '<div class="overview-stats">';
            echo '<div class="stat">';
            echo '<span class="number">' . esc_html($data['total_professionals']) . '</span>';
            echo '<span class="label">Total Professionals</span>';
            echo '</div>';
            
            echo '<div class="stat">';
            echo '<span class="number">' . esc_html($data['available_today']) . '</span>';
            echo '<span class="label">Available Today</span>';
            echo '</div>';
            
            echo '<div class="stat">';
            echo '<span class="number">' . esc_html($data['average_days_per_professional']) . '</span>';
            echo '<span class="label">Avg. Days Available</span>';
            echo '</div>';
            echo '</div>';
            
            // Top professionals
            if (!empty($data['professionals'])) {
                echo '<div class="top-professionals">';
                echo '<h4>Most Available Professionals</h4>';
                echo '<ul>';
                $top_professionals = array_slice($data['professionals'], 0, 5);
                foreach ($top_professionals as $professional) {
                    echo '<li>';
                    echo '<span class="name">' . esc_html($professional['name']) . '</span>';
                    echo '<span class="days">' . esc_html($professional['total_available_days']) . ' days</span>';
                    if ($professional['is_available_today']) {
                        echo '<span class="today-badge">Today</span>';
                    }
                    echo '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
            
            echo '</div>';
        }
    }
}

/**
 * Example 6: AJAX Overview Loader
 */
function example_ajax_overview_loader() {
    ?>
    <div class="ajax-overview-loader">
        <h3>Dynamic Availability Overview</h3>
        <div class="loader-controls">
            <button id="load-calendar" class="btn">Load Calendar</button>
            <button id="load-summary" class="btn">Load Summary</button>
            <button id="load-chart" class="btn">Load Chart</button>
        </div>
        <div id="overview-container"></div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#load-calendar').on('click', function() {
            loadOverview('calendar');
        });
        
        $('#load-summary').on('click', function() {
            loadOverview('summary');
        });
        
        $('#load-chart').on('click', function() {
            loadOverview('chart');
        });
        
        function loadOverview(view) {
            const container = $('#overview-container');
            container.html('<div class="loading">Loading...</div>');
            
            $.ajax({
                url: ycp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ycp_get_overview_data',
                    nonce: ycp_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create overview instance
                        const overview = new AvailabilityOverview('#overview-container', {
                            view: view,
                            dateRange: 30
                        });
                    } else {
                        container.html('<div class="error">Error loading data</div>');
                    }
                },
                error: function() {
                    container.html('<div class="error">Failed to load data</div>');
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Example 7: Responsive Overview Grid
 */
function example_responsive_overview_grid() {
    echo '<div class="responsive-overview-grid">';
    
    // Mobile: Summary only
    echo '<div class="overview-mobile">';
    echo do_shortcode('[ycp_availability_summary limit="5"]');
    echo '</div>';
    
    // Tablet: Calendar and summary
    echo '<div class="overview-tablet">';
    echo '<div class="calendar-column">';
    echo do_shortcode('[ycp_availability_calendar date_range="30"]');
    echo '</div>';
    echo '<div class="summary-column">';
    echo do_shortcode('[ycp_availability_summary limit="10"]');
    echo '</div>';
    echo '</div>';
    
    // Desktop: Full overview
    echo '<div class="overview-desktop">';
    echo '<div class="calendar-section">';
    echo do_shortcode('[ycp_availability_calendar date_range="60"]');
    echo '</div>';
    echo '<div class="summary-section">';
    echo do_shortcode('[ycp_availability_summary limit="15"]');
    echo '</div>';
    echo '<div class="chart-section">';
    echo do_shortcode('[ycp_availability_chart]');
    echo '</div>';
    echo '</div>';
    
    echo '</div>';
}

/**
 * Example 8: Professional Profile Overview
 */
function example_professional_profile_overview() {
    // Only on professional post type
    if (get_post_type() === 'ycp_professional') {
        $professional_id = get_the_ID();
        
        if (function_exists('ycp_get_professional_availability')) {
            try {
                $data = ycp_get_professional_availability($professional_id);
                
                echo '<div class="professional-overview">';
                echo '<h3>Professional Availability</h3>';
                
                echo '<div class="availability-stats">';
                echo '<div class="stat">';
                echo '<span class="number">' . esc_html($data['total_available_days']) . '</span>';
                echo '<span class="label">Total Available Days</span>';
                echo '</div>';
                
                echo '<div class="stat">';
                echo '<span class="number">' . ($data['is_available_today'] ? 'Yes' : 'No') . '</span>';
                echo '<span class="label">Available Today</span>';
                echo '</div>';
                echo '</div>';
                
                if (!empty($data['available_dates'])) {
                    echo '<div class="available-dates">';
                    echo '<h4>Available Dates</h4>';
                    echo '<ul>';
                    foreach (array_slice($data['available_dates'], 0, 10) as $date) {
                        echo '<li>' . esc_html(date('F j, Y', strtotime($date))) . '</li>';
                    }
                    if (count($data['available_dates']) > 10) {
                        echo '<li>... and ' . (count($data['available_dates']) - 10) . ' more dates</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
                
                echo '</div>';
                
            } catch (Exception $e) {
                echo '<p class="error">Unable to load availability data.</p>';
            }
        }
    }
}

/**
 * Example 9: WooCommerce Integration
 */
function example_woocommerce_integration() {
    // Only on product pages
    if (function_exists('is_product') && is_product()) {
        $product_id = get_the_ID();
        $professional_id = get_post_meta($product_id, '_availability_professional_id', true);
        
        if ($professional_id && function_exists('ycp_get_professional_availability')) {
            try {
                $data = ycp_get_professional_availability($professional_id);
                
                echo '<div class="product-availability-overview">';
                echo '<h3>Professional Availability</h3>';
                echo '<p><strong>Professional:</strong> ' . esc_html($data['name']) . '</p>';
                echo '<p><strong>Available Today:</strong> ' . ($data['is_available_today'] ? 'Yes' : 'No') . '</p>';
                echo '<p><strong>Total Available Days:</strong> ' . esc_html($data['total_available_days']) . '</p>';
                
                if ($data['is_available_today']) {
                    echo '<div class="availability-notice">';
                    echo '<span class="available-badge">Available Today!</span>';
                    echo '</div>';
                }
                
                echo '</div>';
                
            } catch (Exception $e) {
                // Handle error silently
            }
        }
    }
}

/**
 * Example 10: Admin Dashboard Enhancement
 */
function example_admin_dashboard_enhancement() {
    if (is_admin() && function_exists('ycp_get_overview_data')) {
        $data = ycp_get_overview_data(['limit' => 20]);
        
        if (!empty($data)) {
            echo '<div class="admin-overview-enhancement">';
            echo '<h4>Quick Availability Stats</h4>';
            
            echo '<div class="admin-stats">';
            echo '<div class="admin-stat">';
            echo '<span class="stat-number">' . esc_html($data['total_professionals']) . '</span>';
            echo '<span class="stat-label">Professionals</span>';
            echo '</div>';
            
            echo '<div class="admin-stat">';
            echo '<span class="stat-number">' . esc_html($data['available_today']) . '</span>';
            echo '<span class="stat-label">Available Today</span>';
            echo '</div>';
            
            echo '<div class="admin-stat">';
            echo '<span class="stat-number">' . esc_html($data['average_days_per_professional']) . '</span>';
            echo '<span class="stat-label">Avg. Days</span>';
            echo '</div>';
            echo '</div>';
            
            echo '</div>';
        }
    }
}

// Register the example widget
add_action('widgets_init', function() {
    register_widget('Example_Overview_Widget');
});

// Add examples to appropriate hooks
add_action('wp_head', 'example_add_overview_to_header');
add_action('get_sidebar', 'example_add_overview_to_sidebar');
add_action('wp_footer', 'example_add_overview_to_page_template');
add_action('wp_footer', 'example_custom_overview_with_data');
add_action('wp_footer', 'example_ajax_overview_loader');
add_action('wp_footer', 'example_responsive_overview_grid');
add_action('the_content', 'example_professional_profile_overview');
add_action('woocommerce_single_product_summary', 'example_woocommerce_integration', 25);
add_action('wp_dashboard_setup', 'example_admin_dashboard_enhancement');

// Add CSS for examples
add_action('wp_head', function() {
    ?>
    <style>
    .header-availability-overview {
        background: #f8f9fa;
        padding: 10px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .sidebar-availability-overview {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
    }
    
    .custom-overview {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin: 20px 0;
    }
    
    .overview-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat {
        text-align: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
    }
    
    .stat .number {
        display: block;
        font-size: 2rem;
        font-weight: 700;
        color: #667eea;
    }
    
    .stat .label {
        display: block;
        font-size: 0.9rem;
        color: #666;
        margin-top: 5px;
    }
    
    .top-professionals ul {
        list-style: none;
        padding: 0;
    }
    
    .top-professionals li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .today-badge {
        background: #28a745;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.8rem;
    }
    
    .ajax-overview-loader {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin: 20px 0;
    }
    
    .loader-controls {
        margin-bottom: 15px;
    }
    
    .btn {
        background: #667eea;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 10px;
    }
    
    .btn:hover {
        background: #5a6fd8;
    }
    
    .responsive-overview-grid {
        margin: 20px 0;
    }
    
    .overview-mobile {
        display: block;
    }
    
    .overview-tablet,
    .overview-desktop {
        display: none;
    }
    
    @media (min-width: 768px) {
        .overview-mobile {
            display: none;
        }
        .overview-tablet {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
    }
    
    @media (min-width: 1024px) {
        .overview-tablet {
            display: none;
        }
        .overview-desktop {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
    }
    
    .professional-overview {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .availability-notice {
        margin-top: 15px;
        text-align: center;
    }
    
    .available-badge {
        background: #28a745;
        color: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-weight: 600;
    }
    
    .admin-overview-enhancement {
        background: #fff;
        padding: 15px;
        border-radius: 6px;
        border-left: 4px solid #667eea;
        margin-top: 15px;
    }
    
    .admin-stats {
        display: flex;
        gap: 20px;
        margin-top: 10px;
    }
    
    .admin-stat {
        text-align: center;
    }
    
    .admin-stat .stat-number {
        display: block;
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
    }
    
    .admin-stat .stat-label {
        display: block;
        font-size: 0.8rem;
        color: #666;
    }
    </style>
    <?php
}); 