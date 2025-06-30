# Availability Calendar Plugin - Documentation

Welcome to the comprehensive documentation for the Availability Calendar Plugin. This documentation covers all aspects of the plugin from basic usage to advanced customization.

## 📚 Documentation Index

### 🚀 Getting Started
- **[Main README](../README.md)** - Complete plugin overview and quick start guide
- **[Implementation Summary](IMPLEMENTATION-SUMMARY.md)** - Technical implementation details

### 🔌 Integration Guides
- **[Integration Guide](README-INTEGRATIONS.md)** - How to integrate the plugin into your site
- **[Overview UI Documentation](README-OVERVIEW-UI.md)** - Using the overview components
- **[Compact Availability](README-COMPACT-AVAILABILITY.md)** - Compact date range display

### 🔧 API Reference
- **[API Documentation](README-AVAILABILITY-API.md)** - Complete REST API and PHP function reference

### 📖 Quick Reference

#### Essential Shortcodes
```php
// Full calendar integration
[ycp_calendar]

// Today's availability only
[ycp_today_simple]

// Overview with statistics
[ycp_availability_overview view="summary"]

// Compact availability display
[ycp_availability_data professional_id="11"]
```

#### Core PHP Functions
```php
// Get professional availability
$data = ycp_get_professional_availability(123);

// Get availability by date
$data = ycp_get_availability_by_date('2024-01-15');

// Render overview
echo ycp_render_availability_overview(['view' => 'summary']);
```

#### REST API Endpoints
```bash
# Get professional availability
GET /wp-json/ycp/v1/availability/{professional_id}

# Get all availability
GET /wp-json/ycp/v1/availability

# Get professionals list
GET /wp-json/ycp/v1/professionals

# Get overview data
GET /wp-json/ycp/v1/overview
```

## 🎯 Use Cases

### For Content Creators
- Use shortcodes to display availability in posts and pages
- Customize appearance with shortcode attributes
- Choose between full calendar and simple displays

### For Theme Developers
- Use PHP functions for custom integrations
- Access REST API for dynamic content
- Customize styling with CSS classes

### For Plugin Developers
- Extend functionality with WordPress hooks
- Integrate with other plugins via REST API
- Create custom widgets and components

## 🔧 Development

### Plugin Architecture
The plugin follows a modular, object-oriented architecture:

- **`YCP_Professional_Manager`** - Custom post type and meta management
- **`YCP_Data_Handler`** - Core data operations and queries
- **`YCP_Ajax_Handler`** - AJAX request processing
- **`YCP_Display_Handler`** - HTML rendering and templates
- **`YCP_Overview_UI`** - Overview components and statistics
- **`YCP_Settings_Manager`** - Admin settings and configuration
- **`YCP_REST_API_Handler`** - REST API endpoints

### File Structure
```
availabitly-calender-plugin/
├── README.md                    # Main documentation
├── availabitly-calender-plugin.php  # Main plugin file
├── includes/                    # Core classes
│   ├── class-professional-manager.php
│   ├── class-data-handler.php
│   ├── class-ajax-handler.php
│   ├── class-display-handler.php
│   ├── class-overview-ui.php
│   ├── class-settings-manager.php
│   └── class-rest-api-handler.php
├── admin/                       # Admin interface
├── public/                      # Frontend assets
├── docs/                        # Documentation
│   ├── README.md               # This file
│   ├── IMPLEMENTATION-SUMMARY.md
│   ├── README-INTEGRATIONS.md
│   ├── README-OVERVIEW-UI.md
│   ├── README-COMPACT-AVAILABILITY.md
│   └── README-AVAILABILITY-API.md
└── vendor/                      # Dependencies
```

## 🛠️ Support

### Getting Help
1. **Check the main README** for quick start and troubleshooting
2. **Review API documentation** for technical details
3. **Test with default theme** to isolate issues
4. **Enable debug mode** for detailed error messages

### Common Issues
- **Shortcode not working**: Check plugin activation and syntax
- **No data displayed**: Verify professionals have availability dates
- **Styling issues**: Clear cache and check theme conflicts
- **API errors**: Verify REST API is enabled and endpoints are correct

### Debug Mode
Enable WordPress debug mode for detailed error messages:

```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 📝 Contributing

When contributing to the documentation:

1. **Follow the existing structure** and formatting
2. **Include code examples** for all features
3. **Update all related files** when making changes
4. **Test all examples** before submitting
5. **Maintain consistency** with existing documentation

## 🔄 Version History

### Documentation Updates
- **v1.0.0**: Complete documentation overhaul and consolidation
- **Previous**: Individual README files for each component
- **Future**: Continuous updates with plugin development

---

**Need specific help?** Check the relevant documentation file above or refer to the main README for comprehensive guidance. 