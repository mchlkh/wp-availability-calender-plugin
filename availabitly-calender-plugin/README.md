# Availability Calendar Plugin

A comprehensive WordPress plugin that displays available professionals on a calendar with multiple integration options, REST API support, and advanced customization features.

## 📋 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Installation](#installation)
4. [Quick Start](#quick-start)
5. [Integration Options](#integration-options)
6. [API Documentation](#api-documentation)
7. [Shortcodes](#shortcodes)
8. [Theme Integration](#theme-integration)
9. [Customization](#customization)
10. [Advanced Usage](#advanced-usage)
11. [Troubleshooting](#troubleshooting)
12. [Changelog](#changelog)

## 🎯 Overview

The Availability Calendar Plugin provides a complete solution for displaying professional availability in WordPress. It features:

- **Interactive Calendar**: Full-featured calendar with date selection
- **Multiple Views**: Calendar, list, chart, and summary views
- **REST API**: Complete API for external integrations
- **AJAX Support**: Dynamic data loading
- **Responsive Design**: Mobile-friendly layouts
- **Customization**: Extensive styling and configuration options

## ✨ Features

### Core Features
- ✅ **Professional Management**: Custom post type with availability dates
- ✅ **Interactive Calendar**: Date picker with availability indicators
- ✅ **Multiple Display Views**: Calendar, list, chart, summary, grid
- ✅ **REST API**: Full API with authentication and validation
- ✅ **AJAX Integration**: Dynamic data loading without page refresh
- ✅ **Shortcodes**: Easy integration with content
- ✅ **Theme Integration**: PHP functions for custom themes
- ✅ **Responsive Design**: Mobile-friendly layouts
- ✅ **Color Customization**: Admin panel for theme colors
- ✅ **Security**: Nonce verification, input validation, XSS protection

### Advanced Features
- ✅ **Compact Availability**: Date range formatting (e.g., "25.06. - 27.06.")
- ✅ **Overview Dashboard**: Admin widget with statistics
- ✅ **Auto-refresh**: Configurable data refresh intervals
- ✅ **Date Filtering**: Range-based availability queries
- ✅ **Professional Profiles**: Individual professional pages
- ✅ **Export Options**: Data export capabilities
- ✅ **Caching**: Performance optimization
- ✅ **Internationalization**: Multi-language support

## 🚀 Installation

### Requirements
- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

### Installation Steps
1. Upload the plugin files to `/wp-content/plugins/availability-calendar-plugin/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to 'Availabilities' → 'Calendar Settings' to configure
4. Add professionals and their availability dates

## ⚡ Quick Start

### 1. Add a Professional
1. Go to **Availabilities** → **Add New**
2. Enter professional name and details
3. Set availability dates in the meta box
4. Publish the professional

### 2. Display on Your Site

#### Option A: Shortcode (Easiest)
```php
// Full calendar view
[ycp_calendar]

// Today's availability only
[ycp_today_simple]

// Overview with statistics
[ycp_availability_overview view="summary"]
```

#### Option B: PHP Function
```php
// In your theme template
if (function_exists('ycp_render_availability_overview')) {
    echo ycp_render_availability_overview([
        'view' => 'calendar',
        'limit' => 10
    ]);
}
```

## 🔌 Integration Options

### 1. Full Integration (Interactive Calendar)
Perfect for booking systems and scheduling interfaces.

```php
[ycp_calendar]
```

**Features:**
- Interactive calendar picker
- Date selection for availability checking
- Professional cards with details
- Responsive grid layout
- Hover effects and profile links

### 2. Simple Integration (Today Only)
Perfect for reception areas and "who's in today" displays.

```php
[ycp_today_simple]
```

**Features:**
- Shows only today's available professionals
- Same styling as full integration
- No calendar picker
- Clean, focused display

### 3. Overview Integration
Perfect for dashboards and statistics displays.

```php
[ycp_availability_overview view="summary"]
```

**Features:**
- Key metrics and statistics
- Professional rankings
- Auto-refresh capability
- Multiple view options

## 📚 API Documentation

### REST API Endpoints

#### Base URL
```
/wp-json/ycp/v1/
```

#### Available Endpoints

**1. Get Professional Availability**
```
GET /availability/{professional_id}
```

**Parameters:**
- `professional_id` (required): Professional ID
- `date_from` (optional): Start date (Y-m-d)
- `date_to` (optional): End date (Y-m-d)

**Example:**
```bash
curl "https://yoursite.com/wp-json/ycp/v1/availability/123?date_from=2024-01-01&date_to=2024-01-31"
```

**2. Get All Availability**
```
GET /availability
```

**Parameters:**
- `date` (optional): Specific date (Y-m-d)
- `limit` (optional): Maximum results (default: 100)
- `professional_id` (optional): Filter by professional

**3. Get Professionals List**
```
GET /professionals
```

**Parameters:**
- `limit` (optional): Maximum results (default: 100)
- `available_today` (optional): Filter by today's availability

**4. Get Overview Data**
```
GET /overview
```

**Parameters:**
- `limit` (optional): Maximum results (default: 100)

### AJAX API

**Endpoint:** `admin-ajax.php`

**Action:** `ycp_get_availability_data`

**Security:** Requires nonce verification

**Example:**
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

### PHP Functions

#### Core Functions

**`ycp_get_professional_availability($professional_id, $date_from = '', $date_to = '')`**
```php
$data = ycp_get_professional_availability(123);
echo $data['name']; // Professional name
echo $data['total_available_days']; // Available days count
```

**`ycp_get_availability_by_date($date = '', $limit = 50)`**
```php
$data = ycp_get_availability_by_date('2024-01-15');
echo $data['count']; // Number of available professionals
```

**`ycp_get_all_professionals_availability($limit = 100)`**
```php
$data = ycp_get_all_professionals_availability();
foreach ($data['professionals'] as $professional) {
    echo $professional['name'] . ' - ' . $professional['total_available_days'] . ' days';
}
```

#### Overview Functions

**`ycp_render_availability_overview($options)`**
```php
echo ycp_render_availability_overview([
    'view' => 'summary',
    'show_today_only' => false,
    'limit' => 10,
    'template' => 'default'
]);
```

**`ycp_get_overview_data($options)`**
```php
$data = ycp_get_overview_data(['limit' => 50]);
echo 'Total professionals: ' . $data['total_professionals'];
echo 'Available today: ' . $data['available_today'];
```

## 🎨 Shortcodes

### Main Shortcodes

**Full Calendar**
```php
[ycp_calendar]
```

**Today's Availability**
```php
[ycp_today_simple]
```

**Overview with Options**
```php
[ycp_availability_overview view="summary" auto_refresh="true"]
```

### Compact Availability

**Basic Usage**
```php
[ycp_availability_data professional_id="11"]
```

**Advanced Usage**
```php
[ycp_availability_data 
    professional_id="11" 
    show_title="false" 
    show_count="true" 
    separator=" | " 
    css_class="ycp-availability-compact modern"]
```

### Shortcode Attributes

| Shortcode | Attribute | Default | Description |
|-----------|-----------|---------|-------------|
| `ycp_calendar` | `date_range` | 30 | Days to display |
| `ycp_calendar` | `highlight_today` | true | Highlight today |
| `ycp_availability_overview` | `view` | calendar | View type |
| `ycp_availability_overview` | `auto_refresh` | false | Auto-refresh |
| `ycp_availability_data` | `professional_id` | Required | Professional ID |
| `ycp_availability_data` | `show_title` | true | Show professional name |
| `ycp_availability_data` | `show_count` | false | Show date count |

## 🎭 Theme Integration

### 1. Header Integration
```php
// In header.php
if (function_exists('ycp_render_availability_overview')) {
    echo '<div class="header-availability">';
    echo ycp_render_availability_overview([
        'show_today_only' => true,
        'template' => 'minimal'
    ]);
    echo '</div>';
}
```

### 2. Sidebar Widget
```php
// In sidebar.php
echo '<div class="sidebar-availability">';
echo '<h3>Today\'s Availability</h3>';
echo do_shortcode('[ycp_today_simple]');
echo '</div>';
```

### 3. Page Template
```php
// In page template
if (is_page('availability')) {
    echo '<div class="availability-page">';
    echo '<h2>Professional Availability</h2>';
    echo do_shortcode('[ycp_calendar date_range="60"]');
    echo '</div>';
}
```

### 4. Professional Profile
```php
// In single-ycp_professional.php
$professional_id = get_the_ID();
echo '<div class="professional-availability">';
echo do_shortcode('[ycp_availability_data professional_id="' . $professional_id . '" show_title="false"]');
echo '</div>';
```

## 🎨 Customization

### Color Settings

Access **Availabilities** → **Calendar Colors** to customize:

- **Primary Color**: Main calendar color
- **Secondary Color**: Highlight color
- **Accent Color**: Special highlights
- **Text Colors**: Various text elements
- **Theme Integration**: Use theme colors

### CSS Customization

**Default Classes:**
```css
.ycp-calendar-container { /* Main calendar container */ }
.ycp-professional-card { /* Professional cards */ }
.ycp-availability-compact { /* Compact availability */ }
.ycp-overview-summary { /* Overview statistics */ }
```

**Custom Styling Example:**
```css
.ycp-professional-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}
```

### JavaScript Customization

**Event Listeners:**
```javascript
// Data loaded event
$('#calendar-container').on('ycp:dataLoaded', function(event, data) {
    console.log('Data loaded:', data);
});

// Date clicked event
$('#calendar-container').on('ycp:dateClicked', function(event, date, professionals) {
    console.log('Date clicked:', date, professionals);
});
```

## 🔧 Advanced Usage

### 1. Custom Widget
```php
class Custom_Availability_Widget extends WP_Widget {
    public function widget($args, $instance) {
        echo $args['before_widget'];
        echo $args['before_title'] . 'Availability' . $args['after_title'];
        
        if (function_exists('ycp_render_availability_overview')) {
            echo ycp_render_availability_overview([
                'view' => 'summary',
                'limit' => 5
            ]);
        }
        
        echo $args['after_widget'];
    }
}
```

### 2. REST API Client
```javascript
// Get availability data
fetch('/wp-json/ycp/v1/availability/123')
    .then(response => response.json())
    .then(data => {
        console.log('Professional:', data.name);
        console.log('Available days:', data.total_available_days);
    });
```

### 3. Conditional Display
```php
// Show availability only on specific pages
if (is_page('booking') || is_page('schedule')) {
    echo do_shortcode('[ycp_calendar]');
}

// Show different views based on user role
if (current_user_can('manage_options')) {
    echo do_shortcode('[ycp_availability_overview view="chart"]');
} else {
    echo do_shortcode('[ycp_today_simple]');
}
```

### 4. Custom Data Processing
```php
// Get and process availability data
$data = ycp_get_all_professionals_availability();

// Filter by availability count
$highly_available = array_filter($data['professionals'], function($professional) {
    return $professional['total_available_days'] > 20;
});

// Display filtered results
foreach ($highly_available as $professional) {
    echo '<div class="high-availability">';
    echo '<h3>' . esc_html($professional['name']) . '</h3>';
    echo '<p>' . esc_html($professional['total_available_days']) . ' days available</p>';
    echo '</div>';
}
```

## 🛠️ Troubleshooting

### Common Issues

**1. Shortcode Not Working**
- Ensure plugin is activated
- Check for JavaScript errors in browser console
- Verify shortcode syntax

**2. No Data Displayed**
- Check if professionals have availability dates set
- Verify date format (Y-m-d)
- Check browser console for AJAX errors

**3. Styling Issues**
- Clear browser cache
- Check for theme CSS conflicts
- Verify color settings in admin panel

**4. REST API Errors**
- Check if REST API is enabled
- Verify endpoint URLs
- Check authentication requirements

### Debug Mode

Enable WordPress debug mode to see detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Support

For additional support:
1. Check the browser console for JavaScript errors
2. Review WordPress debug log
3. Verify plugin settings in admin panel
4. Test with default WordPress theme

## 📝 Changelog

### Version 1.0.0 (Current)
- ✅ **Complete Refactoring**: Modular, object-oriented architecture
- ✅ **REST API**: Full API with authentication and validation
- ✅ **Settings Manager**: Comprehensive admin settings
- ✅ **Overview UI**: Multiple view options and statistics
- ✅ **Professional Manager**: Enhanced CPT management
- ✅ **Data Handler**: Optimized data operations
- ✅ **AJAX Handler**: Improved request processing
- ✅ **Display Handler**: Separated rendering logic
- ✅ **Security**: Enhanced security measures
- ✅ **Documentation**: Comprehensive documentation

### Previous Versions
- Initial plugin development
- Basic calendar functionality
- Professional management features
- Shortcode implementation

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🤝 Contributing

Contributions are welcome! Please ensure:
- Code follows WordPress coding standards
- Security best practices are maintained
- Documentation is updated
- Tests are included for new features

---

**Need Help?** Check the troubleshooting section or review the API documentation for detailed usage examples. 