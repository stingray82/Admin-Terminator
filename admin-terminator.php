<?php
/**
 * Plugin Name: Admin Terminator
 * Plugin URI: https://github.com/stingray82/Admin-Terminator
 * Description: Install this plugin if you are an admin that doesn't want to be on a site anymore and run it will if your not the sole admin it will delete your account and then itself.
 * Author: Stingray82
 * Version: 1.0
 * License: GPLv2 or later
 * Text Domain: admin-terminator
 * Domain Path: /languages/
 */
function direct_delete_admin_user_and_self() {
    global $wpdb;

    $log_file = WP_CONTENT_DIR . '/debug-log.txt';

    // Check if the current user is logged in and has the 'administrator' role
    if (is_user_logged_in() && current_user_can('administrator')) {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;

        error_log("Direct deletion attempt for User ID: {$user_id}\n", 3, $log_file);
        error_log("Current user roles: " . implode(', ', $current_user->roles) . "\n", 3, $log_file);

        // Get all administrators
        $admin_users = get_users(array('role' => 'administrator'));
        error_log("Number of admin users: " . count($admin_users) . "\n", 3, $log_file);

        if (count($admin_users) > 1) {
            // Directly delete the current admin user from the database
            $result = $wpdb->delete($wpdb->users, array('ID' => $user_id));

            if ($result) {
                error_log("Admin account deleted directly from database: User ID {$user_id}\n", 3, $log_file);

                // Deactivate this plugin
                if (!function_exists('deactivate_plugins')) {
                    require_once ABSPATH . '/wp-admin/includes/plugin.php';
                }
                deactivate_plugins(plugin_basename(__FILE__));

                // Delete this plugin
                if (function_exists('delete_plugins')) {
                    $plugin_file = plugin_basename(__FILE__); // Get the plugin's relative path
                    $delete_result = delete_plugins([$plugin_file]);

                    if (is_wp_error($delete_result)) {
                        error_log("Failed to delete the plugin: " . $delete_result->get_error_message() . "\n", 3, $log_file);
                    } else {
                        error_log("Plugin deleted successfully.\n", 3, $log_file);
                    }
                } else {
                    error_log("delete_plugins function not available.\n", 3, $log_file);
                }

                // Redirect to the admin dashboard (or login screen if the user is deleted)
                wp_safe_redirect(admin_url());
                exit;
            } else {
                error_log("Failed to delete admin account directly from database.\n", 3, $log_file);
            }
        } else {
            error_log("Attempted to delete the only admin account.\n", 3, $log_file);
        }
    } else {
        error_log("Current user is not an admin or no user is logged in.\n", 3, $log_file);
    }
}

// Hook into admin_init to run the deletion logic silently
add_action('admin_init', 'direct_delete_admin_user_and_self');