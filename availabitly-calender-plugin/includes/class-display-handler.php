<?php
/**
 * Display Handler Class
 * 
 * Handles all HTML rendering and display logic for the availability calendar
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
 * Class YCP_Display_Handler
 * 
 * Manages HTML rendering and display logic for the availability calendar
 */
class YCP_Display_Handler {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'availability-calendar-plugin';
    
    /**
     * Professional manager instance
     */
    private $professional_manager;
    
    /**
     * Data handler instance
     */
    private $data_handler;
    
    /**
     * Initialize the display handler
     */
    public function __construct(YCP_Professional_Manager $professional_manager, YCP_Data_Handler $data_handler) {
        $this->professional_manager = $professional_manager;
        $this->data_handler = $data_handler;
    }
    
    /**
     * Render calendar shortcode
     */
    public function render_calendar_shortcode(): string {
        $options = get_option('ycp_color_options', []);
        $container_class = $this->get_container_class($options);
        
        ob_start();
        echo '<div id="ycp-calendar-container" ' . $container_class . '>';
        include plugin_dir_path(dirname(__FILE__)) . 'public/frontend-display.php';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render today simple shortcode
     */
    public function render_today_simple_shortcode(array $atts = []): string {
        $options = get_option('ycp_color_options', []);
        $container_class = $this->get_container_class($options);
        
        ob_start();
        echo '<div id="ycp-simple-results" ' . $container_class . '>';
        
        $today = date('Y-m-d');
        $professionals = $this->professional_manager->get_professionals_for_date($today);
        
        echo '<div class="ycp-pro-list">';
        
        if (!empty($professionals)) {
            foreach ($professionals as $professional) {
                echo $this->render_professional_card($professional);
            }
        } else {
            echo '<p>' . esc_html__('No professionals available today.', self::TEXT_DOMAIN) . '</p>';
        }
        
        echo '</div>';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render availability data shortcode
     */
    public function render_availability_data_shortcode(array $atts = []): string {
        $atts = shortcode_atts([
            'professional_id' => 0,
            'date_from' => '',
            'date_to' => '',
            'show_title' => 'true',
            'show_count' => 'false',
            'separator' => '<br>',
            'no_dates_text' => __('No availability dates found.', self::TEXT_DOMAIN),
            'css_class' => 'ycp-availability-compact'
        ], $atts, 'ycp_availability_data');
        
        if (empty($atts['professional_id'])) {
            return '<p class="ycp-error">' . esc_html__('Professional ID is required.', self::TEXT_DOMAIN) . '</p>';
        }
        
        try {
            $data = $this->data_handler->get_professional_availability_compact(
                (int) $atts['professional_id'],
                $atts['date_from'],
                $atts['date_to'],
                true
            );
            
            if (empty($data['available_dates_formatted'])) {
                return '<p class="' . esc_attr($atts['css_class']) . '">' . esc_html($atts['no_dates_text']) . '</p>';
            }
            
            return $this->render_availability_data_output($data, $atts);
            
        } catch (Exception $e) {
            return '<p class="ycp-error">' . sprintf(
                esc_html__('Error loading availability data: %s', self::TEXT_DOMAIN), 
                esc_html($e->getMessage())
            ) . '</p>';
        }
    }
    
    /**
     * Render professionals list for AJAX response
     */
    public function render_professionals_list(array $professionals): string {
        $output = '<div class="ycp-pro-list">';
        
        if (!empty($professionals)) {
            foreach ($professionals as $professional) {
                $output .= $this->render_professional_card($professional);
            }
        }
        
        $output .= '</div>';
        return $output;
    }
    
    /**
     * Render availability data attributes for AJAX response
     */
    public function render_availability_data_attributes(bool $found_professionals, string $date): string {
        return sprintf(
            '<div class="ycp-availability-data" data-available="%s" data-selected-date="%s"></div>',
            $found_professionals ? 'true' : 'false',
            esc_attr($date)
        );
    }
    
    /**
     * Render a professional card
     */
    public function render_professional_card(array $professional): string {
        $output = '<div class="ycp-pro">';
        
        // Check if URL exists and is not empty
        if (!empty($professional['url'])) {
            $url = esc_url($professional['url']);
            $output .= "<a href='{$url}'>";
        }
        
        $output .= "<div class='image-container'>";
        $output .= $professional['image'];
        $output .= "<div class='text-overlay-bg'></div>";
        $output .= "<div class='text-overlay'>";
        $output .= "<h4>" . esc_html($professional['name']) . "</h4>";
        
        // Add "Heute anwesend" banner if person is available today
        if ($professional['is_available_today']) {
            $output .= "<div class='ycp-heute-banner'>" . esc_html__('Heute anwesend', self::TEXT_DOMAIN) . "</div>";
        }
        
        // Add description if it exists
        if (!empty($professional['description'])) {
            $output .= "<p class='description'>" . esc_html($professional['description']) . "</p>";
        }
        
        $output .= "</div>"; // Close text-overlay
        $output .= "</div>"; // Close image-container
        
        if (!empty($professional['url'])) {
            $output .= "</a>";
        }
        
        $output .= '</div>'; // Close ycp-pro
        
        return $output;
    }
    
    /**
     * Get container class based on color options
     */
    private function get_container_class(array $options): string {
        if (empty($options)) {
            return '';
        }
        
        if (isset($options['ycp_use_theme_colors']) && $options['ycp_use_theme_colors']) {
            return 'class="ycp-theme-color-sync"';
        }
        
        return 'class="ycp-custom-colors"';
    }
    
    /**
     * Render availability data output
     */
    private function render_availability_data_output(array $data, array $atts): string {
        $output = '<div class="' . esc_attr($atts['css_class']) . '">';
        
        // Show professional title if requested
        if ($atts['show_title'] === 'true' && !empty($data['professional_name'])) {
            $output .= '<h4 class="ycp-professional-title">' . esc_html($data['professional_name']) . '</h4>';
        }
        
        // Show date ranges
        $output .= '<div class="ycp-date-ranges">';
        $output .= implode($atts['separator'], array_map('esc_html', $data['available_dates_formatted']));
        $output .= '</div>';
        
        // Show count if requested
        if ($atts['show_count'] === 'true') {
            $output .= $this->render_date_count($data);
        }
        
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render date count information
     */
    private function render_date_count(array $data): string {
        $total_dates = count($data['available_dates']);
        $total_ranges = count($data['available_dates_formatted']);
        
        return sprintf(
            '<div class="ycp-date-count"><small>%d %s in %d %s</small></div>',
            $total_dates,
            _n('date', 'dates', $total_dates, self::TEXT_DOMAIN),
            $total_ranges,
            _n('range', 'ranges', $total_ranges, self::TEXT_DOMAIN)
        );
    }
    
    /**
     * Render professional meta box fields
     */
    public function render_meta_box_fields(string $profile_url, string $available_dates, string $description): void {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="ycp_profile_url">
                        <?php esc_html_e('Profile Page URL:', self::TEXT_DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input 
                        type="url" 
                        id="ycp_profile_url" 
                        name="ycp_profile_url" 
                        value="<?php echo esc_attr($profile_url); ?>" 
                        class="regular-text" 
                        placeholder="https://example.com/profile"
                    />
                    <p class="description">
                        <?php esc_html_e('The URL where visitors can learn more about this professional.', self::TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="ycp_available_dates">
                        <?php esc_html_e('Available Dates:', self::TEXT_DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <input 
                        type="text" 
                        id="ycp_available_dates" 
                        name="ycp_available_dates" 
                        value="<?php echo esc_attr($available_dates); ?>" 
                        class="regular-text" 
                        placeholder="<?php esc_attr_e('Select dates...', self::TEXT_DOMAIN); ?>"
                        readonly
                    />
                    <p class="description">
                        <?php esc_html_e('Hold CTRL (or CMD) to select multiple dates.', self::TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="ycp_description">
                        <?php esc_html_e('Description:', self::TEXT_DOMAIN); ?>
                    </label>
                </th>
                <td>
                    <textarea 
                        id="ycp_description" 
                        name="ycp_description" 
                        rows="3" 
                        class="large-text"
                        placeholder="<?php esc_attr_e('Brief description of the professional...', self::TEXT_DOMAIN); ?>"
                    ><?php echo esc_textarea($description); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('A brief description that will be displayed on the calendar.', self::TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }
} 