<?php
/**
 * IMGVerse Uninstall Script
 * 
 * @package IMGVerse
 * @author Krafty Sprouts Media, LLC
 * @version 1.3.0
 * @since 1.0.0
 * @last_modified 10/24/2025
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check user permissions
if (!current_user_can('activate_plugins')) {
    exit;
}

// Include the main plugin file to access the uninstall method
if (file_exists(WP_PLUGIN_DIR . '/imgverse/imgverse.php')) {
    require_once WP_PLUGIN_DIR . '/imgverse/imgverse.php';
    
    // Call the static uninstall method
    if (class_exists('IMGV_Core')) {
        IMGV_Core::uninstall();
    }
}

// Additional cleanup (in case the class method doesn't run)
global $wpdb;

// Remove cache table
$table_name = $wpdb->prefix . 'imgv_cache';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Remove all plugin options
delete_option('imgv_settings');
delete_option('imgv_cache_stats');
delete_option('imgv_version');

// Remove any transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_imgv_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_imgv_%'");

// Clear scheduled events
wp_clear_scheduled_hook('imgv_cleanup_cache');

// Remove any user meta related to the plugin
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'imgv_%'");

// Log the uninstall (optional)
error_log('IMGVerse plugin uninstalled successfully');
