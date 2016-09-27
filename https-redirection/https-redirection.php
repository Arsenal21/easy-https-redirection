<?php
/*
  Plugin Name: Easy HTTPS (SSL) Redirection
  Plugin URI:
  Description: The plugin HTTPS Redirection allows an automatic redirection to the "HTTPS" version/URL of the site.
  Author: Tips and Tricks HQ
  Version: 1.5
  Author URI: https://www.tipsandtricks-hq.com/
  License: GPLv2 or later
 */

if (!defined('ABSPATH'))exit; //Exit if accessed directly

include_once('https-rules-helper.php');
include_once('https-redirection-settings.php');

function add_httpsrdrctn_admin_menu() {
    add_submenu_page('options-general.php', 'HTTPS Redirection', 'HTTPS Redirection', 'manage_options', 'https-redirection', 'httpsrdrctn_settings_page', plugins_url("images/px.png", __FILE__), 1001);
}

function httpsrdrctn_plugin_init() {
    global $httpsrdrctn_options;
    /* Internationalization, first(!) */
    load_plugin_textdomain('https_redirection', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    if (empty($httpsrdrctn_options)) {
        $httpsrdrctn_options = get_option('httpsrdrctn_options');
    }

    //Do force resource embedded using HTTPS
    if (isset($httpsrdrctn_options['force_resources']) && $httpsrdrctn_options['force_resources'] == '1') {
        //Handle the appropriate content filters to force the static resources to use HTTPS URL
        //TODO 1
        add_filter( 'the_content', 'httpsrdrctn_the_content' );
        add_filter( 'get_the_content', 'httpsrdrctn_the_content' );
        add_filter( 'the_excerpt', 'httpsrdrctn_the_content' );
        add_filter( 'get_the_excerpt', 'httpsrdrctn_the_content' );
    }

}

function httpsrdrctn_plugin_admin_init() {
    global $httpsrdrctn_plugin_info;

    $httpsrdrctn_plugin_info = get_plugin_data(__FILE__, false);

    /* Call register settings function */
    if (isset($_GET['page']) && "https-redirection" == $_GET['page']){
        register_httpsrdrctn_settings();
    }
}

/* register settings function */
function register_httpsrdrctn_settings() {
    global $wpmu, $httpsrdrctn_options, $httpsrdrctn_plugin_info;

    $httpsrdrctn_option_defaults = array(
        'https' => 0,
        'https_domain' => 0,
        'https_pages_array' => array(),
        'force_resources' => 0,
        'plugin_option_version' => $httpsrdrctn_plugin_info["Version"]
    );

    /* Install the option defaults */
    if (1 == $wpmu) {
        if (!get_site_option('httpsrdrctn_options'))
            add_site_option('httpsrdrctn_options', $httpsrdrctn_option_defaults, '', 'yes');
    } else {
        if (!get_option('httpsrdrctn_options'))
            add_option('httpsrdrctn_options', $httpsrdrctn_option_defaults, '', 'yes');
    }

    /* Get options from the database */
    if (1 == $wpmu)
        $httpsrdrctn_options = get_site_option('httpsrdrctn_options');
    else
        $httpsrdrctn_options = get_option('httpsrdrctn_options');

    /* Array merge incase this version has added new options */
    if (!isset($httpsrdrctn_options['plugin_option_version']) || $httpsrdrctn_options['plugin_option_version'] != $httpsrdrctn_plugin_info["Version"]) {
        $httpsrdrctn_options = array_merge($httpsrdrctn_option_defaults, $httpsrdrctn_options);
        $httpsrdrctn_options['plugin_option_version'] = $httpsrdrctn_plugin_info["Version"];
        update_option('httpsrdrctn_options', $httpsrdrctn_options);
    }
}

function httpsrdrctn_plugin_action_links($links, $file) {
    /* Static so we don't call plugin_basename on every plugin row. */
    static $this_plugin;
    if (!$this_plugin)
        $this_plugin = plugin_basename(__FILE__);

    if ($file == $this_plugin) {
        $settings_link = '<a href="admin.php?page=https-redirection">' . __('Settings', 'https_redirection') . '</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
}


if (!function_exists('httpsrdrctn_register_plugin_links')) {

    function httpsrdrctn_register_plugin_links($links, $file) {
        $base = plugin_basename(__FILE__);
        if ($file == $base) {
            $links[] = '<a href="admin.php?page=https-redirection">' . __('Settings', 'https_redirection') . '</a>';
        }
        return $links;
    }

}

/* 
 * Function that changes "http" embeds to "https" 
 * TODO - Need to make it better so it only does it for static resources like JS, CSS and Images
 */
function httpsrdrctn_the_content($content) {
    global $httpsrdrctn_options;
    if (empty($httpsrdrctn_options)) {
        $httpsrdrctn_options = get_option('httpsrdrctn_options');
    }
    if ($httpsrdrctn_options['force_resources'] == '1' && $httpsrdrctn_options['https'] == 1) {
        if ($httpsrdrctn_options['https_domain'] == 1) {
            if (strpos(home_url(), 'https') !== false) {
                $http_domain = str_replace('https', 'http', home_url());
                $https_domain = home_url();
            } else {
                $http_domain = home_url();
                $https_domain = str_replace('http', 'https', home_url());
            }
            $content = str_replace($http_domain, $https_domain, $content);
        } else if (!empty($httpsrdrctn_options['https_pages_array'])) {
            foreach ($httpsrdrctn_options['https_pages_array'] as $https_page) {
                if (strpos(home_url(), 'https') !== false) {
                    $http_domain = str_replace('https', 'http', home_url());
                    $https_domain = home_url();
                } else {
                    $http_domain = home_url();
                    $https_domain = str_replace('http', 'https', home_url());
                }
                $content = str_replace($http_domain . '/' . $https_page, $https_domain . '/' . $https_page, $content);
            }
        }
    }
    return $content;
}

if (!function_exists('httpsrdrctn_admin_head')) {

    function httpsrdrctn_admin_head() {
        if (isset($_REQUEST['page']) && 'https-redirection' == $_REQUEST['page']) {
            wp_enqueue_style('httpsrdrctn_stylesheet', plugins_url('css/style.css', __FILE__));
            wp_enqueue_script('httpsrdrctn_script', plugins_url('js/script.js', __FILE__));
        }
    }

}

/* Function for delete delete options */
if (!function_exists('httpsrdrctn_delete_options')) {

    function httpsrdrctn_delete_options() {
        delete_option('httpsrdrctn_options');
        delete_site_option('httpsrdrctn_options');
    }

}

add_action('admin_menu', 'add_httpsrdrctn_admin_menu');
add_action('init', 'httpsrdrctn_plugin_init');
add_action('admin_init', 'httpsrdrctn_plugin_admin_init');
add_action('admin_enqueue_scripts', 'httpsrdrctn_admin_head');

/* Adds "Settings" link to the plugin action page */
add_filter('plugin_action_links', 'httpsrdrctn_plugin_action_links', 10, 2);
/* Additional links on the plugin page */
add_filter('plugin_row_meta', 'httpsrdrctn_register_plugin_links', 10, 2);
//add_filter('mod_rewrite_rules', 'httpsrdrctn_mod_rewrite_rules');//TODO 5

register_uninstall_hook(__FILE__, 'httpsrdrctn_delete_options');
