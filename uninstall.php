<?php
/**
 * Uninstall file for Cloudflare Responsive Images plugin
 * 
 * This file is executed when the plugin is deleted.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin options
delete_option('cfri_options');
delete_option('cfri_stats');

// Remove any transients
delete_transient('cfri_connection_test');
delete_transient('cfri_stats_cache');

// Clean up any scheduled events
wp_clear_scheduled_hook('cfri_cleanup_old_sizes');
wp_clear_scheduled_hook('cfri_update_stats');

// Note: We don't delete the actual image files as they might be needed
// The user can manually clean them up if desired
