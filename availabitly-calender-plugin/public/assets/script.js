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
    
    const datePicker = document.getElementById('ycp-date-picker');
    const resultsContainer = document.getElementById('ycp-results');
    const showAllBtn = document.getElementById('ycp-show-all-btn');
    let calendar;
    
    function fetchAllProfessionals() {
        if (!resultsContainer) return;
        resultsContainer.innerHTML = '<div class="ycp-loading">Alle Ladies werden geladen...</div>';
        fetch(`${ycp_ajax.ajax_url}?action=ycp_get_all_professionals`)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Network response was not ok');
                }
                return res.text();
            })
            .then(html => {
                resultsContainer.innerHTML = html;
                // Change header text after content is loaded
                changeHeaderText();
                // Apply dynamic transforms based on description height
                applyDynamicTransforms();
            })
            .catch(error => {
                console.error('Error fetching all professionals:', error);
                resultsContainer.innerHTML = '<p>Entschuldigung, es gab ein Problem beim Laden der Ladies. Bitte versuchen Sie es erneut.</p>';
                // Change header text even if there's an error
                changeHeaderText();
            });
    }

    function changeHeaderText() {
        // Try multiple times with increasing delays to ensure it works
        const attempts = [100, 300, 500, 1000];
        
        attempts.forEach(delay => {
            setTimeout(() => {
                const headerTitle = document.querySelector('.ycp-calendar-header h3');
                console.log(`Attempt after ${delay}ms - Header element found:`, headerTitle); // Debug log
                
                if (headerTitle && headerTitle.textContent === 'Heute anwesend') {
                    headerTitle.textContent = 'Die gesamte Royal Crew';
                    console.log(`Header text changed to "Die gesamte Royal Crew" after ${delay}ms`); // Debug log
                } else if (headerTitle) {
                    console.log(`Header already changed or different text: "${headerTitle.textContent}"`);
                } else {
                    console.log(`Header element not found after ${delay}ms - trying alternative selectors`);
                    // Try alternative selectors
                    const altHeader1 = document.querySelector('h3');
                    console.log('Alt header 1:', altHeader1);
                    if (altHeader1 && altHeader1.textContent === 'Heute anwesend') {
                        altHeader1.textContent = 'Die gesamte Royal Crew';
                        console.log(`Header text changed using alt selector after ${delay}ms`);
                    }
                }
            }, delay);
        });
    }

    /**
     * Dynamically calculate and apply transform values based on description text height
     */
    function applyDynamicTransforms() {
        const professionalCards = document.querySelectorAll('.ycp-pro');
        console.log(`Applying dynamic transforms to ${professionalCards.length} professional cards`);
        
        // Remove any existing dynamic transform styles
        const existingStyle = document.getElementById('ycp-dynamic-transforms');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        // Create a single style element for all dynamic transforms
        const style = document.createElement('style');
        style.id = 'ycp-dynamic-transforms';
        let styleContent = '';
        
        professionalCards.forEach((card, index) => {
            const description = card.querySelector('.description');
            const name = card.querySelector('h4');
            const banner = card.querySelector('.ycp-heute-banner');
            
            if (!description || (!name && !banner)) {
                console.log(`Card ${index}: Skipping - no description or name/banner found`);
                return;
            }
            
            // Add unique identifier to card
            const cardId = `ycp-card-${index}`;
            card.dataset.cardId = cardId;
            
            // Create a temporary element to measure the description height
            const tempDesc = description.cloneNode(true);
            tempDesc.style.cssText = `
                position: absolute;
                visibility: hidden;
                opacity: 1;
                transform: translateY(0);
                z-index: -1;
                width: ${description.offsetWidth}px;
                padding: ${getComputedStyle(description).padding};
                font-size: ${getComputedStyle(description).fontSize};
                line-height: ${getComputedStyle(description).lineHeight};
                font-family: ${getComputedStyle(description).fontFamily};
            `;
            
            // Temporarily add to DOM to measure
            card.appendChild(tempDesc);
            const descriptionHeight = tempDesc.offsetHeight;
            card.removeChild(tempDesc);
            
            // Calculate the transform value with reduced spacing
            // We want to move the name/banner up by the description height plus minimal padding
            const baseOffset = 0; // Reduced from 30px to 10px for tighter spacing
            const transformValue = -(descriptionHeight + baseOffset);
            
            console.log(`Card ${index}: Description height: ${descriptionHeight}px, Transform: ${transformValue}px`);
            
            // Add CSS rules for this specific card
            styleContent += `
                .ycp-pro[data-card-id="${cardId}"]:hover h4,
                .ycp-pro[data-card-id="${cardId}"]:focus-within h4 {
                    transform: translateY(${transformValue}px) !important;
                }
                .ycp-pro[data-card-id="${cardId}"]:hover .ycp-heute-banner,
                .ycp-pro[data-card-id="${cardId}"]:focus-within .ycp-heute-banner {
                    transform: translateY(${transformValue}px) !important;
                }
            `;
        });
        
        // Add the style to the document
        if (styleContent) {
            style.textContent = styleContent;
            document.head.appendChild(style);
            console.log('Dynamic transform styles applied successfully');
        } else {
            console.log('No dynamic transform styles to apply');
        }
    }

    if (showAllBtn) {
        showAllBtn.addEventListener('click', function() {
            fetchAllProfessionals();
            if (calendar && typeof calendar.clear === 'function') {
                // Clear the selected date completely
                console.log('Before clearing - selected dates:', calendar.selectedDates);
                calendar.clear();
                calendar.setDate(null);
                console.log('After clearing - selected dates:', calendar.selectedDates);
                
                // Also remove any visual selection styling from the calendar container
                setTimeout(() => {
                    // Target elements specifically within ycp-calendar-container
                    const calendarContainer = document.getElementById('ycp-calendar-container');
                    if (calendarContainer) {
                        // Remove selected class from all days in the container
                        const selectedDays = calendarContainer.querySelectorAll('.flatpickr-day.selected');
                        selectedDays.forEach(day => {
                            day.classList.remove('selected');
                        });
                        
                        const todaySelected = calendarContainer.querySelectorAll('.flatpickr-day.today.selected');
                        todaySelected.forEach(day => {
                            day.classList.remove('selected');
                        });
                        
                        // Also target any selected days globally as backup
                        const allSelectedDays = document.querySelectorAll('.flatpickr-day.selected');
                        allSelectedDays.forEach(day => {
                            day.classList.remove('selected');
                        });
                        
                        console.log('Date selection cleared from ycp-calendar-container');
                        
                        // Debug: Log all remaining classes on calendar days
                        const allDays = calendarContainer.querySelectorAll('.flatpickr-day');
                        console.log('All calendar days after clearing:', allDays.length);
                        allDays.forEach((day, index) => {
                            if (day.classList.contains('selected') || day.classList.contains('today')) {
                                console.log(`Day ${index}: classes = ${day.className}, text = "${day.textContent}"`);
                            }
                        });
                    } else {
                        console.log('ycp-calendar-container not found, clearing globally');
                        // Fallback to global clearing
                        const selectedDays = document.querySelectorAll('.flatpickr-day.selected');
                        selectedDays.forEach(day => {
                            day.classList.remove('selected');
                        });
                    }
                }, 100);
            } else {
                console.log('Calendar object not available, clearing selection manually');
                // Fallback: Clear selection manually without calendar object
                setTimeout(() => {
                    const allSelectedDays = document.querySelectorAll('.flatpickr-day.selected');
                    allSelectedDays.forEach(day => {
                        day.classList.remove('selected');
                    });
                    console.log('Manual date selection clearing completed');
                }, 100);
            }
            // Header text will be changed in fetchAllProfessionals after content loads
        });
    }
    
    if (datePicker && resultsContainer) {
        // Get today's date in YYYY-MM-DD format for default selection
        const today = new Date();
        const todayFormatted = today.getFullYear() + '-' + 
                              String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(today.getDate()).padStart(2, '0');
        
        // Show loading indicator while fetching professionals
        function showLoadingIndicator() {
            resultsContainer.innerHTML = '<div class="ycp-loading">Verfügbare Ladies werden geladen...</div>';
        }
        
        // Fetch and display professionals for a given date
        function fetchProfessionals(date) {
            showLoadingIndicator();
            
            fetch(`${ycp_ajax.ajax_url}?action=ycp_get_professionals&date=${date}`)
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.text();
                })
                .then(html => {
                    resultsContainer.innerHTML = html;
                    
                    // Check if professionals are available for the selected date
                    const availabilityData = resultsContainer.querySelector('.ycp-availability-data');
                    const headerTitle = document.querySelector('.ycp-calendar-header h3');
                    
                    if (availabilityData && headerTitle) {
                        const isAvailable = availabilityData.getAttribute('data-available') === 'true';
                        const selectedDate = availabilityData.getAttribute('data-selected-date');
                        const today = new Date().toISOString().split('T')[0];
                        
                        if (!isAvailable && selectedDate === date) {
                            // No professionals available for the selected date
                            headerTitle.textContent = 'Für den ausgewählten Tag ist niemand verfügbar';
                        } else if (date === today) {
                            // Today's date selected and professionals are available
                            headerTitle.textContent = 'Heute anwesend';
                        } else {
                            // Other date selected and professionals are available
                            headerTitle.textContent = 'Verfügbare Ladies';
                        }
                    }
                    
                    // Apply dynamic transforms after content is loaded
                    setTimeout(() => {
                        applyDynamicTransforms();
                    }, 100);
                })
                .catch(error => {
                    console.error('Error fetching professionals:', error);
                    resultsContainer.innerHTML = '<p>Entschuldigung, es gab ein Problem beim Laden der Ladies. Bitte versuchen Sie es erneut.</p>';
                });
        }
        
        // Fetch professionals for today immediately - don't wait for calendar to be ready
        fetchProfessionals(todayFormatted);
        
        // Determine number of months to show based on screen width
        const getMonthsToShow = () => {
            // Show 1 month on phones only (under 480px), 2 months on all other devices
            return window.innerWidth < 480 ? 1 : 2;
        };
        
        // Initialize flatpickr
        calendar = flatpickr(datePicker, {
            dateFormat: 'Y-m-d',
            inline: true,
            showMonths: getMonthsToShow(),
            static: true,
            monthSelectorType: 'static',
            defaultDate: todayFormatted, // Set default date to today
            prevArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>',
            nextArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>',
            // locale: "de", // German locale disabled due to loading issues
            onChange: function (selectedDates, dateStr) {
                // Send AJAX request when a date is selected
                fetchProfessionals(dateStr);
            },
            onReady: function(selectedDates, dateStr, instance) {
                // Check and enforce single month on mobile, double on larger screens
                enforceMobileMonthCount();
                
                // Fix calendar appearance after initialization with a delay to ensure DOM is fully rendered
                setTimeout(fixCalendarAppearance, 100);
                
                // Make sure today is selected and highlighted
                calendar.setDate(today);
                
                // Fetch professionals for today when the calendar is ready (as a backup)
                // This ensures professionals are shown even if the earlier fetch failed
                setTimeout(() => fetchProfessionals(todayFormatted), 200);
                
                // Call redraw after a slight delay to ensure all styles are applied
                setTimeout(() => {
                    if (calendar && typeof calendar.redraw === 'function') {
                        calendar.redraw();
                    }
                }, 150);
            },
            onMonthChange: function() {
                // Re-apply fixes when month changes
                setTimeout(fixCalendarAppearance, 50);
            },
            onOpen: function() {
                fixCalendarAppearance();
                if (typeof calendar.redraw === 'function') {
                    calendar.redraw();
                }
            }
        });
        
        // Function to ensure mobile shows only one month, larger screens show two
        function enforceMobileMonthCount() {
            if (!calendar || !calendar.config) {
                console.log('Calendar not ready for mobile month count enforcement');
                return;
            }
            const isPhone = window.innerWidth < 480;
            const currentMonthCount = calendar.config.showMonths;
            
            if (isPhone && currentMonthCount > 1) {
                // Phone view - show only one month
                calendar.set('showMonths', 1);
                if (typeof calendar.redraw === 'function') {
                    calendar.redraw();
                }
            } else if (!isPhone && currentMonthCount === 1) {
                // Tablet/Desktop view - show two months
                calendar.set('showMonths', 2);
                if (typeof calendar.redraw === 'function') {
                    calendar.redraw();
                }
            }
        }
        
        // Function to fix calendar appearance to match screenshot
        function fixCalendarAppearance() {
            // Check screen size for responsive adjustments
            const screenWidth = window.innerWidth;
            const isMobile = screenWidth < 480;
            const isSmallMobile = screenWidth < 360;
            const isTablet = screenWidth >= 480 && screenWidth < 768;
            
            // Apply grid layout to dayContainer for even spacing
            const dayContainers = document.querySelectorAll('.dayContainer');
            dayContainers.forEach(container => {
                container.style.cssText = `
                    display: grid !important;
                    grid-template-columns: repeat(7, 1fr) !important;
                    gap: 0 !important;
                    justify-content: space-between !important;
                    padding: 0 4px !important;
                    width: 100% !important;
                    min-width: auto !important;
                    overflow: visible !important;
                `;
            });
            
            // Apply grid layout to weekdays for even spacing
            const weekdayContainer = document.querySelector('.flatpickr-weekdaycontainer');
            if (weekdayContainer) {
                weekdayContainer.style.cssText = `
                    display: grid !important;
                    grid-template-columns: repeat(7, 1fr) !important;
                    width: 100% !important;
                `;
            }
            
            // Fix last row spacing - inspect all rows
            const calendarDays = document.querySelectorAll('.flatpickr-day');
            calendarDays.forEach(day => {
                // Center all days horizontally
                day.style.margin = '0 auto !important';
                day.style.justifySelf = 'center !important';
            });
            
            // Add proper spacing between days
            const flatpickrDays = document.querySelector('.flatpickr-days');
            if (flatpickrDays) {
                flatpickrDays.style.cssText = `
                    width: 100% !important;
                    min-width: auto !important;
                    border: none !important;
                    overflow: visible !important;
                `;
            }
            
            // Remove scrollbars entirely
            const calendarElements = document.querySelectorAll('.flatpickr-calendar, .flatpickr-days, .dayContainer');
            calendarElements.forEach(el => {
                // Set overflow to visible for all screens to prevent scrollbars
                el.style.overflow = 'visible !important';
            });
            
            // Make sure multi-month display looks good
            if (screenWidth >= 480) {  // Show 2 months on tablet and desktop
                // Add some space between months in multi-month display
                const monthElements = document.querySelectorAll('.flatpickr-month');
                if (monthElements.length > 1) {
                    const monthWrappers = document.querySelectorAll('.flatpickr-calendar > .flatpickr-months');
                    monthWrappers.forEach(wrapper => {
                        wrapper.style.marginBottom = '15px !important';
                    });
                }
            }
            
            // Set the width of the calendar to match the container
            const calendar = document.querySelector('.flatpickr-calendar');
            if (calendar) {
                calendar.style.cssText = `
                    width: 100% !important;
                    max-width: 900px !important;
                    padding: ${isSmallMobile ? '2px' : '5px'} !important;
                    overflow: visible !important;
                `;
            }
            
            // Ensure the wrapper container is large enough
            const calendarContainer = document.querySelector('#ycp-date-picker').parentElement;
            if (calendarContainer) {
                calendarContainer.style.cssText = `
                    width: 100% !important;
                    max-width: 900px !important;
                    overflow: visible !important;
                `;
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
                day.style.cssText = `
                    font-size: ${fontSize} !important;
                    width: ${daySize} !important;
                    height: ${daySize} !important;
                    line-height: ${daySize} !important;
                    max-width: ${daySize} !important;
                    margin: 0 auto !important;
                `;
            });
            
            // Make month and year text bigger but responsive to screen size
            const monthText = document.querySelector('.cur-month');
            const yearText = document.querySelector('.numInputWrapper');
            
            if (monthText) {
                monthText.style.fontSize = isSmallMobile ? '1em !important' : isMobile ? '1.1em !important' : '1.2em !important';
                monthText.style.fontWeight = '500 !important';
            }
            if (yearText) {
                yearText.style.fontSize = isSmallMobile ? '1em !important' : isMobile ? '1.1em !important' : '1.2em !important';
            }
            
            // Make sure today's date is visually selected
            const todayElement = document.querySelector('.flatpickr-day.today');
            if (todayElement) {
                todayElement.classList.add('selected');
            }
            
            // Force a reflow to ensure styles are applied
            document.querySelector('.flatpickr-calendar').offsetHeight;
        }
        
        // Update calendar when window is resized
        window.addEventListener('resize', function() {
            // Force proper month count on resize
            enforceMobileMonthCount();
            
            // Re-apply appearance fixes
            fixCalendarAppearance();
            
            // Recalculate dynamic transforms for professional cards
            setTimeout(() => {
                applyDynamicTransforms();
            }, 200);
        });

        // Force reflow to apply styles - use the proper selector instead of undefined calendarContainer
        const calendarElement = document.querySelector('.flatpickr-calendar');
        if (calendarElement) {
            calendarElement.offsetHeight; // Force reflow
        }

        // Add window load event to ensure calendar is fully rendered and styled
        window.addEventListener('load', function() {
            // Apply all styles again after complete page load
            if (calendar) {
                fixCalendarAppearance();
                calendar.redraw();
            }
            
            // Apply dynamic transforms after page load
            setTimeout(() => {
                applyDynamicTransforms();
            }, 300);
        });
    }
});
