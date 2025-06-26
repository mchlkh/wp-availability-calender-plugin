/**
 * Availability Calendar Plugin - Overview UI Components
 * 
 * Provides UI components for displaying availability overviews
 * 
 * @package AvailabilityCalendarPlugin
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Availability Overview Class
     */
    class AvailabilityOverview {
        constructor(containerSelector, options = {}) {
            this.container = $(containerSelector);
            this.options = {
                view: 'calendar', // calendar, list, chart, summary
                dateRange: 30, // days to show
                showToday: true,
                showWeekends: true,
                highlightToday: true,
                autoRefresh: false,
                refreshInterval: 300000, // 5 minutes
                ...options
            };
            
            this.currentDate = new Date();
            this.availabilityData = {};
            this.init();
        }

        init() {
            if (this.container.length === 0) {
                console.error('Container not found:', this.container.selector);
                return;
            }

            this.loadData();
            
            if (this.options.autoRefresh) {
                setInterval(() => this.loadData(), this.options.refreshInterval);
            }
        }

        async loadData() {
            try {
                this.container.html('<div class="ycp-loading">Loading availability data...</div>');
                
                // Get all professionals availability
                const response = await this.fetchAllAvailability();
                this.availabilityData = response;
                
                this.render();
            } catch (error) {
                this.container.html(`<div class="ycp-error">Error loading data: ${error.message}</div>`);
                console.error('Availability overview error:', error);
            }
        }

        async fetchAllAvailability() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    url: ycp_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'ycp_get_availability_data',
                        nonce: ycp_ajax.nonce,
                        show_all: true,
                        limit: 100
                    },
                    success: function(response) {
                        if (response.success) {
                            resolve(response.data);
                        } else {
                            reject(new Error(response.data.message || 'Failed to load data'));
                        }
                    },
                    error: function(xhr, status, error) {
                        reject(new Error('AJAX request failed: ' + error));
                    }
                });
            });
        }

        render() {
            switch (this.options.view) {
                case 'calendar':
                    this.renderCalendar();
                    break;
                case 'list':
                    this.renderList();
                    break;
                case 'chart':
                    this.renderChart();
                    break;
                case 'summary':
                    this.renderSummary();
                    break;
                default:
                    this.renderCalendar();
            }
        }

        renderCalendar() {
            const calendar = this.generateCalendarHTML();
            this.container.html(calendar);
            this.attachCalendarEvents();
        }

        generateCalendarHTML() {
            const today = new Date();
            const startDate = new Date(today);
            startDate.setDate(today.getDate() - Math.floor(this.options.dateRange / 2));
            
            let html = '<div class="ycp-availability-calendar">';
            html += '<div class="calendar-header">';
            html += '<h3>Availability Overview</h3>';
            html += '<div class="calendar-controls">';
            html += '<button class="prev-month">&lt;</button>';
            html += '<span class="current-month">' + this.formatMonthYear(startDate) + '</span>';
            html += '<button class="next-month">&gt;</button>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="calendar-grid">';
            html += '<div class="calendar-weekdays">';
            const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            weekdays.forEach(day => {
                html += '<div class="weekday">' + day + '</div>';
            });
            html += '</div>';
            
            html += '<div class="calendar-days">';
            
            // Generate calendar days
            const currentDate = new Date(startDate);
            for (let i = 0; i < this.options.dateRange; i++) {
                const dateStr = this.formatDate(currentDate);
                const isToday = this.isSameDate(currentDate, today);
                const isWeekend = currentDate.getDay() === 0 || currentDate.getDay() === 6;
                const availabilityCount = this.getAvailabilityCount(dateStr);
                
                let dayClass = 'calendar-day';
                if (isToday && this.options.highlightToday) dayClass += ' today';
                if (isWeekend && !this.options.showWeekends) dayClass += ' hidden';
                if (availabilityCount > 0) dayClass += ' has-availability';
                
                html += '<div class="' + dayClass + '" data-date="' + dateStr + '">';
                html += '<div class="day-number">' + currentDate.getDate() + '</div>';
                if (availabilityCount > 0) {
                    html += '<div class="availability-count">' + availabilityCount + '</div>';
                }
                html += '</div>';
                
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            html += '</div>';
            html += '</div>';
            
            html += '<div class="calendar-legend">';
            html += '<div class="legend-item"><span class="legend-color available"></span> Available</div>';
            html += '<div class="legend-item"><span class="legend-color today"></span> Today</div>';
            html += '</div>';
            
            html += '</div>';
            
            return html;
        }

        renderList() {
            let html = '<div class="ycp-availability-list">';
            html += '<h3>Availability List</h3>';
            
            const sortedDates = this.getSortedAvailabilityDates();
            
            if (sortedDates.length > 0) {
                html += '<div class="availability-dates">';
                sortedDates.forEach(dateInfo => {
                    html += '<div class="date-item">';
                    html += '<div class="date-header">';
                    html += '<span class="date">' + this.formatDisplayDate(dateInfo.date) + '</span>';
                    html += '<span class="count">' + dateInfo.count + ' professionals</span>';
                    html += '</div>';
                    
                    if (dateInfo.professionals.length > 0) {
                        html += '<div class="professionals-list">';
                        dateInfo.professionals.forEach(professional => {
                            html += '<div class="professional-item">';
                            html += '<span class="name">' + this.escapeHtml(professional.name) + '</span>';
                            if (professional.description) {
                                html += '<span class="description">' + this.escapeHtml(professional.description) + '</span>';
                            }
                            html += '</div>';
                        });
                        html += '</div>';
                    }
                    
                    html += '</div>';
                });
                html += '</div>';
            } else {
                html += '<p>No availability data found.</p>';
            }
            
            html += '</div>';
            this.container.html(html);
        }

        renderChart() {
            let html = '<div class="ycp-availability-chart">';
            html += '<h3>Availability Chart</h3>';
            
            const chartData = this.generateChartData();
            
            html += '<div class="chart-container">';
            html += '<canvas id="availability-chart" width="400" height="200"></canvas>';
            html += '</div>';
            
            html += '<div class="chart-stats">';
            html += '<div class="stat-item">';
            html += '<span class="stat-label">Total Days:</span>';
            html += '<span class="stat-value">' + chartData.totalDays + '</span>';
            html += '</div>';
            html += '<div class="stat-item">';
            html += '<span class="stat-label">Available Days:</span>';
            html += '<span class="stat-value">' + chartData.availableDays + '</span>';
            html += '</div>';
            html += '<div class="stat-item">';
            html += '<span class="stat-label">Availability Rate:</span>';
            html += '<span class="stat-value">' + chartData.availabilityRate + '%</span>';
            html += '</div>';
            html += '</div>';
            
            html += '</div>';
            this.container.html(html);
            
            // Initialize chart if Chart.js is available
            if (typeof Chart !== 'undefined') {
                this.initChart(chartData);
            }
        }

        renderSummary() {
            const summary = this.generateSummaryData();
            
            let html = '<div class="ycp-availability-summary">';
            html += '<h3>Availability Summary</h3>';
            
            html += '<div class="summary-grid">';
            html += '<div class="summary-card">';
            html += '<div class="summary-number">' + summary.totalProfessionals + '</div>';
            html += '<div class="summary-label">Total Professionals</div>';
            html += '</div>';
            
            html += '<div class="summary-card">';
            html += '<div class="summary-number">' + summary.availableToday + '</div>';
            html += '<div class="summary-label">Available Today</div>';
            html += '</div>';
            
            html += '<div class="summary-card">';
            html += '<div class="summary-number">' + summary.totalAvailableDays + '</div>';
            html += '<div class="summary-label">Total Available Days</div>';
            html += '</div>';
            
            html += '<div class="summary-card">';
            html += '<div class="summary-number">' + summary.averageAvailability + '</div>';
            html += '<div class="summary-label">Avg. Days per Professional</div>';
            html += '</div>';
            html += '</div>';
            
            html += '<div class="summary-details">';
            html += '<h4>Top Available Professionals</h4>';
            html += '<ul class="top-professionals">';
            summary.topProfessionals.forEach(professional => {
                html += '<li>';
                html += '<span class="name">' + this.escapeHtml(professional.name) + '</span>';
                html += '<span class="days">' + professional.total_available_days + ' days</span>';
                html += '</li>';
            });
            html += '</ul>';
            html += '</div>';
            
            html += '</div>';
            this.container.html(html);
        }

        generateChartData() {
            const totalDays = this.options.dateRange;
            let availableDays = 0;
            
            const currentDate = new Date();
            currentDate.setDate(currentDate.getDate() - Math.floor(this.options.dateRange / 2));
            
            for (let i = 0; i < totalDays; i++) {
                const dateStr = this.formatDate(currentDate);
                if (this.getAvailabilityCount(dateStr) > 0) {
                    availableDays++;
                }
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            return {
                totalDays: totalDays,
                availableDays: availableDays,
                availabilityRate: Math.round((availableDays / totalDays) * 100)
            };
        }

        generateSummaryData() {
            const professionals = this.availabilityData.professionals || [];
            const totalProfessionals = professionals.length;
            let availableToday = 0;
            let totalAvailableDays = 0;
            
            professionals.forEach(professional => {
                if (professional.is_available_today) {
                    availableToday++;
                }
                totalAvailableDays += professional.total_available_days;
            });
            
            const averageAvailability = totalProfessionals > 0 ? 
                Math.round(totalAvailableDays / totalProfessionals) : 0;
            
            // Get top 5 most available professionals
            const topProfessionals = professionals
                .sort((a, b) => b.total_available_days - a.total_available_days)
                .slice(0, 5);
            
            return {
                totalProfessionals,
                availableToday,
                totalAvailableDays,
                averageAvailability,
                topProfessionals
            };
        }

        getSortedAvailabilityDates() {
            const dateMap = {};
            
            if (this.availabilityData.professionals) {
                this.availabilityData.professionals.forEach(professional => {
                    if (professional.available_dates) {
                        professional.available_dates.forEach(date => {
                            if (!dateMap[date]) {
                                dateMap[date] = {
                                    date: date,
                                    count: 0,
                                    professionals: []
                                };
                            }
                            dateMap[date].count++;
                            dateMap[date].professionals.push(professional);
                        });
                    }
                });
            }
            
            return Object.values(dateMap).sort((a, b) => new Date(a.date) - new Date(b.date));
        }

        getAvailabilityCount(dateStr) {
            let count = 0;
            if (this.availabilityData.professionals) {
                this.availabilityData.professionals.forEach(professional => {
                    if (professional.available_dates && professional.available_dates.includes(dateStr)) {
                        count++;
                    }
                });
            }
            return count;
        }

        attachCalendarEvents() {
            this.container.find('.calendar-day').on('click', (e) => {
                const date = $(e.currentTarget).data('date');
                this.showDateDetails(date);
            });
            
            this.container.find('.prev-month').on('click', () => {
                this.navigateMonth(-1);
            });
            
            this.container.find('.next-month').on('click', () => {
                this.navigateMonth(1);
            });
        }

        showDateDetails(date) {
            const professionals = this.getProfessionalsForDate(date);
            
            let html = '<div class="date-details-modal">';
            html += '<div class="modal-content">';
            html += '<div class="modal-header">';
            html += '<h4>Available on ' + this.formatDisplayDate(date) + '</h4>';
            html += '<button class="close-modal">&times;</button>';
            html += '</div>';
            html += '<div class="modal-body">';
            
            if (professionals.length > 0) {
                html += '<ul class="professionals-list">';
                professionals.forEach(professional => {
                    html += '<li class="professional-item">';
                    html += '<h5>' + this.escapeHtml(professional.name) + '</h5>';
                    if (professional.description) {
                        html += '<p>' + this.escapeHtml(professional.description) + '</p>';
                    }
                    if (professional.profile_url) {
                        html += '<a href="' + this.escapeHtml(professional.profile_url) + '" target="_blank">View Profile</a>';
                    }
                    html += '</li>';
                });
                html += '</ul>';
            } else {
                html += '<p>No professionals available on this date.</p>';
            }
            
            html += '</div>';
            html += '</div>';
            html += '</div>';
            
            // Create modal
            const modal = $(html);
            $('body').append(modal);
            
            modal.find('.close-modal, .date-details-modal').on('click', (e) => {
                if (e.target === e.currentTarget) {
                    modal.remove();
                }
            });
        }

        getProfessionalsForDate(date) {
            const professionals = [];
            if (this.availabilityData.professionals) {
                this.availabilityData.professionals.forEach(professional => {
                    if (professional.available_dates && professional.available_dates.includes(date)) {
                        professionals.push(professional);
                    }
                });
            }
            return professionals;
        }

        navigateMonth(direction) {
            // Implementation for month navigation
            console.log('Navigate month:', direction);
        }

        formatDate(date) {
            return date.getFullYear() + '-' + 
                   String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(date.getDate()).padStart(2, '0');
        }

        formatDisplayDate(dateStr) {
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }

        formatMonthYear(date) {
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long' 
            });
        }

        isSameDate(date1, date2) {
            return date1.getFullYear() === date2.getFullYear() &&
                   date1.getMonth() === date2.getMonth() &&
                   date1.getDate() === date2.getDate();
        }

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        initChart(chartData) {
            const ctx = document.getElementById('availability-chart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Not Available'],
                    datasets: [{
                        data: [chartData.availableDays, chartData.totalDays - chartData.availableDays],
                        backgroundColor: ['#4CAF50', '#f0f0f0'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    }

    // Expose the class globally
    window.AvailabilityOverview = AvailabilityOverview;

    // Initialize when document is ready
    $(document).ready(function() {
        // Auto-initialize overviews with data attributes
        $('[data-ycp-overview]').each(function() {
            const $element = $(this);
            const options = {
                view: $element.data('ycp-view') || 'calendar',
                dateRange: parseInt($element.data('ycp-date-range')) || 30,
                showToday: $element.data('ycp-show-today') !== 'false',
                showWeekends: $element.data('ycp-show-weekends') !== 'false',
                highlightToday: $element.data('ycp-highlight-today') !== 'false',
                autoRefresh: $element.data('ycp-auto-refresh') === 'true',
                refreshInterval: parseInt($element.data('ycp-refresh-interval')) || 300000
            };
            
            new AvailabilityOverview($element.selector, options);
        });
    });

})(jQuery); 