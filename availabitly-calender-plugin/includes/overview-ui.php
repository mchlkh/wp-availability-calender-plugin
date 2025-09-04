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
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'availability-calendar-plugin';
    
    /**
     * Nonce action for AJAX requests
     */
    const NONCE_ACTION = 'ycp_overview_nonce';
    
    /**
     * Data handler instance
     */
    private $data_handler;
    
    /**
     * Initialize the overview UI
     */
    public function __construct() {
        $this->init_data_handler();
        $this->init_hooks();
    }
    
    /**
     * Initialize data handler
     */
    private function init_data_handler(): void {
        global $ycp_data_handler;
        if (!$ycp_data_handler) {
            $ycp_data_handler = new YCP_Data_Handler();
        }
        $this->data_handler = $ycp_data_handler;
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
        
        // Assets will be enqueued only when shortcodes render
        
        // Add AJAX handlers
        add_action('wp_ajax_ycp_get_overview_data', [$this, 'handle_get_overview_data']);
        add_action('wp_ajax_nopriv_ycp_get_overview_data', [$this, 'handle_get_overview_data']);
        
        // Register admin dashboard widget
        add_action('wp_dashboard_setup', [$this, 'register_admin_widget']);
    }
    
    /**
     * Register admin dashboard widget
     */
    public function register_admin_widget(): void {
        wp_add_dashboard_widget(
            'ycp_availability_overview_widget',
            __('Availability Overview', self::TEXT_DOMAIN),
            [$this, 'render_admin_overview_widget']
        );
    }
    
    /**
     * Enqueue overview assets
     */
    public function enqueue_overview_assets(): void {
        wp_enqueue_style(
            'ycp-overview-style', 
            plugin_dir_url(dirname(__FILE__)) . 'public/assets/availability-overview.css', 
            [], 
            '1.0.0'
        );
        
        wp_enqueue_script(
            'ycp-overview-script', 
            plugin_dir_url(dirname(__FILE__)) . 'public/assets/availability-overview.js', 
            ['jquery'], 
            '1.0.0', 
            true
        );
        
        // Add Chart.js for chart view
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        
        // Localize script
        wp_localize_script('ycp-overview-script', 'ycp_overview', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION)
        ]);
    }
    
    /**
     * Handle AJAX request for overview data
     */
    public function handle_get_overview_data(): void {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            wp_send_json_error(['message' => __('Security check failed', self::TEXT_DOMAIN)], 403);
        }
        
        try {
            $data = $this->data_handler->get_all_professionals_availability(100);
            wp_send_json_success($data);
        } catch (Exception $e) {
            wp_send_json_error(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Render overview shortcode
     */
    public function render_overview_shortcode(array $atts = []): string {
        // Enqueue front-end assets only when shortcode is used
        $this->enqueue_overview_assets();
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
        
        $data_attrs = $this->build_data_attributes($atts);
        
        return sprintf(
            '<div id="%s" class="%s"%s></div>',
            esc_attr($container_id),
            esc_attr($container_class),
            $data_attrs
        );
    }
    
    /**
     * Build data attributes string for shortcode
     */
    private function build_data_attributes(array $atts): string {
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
        
        return $data_attr_string;
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
            $overview_data = $this->get_overview_data(['limit' => 50]);
            
            if (empty($overview_data)) {
                echo '<p class="error">' . __('Unable to load overview data.', self::TEXT_DOMAIN) . '</p>';
                return;
            }
            
            $this->render_admin_widget_content($overview_data);
            
        } catch (Exception $e) {
            echo '<p class="error">' . __('Unable to load overview data.', self::TEXT_DOMAIN) . '</p>';
            error_log('Admin overview error: ' . $e->getMessage());
        }
    }
    
    /**
     * Render admin widget content
     */
    private function render_admin_widget_content(array $data): void {
        echo '<div class="ycp-admin-overview-widget">';
        echo '<h3>' . __('Availability Overview', self::TEXT_DOMAIN) . '</h3>';
        
        // Summary cards
        echo '<div class="overview-summary-cards">';
        $this->render_summary_card((int) $data['total_professionals'], __('Total Professionals', self::TEXT_DOMAIN));
        $this->render_summary_card((int) $data['available_today'], __('Available Today', self::TEXT_DOMAIN));
        $this->render_summary_card((int) $data['average_days_per_professional'], __('Avg. Days Available', self::TEXT_DOMAIN));
        echo '</div>';
        
        // Top professionals
        if (!empty($data['professionals'])) {
            $this->render_top_professionals($data['professionals']);
        }
        
        echo '</div>';
    }
    
    /**
     * Render a summary card
     */
    private function render_summary_card(int $number, string $label): void {
        echo '<div class="summary-card">';
        echo '<div class="card-number">' . esc_html($number) . '</div>';
        echo '<div class="card-label">' . esc_html($label) . '</div>';
        echo '</div>';
    }
    
    /**
     * Render top professionals list
     */
    private function render_top_professionals(array $professionals): void {
        echo '<h4>' . __('Most Available Professionals', self::TEXT_DOMAIN) . '</h4>';
        echo '<ul class="top-professionals-list">';
        
        $top_professionals = array_slice($professionals, 0, 5);
        foreach ($top_professionals as $professional) {
            echo '<li>';
            echo '<span class="professional-name">' . esc_html($professional['name']) . '</span>';
            echo '<span class="available-days">' . esc_html($professional['total_available_days']) . ' ' . __('days', self::TEXT_DOMAIN) . '</span>';
            echo '</li>';
        }
        
        echo '</ul>';
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
            if ($options['show_today_only']) {
                $data = $this->data_handler->get_availability_by_date('', $options['limit']);
                return $this->render_today_overview_html($data, $options['template']);
            } else {
                $data = $this->data_handler->get_all_professionals_availability($options['limit']);
                return $this->render_full_overview_html($data, $options['view'], $options['template']);
            }
            
        } catch (Exception $e) {
            error_log('Theme overview error: ' . $e->getMessage());
            return '<p class="ycp-error">' . __('Unable to load availability overview.', self::TEXT_DOMAIN) . '</p>';
        }
    }
    
    /**
     * Render today's overview HTML
     */
    private function render_today_overview_html(array $data, string $template): string {
        ob_start();
        
        if ($template === 'minimal') {
            echo '<div class="ycp-today-minimal">';
            echo '<span class="count">' . esc_html($data['count']) . '</span>';
            echo '<span class="label">' . __('available today', self::TEXT_DOMAIN) . '</span>';
            echo '</div>';
        } else {
            echo '<div class="ycp-today-overview">';
            echo '<h3>' . sprintf(__('Available Today (%d)', self::TEXT_DOMAIN), $data['count']) . '</h3>';
            
            if ($data['count'] > 0) {
                echo '<ul class="today-professionals">';
                foreach ($data['available_professionals'] as $professional) {
                    echo '<li>' . esc_html($professional['name']) . '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>' . __('No professionals available today.', self::TEXT_DOMAIN) . '</p>';
            }
            echo '</div>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render full overview HTML
     */
    private function render_full_overview_html(array $data, string $view, string $template): string {
        ob_start();
        
        switch ($view) {
            case 'summary':
                $this->render_summary_view_html($data, $template);
                break;
            case 'list':
                $this->render_list_view_html($data, $template);
                break;
            case 'grid':
                $this->render_grid_view_html($data, $template);
                break;
            default:
                $this->render_summary_view_html($data, $template);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render summary view HTML
     */
    private function render_summary_view_html(array $data, string $template): void {
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
        $this->render_stat_item($total_professionals, __('Total Professionals', self::TEXT_DOMAIN));
        $this->render_stat_item($available_today, __('Available Today', self::TEXT_DOMAIN));
        $this->render_stat_item($avg_days, __('Avg. Days Available', self::TEXT_DOMAIN));
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Render a stat item
     */
    private function render_stat_item(int $number, string $label): void {
        echo '<div class="stat-item">';
        echo '<div class="stat-number">' . esc_html($number) . '</div>';
        echo '<div class="stat-label">' . esc_html($label) . '</div>';
        echo '</div>';
    }
    
    /**
     * Render list view HTML
     */
    private function render_list_view_html(array $data, string $template): void {
        echo '<div class="ycp-list-view">';
        echo '<h3>' . __('All Professionals', self::TEXT_DOMAIN) . '</h3>';
        echo '<ul class="professionals-list">';
        
        foreach ($data['professionals'] as $professional) {
            echo '<li class="professional-item">';
            echo '<span class="name">' . esc_html($professional['name']) . '</span>';
            echo '<span class="days">' . esc_html($professional['total_available_days']) . ' ' . __('days', self::TEXT_DOMAIN) . '</span>';
            if ($professional['is_available_today']) {
                echo '<span class="today-badge">' . __('Today', self::TEXT_DOMAIN) . '</span>';
            }
            echo '</li>';
        }
        
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Render grid view HTML
     */
    private function render_grid_view_html(array $data, string $template): void {
        echo '<div class="ycp-grid-view">';
        echo '<h3>' . __('Professional Availability', self::TEXT_DOMAIN) . '</h3>';
        echo '<div class="professionals-grid">';
        
        foreach ($data['professionals'] as $professional) {
            echo '<div class="professional-card">';
            echo '<h4>' . esc_html($professional['name']) . '</h4>';
            echo '<div class="availability-info">';
            echo '<span class="days-count">' . esc_html($professional['total_available_days']) . ' ' . __('days available', self::TEXT_DOMAIN) . '</span>';
            if ($professional['is_available_today']) {
                echo '<span class="today-indicator">' . __('Available Today', self::TEXT_DOMAIN) . '</span>';
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
            $all_data = $this->data_handler->get_all_professionals_availability($options['limit'] ?? 100);
            $today_data = $this->data_handler->get_availability_by_date();
            
            return $this->calculate_overview_statistics($all_data, $today_data);
            
        } catch (Exception $e) {
            error_log('Overview data error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate overview statistics
     */
    private function calculate_overview_statistics(array $all_data, array $today_data): array {
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
    }
}

// Removed global initializers to avoid side effects at file load.

// Back-compat helper functions without globals
if (!function_exists('ycp_render_availability_overview')) {
    function ycp_render_availability_overview(array $options = []) {
        $ui = new YCP_Overview_UI();
        return $ui->render_theme_overview($options);
    }
}

if (!function_exists('ycp_get_overview_data')) {
    function ycp_get_overview_data(array $options = []) {
        $ui = new YCP_Overview_UI();
        return $ui->get_overview_data($options);
    }
}