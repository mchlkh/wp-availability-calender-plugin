/**
 * Calendar Color Settings Admin JavaScript
 * For use in the Availabilities section
 */
(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize color pickers
        $('.ycp-color-picker').wpColorPicker({
            change: function(event, ui) {
                // Update the preview when color changes
                updatePreview();
            }
        });

        // Initialize preview
        initPreview();
        
        // Listen for checkbox changes
        $('#ycp_use_theme_colors').on('change', function() {
            // If checked, disable color pickers
            if ($(this).is(':checked')) {
                $('.ycp-color-picker').wpColorPicker('disable');
                $('.wp-picker-container').css('opacity', '0.5');
            } else {
                $('.ycp-color-picker').wpColorPicker('enable');
                $('.wp-picker-container').css('opacity', '1');
            }
            
            // Update preview
            updatePreview();
        });
        
        // Special listener for month/year color to update preview immediately
        $('#ycp_month_year_color').wpColorPicker({
            change: function(event, ui) {
                // Update just the month/year text color for immediate feedback
                var newColor = ui.color.toString();
                $('.preview-month-name').css('color', newColor);
                // Update full preview after a short delay
                setTimeout(updatePreview, 50);
            }
        });
        
        // Trigger initial state
        if ($('#ycp_use_theme_colors').is(':checked')) {
            $('.ycp-color-picker').wpColorPicker('disable');
            $('.wp-picker-container').css('opacity', '0.5');
        }
    });

    /**
     * Initialize the preview
     */
    function initPreview() {
        // Create a simple calendar preview
        var previewHtml = '<div class="ycp-calendar-preview-wrapper">';
        previewHtml += '<div class="preview-header">Calendar Preview</div>';
        previewHtml += '<div class="preview-calendar">';
        
        // Month header
        previewHtml += '<div class="preview-month">';
        previewHtml += '<span class="preview-nav">&lt;</span>';
        previewHtml += '<span class="preview-month-name">January 2024</span>';
        previewHtml += '<span class="preview-nav">&gt;</span>';
        previewHtml += '</div>';
        
        // Weekdays
        previewHtml += '<div class="preview-weekdays">';
        var weekdays = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
        for (var i = 0; i < weekdays.length; i++) {
            previewHtml += '<span class="preview-weekday">' + weekdays[i] + '</span>';
        }
        previewHtml += '</div>';
        
        // Days
        previewHtml += '<div class="preview-days">';
        for (var i = 1; i <= 31; i++) {
            var className = 'preview-day';
            
            // Add some example states
            if (i === 10) {
                className += ' today';
            } else if (i === 15) {
                className += ' selected';
            } else if (i === 20) {
                className += ' today selected';
            }
            
            previewHtml += '<span class="' + className + '">' + i + '</span>';
        }
        previewHtml += '</div>';
        
        previewHtml += '</div>'; // End preview-calendar
        previewHtml += '</div>'; // End preview-wrapper
        
        // Add the preview HTML
        $('#ycp-calendar-preview').html(previewHtml);
        
        // Add preview styling
        var previewStyle = '<style id="ycp-preview-style">';
        previewStyle += '.ycp-calendar-preview-wrapper { font-family: Arial, sans-serif; }';
        previewStyle += '.preview-header { text-align: center; margin-bottom: 10px; font-weight: bold; }';
        previewStyle += '.preview-month { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }';
        previewStyle += '.preview-nav { cursor: pointer; font-weight: bold; }';
        previewStyle += '.preview-month-name { font-weight: bold; }';
        previewStyle += '.preview-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); margin-bottom: 5px; }';
        previewStyle += '.preview-weekday { text-align: center; font-weight: bold; opacity: 0.7; }';
        previewStyle += '.preview-days { display: grid; grid-template-columns: repeat(7, 1fr); gap: 5px; }';
        previewStyle += '.preview-day { text-align: center; padding: 5px; cursor: pointer; }';
        previewStyle += '.preview-day.today { background-color: var(--preview-secondary-light, #E3F2FD); color: var(--preview-primary-dark, #0066CC); box-shadow: inset 0 0 0 2px var(--preview-primary-color, #1E90FF); }';
        previewStyle += '.preview-day.selected { background-color: var(--preview-primary-color, #1E90FF); color: var(--preview-primary-contrast, white); }';
        previewStyle += '.preview-day.today.selected { background-color: var(--preview-accent-color, #FF6B00); color: var(--preview-accent-contrast, white); }';
        previewStyle += '.preview-day:hover { background-color: var(--preview-secondary-light, #E6F2FF); }';
        previewStyle += '</style>';
        
        $('head').append(previewStyle);
        
        // Update with current settings
        updatePreview();
    }

    /**
     * Update the preview with current color settings
     */
    function updatePreview() {
        var useThemeColors = $('#ycp_use_theme_colors').is(':checked');
        var previewCSS = '';
        
        // Get text color variable (this was undefined in the original code)
        var textColor = $('#ycp_month_year_color').val() || '#333333';
        
        if (!useThemeColors) {
            previewCSS += ':root {';
            
            // Map settings to preview variables
            var colorMappings = {
                'ycp_primary_color': '--preview-primary-color',
                'ycp_primary_dark': '--preview-primary-dark',
                'ycp_primary_contrast': '--preview-primary-contrast',
                'ycp_secondary_color': '--preview-secondary-color',
                'ycp_secondary_light': '--preview-secondary-light',
                'ycp_accent_color': '--preview-accent-color',
                'ycp_accent_contrast': '--preview-accent-contrast',
                'ycp_month_year_color': '--preview-month-year-color'
            };
            
            // Add each color if it's set
            for (var setting in colorMappings) {
                var value = $('#' + setting).val();
                if (value) {
                    previewCSS += colorMappings[setting] + ': ' + value + ';';
                }
            }
            
            previewCSS += '}';
        }
        
        // Update or add style
        if ($('#ycp-preview-vars').length) {
            $('#ycp-preview-vars').html(previewCSS);
        } else {
            $('head').append('<style id="ycp-preview-vars">' + previewCSS + '</style>');
        }

        // Get a variable for primary color (fix undefined variable error)
        var primaryColor = $('#ycp_primary_color').val() || '#1E90FF';
        
        // Get advanced color values if available
        const primaryColorAdvanced = document.getElementById('ycp_primary_color')?.value || primaryColor;
        const primaryDark = document.getElementById('ycp_primary_dark')?.value || '#0060C0';
        const primaryContrast = document.getElementById('ycp_primary_contrast')?.value || 'white';
        const secondaryColor = document.getElementById('ycp_secondary_color')?.value || '#9ECBFF';
        const secondaryLight = document.getElementById('ycp_secondary_light')?.value || '#E6F2FF';
        const accentColor = document.getElementById('ycp_accent_color')?.value || '#FF6B00';
        const accentContrast = document.getElementById('ycp_accent_contrast')?.value || 'white';
        const monthYearColor = document.getElementById('ycp_month_year_color')?.value || '#333333';
        
        // Use basic colors as fallback when advanced settings are not set or when using theme colors
        const effectivePrimaryColor = useThemeColors ? 'var(--ycp-primary-color)' : (primaryColorAdvanced || primaryColor);
        const effectivePrimaryDark = useThemeColors ? 'var(--ycp-primary-dark)' : primaryDark;
        const effectivePrimaryContrast = useThemeColors ? 'var(--ycp-primary-contrast)' : primaryContrast;
        const effectiveSecondaryLight = useThemeColors ? 'var(--ycp-secondary-light)' : secondaryLight;
        const effectiveSecondaryColor = useThemeColors ? 'var(--ycp-secondary-color)' : secondaryColor;
        const effectiveAccentColor = useThemeColors ? 'var(--ycp-accent-color)' : accentColor;
        const effectiveAccentContrast = useThemeColors ? 'var(--ycp-accent-contrast)' : accentContrast;
        const effectiveMonthYearColor = useThemeColors ? 'var(--ycp-month-year-color)' : monthYearColor;

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
                --preview-month-year-color: ${effectiveMonthYearColor};
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
                color: var(--preview-month-year-color) !important;
            }
            .preview-month-name {
                color: var(--preview-month-year-color) !important;
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
            .flatpickr-current-month .cur-month,
            .flatpickr-current-month .numInputWrapper {
                color: var(--preview-month-year-color) !important;
            }
        `;
        
        // Remove any previous preview styles and add the new styles
        $('#ycp-preview-styles').remove();
        document.head.appendChild(styleEl);
        
        // Update the preview month/year color directly for immediate effect
        $('.preview-month-name').css('color', effectiveMonthYearColor);
    }

})(jQuery); 