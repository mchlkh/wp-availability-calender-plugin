<?php
/**
 * Availability Calendar Plugin - Overview UI
 * 
 * Provides UI components for displaying availability overviews
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
 * Class YCP_Overview_UI
 * 
 * Handles UI components for availability overviews
 */
class YCP_Overview_UI {
    
    /**
     * Initialize the overview UI
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        // Register shortcodes
        add_shortcode('ycp_availability_overview', [$this, 'render_overview_shortcode']);
        add_shortcode('ycp_availability_calendar', [$this, 'render_calendar_shortcode']);
        add_shortcode('ycp_availability_summary', [$this, 'render_summary_shortcode']);
        add_shortcode('ycp_availability_chart', [$this, 'render_chart_shortcode']);
        add_shortcode('ycp_availability_list', [$this, 'render_list_shortcode']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_overview_assets']);
        
        // Add AJAX handlers
        add_action('wp_ajax_ycp_get_overview_data', [$this, 'handle_get_overview_data']);
        add_action('wp_ajax_nopriv_ycp_get_overview_data', [$this, 'handle_get_overview_data']);
    }
    
    /**
     * Enqueue overview assets
     */
    public function enqueue_overview_assets(): void {
        wp_enqueue_style('ycp-overview-style', plugin_dir_url(dirname(__FILE__)) . 'public/assets/availability-overview.css', [], '1.0.0');
        wp_enqueue_script('ycp-overview-script', plugin_dir_url(dirname(__FILE__)) . 'public/assets/availability-overview.js', ['jquery'], '1.0.0', true);
        
        // Add Chart.js for chart view
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        
        // Localize script
        wp_localize_script('ycp-overview-script', 'ycp_overview', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ycp_overview_nonce')
        ]);
    }
    
    /**
     * Handle AJAX request for overview data
     */
    public function handle_get_overview_data(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ycp_overview_nonce')) {
            wp_send_json_error(['message' => 'Security check failed'], 403);
        }
        
        try {
            global $ycp_data_handler;
            if (!$ycp_data_handler) {
                $ycp_data_handler = new YCP_Data_Handler();
            }
            
            $data = $ycp_data_handler->get_all_professionals_availability(100);
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Render overview shortcode
     */
    public function render_overview_shortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'view' => 'calendar', // calendar, list, chart, summary
            'date_range' => 30,
            'show_today' => 'true',
            'show_weekends' => 'true',
            'highlight_today' => 'true',
            'auto_refresh' => 'false',
            'refresh_interval' => 300000,
            'class' => '',
            'id' => ''
        ], $atts, 'ycp_availability_overview');
        
        $container_id = !empty($atts['id']) ? $atts['id'] : 'ycp-overview-' . uniqid();
        $container_class = 'ycp-availability-overview';
        if (!empty($atts['class'])) {
            $container_class .= ' ' . esc_attr($atts['class']);
        }
        
        $data_attrs = [
            'data-ycp-overview' => 'true',
            'data-ycp-view' => esc_attr($atts['view']),
            'data-ycp-date-range' => absint($atts['date_range']),
            'data-ycp-show-today' => $atts['show_today'],
            'data-ycp-show-weekends' => $atts['show_weekends'],
            'data-ycp-highlight-today' => $atts['highlight_today'],
            'data-ycp-auto-refresh' => $atts['auto_refresh'],
            'data-ycp-refresh-interval' => absint($atts['refresh_interval'])
        ];
        
        $data_attr_string = '';
        foreach ($data_attrs as $key => $value) {
            $data_attr_string .= ' ' . $key . '="' . $value . '"';
        }
        
        return '<div id="' . esc_attr($container_id) . '" class="' . $container_class . '"' . $data_attr_string . '></div>';
    }
    
    /**
     * Render calendar shortcode
     */
    public function render_calendar_shortcode(array $atts = []): string {
        $atts['view'] = 'calendar';
        return $this->render_overview_shortcode($atts);
    }
    
    /**
     * Render summary shortcode
     */
    public function render_summary_shortcode(array $atts = []): string {
        $atts['view'] = 'summary';
        return $this->render_overview_shortcode($atts);
    }
    
    /**
     * Render chart shortcode
     */
    public function render_chart_shortcode(array $atts = []): string {
        $atts['view'] = 'chart';
        return $this->render_overview_shortcode($atts);
    }
    
    /**
     * Render list shortcode
     */
    public function render_list_shortcode(array $atts = []): string {
        $atts['view'] = 'list';
        return $this->render_overview_shortcode($atts);
    }
    
    /**
     * Render overview widget for admin dashboard
     */
    public function render_admin_overview_widget(): void {
        try {
            global $ycp_data_handler;
            if (!$ycp_data_handler) {
                $ycp_data_handler = new YCP_Data_Handler();
            }
            
            $all_data = $ycp_data_handler->get_all_professionals_availability(50);
            $today_data = $ycp_data_handler->get_availability_by_date();
            
            echo '<div class="ycp-admin-overview-widget">';
            echo '<h3>Availability Overview</h3>';
            
            // Summary cards
            echo '<div class="overview-summary-cards">';
            echo '<div class="summary-card">';
            echo '<div class="card-number">' . esc_html($all_data['count']) . '</div>';
            echo '<div class="card-label">Total Professionals</div>';
            echo '</div>';
            
            echo '<div class="summary-card">';
            echo '<div class="card-number">' . esc_html($today_data['count']) . '</div>';
            echo '<div class="card-label">Available Today</div>';
            echo '</div>';
            
            $total_days = 0;
            foreach ($all_data['professionals'] as $professional) {
                $total_days += $professional['total_available_days'];
            }
            $avg_days = $all_data['count'] > 0 ? round($total_days / $all_data['count']) : 0;
            
            echo '<div class="summary-card">';
            echo '<div class="card-number">' . esc_html($avg_days) . '</div>';
            echo '<div class="card-label">Avg. Days Available</div>';
            echo '</div>';
            echo '</div>';
            
            // Top professionals
            if (!empty($all_data['professionals'])) {
                echo '<h4>Most Available Professionals</h4>';
                echo '<ul class="top-professionals-list">';
                $top_professionals = array_slice($all_data['professionals'], 0, 5);
                foreach ($top_professionals as $professional) {
                    echo '<li>';
                    echo '<span class="professional-name">' . esc_html($professional['name']) . '</span>';
                    echo '<span class="available-days">' . esc_html($professional['total_available_days']) . ' days</span>';
                    echo '</li>';
                }
                echo '</ul>';
            }
            
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<p class="error">Unable to load overview data.</p>';
            error_log('Admin overview error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render overview for theme integration
     */
    public function render_theme_overview(array $options = []): string {
        $defaults = [
            'view' => 'summary',
            'show_today_only' => false,
            'limit' => 10,
            'template' => 'default'
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        try {
            global $ycp_data_handler;
            if (!$ycp_data_handler) {
                $ycp_data_handler = new YCP_Data_Handler();
            }
            
            ob_start();
            
            if ($options['show_today_only']) {
                $data = $ycp_data_handler->get_availability_by_date('', $options['limit']);
                $this->render_today_overview($data, $options['template']);
            } else {
                $data = $ycp_data_handler->get_all_professionals_availability($options['limit']);
                $this->render_full_overview($data, $options['view'], $options['template']);
            }
            
            return ob_get_clean();
            
        } catch (Exception $e) {
            error_log('Theme overview error: ' . $e->getMessage());
            return '<p class="ycp-error">Unable to load availability overview.</p>';
        }
    }
    
    /**
     * Render today's overview
     */
    private function render_today_overview(array $data, string $template): void {
        if ($template === 'minimal') {
            echo '<div class="ycp-today-minimal">';
            echo '<span class="count">' . esc_html($data['count']) . '</span>';
            echo '<span class="label">available today</span>';
            echo '</div>';
        } else {
            echo '<div class="ycp-today-overview">';
            echo '<h3>Available Today (' . esc_html($data['count']) . ')</h3>';
            if ($data['count'] > 0) {
                echo '<ul class="today-professionals">';
                foreach ($data['available_professionals'] as $professional) {
                    echo '<li>' . esc_html($professional['name']) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No professionals available today.</p>';
            }
            echo '</div>';
        }
    }
    
    /**
     * Render full overview
     */
    private function render_full_overview(array $data, string $view, string $template): void {
        switch ($view) {
            case 'summary':
                $this->render_summary_view($data, $template);
                break;
            case 'list':
                $this->render_list_view($data, $template);
                break;
            case 'grid':
                $this->render_grid_view($data, $template);
                break;
            default:
                $this->render_summary_view($data, $template);
        }
    }
    
    /**
     * Render summary view
     */
    private function render_summary_view(array $data, string $template): void {
        $total_professionals = $data['count'];
        $available_today = 0;
        $total_days = 0;
        
        foreach ($data['professionals'] as $professional) {
            if ($professional['is_available_today']) {
                $available_today++;
            }
            $total_days += $professional['total_available_days'];
        }
        
        $avg_days = $total_professionals > 0 ? round($total_days / $total_professionals) : 0;
        
        echo '<div class="ycp-summary-view">';
        echo '<div class="summary-stats">';
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . esc_html($total_professionals) . '</div>';
        echo '<div class="stat-label">Total Professionals</div>';
        echo '</div>';
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . esc_html($available_today) . '</div>';
        echo '<div class="stat-label">Available Today</div>';
        echo '</div>';
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . esc_html($avg_days) . '</div>';
        echo '<div class="stat-label">Avg. Days Available</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render list view
     */
    private function render_list_view(array $data, string $template): void {
        echo '<div class="ycp-list-view">';
        echo '<h3>All Professionals</h3>';
        echo '<ul class="professionals-list">';
        foreach ($data['professionals'] as $professional) {
            echo '<li class="professional-item">';
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
    
    /**
     * Render grid view
     */
    private function render_grid_view(array $data, string $template): void {
        echo '<div class="ycp-grid-view">';
        echo '<h3>Professional Availability</h3>';
        echo '<div class="professionals-grid">';
        foreach ($data['professionals'] as $professional) {
            echo '<div class="professional-card">';
            echo '<h4>' . esc_html($professional['name']) . '</h4>';
            echo '<div class="availability-info">';
            echo '<span class="days-count">' . esc_html($professional['total_available_days']) . ' days available</span>';
            if ($professional['is_available_today']) {
                echo '<span class="today-indicator">Available Today</span>';
            }
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Get overview data for external use
     */
    public function get_overview_data(array $options = []): array {
        try {
            global $ycp_data_handler;
            if (!$ycp_data_handler) {
                $ycp_data_handler = new YCP_Data_Handler();
            }
            
            $all_data = $ycp_data_handler->get_all_professionals_availability($options['limit'] ?? 100);
            $today_data = $ycp_data_handler->get_availability_by_date();
            
            $total_days = 0;
            $available_today = 0;
            
            foreach ($all_data['professionals'] as $professional) {
                $total_days += $professional['total_available_days'];
                if ($professional['is_available_today']) {
                    $available_today++;
                }
            }
            
            return [
                'total_professionals' => $all_data['count'],
                'available_today' => $today_data['count'],
                'total_available_days' => $total_days,
                'average_days_per_professional' => $all_data['count'] > 0 ? round($total_days / $all_data['count']) : 0,
                'professionals' => $all_data['professionals'],
                'today_professionals' => $today_data['available_professionals']
            ];
            
        } catch (Exception $e) {
            error_log('Overview data error: ' . $e->getMessage());
            return [];
        }
    }
}

// Initialize the overview UI
global $ycp_overview_ui;
$ycp_overview_ui = new YCP_Overview_UI();

// Register admin dashboard widget
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'ycp_availability_overview_widget',
        'Availability Overview',
        [$ycp_overview_ui, 'render_admin_overview_widget']
    );
});

// Register global functions for theme integration
if (!function_exists('ycp_render_availability_overview')) {
    function ycp_render_availability_overview(array $options = []) {
        global $ycp_overview_ui;
        if (!$ycp_overview_ui) {
            $ycp_overview_ui = new YCP_Overview_UI();
        }
        return $ycp_overview_ui->render_theme_overview($options);
    }
}

if (!function_exists('ycp_get_overview_data')) {
    function ycp_get_overview_data(array $options = []) {
        global $ycp_overview_ui;
        if (!$ycp_overview_ui) {
            $ycp_overview_ui = new YCP_Overview_UI();
        }
        return $ycp_overview_ui->get_overview_data($options);
    }
} 