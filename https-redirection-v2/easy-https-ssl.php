<?php
/*
Plugin Name: Easy HTTPS (SSL) Redirection v2
Plugin URI: https://www.tipsandtricks-hq.com/wordpress-easy-https-redirection-plugin
Description: The plugin HTTPS Redirection allows an automatic redirection to the "HTTPS" version/URL of the site.
Author: Tips and Tricks HQ
Version: 1.9.2
Author URI: https://www.tipsandtricks-hq.com/
License: GPLv2 or later
Text Domain: https-redirection
Domain Path: /languages/
 */

// Slug: ehssl

if (!defined('ABSPATH')) {
    // Exit if accessed directly.
    exit;
}

include_once 'easy-https-ssl-core.php';

// Activation hook.
register_activation_hook(__FILE__, array('Easy_HTTPS_SSL', 'plugin_activate_handler')); 

// Uninstall hook.
register_uninstall_hook(__FILE__, array('Easy_HTTPS_SSL', 'plugin_uninstall_handler'));

/**
 * Adds "Settings" link to the plugin action page
 */
function ehssl_plugin_action_links($links, $file)
{
    // Static so we don't call plugin_basename on every plugin row.
    static $this_plugin;
    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="admin.php?page=ehssl_settings">' . __('Settings', 'https_redirection') . '</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}
add_filter('plugin_action_links', 'ehssl_plugin_action_links', 10, 2);

/**
 * Additional links on the plugin page
 */
function ehssl_register_plugin_links($links, $file)
{
    $base = plugin_basename(__FILE__);
    if ($file == $base) {
        $links[] = '<a href="admin.php?page=ehssl_settings">' . __('Settings', 'https_redirection') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'ehssl_register_plugin_links', 10, 2);
