# Availability Calendar Plugin - API Documentation

This document explains how to access availability data for professionals through various secure interfaces provided by the Availability Calendar Plugin.

## Table of Contents

1. [Security Measures](#security-measures)
2. [PHP Functions (Direct Access)](#php-functions-direct-access)
3. [Shortcodes](#shortcodes)
4. [AJAX API](#ajax-api)
5. [REST API](#rest-api)
6. [JavaScript API](#javascript-api)
7. [Usage Examples](#usage-examples)
8. [Error Handling](#error-handling)
9. [Best Practices](#best-practices)

## Security Measures

The plugin implements several security measures to protect availability data:

### 1. Nonce Verification
- All AJAX requests require a valid nonce
- Nonces are automatically generated and verified
- Prevents CSRF attacks

### 2. Input Validation and Sanitization
- All user inputs are validated and sanitized
- Date formats are strictly validated (Y-m-d format)
- SQL injection prevention through WordPress functions
- XSS prevention through proper escaping

### 3. Permission Checks
- User capability checks for AJAX requests
- REST API permission callbacks
- Public access allowed for availability data (configurable)

### 4. Data Escaping
- All output is properly escaped using WordPress functions
- HTML entities are encoded
- URLs are validated and escaped

## PHP Functions (Direct Access)

### `ycp_get_professional_availability($professional_id, $date_from = '', $date_to = '')`

Get availability data for a specific professional.

**Parameters:**
- `$professional_id` (int): The professional's post ID
- `$date_from` (string): Optional start date (Y-m-d format)
- `$date_to` (string): Optional end date (Y-m-d format)

**Returns:** Array with professional data

**Example:**
```php
// Get all availability for professional ID 123
$data = ycp_get_professional_availability(123);

// Get availability for a date range
$data = ycp_get_professional_availability(123, '2024-01-01', '2024-01-31');

// Access the data
echo $data['name']; // Professional name
echo $data['total_available_days']; // Number of available days
echo $data['is_available_today']; // Boolean
```

### `ycp_get_availability_by_date($date = '', $limit = 50)`

Get all professionals available on a specific date.

**Parameters:**
- `$date` (string): Date in Y-m-d format (defaults to today)
- `$limit` (int): Maximum number of professionals to return

**Returns:** Array with available professionals

**Example:**
```php
// Get professionals available today
$data = ycp_get_availability_by_date();

// Get professionals available on a specific date
$data = ycp_get_availability_by_date('2024-01-15');

// Access the data
echo $data['date']; // The date
echo $data['count']; // Number of available professionals
foreach ($data['available_professionals'] as $professional) {
    echo $professional['name'];
}
```

### `ycp_get_all_professionals_availability($limit = 100)`

Get all professionals with their availability data.

**Parameters:**
- `$limit` (int): Maximum number of professionals to return

**Returns:** Array with all professionals

**Example:**
```php
$data = ycp_get_all_professionals_availability();

foreach ($data['professionals'] as $professional) {
    echo $professional['name'] . ' - ' . $professional['total_available_days'] . ' days available';
}
```

## Shortcodes

### `[ycp_availability_data]`

Display availability data using shortcodes.

**Attributes:**
- `professional_id`: Show data for specific professional
- `date`: Show availability for specific date
- `show_all`: Show all professionals (true/false)
- `limit`: Maximum number of professionals to show
- `template`: Display template (list/grid/calendar)

**Examples:**
```php
// Show specific professional
[ycp_availability_data professional_id="123"]

// Show today's availability
[ycp_availability_data date="2024-01-15"]

// Show all professionals
[ycp_availability_data show_all="true" limit="20"]

// Use grid template
[ycp_availability_data show_all="true" template="grid"]
```

## AJAX API

### Endpoint: `admin-ajax.php`

**Action:** `ycp_get_availability_data`

**Security:** Requires nonce verification

**Parameters:**
- `action`: `ycp_get_availability_data`
- `nonce`: Security nonce
- `professional_id`: Professional ID (optional)
- `date`: Date in Y-m-d format (optional)
- `date_from`: Start date (optional)
- `date_to`: End date (optional)
- `limit`: Maximum results (optional)

**Example (jQuery):**
```javascript
$.ajax({
    url: ycp_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ycp_get_availability_data',
        nonce: ycp_ajax.nonce,
        professional_id: 123
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data);
        }
    }
});
```

## REST API

### Endpoints

#### 1. Get Professional Availability
```
GET /wp-json/ycp/v1/availability/{professional_id}
```

**Parameters:**
- `professional_id`: Professional ID (path parameter)
- `date_from`: Start date (query parameter)
- `date_to`: End date (query parameter)

**Example:**
```bash
curl "https://yoursite.com/wp-json/ycp/v1/availability/123?date_from=2024-01-01&date_to=2024-01-31"
```

#### 2. Get Availability by Date
```
GET /wp-json/ycp/v1/availability
```

**Parameters:**
- `date`: Date in Y-m-d format (query parameter)
- `limit`: Maximum results (query parameter, default: 50)

**Example:**
```bash
curl "https://yoursite.com/wp-json/ycp/v1/availability?date=2024-01-15&limit=10"
```

## JavaScript API

The plugin provides a JavaScript API for easy client-side access.

### Available Functions

#### `YCPAvailabilityAPI.getProfessionalAvailability(professionalId, dateFrom, dateTo)`
Returns a Promise with professional availability data.

#### `YCPAvailabilityAPI.getAvailabilityByDate(date, limit)`
Returns a Promise with availability data for a specific date.

#### `YCPAvailabilityAPI.getAvailabilityViaREST(endpoint, params)`
Returns a Promise with data from REST API.

#### `YCPAvailabilityAPI.displayAvailabilityData(data, containerSelector, template)`
Displays availability data in a container.

#### `YCPAvailabilityAPI.createAvailabilityWidget(containerSelector, options)`
Creates an auto-updating availability widget.

### Examples

#### Basic Usage
```javascript
// Get professional availability
YCPAvailabilityAPI.getProfessionalAvailability(123)
    .then(data => {
        console.log('Professional:', data.name);
        console.log('Available days:', data.total_available_days);
    })
    .catch(error => {
        console.error('Error:', error.message);
    });

// Get today's availability
YCPAvailabilityAPI.getAvailabilityByDate()
    .then(data => {
        console.log('Available today:', data.count, 'professionals');
    });
```

#### Display in Container
```javascript
// Display professional data
YCPAvailabilityAPI.getProfessionalAvailability(123)
    .then(data => {
        YCPAvailabilityAPI.displayAvailabilityData(data, '#availability-container', 'list');
    });

// Display today's availability
YCPAvailabilityAPI.getAvailabilityByDate()
    .then(data => {
        YCPAvailabilityAPI.displayAvailabilityData(data, '#today-availability', 'grid');
    });
```

#### Create Widget
```javascript
// Create auto-refreshing widget
YCPAvailabilityAPI.createAvailabilityWidget('#availability-widget', {
    date: '2024-01-15',
    limit: 10,
    template: 'grid',
    autoRefresh: true,
    refreshInterval: 300000 // 5 minutes
});
```

#### REST API Usage
```javascript
// Get data via REST API
YCPAvailabilityAPI.getAvailabilityViaREST('availability/123', {
    date_from: '2024-01-01',
    date_to: '2024-01-31'
})
.then(data => {
    console.log(data);
});
```

## Usage Examples

### 1. Display Today's Availability in Theme
```php
// In your theme's functions.php or template
$today_availability = ycp_get_availability_by_date();
if ($today_availability['count'] > 0) {
    echo '<h3>Available Today (' . $today_availability['count'] . ')</h3>';
    foreach ($today_availability['available_professionals'] as $professional) {
        echo '<div class="professional">';
        echo '<h4>' . esc_html($professional['name']) . '</h4>';
        if (!empty($professional['description'])) {
            echo '<p>' . esc_html($professional['description']) . '</p>';
        }
        echo '</div>';
    }
}
```

### 2. Create Custom Availability Widget
```php
// Create a custom widget
class Custom_Availability_Widget extends WP_Widget {
    public function widget($args, $instance) {
        $date = !empty($instance['date']) ? $instance['date'] : date('Y-m-d');
        $data = ycp_get_availability_by_date($date, $instance['limit'] ?? 10);
        
        echo $args['before_widget'];
        echo '<h3>Available on ' . date('F j, Y', strtotime($date)) . '</h3>';
        
        if ($data['count'] > 0) {
            foreach ($data['available_professionals'] as $professional) {
                echo '<div class="professional-item">';
                echo '<h4>' . esc_html($professional['name']) . '</h4>';
                echo '</div>';
            }
        } else {
            echo '<p>No professionals available.</p>';
        }
        
        echo $args['after_widget'];
    }
}
```

### 3. AJAX Availability Checker
```javascript
// Create a date picker that shows availability
$('#date-picker').on('change', function() {
    const selectedDate = $(this).val();
    
    YCPAvailabilityAPI.getAvailabilityByDate(selectedDate)
        .then(data => {
            $('#availability-results').html('');
            
            if (data.count > 0) {
                data.available_professionals.forEach(professional => {
                    $('#availability-results').append(`
                        <div class="professional-card">
                            <h4>${professional.name}</h4>
                            ${professional.description ? `<p>${professional.description}</p>` : ''}
                        </div>
                    `);
                });
            } else {
                $('#availability-results').html('<p>No professionals available on this date.</p>');
            }
        })
        .catch(error => {
            $('#availability-results').html('<p class="error">Error loading availability data.</p>');
        });
});
```

## Error Handling

### PHP Functions
```php
try {
    $data = ycp_get_professional_availability(123);
    // Process data
} catch (Exception $e) {
    // Handle error
    error_log('Availability API Error: ' . $e->getMessage());
    echo 'Error: ' . esc_html($e->getMessage());
}
```

### JavaScript
```javascript
YCPAvailabilityAPI.getProfessionalAvailability(123)
    .then(data => {
        // Process data
    })
    .catch(error => {
        console.error('Error:', error.message);
        // Handle error (show user-friendly message, etc.)
    });
```

### REST API
```javascript
fetch('/wp-json/ycp/v1/availability/123')
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        // Process data
    })
    .catch(error => {
        console.error('Error:', error);
    });
```

## Best Practices

### 1. Security
- Always use nonces for AJAX requests
- Validate and sanitize all inputs
- Escape all outputs
- Use WordPress functions for database operations

### 2. Performance
- Cache availability data when possible
- Use appropriate limits for large datasets
- Consider using transients for frequently accessed data

### 3. User Experience
- Provide loading states for AJAX requests
- Handle errors gracefully
- Use appropriate templates for different use cases

### 4. Code Organization
- Use the provided functions instead of direct database queries
- Follow WordPress coding standards
- Document custom implementations

### 5. Data Validation
- Always validate date formats
- Check for empty or invalid professional IDs
- Handle edge cases (no data, invalid dates, etc.)

## Troubleshooting

### Common Issues

1. **Nonce verification failed**
   - Ensure the nonce is being passed correctly
   - Check that the nonce action matches

2. **Professional not found**
   - Verify the professional ID exists
   - Check that the post type is 'ycp_professional'

3. **Invalid date format**
   - Use Y-m-d format (e.g., '2024-01-15')
   - Validate dates before passing to functions

4. **AJAX not working**
   - Check that jQuery is loaded
   - Verify the AJAX URL is correct
   - Check browser console for errors

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Support

For additional support or feature requests, please refer to the plugin documentation or contact the plugin developer. 