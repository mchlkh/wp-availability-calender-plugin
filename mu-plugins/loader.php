<?php
declare(strict_types=1);

// Ensure WordPress is loaded
if (!defined('ABSPATH')) {
    return;
}

// Load the availability calendar plugin as a must-use plugin for testing
$plugin_main = WP_CONTENT_DIR . '/plugins/availabitly-calender-plugin/availabitly-calender-plugin.php';
if (file_exists($plugin_main)) {
    require_once $plugin_main;
}

// Ensure DB table exists even without activation hook
if (class_exists('YCP_Professional_Manager')) {
    YCP_Professional_Manager::create_table();
}


