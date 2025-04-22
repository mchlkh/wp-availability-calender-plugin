document.addEventListener('DOMContentLoaded', function () {
    // Import German locale for flatpickr
    const German = {
        weekdays: {
            shorthand: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"],
            longhand: [
                "Sonntag",
                "Montag",
                "Dienstag",
                "Mittwoch",
                "Donnerstag",
                "Freitag",
                "Samstag",
            ]
        },
        months: {
            shorthand: [
                "Jan",
                "Feb",
                "Mär",
                "Apr",
                "Mai",
                "Jun",
                "Jul",
                "Aug",
                "Sep",
                "Okt",
                "Nov",
                "Dez",
            ],
            longhand: [
                "Januar",
                "Februar",
                "März",
                "April",
                "Mai",
                "Juni",
                "Juli",
                "August",
                "September",
                "Oktober",
                "November",
                "Dezember",
            ]
        },
        firstDayOfWeek: 1, // Monday as first day
        weekAbbreviation: "KW",
        rangeSeparator: " bis ",
        scrollTitle: "Zum Ändern scrollen",
        toggleTitle: "Zum Umschalten klicken",
        time_24hr: true
    };
    
    // Add German locale to flatpickr globally
    flatpickr.localize(German);
    
    // Initialize calendar for settings page preview
    initSettingsPagePreview();
    
    // Initialize calendar for date selection in admin forms
    initDateSelector();
    
    // Initialize color pickers on settings page
    initColorPickers();
    
    /**
     * Initialize settings page preview calendar
     */
    function initSettingsPagePreview() {
        const previewContainer = document.querySelector('#admin-calendar-preview');
        if (!previewContainer) return;
        
        // Get settings values or use defaults
        const primaryColor = document.querySelector('input[name="ycp_calendar_primary_color"]')?.value || '#1E90FF';
        const textColor = document.querySelector('input[name="ycp_calendar_text_color"]')?.value || '#333333';
        const monthCountEl = document.querySelector('select[name="ycp_default_month_count"]');
        const monthCount = monthCountEl ? parseInt(monthCountEl.value) : 2;
        
        // Initialize preview calendar
        const previewCalendar = flatpickr(previewContainer, {
            inline: true,
            mode: 'multiple',
            showMonths: getResponsiveMonthCount(monthCount),
            static: true,
            monthSelectorType: 'static',
            locale: "de",
            prevArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>',
            nextArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>',
            onReady: function() {
                // Apply custom styles
                applyPreviewStyles(primaryColor, textColor);
                
                // Fix calendar appearance
                fixCalendarAppearance(previewCalendar);
                
                // Add today button if option is enabled
                const showTodayBtn = document.querySelector('input[name="ycp_show_today_button"]')?.checked || false;
                if (showTodayBtn) {
                    addTodayButton(previewCalendar);
                }
            }
        });
        
        // Update preview on settings change
        document.querySelectorAll('input[name="ycp_calendar_primary_color"], input[name="ycp_calendar_text_color"]').forEach(input => {
            input.addEventListener('change', function() {
                const newPrimaryColor = document.querySelector('input[name="ycp_calendar_primary_color"]').value;
                const newTextColor = document.querySelector('input[name="ycp_calendar_text_color"]').value;
                applyPreviewStyles(newPrimaryColor, newTextColor);
                
                // Re-add today button with new color
                const todayBtn = document.querySelector('.ycp-today-btn');
                if (todayBtn) {
                    todayBtn.style.backgroundColor = newPrimaryColor;
                }
            });
        });
        
        // Update month count on change
        const monthSelect = document.querySelector('select[name="ycp_default_month_count"]');
        if (monthSelect) {
            monthSelect.addEventListener('change', function() {
                const newMonthCount = parseInt(this.value);
                previewCalendar.set('showMonths', getResponsiveMonthCount(newMonthCount));
                previewCalendar.redraw();
                fixCalendarAppearance(previewCalendar);
            });
        }
        
        // Toggle today button
        const todayBtnCheckbox = document.querySelector('input[name="ycp_show_today_button"]');
        if (todayBtnCheckbox) {
            todayBtnCheckbox.addEventListener('change', function() {
                const existingBtn = document.querySelector('.ycp-today-btn');
                if (this.checked && !existingBtn) {
                    addTodayButton(previewCalendar);
                } else if (!this.checked && existingBtn) {
                    existingBtn.remove();
                }
            });
        }
        
        // Apply custom styles to the preview based on color settings
        function applyPreviewStyles(primaryColor, textColor) {
            // Remove existing style element if it exists
            const existingStyle = document.getElementById('ycp-preview-styles');
            if (existingStyle) {
                existingStyle.remove();
            }
            
            // Check if we should use advanced color settings
            const useThemeColors = document.getElementById('ycp_use_theme_colors')?.checked || false;
            
            // Get advanced color values if available
            const primaryColorAdvanced = document.getElementById('ycp_primary_color')?.value || primaryColor;
            const primaryDark = document.getElementById('ycp_primary_dark')?.value || '#0060C0';
            const primaryContrast = document.getElementById('ycp_primary_contrast')?.value || 'white';
            const secondaryColor = document.getElementById('ycp_secondary_color')?.value || '#9ECBFF';
            const secondaryLight = document.getElementById('ycp_secondary_light')?.value || '#E6F2FF';
            const accentColor = document.getElementById('ycp_accent_color')?.value || '#FF6B00';
            const accentContrast = document.getElementById('ycp_accent_contrast')?.value || 'white';
            
            // Use basic colors as fallback when advanced settings are not set or when using theme colors
            const effectivePrimaryColor = useThemeColors ? 'var(--ycp-primary-color)' : (primaryColorAdvanced || primaryColor);
            const effectivePrimaryDark = useThemeColors ? 'var(--ycp-primary-dark)' : primaryDark;
            const effectivePrimaryContrast = useThemeColors ? 'var(--ycp-primary-contrast)' : primaryContrast;
            const effectiveSecondaryLight = useThemeColors ? 'var(--ycp-secondary-light)' : secondaryLight;
            const effectiveSecondaryColor = useThemeColors ? 'var(--ycp-secondary-color)' : secondaryColor;
            const effectiveAccentColor = useThemeColors ? 'var(--ycp-accent-color)' : accentColor;
            const effectiveAccentContrast = useThemeColors ? 'var(--ycp-accent-contrast)' : accentContrast;
            
            // Create new style element with CSS variables approach
            const styleEl = document.createElement('style');
            styleEl.id = 'ycp-preview-styles';
            styleEl.textContent = `
                #admin-calendar-preview {
                    /* Define CSS variables for the preview */
                    --preview-primary-color: ${effectivePrimaryColor};
                    --preview-primary-dark: ${effectivePrimaryDark};
                    --preview-primary-contrast: ${effectivePrimaryContrast};
                    --preview-secondary-color: ${effectiveSecondaryColor};
                    --preview-secondary-light: ${effectiveSecondaryLight};
                    --preview-accent-color: ${effectiveAccentColor};
                    --preview-accent-contrast: ${effectiveAccentContrast};
                }
                
                .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange, 
                .flatpickr-day.selected.inRange, .flatpickr-day.startRange.inRange, .flatpickr-day.endRange.inRange, 
                .flatpickr-day.selected:focus, .flatpickr-day.startRange:focus, .flatpickr-day.endRange:focus, 
                .flatpickr-day.selected:hover, .flatpickr-day.startRange:hover, .flatpickr-day.endRange:hover, 
                .flatpickr-day.selected.prevMonthDay, .flatpickr-day.startRange.prevMonthDay, .flatpickr-day.endRange.prevMonthDay, 
                .flatpickr-day.selected.nextMonthDay, .flatpickr-day.startRange.nextMonthDay, .flatpickr-day.endRange.nextMonthDay {
                    background: var(--preview-primary-color);
                    border-color: var(--preview-primary-color);
                    color: var(--preview-primary-contrast);
                }
                .flatpickr-day.today {
                    border-color: var(--preview-primary-color);
                    color: var(--preview-primary-dark);
                    background-color: var(--preview-secondary-light);
                }
                .flatpickr-months .flatpickr-month {
                    color: ${textColor};
                }
                .today-highlight {
                    background-color: var(--preview-secondary-light) !important;
                }
                .flatpickr-day.today.selected {
                    background-color: var(--preview-accent-color) !important;
                    color: var(--preview-accent-contrast) !important;
                }
                .flatpickr-day:hover {
                    background-color: var(--preview-secondary-light);
                    border-color: var(--preview-secondary-color);
                }
            `;
            document.head.appendChild(styleEl);
        }
    }
    
    /**
     * Initialize date selector for admin forms
     */
    function initDateSelector() {
        const dateInput = document.querySelector('#ycp_available_dates');
        if (!dateInput) return;
        
        // Get today's date
        const today = new Date();
        const todayFormatted = today.getFullYear() + '-' + 
                              String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(today.getDate()).padStart(2, '0');
        
        // Initialize flatpickr
        const adminCalendar = flatpickr(dateInput, {
            mode: 'multiple',
            dateFormat: 'Y-m-d',
            showMonths: getResponsiveMonthCount(2), // Default to 2 months, but adjust for small screens
            static: true,
            monthSelectorType: 'static',
            locale: "de",
            defaultDate: todayFormatted, // Default to today
            prevArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>',
            nextArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>',
            onReady: function() {
                // Fix calendar appearance after initialization
                fixCalendarAppearance(adminCalendar);
                
                // If the field is empty, select today's date
                const currentValue = dateInput.value.trim();
                if (!currentValue) {
                    adminCalendar.setDate(today);
                    // Update the input value with today's date
                    dateInput.value = todayFormatted;
                    // Trigger a change event to ensure any listeners are notified
                    const changeEvent = new Event('change', { bubbles: true });
                    dateInput.dispatchEvent(changeEvent);
                }
                
                // Add a "Today" button to the calendar for easy navigation
                addTodayButton(adminCalendar);
            },
            onMonthChange: function() {
                // Re-apply fixes when month changes
                setTimeout(() => fixCalendarAppearance(adminCalendar), 50);
            },
            onChange: function(selectedDates, dateStr) {
                // Make sure the input value reflects the selection
                dateInput.value = dateStr;
            }
        });
        
        // Update calendar when window is resized
        window.addEventListener('resize', function() {
            // Re-apply appearance fixes
            fixCalendarAppearance(adminCalendar);
        });
    }
    
    /**
     * Initialize color pickers on settings page
     */
    function initColorPickers() {
        // Initialize WordPress color pickers if available
        if (jQuery && jQuery.fn.wpColorPicker) {
            // Initialize basic color pickers
            jQuery('input[type="color"]').wpColorPicker({
                change: function(event, ui) {
                    // Trigger change event for preview updates
                    setTimeout(() => {
                        this.value = ui.color.toString();
                        this.dispatchEvent(new Event('change', { bubbles: true }));
                    }, 100);
                }
            });
            
            // Initialize advanced color pickers
            jQuery('.ycp-color-picker').wpColorPicker({
                change: function(event, ui) {
                    // Update preview when advanced color changes
                    setTimeout(() => {
                        const primaryColor = document.querySelector('input[name="ycp_calendar_primary_color"]').value;
                        const textColor = document.querySelector('input[name="ycp_calendar_text_color"]').value;
                        applyPreviewStyles(primaryColor, textColor);
                        
                        // Update today button color if it exists
                        const todayBtn = document.querySelector('.ycp-today-btn, .preview-today-btn');
                        if (todayBtn) {
                            const primaryColorAdvanced = document.getElementById('ycp_primary_color')?.value || primaryColor;
                            const useThemeColors = document.getElementById('ycp_use_theme_colors')?.checked || false;
                            todayBtn.style.backgroundColor = useThemeColors ? 'var(--ycp-primary-color)' : primaryColorAdvanced;
                        }
                    }, 100);
                }
            });
            
            // Toggle advanced color settings based on "Use Theme Colors" checkbox
            const themeColorsCheckbox = document.getElementById('ycp_use_theme_colors');
            if (themeColorsCheckbox) {
                themeColorsCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        // Disable color pickers when using theme colors
                        jQuery('.ycp-color-picker').each(function() {
                            jQuery(this).wpColorPicker('disable');
                            jQuery(this).closest('.wp-picker-container').css('opacity', '0.5');
                        });
                    } else {
                        // Enable color pickers when using custom colors
                        jQuery('.ycp-color-picker').each(function() {
                            jQuery(this).wpColorPicker('enable');
                            jQuery(this).closest('.wp-picker-container').css('opacity', '1');
                        });
                    }
                    
                    // Update preview with current settings
                    const primaryColor = document.querySelector('input[name="ycp_calendar_primary_color"]').value;
                    const textColor = document.querySelector('input[name="ycp_calendar_text_color"]').value;
                    applyPreviewStyles(primaryColor, textColor);
                });
                
                // Set initial state
                if (themeColorsCheckbox.checked) {
                    jQuery('.ycp-color-picker').each(function() {
                        jQuery(this).wpColorPicker('disable');
                        jQuery(this).closest('.wp-picker-container').css('opacity', '0.5');
                    });
                }
            }
        }
    }
    
    /**
     * Add a "Today" button to a calendar
     */
    function addTodayButton(calendar) {
        // Ensure we don't add duplicates
        const existingBtn = document.querySelector('.ycp-today-btn');
        if (existingBtn) {
            return;
        }
        
        // Create the button container
        const btnContainer = document.createElement('div');
        btnContainer.className = 'ycp-today-btn-container';
        
        // Create the button
        const todayBtn = document.createElement('button');
        todayBtn.type = 'button';
        todayBtn.className = 'ycp-today-btn';
        todayBtn.textContent = 'Heute'; // Change to German "Today"
        
        // Use primary color or default color for button background
        const primaryColor = document.getElementById('ycp_primary_color')?.value || '#1E90FF';
        const primaryContrast = document.getElementById('ycp_primary_contrast')?.value || 'white';
        
        // Style the button
        todayBtn.style.cssText = `
            background-color: ${primaryColor};
            color: ${primaryContrast};
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 10px;
            cursor: pointer;
            font-weight: 500;
            transition: opacity 0.2s;
        `;
        
        // Add hover effect
        todayBtn.addEventListener('mouseover', function() {
            this.style.opacity = '0.9';
        });
        todayBtn.addEventListener('mouseout', function() {
            this.style.opacity = '1';
        });
        
        // Handle click - go to today's date
        todayBtn.addEventListener('click', function() {
            const today = new Date();
            calendar.setDate(today);
            calendar.jumpToDate(today);
            
            // Highlight today in the UI
            highlightTodayDate();
            
            // If this was called from a multi-select calendar, also update the input value
            const adminInput = document.getElementById('ycp_available_dates');
            if (adminInput && calendar.config.mode === 'multiple') {
                // Make sure we're not clearing other selected dates
                const currentDates = adminInput.value ? adminInput.value.split(',') : [];
                const todayFormatted = today.getFullYear() + '-' + 
                                      String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                                      String(today.getDate()).padStart(2, '0');
                                      
                if (!currentDates.includes(todayFormatted)) {
                    currentDates.push(todayFormatted);
                    adminInput.value = currentDates.join(',');
                    
                    // Trigger change event
                    const changeEvent = new Event('change', { bubbles: true });
                    adminInput.dispatchEvent(changeEvent);
                }
            }
        });
        
        // Add the button to the container
        btnContainer.appendChild(todayBtn);
        
        // Add the container after the calendar
        const calendarContainer = calendar.element.closest('.flatpickr-wrapper') || calendar.element.parentNode;
        calendarContainer.appendChild(btnContainer);
    }

    // Function to highlight today's date
    function highlightTodayDate() {
        const todayElement = document.querySelector('.flatpickr-day.today');
        if (todayElement) {
            todayElement.classList.add('today-highlight');
        }
    }
    
    // Function to get responsive month count based on screen width
    function getResponsiveMonthCount(defaultCount) {
        const width = window.innerWidth;
        if (width < 480) return 1;
        if (width < 768) return Math.min(defaultCount, 2);
        return defaultCount;
    }
    
    // Function to fix calendar appearance to match requirements
    function fixCalendarAppearance(calendar) {
        if (!calendar) return;
        
        // Check screen size for responsive adjustments
        const screenWidth = window.innerWidth;
        const isMobile = screenWidth < 480;
        const isSmallMobile = screenWidth < 360;
        const isTablet = screenWidth >= 480 && screenWidth < 768;
        
        // Update month count based on screen size
        const desiredMonthCount = getResponsiveMonthCount(
            parseInt(document.querySelector('select[name="ycp_default_month_count"]')?.value || 2)
        );
        
        if (calendar.config.showMonths !== desiredMonthCount) {
            calendar.set('showMonths', desiredMonthCount);
            calendar.redraw();
        }
        
        // Apply grid layout to dayContainer for even spacing
        const dayContainers = document.querySelectorAll('.dayContainer');
        dayContainers.forEach(container => {
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(7, 1fr)';
            container.style.gap = '0';
            container.style.justifyContent = 'space-between';
            container.style.padding = '0 4px';
        });
        
        // Apply grid layout to weekdays for even spacing
        const weekdayContainer = document.querySelector('.flatpickr-weekdaycontainer');
        if (weekdayContainer) {
            weekdayContainer.style.display = 'grid';
            weekdayContainer.style.gridTemplateColumns = 'repeat(7, 1fr)';
            weekdayContainer.style.width = '100%';
        }
        
        // Add proper spacing between days
        const flatpickrDays = document.querySelector('.flatpickr-days');
        if (flatpickrDays) {
            flatpickrDays.style.width = '100%';
            flatpickrDays.style.minWidth = 'auto';
            flatpickrDays.style.border = 'none';
        }
        
        // Fix last row spacing - ensure all days have consistent alignment
        const calendarDays = document.querySelectorAll('.flatpickr-day');
        calendarDays.forEach(day => {
            // Center all days horizontally
            day.style.margin = '0 auto';
            day.style.justifySelf = 'center';
        });
        
        // Remove scrollbars entirely
        const calendarElements = document.querySelectorAll('.flatpickr-calendar, .flatpickr-days, .dayContainer');
        calendarElements.forEach(el => {
            // Set overflow to visible for all screens to prevent scrollbars
            el.style.overflow = 'visible';
        });
        
        // Make sure multi-month display looks good
        if (screenWidth >= 480) {  // Show 2 months on tablet and desktop
            // Add some space between months in multi-month display
            const monthElements = document.querySelectorAll('.flatpickr-month');
            if (monthElements.length > 1) {
                const monthWrappers = document.querySelectorAll('.flatpickr-calendar > .flatpickr-months');
                monthWrappers.forEach(wrapper => {
                    wrapper.style.marginBottom = '15px';
                });
            }
        }
        
        // Set the width of the calendar to match the container
        const calendarElement = document.querySelector('.flatpickr-calendar');
        if (calendarElement) {
            calendarElement.style.width = '100%';
            calendarElement.style.maxWidth = '900px'; // Increased from 680px to give more space
            calendarElement.style.padding = isSmallMobile ? '2px' : '5px';
        }
        
        // Ensure the wrapper container is large enough for both months
        const calendarContainer = document.querySelector('.flatpickr-calendar').parentElement;
        if (calendarContainer) {
            calendarContainer.style.width = '100%';
            calendarContainer.style.maxWidth = '900px'; // Match the calendar width
            calendarContainer.style.overflow = 'visible';
        }
        
        // Apply appropriate sizing to the days based on screen size
        const days = document.querySelectorAll('.flatpickr-day');
        
        // Responsive sizing for different device types
        let fontSize, daySize;
        
        if (isSmallMobile) {
            fontSize = '13px';
            daySize = '32px';
        } else if (isMobile) {
            fontSize = '14px';
            daySize = '36px';
        } else if (isTablet) {
            fontSize = '15px';
            daySize = '42px';
        } else {
            fontSize = '16px';
            daySize = '44px';
        }
        
        days.forEach(day => {
            day.style.fontSize = fontSize;
            day.style.width = daySize;
            day.style.height = daySize;
            day.style.lineHeight = daySize;
            day.style.maxWidth = daySize;
        });
        
        // Make month and year text bigger but responsive to screen size
        const monthText = document.querySelector('.cur-month');
        const yearText = document.querySelector('.numInputWrapper');
        
        if (monthText) {
            monthText.style.fontSize = isSmallMobile ? '1em' : isMobile ? '1.1em' : '1.2em';
            monthText.style.fontWeight = '500';
        }
        if (yearText) {
            yearText.style.fontSize = isSmallMobile ? '1em' : isMobile ? '1.1em' : '1.2em';
        }
        
        // Highlight today's date
        highlightTodayDate();
    }
});
