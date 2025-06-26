/**
 * Availability Calendar Plugin - API JavaScript
 * 
 * Provides easy-to-use functions for accessing availability data
 * 
 * @package AvailabilityCalendarPlugin
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // Store the nonce for AJAX requests
    let ycpNonce = '';

    /**
     * Initialize the availability API
     */
    function initAvailabilityAPI() {
        // Get nonce from WordPress
        if (typeof ycp_ajax !== 'undefined' && ycp_ajax.nonce) {
            ycpNonce = ycp_ajax.nonce;
        }
    }

    /**
     * Get availability data for a specific professional
     * 
     * @param {number} professionalId - The professional's post ID
     * @param {string} dateFrom - Optional start date (Y-m-d format)
     * @param {string} dateTo - Optional end date (Y-m-d format)
     * @returns {Promise} Promise that resolves with availability data
     */
    function getProfessionalAvailability(professionalId, dateFrom = '', dateTo = '') {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ycp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ycp_get_availability_data',
                    nonce: ycpNonce,
                    professional_id: professionalId,
                    date_from: dateFrom,
                    date_to: dateTo
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data.message || 'Failed to get availability data'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error('AJAX request failed: ' + error));
                }
            });
        });
    }

    /**
     * Get availability data for a specific date
     * 
     * @param {string} date - Date in Y-m-d format (defaults to today)
     * @param {number} limit - Maximum number of professionals to return
     * @returns {Promise} Promise that resolves with availability data
     */
    function getAvailabilityByDate(date = '', limit = 50) {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: ycp_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ycp_get_availability_data',
                    nonce: ycpNonce,
                    date: date,
                    limit: limit
                },
                success: function(response) {
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.data.message || 'Failed to get availability data'));
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error('AJAX request failed: ' + error));
                }
            });
        });
    }

    /**
     * Get availability data via REST API
     * 
     * @param {string} endpoint - REST API endpoint
     * @param {Object} params - Query parameters
     * @returns {Promise} Promise that resolves with availability data
     */
    function getAvailabilityViaREST(endpoint, params = {}) {
        return new Promise((resolve, reject) => {
            const url = new URL(ycp_ajax.rest_url + '/ycp/v1/' + endpoint);
            
            // Add parameters to URL
            Object.keys(params).forEach(key => {
                if (params[key] !== undefined && params[key] !== '') {
                    url.searchParams.append(key, params[key]);
                }
            });

            fetch(url.toString())
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP error! status: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => resolve(data))
                .catch(error => reject(error));
        });
    }

    /**
     * Display availability data in a container
     * 
     * @param {Object} data - Availability data
     * @param {string} containerSelector - CSS selector for container
     * @param {string} template - Template type (list, grid, calendar)
     */
    function displayAvailabilityData(data, containerSelector, template = 'list') {
        const container = $(containerSelector);
        if (container.length === 0) {
            console.error('Container not found:', containerSelector);
            return;
        }

        let html = '';

        if (data.professionals) {
            // All professionals data
            html = renderAllProfessionalsTemplate(data, template);
        } else if (data.available_professionals) {
            // Date-specific data
            html = renderDateAvailabilityTemplate(data, template);
        } else if (data.available_dates) {
            // Single professional data
            html = renderProfessionalTemplate(data, template);
        }

        container.html(html);
    }

    /**
     * Render template for all professionals
     */
    function renderAllProfessionalsTemplate(data, template) {
        let html = `<div class="ycp-availability-data all-professionals">
            <h3>All Professionals (${data.count})</h3>`;
        
        if (template === 'grid') {
            html += '<div class="ycp-grid">';
        }

        data.professionals.forEach(professional => {
            if (template === 'grid') {
                html += `<div class="ycp-professional-card">
                    <h4>${escapeHtml(professional.name)}</h4>
                    <p>Available Days: ${professional.total_available_days}</p>
                    <p>Available Today: ${professional.is_available_today ? 'Yes' : 'No'}</p>
                </div>`;
            } else {
                html += `<div class="ycp-professional-item">
                    <h4>${escapeHtml(professional.name)}</h4>
                    <p>Available Days: ${professional.total_available_days}</p>
                    <p>Available Today: ${professional.is_available_today ? 'Yes' : 'No'}</p>
                </div>`;
            }
        });

        if (template === 'grid') {
            html += '</div>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Render template for date availability
     */
    function renderDateAvailabilityTemplate(data, template) {
        const date = new Date(data.date);
        const formattedDate = date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });

        let html = `<div class="ycp-availability-data date-${data.date}">
            <h3>Available on ${formattedDate} (${data.count})</h3>`;
        
        if (data.available_professionals.length > 0) {
            if (template === 'grid') {
                html += '<div class="ycp-grid">';
            }

            data.available_professionals.forEach(professional => {
                if (template === 'grid') {
                    html += `<div class="ycp-professional-card">
                        <h4>${escapeHtml(professional.name)}</h4>
                        ${professional.description ? `<p>${escapeHtml(professional.description)}</p>` : ''}
                    </div>`;
                } else {
                    html += `<div class="ycp-professional-item">
                        <h4>${escapeHtml(professional.name)}</h4>
                        ${professional.description ? `<p>${escapeHtml(professional.description)}</p>` : ''}
                    </div>`;
                }
            });

            if (template === 'grid') {
                html += '</div>';
            }
        } else {
            html += '<p>No professionals available on this date.</p>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Render template for single professional
     */
    function renderProfessionalTemplate(data, template) {
        let html = `<div class="ycp-availability-data professional-${data.id}">
            <h3>${escapeHtml(data.name)}</h3>
            <div class="ycp-availability-info">
                <p><strong>Available Days:</strong> ${data.total_available_days}</p>
                <p><strong>Available Today:</strong> ${data.is_available_today ? 'Yes' : 'No'}</p>
                ${data.description ? `<p><strong>Description:</strong> ${escapeHtml(data.description)}</p>` : ''}
            </div>`;

        if (data.available_dates && data.available_dates.length > 0) {
            html += '<div class="ycp-available-dates"><h4>Available Dates:</h4><ul>';
            data.available_dates.forEach(date => {
                const dateObj = new Date(date);
                const formattedDate = dateObj.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                html += `<li>${formattedDate}</li>`;
            });
            html += '</ul></div>';
        }

        html += '</div>';
        return html;
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Create a simple availability widget
     * 
     * @param {string} containerSelector - CSS selector for container
     * @param {Object} options - Widget options
     */
    function createAvailabilityWidget(containerSelector, options = {}) {
        const defaults = {
            date: '',
            limit: 10,
            template: 'list',
            autoRefresh: false,
            refreshInterval: 300000 // 5 minutes
        };

        const settings = { ...defaults, ...options };
        const container = $(containerSelector);

        if (container.length === 0) {
            console.error('Container not found:', containerSelector);
            return;
        }

        // Add loading state
        container.html('<div class="ycp-loading">Loading availability data...</div>');

        // Load initial data
        loadWidgetData();

        // Set up auto-refresh if enabled
        if (settings.autoRefresh) {
            setInterval(loadWidgetData, settings.refreshInterval);
        }

        function loadWidgetData() {
            getAvailabilityByDate(settings.date, settings.limit)
                .then(data => {
                    displayAvailabilityData(data, containerSelector, settings.template);
                })
                .catch(error => {
                    container.html(`<div class="ycp-error">Error loading availability data: ${error.message}</div>`);
                });
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        initAvailabilityAPI();
    });

    // Expose functions globally
    window.YCPAvailabilityAPI = {
        getProfessionalAvailability,
        getAvailabilityByDate,
        getAvailabilityViaREST,
        displayAvailabilityData,
        createAvailabilityWidget
    };

})(jQuery); 