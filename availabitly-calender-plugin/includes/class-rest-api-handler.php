<?php
/**
 * Availability Calendar Plugin - REST API Handler
 * 
 * Handles all REST API endpoints for the availability calendar
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
 * Class YCP_REST_API_Handler
 * 
 * Manages REST API endpoints for availability data
 */
class YCP_REST_API_Handler {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'availability-calendar-plugin';
    
    /**
     * REST API namespace
     */
    const API_NAMESPACE = 'ycp/v1';
    
    /**
     * Data handler instance
     */
    private $data_handler;
    
    /**
     * Initialize the REST API handler
     */
    public function __construct(YCP_Data_Handler $data_handler) {
        $this->data_handler = $data_handler;
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void {
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }
    
    /**
     * Register REST API routes
     */
    public function register_rest_routes(): void {
        // Get availability for a specific professional
        register_rest_route(self::API_NAMESPACE, '/availability/(?P<professional_id>\d+)', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_availability'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'professional_id' => [
                        'required' => true,
                        'validate_callback' => [$this, 'validate_professional_id'],
                        'sanitize_callback' => 'absint'
                    ],
                    'date_from' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_date'],
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'date_to' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_date'],
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // Get all availability data
        register_rest_route(self::API_NAMESPACE, '/availability', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_all_availability'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'date' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_date'],
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'limit' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_limit'],
                        'sanitize_callback' => 'absint',
                        'default' => 100
                    ],
                    'professional_id' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_professional_id'],
                        'sanitize_callback' => 'absint'
                    ],
                    'date_from' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_date'],
                        'sanitize_callback' => 'sanitize_text_field'
                    ],
                    'date_to' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_date'],
                        'sanitize_callback' => 'sanitize_text_field'
                    ]
                ]
            ]
        ]);
        
        // Get professionals list
        register_rest_route(self::API_NAMESPACE, '/professionals', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_professionals'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'limit' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_limit'],
                        'sanitize_callback' => 'absint',
                        'default' => 100
                    ],
                    'available_today' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_boolean'],
                        'sanitize_callback' => 'rest_sanitize_boolean',
                        'default' => false
                    ]
                ]
            ]
        ]);
        
        // Get overview data
        register_rest_route(self::API_NAMESPACE, '/overview', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'get_overview'],
                'permission_callback' => [$this, 'check_read_permission'],
                'args' => [
                    'limit' => [
                        'required' => false,
                        'validate_callback' => [$this, 'validate_limit'],
                        'sanitize_callback' => 'absint',
                        'default' => 100
                    ]
                ]
            ]
        ]);
    }
    
    /**
     * Check read permission for REST API
     */
    public function check_read_permission(): bool {
        return true; // Public read access
    }
    
    /**
     * Validate professional ID
     */
    public function validate_professional_id($param, $request, $key): bool {
        $professional_id = absint($param);
        if ($professional_id <= 0) {
            return false;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'ycp_professionals';
        $exists = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(1) FROM $table WHERE id = %d", $professional_id));
        return $exists > 0;
    }
    
    /**
     * Validate date parameter
     */
    public function validate_date($param, $request, $key): bool {
        if (empty($param)) {
            return true; // Empty date is valid (uses current date)
        }
        
        $date = DateTime::createFromFormat('Y-m-d', $param);
        return $date !== false && $date->format('Y-m-d') === $param;
    }
    
    /**
     * Validate limit parameter
     */
    public function validate_limit($param, $request, $key): bool {
        $limit = absint($param);
        return $limit > 0 && $limit <= 1000; // Max 1000 records
    }
    
    /**
     * Validate boolean parameter
     */
    public function validate_boolean($param, $request, $key): bool {
        return is_bool($param) || in_array($param, ['true', 'false', '1', '0', 1, 0], true);
    }
    
    /**
     * Get availability for a specific professional
     */
    public function get_availability(WP_REST_Request $request): WP_REST_Response {
        try {
            $professional_id = $request->get_param('professional_id');
            $date_from = (string) $request->get_param('date_from');
            $date_to = (string) $request->get_param('date_to');
            
            $data = $this->data_handler->get_professional_availability((int) $professional_id, $date_from, $date_to);
            
            return new WP_REST_Response($data, 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'error' => $e->getMessage(),
                'code' => 'availability_error'
            ], 500);
        }
    }
    
    /**
     * Get all availability data
     */
    public function get_all_availability(WP_REST_Request $request): WP_REST_Response {
        try {
            $date = (string) $request->get_param('date');
            $limit = (int) $request->get_param('limit');
            $professional_id = (int) $request->get_param('professional_id');
            $date_from = (string) $request->get_param('date_from');
            $date_to = (string) $request->get_param('date_to');
            
            if ($professional_id > 0) {
                $data = $this->data_handler->get_professional_availability($professional_id, $date_from, $date_to);
            } else if (!empty($date)) {
                $data = $this->data_handler->get_availability_by_date($date, $limit ?: 100);
            } else {
                $data = $this->data_handler->get_all_professionals_availability($limit ?: 100);
            }
            
            return new WP_REST_Response($data, 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'error' => $e->getMessage(),
                'code' => 'availability_error'
            ], 500);
        }
    }
    
    /**
     * Get professionals list
     */
    public function get_professionals(WP_REST_Request $request): WP_REST_Response {
        try {
            $limit = $request->get_param('limit');
            $available_today = $request->get_param('available_today');
            
            if ($available_today) {
                $data = $this->data_handler->get_availability_by_date('', $limit);
                $professionals = $data['available_professionals'] ?? [];
            } else {
                $data = $this->data_handler->get_all_professionals_availability($limit);
                $professionals = $data['professionals'] ?? [];
            }
            
            return new WP_REST_Response([
                'professionals' => $professionals,
                'count' => count($professionals)
            ], 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'error' => $e->getMessage(),
                'code' => 'professionals_error'
            ], 500);
        }
    }
    
    /**
     * Get overview data
     */
    public function get_overview(WP_REST_Request $request): WP_REST_Response {
        try {
            $limit = $request->get_param('limit');
            
            // Get overview data using the overview UI class
            global $ycp_overview_ui;
            if (!$ycp_overview_ui) {
                $ycp_overview_ui = new YCP_Overview_UI();
            }
            
            $data = $ycp_overview_ui->get_overview_data(['limit' => $limit]);
            
            return new WP_REST_Response($data, 200);
        } catch (Exception $e) {
            return new WP_REST_Response([
                'error' => $e->getMessage(),
                'code' => 'overview_error'
            ], 500);
        }
    }
    
    /**
     * Get API documentation
     */
    public function get_api_documentation(): array {
        return [
            'namespace' => self::API_NAMESPACE,
            'endpoints' => [
                'GET /availability/{professional_id}' => [
                    'description' => __('Get availability for a specific professional', self::TEXT_DOMAIN),
                    'parameters' => [
                        'professional_id' => __('Professional ID (required)', self::TEXT_DOMAIN),
                        'date' => __('Date in Y-m-d format (optional)', self::TEXT_DOMAIN),
                        'limit' => __('Maximum number of records (optional, default: 100)', self::TEXT_DOMAIN)
                    ]
                ],
                'GET /availability' => [
                    'description' => __('Get all availability data', self::TEXT_DOMAIN),
                    'parameters' => [
                        'date' => __('Date in Y-m-d format (optional)', self::TEXT_DOMAIN),
                        'limit' => __('Maximum number of records (optional, default: 100)', self::TEXT_DOMAIN),
                        'professional_id' => __('Filter by professional ID (optional)', self::TEXT_DOMAIN)
                    ]
                ],
                'GET /professionals' => [
                    'description' => __('Get list of professionals', self::TEXT_DOMAIN),
                    'parameters' => [
                        'limit' => __('Maximum number of records (optional, default: 100)', self::TEXT_DOMAIN),
                        'available_today' => __('Filter by availability today (optional, default: false)', self::TEXT_DOMAIN)
                    ]
                ],
                'GET /overview' => [
                    'description' => __('Get overview statistics', self::TEXT_DOMAIN),
                    'parameters' => [
                        'limit' => __('Maximum number of records (optional, default: 100)', self::TEXT_DOMAIN)
                    ]
                ]
            ],
            'authentication' => __('No authentication required for read access', self::TEXT_DOMAIN),
            'rate_limiting' => __('Standard WordPress REST API rate limiting applies', self::TEXT_DOMAIN)
        ];
    }
    
    /**
     * Get API base URL
     */
    public function get_api_base_url(): string {
        return rest_url(self::API_NAMESPACE);
    }
    
    /**
     * Get API endpoint URL
     */
    public function get_endpoint_url(string $endpoint): string {
        return rest_url(self::API_NAMESPACE . '/' . ltrim($endpoint, '/'));
    }
} 