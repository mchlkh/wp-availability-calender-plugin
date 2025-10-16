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
     * Render weekly-by-floor table view
     *
     * Shortcode: [ycp_weekly_floor floor_id="123" weeks="1"]
     * - floor_id: required, references `ycp_floors.id`
     * - weeks: number of consecutive weeks starting from current week (site timezone), default 1
     */
    public function render_weekly_floor_shortcode(array $atts = []): string {
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style('ycp-style');
        }
        if (function_exists('wp_enqueue_script')) {
            // Reuse existing frontend script; safe on pages without calendar
            wp_enqueue_script('ycp-script');
        }

        $atts = shortcode_atts([
            'floor_id' => 0,
            'floor' => 0,
            'weeks' => 1,
        ], $atts, 'ycp_weekly_floor');

        $floor_id = absint($atts['floor_id'] ? $atts['floor_id'] : $atts['floor']);
        $weeks = max(1, (int) $atts['weeks']);

        if ($floor_id <= 0) {
            return '<p class="ycp-error">' . esc_html__('Bitte eine gültige Stock-ID angeben.', self::TEXT_DOMAIN) . '</p>';
        }

        global $wpdb;
        $floors_table = $wpdb->prefix . 'ycp_floors';
        $floor_row = $wpdb->get_row($wpdb->prepare("SELECT name, url FROM $floors_table WHERE id = %d", $floor_id), ARRAY_A);
        if (!$floor_row) {
            return '<p class="ycp-error">' . esc_html__('Stock nicht gefunden.', self::TEXT_DOMAIN) . '</p>';
        }
        $floor_name = isset($floor_row['name']) ? (string) $floor_row['name'] : '';
        $floor_url = isset($floor_row['url']) ? (string) $floor_row['url'] : '';

        // Resolve current week based on site timezone
        $tz = function_exists('wp_timezone') ? wp_timezone() : new \DateTimeZone(wp_timezone_string());
        $now = new \DateTime('now', $tz);
        $weekStart = (clone $now)->modify('monday this week')->setTime(0, 0, 0);

        // Determine initial visible weeks: show up to 3 if more are requested, otherwise all
        $initial_visible = ($weeks > 3) ? 3 : $weeks;

        // Unique wrapper per instance to support multiple shortcodes on same page
        $instance_id = 'ycp-weekly-floor-' . wp_generate_uuid4();

        $output = '<div id="' . esc_attr($instance_id) . '" class="ycp-weekly-floor-wrapper" data-total-weeks="' . (int) $weeks . '" data-initial-visible="' . (int) $initial_visible . '">';

        for ($w = 0; $w < $weeks; $w++) {
            $start = (clone $weekStart)->modify("+{$w} week");
            $end = (clone $start)->modify('+6 days');

			// Determine if this loop iteration is the current week and capture today's DOW (1..7)
			$is_current_week = ($now >= $start && $now <= $end);
			$today_dow = (int) $now->format('N');

            // Query availability for this floor and date range
            $availability_table = $wpdb->prefix . 'ycp_availability';
            $professionals_table = $wpdb->prefix . 'ycp_professionals';
			$sql = $wpdb->prepare(
				"SELECT a.date, a.professional_id, a.room, p.name, p.display_order, p.profile_url
				 FROM $availability_table a
				 INNER JOIN $professionals_table p ON p.id = a.professional_id
				 WHERE a.floor = %s AND a.date BETWEEN %s AND %s
				 ORDER BY p.display_order ASC, p.name ASC",
				$floor_name,
				$start->format('Y-m-d'),
				$end->format('Y-m-d')
			);
            $rows = $wpdb->get_results($sql, ARRAY_A) ?: [];

            // Build rows grouped by professional
            $byPro = [];
            foreach ($rows as $row) {
                $proId = (int) $row['professional_id'];
				if (!isset($byPro[$proId])) {
					$byPro[$proId] = [
						'name' => (string) $row['name'],
						'url' => isset($row['profile_url']) ? (string) $row['profile_url'] : '',
						// 1..7 => bool
						'days' => [1=>false,2=>false,3=>false,4=>false,5=>false,6=>false,7=>false],
					];
				}
                $date = (string) $row['date'];
                $dow = (int) (new \DateTime($date, $tz))->format('N');
                $byPro[$proId]['days'][$dow] = true; // do not dedupe count; dot is presence
            }

            // Table header and caption
            $header = sprintf(
                /* translators: 1: floor name, 2: start date (d.m.), 3: end date (d.m.Y) */
                esc_html__('Anwesend im %1$s in der Woche vom %2$s bis %3$s', self::TEXT_DOMAIN),
                esc_html($floor_name),
                esc_html(wp_date('d.m.', $start->getTimestamp(), $tz)),
                esc_html(wp_date('d.m.Y', $end->getTimestamp(), $tz))
            );

            // Wrap each week's table in a block so we can show/hide in chunks of 3
            $week_classes = 'ycp-week-block' . ($w >= $initial_visible ? ' ycp-week-hidden' : '');
            $output .= '<div class="' . $week_classes . '">';
            $output .= '<div class="ycp-weekly-floor">';
            $output .= '<div class="ycp-calendar-header"><h3>' . $header . '</h3></div>';
            $output .= '<table class="ycp-weekly-floor-table">';
            $output .= '<thead><tr>'
                . '<th>' . esc_html__('Crew', self::TEXT_DOMAIN) . '</th>'
                . '<th>' . esc_html__('Mo', self::TEXT_DOMAIN) . '</th>'
                . '<th>' . esc_html__('Di', self::TEXT_DOMAIN) . '</th>'
                . '<th>' . esc_html__('Mi', self::TEXT_DOMAIN) . '</th>'
                . '<th>' . esc_html__('Do', self::TEXT_DOMAIN) . '</th>'
                . '<th>' . esc_html__('Fr', self::TEXT_DOMAIN) . '</th>'
                . '<th>' . esc_html__('Sa', self::TEXT_DOMAIN) . '</th>'
                . '<th>' . esc_html__('So', self::TEXT_DOMAIN) . '</th>'
                . '</tr></thead>';

            $output .= '<tbody>';
            if (empty($byPro)) {
                $output .= '<tr><td colspan="8" class="ycp-empty">' . esc_html__('Keine Einträge für diese Woche.', self::TEXT_DOMAIN) . '</td></tr>';
            } else {
				foreach ($byPro as $pro) {
					$output .= '<tr>';
					$nameHtml = esc_html($pro['name']);
					if (!empty($pro['url'])) {
						$url = esc_url((string) $pro['url']);
						$nameHtml = '<a href="' . $url . '">' . $nameHtml . '</a>';
					}
					$output .= '<td class="ycp-col-name">' . $nameHtml . '</td>';
					for ($d = 1; $d <= 7; $d++) {
						if ($pro['days'][$d]) {
							$dotClass = 'ycp-dot';
							if ($is_current_week && $d === $today_dow) {
								$dotClass .= ' ycp-dot-today';
							}
							$output .= '<td class="ycp-col-day"><span class="' . $dotClass . '" aria-hidden="true"></span></td>';
						} else {
							$output .= '<td class="ycp-col-day"></td>';
						}
					}
					$output .= '</tr>';
				}
            }
            $output .= '</tbody></table></div></div>';
        }
        // Add Show More button if there are hidden weeks
        if ($weeks > $initial_visible) {
            $output .= '<div class="ycp-weekly-show-more">'
                . '<button type="button" class="ycp-show-all-button" aria-expanded="false">' . esc_html__('MEHR ANZEIGEN', self::TEXT_DOMAIN) . '</button>'
                . '</div>';
        }

        $output .= '</div>';

        return $output;
    }

    // Alias renderer removed; only ycp_weekly_floor is supported
    
    /**
     * Render calendar shortcode
     */
    public function render_calendar_shortcode(array $atts = []): string {
        // Enqueue assets needed for calendar display only when shortcode is used
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style('flatpickr-style');
            wp_enqueue_style('ycp-style');
        }
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script('flatpickr');
            wp_enqueue_script('ycp-availability-api');
            wp_enqueue_script('ycp-script');
        }
        $options = get_option('ycp_color_options', []);
        $container_class = $this->get_container_class($options);

        // Shortcode attributes to control Royal Crew preview behavior
        $atts = shortcode_atts([
            'crew_preview' => 'false', // 'true' to enable preview limiting for the crew section
            'crew_limit' => 4,         // number of cards to show initially
            'crew_button_text' => __('MEHR ANZEIGEN', self::TEXT_DOMAIN)
        ], $atts, 'ycp_calendar');
        
        $crew_preview = $atts['crew_preview'] === 'true' ? 'true' : 'false';
        $crew_limit = (int) $atts['crew_limit'];
        $crew_button_text = esc_attr($atts['crew_button_text']);
        
        ob_start();
        // Attach data attributes so the frontend JS can pick up config
        echo '<div id="ycp-calendar-container" ' . $container_class . ' data-crew-preview="' . $crew_preview . '" data-crew-limit="' . $crew_limit . '" data-crew-button-text="' . $crew_button_text . '">';
        include plugin_dir_path(dirname(__FILE__)) . 'public/frontend-display.php';
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render today simple shortcode
     */
    public function render_today_simple_shortcode(array $atts = []): string {
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style('ycp-style');
        }
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
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style('ycp-style');
        }
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
        $room = isset($professional['room']) ? trim((string) $professional['room']) : '';
        $floor = isset($professional['floor']) ? trim((string) $professional['floor']) : '';
        // Show location bar only if a floor is provided. If only room is provided or both empty, hide it.
        $hasLocation = ($floor !== '');

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

        // Close outer link BEFORE rendering location bar to avoid nested anchors
        if (!empty($professional['url'])) {
            $output .= "</a>";
        }

        // Sticky bottom bar inside the card container, but outside the hover overlay
        $locationText = '';
        if ($hasLocation) {
            $floorUrl = isset($professional['floor_url']) ? esc_url($professional['floor_url']) : '';
            $floorHtml = $floorUrl ? '<a href="' . $floorUrl . '">' . esc_html($floor) . '</a>' : esc_html($floor);
            if ($room !== '') {
                $locationText = sprintf(__('Am ausgewählten Tag im %s, %s', self::TEXT_DOMAIN), $floorHtml, esc_html($room));
            } else {
                // Room empty: show only floor without comma
                $locationText = sprintf(__('Am ausgewählten Tag im %s', self::TEXT_DOMAIN), $floorHtml);
            }
            if ($locationText !== '') {
                $output .= '<div class="ycp-location-bar">' . $locationText . '</div>';
            }
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
        
        // Show date ranges - each in its own div for proper spacing
        $output .= '<div class="ycp-date-ranges">';
        foreach ($data['available_dates_formatted'] as $date_range) {
            $output .= '<div class="ycp-date-range-item">' . esc_html($date_range) . '</div>';
        }
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