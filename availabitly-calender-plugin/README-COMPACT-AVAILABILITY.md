# Compact Availability Display

This feature provides a compact way to display professional availability dates in ranges like "25.06. - 27.06." instead of listing individual dates.

## Features

- **Date Range Formatting**: Consecutive dates are automatically grouped into ranges
- **Multiple Display Options**: Shortcode, PHP functions, AJAX, and REST API
- **Customizable Styling**: Multiple CSS themes and custom styling options
- **Flexible Configuration**: Various attributes to control display behavior

## Shortcode Usage

### Basic Usage
```
[ycp_availability_data professional_id="11"]
```
**Output**: `25.06. - 27.06.`

### Advanced Usage
```
[ycp_availability_data 
    professional_id="11" 
    show_title="false" 
    show_count="true" 
    separator=" | " 
    css_class="ycp-availability-compact modern"]
```

## Shortcode Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `professional_id` | Required | The professional's post ID |
| `date_from` | Empty | Filter dates from this date (Y-m-d format) |
| `date_to` | Empty | Filter dates to this date (Y-m-d format) |
| `show_title` | "true" | Show professional name (true/false) |
| `show_count` | "false" | Show date count summary (true/false) |
| `separator` | "<br>" | Separator between date ranges |
| `no_dates_text` | "No availability dates found." | Text when no dates available |
| `css_class` | "ycp-availability-compact" | CSS class for styling |

## PHP Function Usage

### Basic Function
```php
$data_handler = new YCP_Data_Handler();
$data = $data_handler->get_professional_availability_compact(11);
```

### Function with Date Range
```php
$data = $data_handler->get_professional_availability_compact(
    11, 
    '2024-06-01', 
    '2024-06-30'
);
```

## Return Data Structure

```php
[
    'id' => 11,
    'professional_name' => 'Professional Name',
    'available_dates' => ['2024-06-25', '2024-06-26', '2024-06-27'],
    'available_dates_formatted' => ['25.06. - 27.06.'],
    'available_dates_ranges' => [
        [
            'start' => '2024-06-25',
            'end' => '2024-06-27',
            'start_formatted' => '25.06.',
            'end_formatted' => '27.06.',
            'display' => '25.06. - 27.06.',
            'days_count' => 3
        ]
    ],
    'profile_url' => 'https://example.com/profile',
    'description' => 'Professional description',
    'thumbnail_url' => 'https://example.com/image.jpg',
    'is_available_today' => true,
    'total_available_days' => 3
]
```

## AJAX Usage

### JavaScript Example
```javascript
jQuery.ajax({
    url: ycp_ajax.ajax_url,
    type: 'POST',
    data: {
        action: 'ycp_get_compact_availability_data',
        professional_id: 11,
        nonce: ycp_ajax.nonce
    },
    success: function(response) {
        if (response.success) {
            var ranges = response.data.available_dates_formatted;
            var display = ranges.join('<br>');
            $('#availability-display').html(display);
        }
    }
});
```

## REST API Usage

### Endpoint
```
GET /wp-json/ycp/v1/professional/{professional_id}/availability/compact
```

### Example Request
```javascript
fetch('/wp-json/ycp/v1/professional/11/availability/compact')
    .then(response => response.json())
    .then(data => {
        console.log(data.available_dates_formatted);
    });
```

## CSS Styling

### Default Styling
```css
.ycp-availability-compact {
    font-family: Arial, sans-serif;
    margin: 15px 0;
    padding: 10px;
    border: 1px solid #e0e0e0;
    border-radius: 5px;
    background-color: #f9f9f9;
}
```

### Modern Theme
```css
.ycp-availability-compact.modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
```

### Minimal Theme
```css
.ycp-availability-compact.minimal {
    background: transparent;
    border: 1px solid #ddd;
    padding: 8px 12px;
}
```

## Integration Examples

### Theme Template Integration
```php
// In single-ycp_professional.php
$professional_id = get_the_ID();
echo do_shortcode('[ycp_availability_data professional_id="' . $professional_id . '" show_title="false"]');
```

### Widget Integration
```php
// In a custom widget
echo '<div class="availability-widget">';
echo '<h3>Availability</h3>';
echo do_shortcode('[ycp_availability_data professional_id="11" css_class="ycp-availability-compact minimal"]');
echo '</div>';
```

### Custom Template
```php
function custom_availability_display($professional_id) {
    $data_handler = new YCP_Data_Handler();
    $data = $data_handler->get_professional_availability_compact($professional_id);
    
    if (empty($data['available_dates_ranges'])) {
        return;
    }
    
    echo '<div class="custom-availability">';
    foreach ($data['available_dates_ranges'] as $range) {
        echo '<div class="date-range">';
        echo '<span class="range-dates">' . esc_html($range['display']) . '</span>';
        echo '<span class="range-days">(' . $range['days_count'] . ' days)</span>';
        echo '</div>';
    }
    echo '</div>';
}
```

## Date Formatting Logic

The system automatically groups consecutive dates into ranges:

- **Single Date**: `25.06.`
- **Consecutive Dates**: `25.06. - 27.06.`
- **Multiple Ranges**: `25.06. - 27.06.<br>30.06. - 02.07.`

## Security Features

- **Nonce Verification**: All AJAX requests require valid nonces
- **Permission Checks**: User capability verification
- **Input Sanitization**: All inputs are properly sanitized
- **Output Escaping**: All outputs are properly escaped

## Performance Considerations

- **Caching**: Consider using WordPress transients for frequently accessed data
- **Database Queries**: Optimized to minimize database calls
- **Asset Loading**: CSS and JS are only loaded when needed

## Troubleshooting

### Common Issues

1. **No dates displayed**: Check if professional ID exists and has availability dates
2. **Styling not applied**: Ensure CSS is properly enqueued
3. **AJAX errors**: Verify nonce and user permissions
4. **Date format issues**: Ensure dates are in Y-m-d format

### Debug Mode
Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Changelog

### Version 1.0
- Initial release of compact availability display
- Shortcode support with multiple attributes
- PHP function integration
- AJAX and REST API endpoints
- Multiple CSS themes
- Comprehensive documentation 