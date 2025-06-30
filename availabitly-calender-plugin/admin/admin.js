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
    
    // Initialize professionals admin interface
    initProfessionalsAdmin();
    
    /**
     * Initialize professionals admin interface
     */
    function initProfessionalsAdmin() {
        const form = document.getElementById('ycp-professional-form');
        const dateInput = document.getElementById('ycp_available_dates');
        const uploadButton = document.getElementById('ycp_upload_image');
        const resetButton = document.getElementById('ycp_reset_form');
        
        if (!form) return;
        
        // Initialize date picker
        if (dateInput) {
            initDatePicker(dateInput);
        }
        
        // Initialize image upload
        if (uploadButton) {
            initImageUpload(uploadButton);
        }
        
        // Initialize form submission
        initFormSubmission(form);
        
        // Initialize reset button
        if (resetButton) {
            resetButton.addEventListener('click', resetForm);
        }
        
        // Initialize edit and delete buttons
        initActionButtons();
    }
    
    /**
     * Initialize date picker
     */
    function initDatePicker(dateInput) {
        // Parse existing dates from the input field
        const currentValue = dateInput.value.trim();
        let existingDates = [];
        
        if (currentValue) {
            existingDates = currentValue.split(',').map(date => date.trim()).filter(date => {
                const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
                return date && dateRegex.test(date);
            });
        }
        
        // Initialize flatpickr
        const calendar = flatpickr(dateInput, {
            mode: 'multiple',
            dateFormat: 'Y-m-d',
            showMonths: 2,
            static: true,
            monthSelectorType: 'static',
            locale: "de",
            defaultDate: existingDates.length > 0 ? existingDates : new Date(),
            prevArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>',
            nextArrow: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>',
            onChange: function(selectedDates, dateStr) {
                dateInput.value = dateStr;
            }
        });
    }
    
    /**
     * Initialize image upload
     */
    function initImageUpload(uploadButton) {
        uploadButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Create media frame
            const frame = wp.media({
                title: 'Select Professional Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            // When image selected
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                document.getElementById('ycp_image_url').value = attachment.url;
            });
            
            // Open frame
            frame.open();
        });
    }
    
    /**
     * Initialize form submission
     */
    function initFormSubmission(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            formData.append('action', 'ycp_save_professional');
            formData.append('nonce', ycp_ajax.nonce);
            
            // Show loading state
            const submitButton = form.querySelector('button[type="submit"]');
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Saving...';
            submitButton.disabled = true;
            
            // Send AJAX request
            fetch(ycp_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showMessage(data.data.message, 'success');
                    
                    // Reset form
                    resetForm();
                    
                    // Update professionals list
                    updateProfessionalsList(data.data.professionals);
                } else {
                    showMessage(data.data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('An error occurred while saving the professional.', 'error');
                console.error('Error:', error);
            })
            .finally(() => {
                // Restore button state
                submitButton.textContent = originalText;
                submitButton.disabled = false;
            });
        });
    }
    
    /**
     * Initialize action buttons (edit/delete)
     */
    function initActionButtons() {
        // Edit buttons
        document.querySelectorAll('.ycp-edit-professional').forEach(button => {
            button.addEventListener('click', function() {
                const professionalId = this.getAttribute('data-id');
                loadProfessionalForEdit(professionalId);
            });
        });
        
        // Delete buttons
        document.querySelectorAll('.ycp-delete-professional').forEach(button => {
            button.addEventListener('click', function() {
                const professionalId = this.getAttribute('data-id');
                const professionalName = this.closest('.ycp-professional-item').querySelector('h3').textContent;
                
                if (confirm(`Are you sure you want to delete "${professionalName}"?`)) {
                    deleteProfessional(professionalId);
                }
            });
        });
    }
    
    /**
     * Load professional data for editing
     */
    function loadProfessionalForEdit(professionalId) {
        const formData = new FormData();
        formData.append('action', 'ycp_get_professional');
        formData.append('nonce', ycp_ajax.nonce);
        formData.append('professional_id', professionalId);
        
        fetch(ycp_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const professional = data.data;
                
                // Fill form fields
                document.getElementById('ycp_professional_id').value = professional.id;
                document.getElementById('ycp_name').value = professional.name;
                document.getElementById('ycp_description').value = professional.description || '';
                document.getElementById('ycp_profile_url').value = professional.profile_url || '';
                document.getElementById('ycp_image_url').value = professional.image_url || '';
                document.getElementById('ycp_available_dates').value = professional.available_dates || '';
                
                // Update date picker
                const dateInput = document.getElementById('ycp_available_dates');
                if (dateInput._flatpickr) {
                    const dates = professional.available_dates ? professional.available_dates.split(',').map(d => d.trim()) : [];
                    dateInput._flatpickr.setDate(dates);
                }
                
                // Scroll to form
                document.querySelector('.ycp-form-section').scrollIntoView({ behavior: 'smooth' });
                
                showMessage('Professional loaded for editing.', 'info');
            } else {
                showMessage(data.data.message, 'error');
            }
        })
        .catch(error => {
            showMessage('An error occurred while loading the professional.', 'error');
            console.error('Error:', error);
        });
    }
    
    /**
     * Delete professional
     */
    function deleteProfessional(professionalId) {
        const formData = new FormData();
        formData.append('action', 'ycp_delete_professional');
        formData.append('nonce', ycp_ajax.nonce);
        formData.append('professional_id', professionalId);
        
        fetch(ycp_ajax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(data.data.message, 'success');
                updateProfessionalsList(data.data.professionals);
            } else {
                showMessage(data.data.message, 'error');
            }
        })
        .catch(error => {
            showMessage('An error occurred while deleting the professional.', 'error');
            console.error('Error:', error);
        });
    }
    
    /**
     * Update professionals list
     */
    function updateProfessionalsList(professionals) {
        const listContainer = document.getElementById('ycp-professionals-list');
        if (!listContainer) return;
        
        if (professionals.length === 0) {
            listContainer.innerHTML = '<p>No professionals found.</p>';
            return;
        }
        
        let html = '';
        professionals.forEach(professional => {
            html += `
                <div class="ycp-professional-item" data-id="${professional.id}">
                    <h3>
                        ${escapeHtml(professional.name)}
                        <span class="ycp-professional-id">(ID: ${professional.id})</span>
                    </h3>
                    ${professional.image_url ? `<img src="${escapeHtml(professional.image_url)}" class="ycp-image-preview" alt="">` : ''}
                    ${professional.description ? `<p>${escapeHtml(professional.description)}</p>` : ''}
                    ${professional.available_dates ? `<p><strong>Available Dates:</strong> ${escapeHtml(professional.available_dates)}</p>` : ''}
                    <div class="ycp-professional-actions">
                        <button type="button" class="button ycp-edit-professional" data-id="${professional.id}">Edit</button>
                        <button type="button" class="button button-link-delete ycp-delete-professional" data-id="${professional.id}">Delete</button>
                    </div>
                </div>
            `;
        });
        
        listContainer.innerHTML = html;
        
        // Re-initialize action buttons
        initActionButtons();
    }
    
    /**
     * Reset form
     */
    function resetForm() {
        const form = document.getElementById('ycp-professional-form');
        if (form) {
            form.reset();
            document.getElementById('ycp_professional_id').value = '';
            
            // Reset date picker
            const dateInput = document.getElementById('ycp_available_dates');
            if (dateInput._flatpickr) {
                dateInput._flatpickr.clear();
            }
        }
    }
    
    /**
     * Show message
     */
    function showMessage(message, type = 'info') {
        // Remove existing messages
        const existingMessages = document.querySelectorAll('.ycp-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Create message element
        const messageDiv = document.createElement('div');
        messageDiv.className = `ycp-message ycp-message-${type}`;
        messageDiv.textContent = message;
        
        // Style the message
        messageDiv.style.cssText = `
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
            ${type === 'success' ? 'background: #d4edda; border-color: #28a745; color: #155724;' : ''}
            ${type === 'error' ? 'background: #f8d7da; border-color: #dc3545; color: #721c24;' : ''}
            ${type === 'info' ? 'background: #d1ecf1; border-color: #17a2b8; color: #0c5460;' : ''}
        `;
        
        // Insert message at top of page
        const container = document.querySelector('.ycp-admin-container');
        if (container) {
            container.parentNode.insertBefore(messageDiv, container);
        }
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.remove();
            }
        }, 5000);
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
