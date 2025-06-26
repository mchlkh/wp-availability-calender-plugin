# Availability Calendar Plugin - Overview UI Documentation

This document explains how to use the availability overview UI components to display availability data in various formats.

## Table of Contents

1. [Overview](#overview)
2. [Available Views](#available-views)
3. [Shortcodes](#shortcodes)
4. [PHP Functions](#php-functions)
5. [JavaScript API](#javascript-api)
6. [Usage Examples](#usage-examples)
7. [Customization](#customization)
8. [Integration](#integration)

## Overview

The Overview UI provides multiple ways to display availability data:

- **Calendar View**: Interactive calendar showing availability by date
- **List View**: Chronological list of available dates and professionals
- **Chart View**: Visual charts and statistics
- **Summary View**: Key metrics and overview cards

## Available Views

### 1. Calendar View
- Interactive calendar grid
- Color-coded availability indicators
- Click to view details
- Month navigation
- Configurable date range

### 2. List View
- Chronological list of available dates
- Professional details for each date
- Expandable sections
- Clean, readable format

### 3. Chart View
- Visual charts using Chart.js
- Availability statistics
- Doughnut chart showing availability ratio
- Key metrics display

### 4. Summary View
- Overview cards with key metrics
- Top available professionals
- Quick statistics
- Professional rankings

## Shortcodes

### Main Overview Shortcode
```php
[ycp_availability_overview]
```

**Attributes:**
- `view`: calendar, list, chart, summary (default: calendar)
- `date_range`: Number of days to show (default: 30)
- `show_today`: Show today's date (true/false, default: true)
- `show_weekends`: Show weekend dates (true/false, default: true)
- `highlight_today`: Highlight today's date (true/false, default: true)
- `auto_refresh`: Auto-refresh data (true/false, default: false)
- `refresh_interval`: Refresh interval in milliseconds (default: 300000)
- `class`: Additional CSS classes
- `id`: Custom container ID

### Specific View Shortcodes
```php
// Calendar view
[ycp_availability_calendar date_range="60" highlight_today="true"]

// Summary view
[ycp_availability_summary auto_refresh="true"]

// Chart view
[ycp_availability_chart]

// List view
[ycp_availability_list date_range="90"]
```

## PHP Functions

### Theme Integration Functions

#### `ycp_render_availability_overview($options)`
Render overview in themes.

```php
// Basic usage
echo ycp_render_availability_overview();

// With options
echo ycp_render_availability_overview([
    'view' => 'summary',
    'show_today_only' => false,
    'limit' => 10,
    'template' => 'default'
]);
```

#### `ycp_get_overview_data($options)`
Get overview data for custom rendering.

```php
$data = ycp_get_overview_data([
    'limit' => 50
]);

echo 'Total professionals: ' . $data['total_professionals'];
echo 'Available today: ' . $data['available_today'];
```

### Options Reference

#### Overview Options
- `view`: calendar, list, chart, summary, grid
- `show_today_only`: Show only today's availability
- `limit`: Maximum number of professionals to show
- `template`: default, minimal, custom

#### Calendar Options
- `date_range`: Number of days to display
- `show_today`: Highlight today's date
- `show_weekends`: Include weekend dates
- `highlight_today`: Special styling for today

## JavaScript API

### AvailabilityOverview Class

```javascript
// Create overview instance
const overview = new AvailabilityOverview('#container', {
    view: 'calendar',
    dateRange: 30,
    showToday: true,
    showWeekends: true,
    highlightToday: true,
    autoRefresh: false,
    refreshInterval: 300000
});
```

### Methods

#### `loadData()`
Reload availability data.

```javascript
overview.loadData();
```

#### `render()`
Re-render the current view.

```javascript
overview.render();
```

### Events

```javascript
// Listen for data loaded event
$('#container').on('ycp:dataLoaded', function(event, data) {
    console.log('Data loaded:', data);
});

// Listen for date clicked event
$('#container').on('ycp:dateClicked', function(event, date, professionals) {
    console.log('Date clicked:', date, professionals);
});
```

## Usage Examples

### 1. Basic Calendar Overview
```php
// In page/post content
[ycp_availability_calendar date_range="60" highlight_today="true"]
```

### 2. Summary Dashboard
```php
// Show summary with auto-refresh
[ycp_availability_summary auto_refresh="true" refresh_interval="60000"]
```

### 3. Theme Integration
```php
// In theme template
<?php
// Show today's availability in header
echo ycp_render_availability_overview([
    'show_today_only' => true,
    'template' => 'minimal'
]);

// Show full overview in sidebar
echo ycp_render_availability_overview([
    'view' => 'summary',
    'limit' => 5
]);
?>
```

### 4. Custom JavaScript Widget
```javascript
// Create custom overview widget
const widget = new AvailabilityOverview('#availability-widget', {
    view: 'calendar',
    dateRange: 30,
    autoRefresh: true,
    refreshInterval: 300000
});

// Handle date clicks
$('#availability-widget').on('click', '.calendar-day', function() {
    const date = $(this).data('date');
    console.log('Selected date:', date);
});
```

### 5. Admin Dashboard
The overview automatically appears in the WordPress admin dashboard, showing:
- Total professionals
- Available today
- Average availability
- Top available professionals

## Customization

### CSS Customization

The overview uses CSS classes for styling:

```css
/* Custom calendar styling */
.ycp-availability-calendar {
    background: #f8f9fa;
    border-radius: 12px;
}

.calendar-day.has-availability {
    background: linear-gradient(135deg, #28a745, #20c997);
}

/* Custom summary cards */
.summary-card {
    background: linear-gradient(135deg, #007bff, #6610f2);
}
```

### Template Customization

Create custom templates by overriding the render methods:

```php
// In your theme's functions.php
add_filter('ycp_overview_template', function($template, $view, $data) {
    if ($view === 'summary') {
        // Custom summary template
        return 'custom-summary-template.php';
    }
    return $template;
}, 10, 3);
```

### JavaScript Customization

Extend the AvailabilityOverview class:

```javascript
class CustomAvailabilityOverview extends AvailabilityOverview {
    constructor(containerSelector, options) {
        super(containerSelector, options);
        this.customMethod();
    }
    
    customMethod() {
        // Custom functionality
    }
    
    renderCalendar() {
        // Custom calendar rendering
        super.renderCalendar();
        this.addCustomFeatures();
    }
}
```

## Integration

### 1. WordPress Theme Integration

```php
// In header.php
<?php if (function_exists('ycp_render_availability_overview')): ?>
    <div class="header-availability">
        <?php echo ycp_render_availability_overview([
            'show_today_only' => true,
            'template' => 'minimal'
        ]); ?>
    </div>
<?php endif; ?>

// In sidebar.php
<?php if (function_exists('ycp_render_availability_overview')): ?>
    <div class="sidebar-availability">
        <?php echo ycp_render_availability_overview([
            'view' => 'summary',
            'limit' => 10
        ]); ?>
    </div>
<?php endif; ?>
```

### 2. Page Builder Integration

#### Elementor
```php
// Register custom widget
class YCP_Overview_Widget extends \Elementor\Widget_Base {
    protected function render() {
        echo do_shortcode('[ycp_availability_overview view="summary"]');
    }
}
```

#### Gutenberg
```php
// Register block
register_block_type('ycp/availability-overview', [
    'render_callback' => function($attributes) {
        return do_shortcode('[ycp_availability_overview view="' . $attributes['view'] . '"]');
    }
]);
```

### 3. WooCommerce Integration

```php
// Add to product pages
add_action('woocommerce_single_product_summary', function() {
    echo '<div class="product-availability">';
    echo ycp_render_availability_overview([
        'view' => 'summary',
        'limit' => 5
    ]);
    echo '</div>';
}, 25);
```

### 4. Custom Post Types

```php
// Add to custom post type templates
add_action('ycp_professional_single', function() {
    $professional_id = get_the_ID();
    $data = ycp_get_professional_availability($professional_id);
    
    echo '<div class="professional-availability">';
    echo '<h3>Availability Overview</h3>';
    echo '<p>Available Days: ' . $data['total_available_days'] . '</p>';
    echo '<p>Available Today: ' . ($data['is_available_today'] ? 'Yes' : 'No') . '</p>';
    echo '</div>';
});
```

## Configuration

### Admin Settings

The overview UI can be configured through WordPress admin:

1. Go to **Settings > Availability Calendar**
2. Configure overview settings:
   - Default view
   - Date range
   - Auto-refresh settings
   - Display options

### Performance Optimization

```php
// Cache overview data
add_filter('ycp_overview_cache_enabled', '__return_true');
add_filter('ycp_overview_cache_duration', function() {
    return 300; // 5 minutes
});

// Limit data for better performance
add_filter('ycp_overview_limit', function() {
    return 50; // Maximum 50 professionals
});
```

## Troubleshooting

### Common Issues

1. **Overview not loading**
   - Check if jQuery is loaded
   - Verify AJAX URL is correct
   - Check browser console for errors

2. **Calendar not displaying**
   - Ensure CSS is loaded
   - Check for JavaScript conflicts
   - Verify data is being returned

3. **Performance issues**
   - Reduce date range
   - Limit number of professionals
   - Enable caching

### Debug Mode

```php
// Enable debug mode
add_filter('ycp_overview_debug', '__return_true');

// Check data in browser console
console.log('YCP Overview Data:', ycp_overview);
```

## Best Practices

### 1. Performance
- Use appropriate limits for large datasets
- Enable caching for frequently accessed data
- Optimize date ranges for your use case

### 2. User Experience
- Provide loading states
- Handle errors gracefully
- Use appropriate views for different contexts

### 3. Security
- Always validate and sanitize inputs
- Use nonces for AJAX requests
- Escape all output

### 4. Accessibility
- Ensure keyboard navigation
- Provide alt text for images
- Use semantic HTML structure

## Support

For additional support or feature requests, please refer to the plugin documentation or contact the plugin developer. 