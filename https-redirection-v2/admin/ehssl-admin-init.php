<?php
/**
 * Inits the admin dashboard side of things.
 * Main admin file which loads all settings panels and sets up admin menus.
 */
class EHSSL_Admin_Init
{
    public $main_menu_page;
    public $dashboard_menu;
    public $settings_menu;

    public function __construct()
    {
        $this->admin_includes();
        add_action('admin_print_scripts', array(&$this, 'admin_menu_page_scripts'));
        add_action('admin_print_styles', array(&$this, 'admin_menu_page_styles'));
        add_action('admin_menu', array(&$this, 'create_admin_menus'));
        add_action('admin_init', array(&$this, 'plugin_admin_init'));
        add_action('admin_enqueue_scripts', array(&$this, 'plugin_admin_head'));
    }

    public function admin_includes()
    {
        include_once 'ehssl-admin-menu.php';
        include_once EASY_HTTPS_SSL_PATH. '/classes/ehssl-rules-helper.php';
    }

    public function admin_menu_page_scripts()
    {
        // Make sure we are on the appropriate menu page.
        if (isset($_GET['page']) && strpos($_GET['page'], EHSSL_MENU_SLUG_PREFIX) !== false) {
            wp_enqueue_script('postbox');
            wp_enqueue_script('dashboard');
            wp_enqueue_script('thickbox');
            wp_enqueue_script('media-upload');
        }
    }

    public function admin_menu_page_styles()
    {
        // Make sure we are on the appropriate menu page.
        if (isset($_GET['page']) && strpos($_GET['page'], EHSSL_MENU_SLUG_PREFIX) !== false) {
            wp_enqueue_style('dashboard');
            wp_enqueue_style('thickbox');
            wp_enqueue_style('global');
            wp_enqueue_style('wp-admin');
            wp_enqueue_style('ehssl-admin-css', EASY_HTTPS_SSL_URL . '/css/ehssl-admin-styles.css');
        }
    }

    public function create_admin_menus()
    {
        $menu_icon_url = EASY_HTTPS_SSL_URL . '/images/plugin-icon.png';
        $this->main_menu_page = add_menu_page(__('Easy HTTPS & SSL', EHSSL_TEXT_DOMAIN), __('Easy HTTPS & SSL', EHSSL_TEXT_DOMAIN), EHSSL_MANAGEMENT_PERMISSION, EHSSL_MAIN_MENU_SLUG, array(&$this, 'handle_dashboard_menu_rendering'), $menu_icon_url);
        add_submenu_page( 'options-general.php', __('HTTPS Redirection',EHSSL_TEXT_DOMAIN), __('HTTPS Redirection',EHSSL_TEXT_DOMAIN), EHSSL_MANAGEMENT_PERMISSION, 'https-redirection', array(&$this, 'handle_settings_menu_rendering_old') );
        add_submenu_page(EHSSL_MAIN_MENU_SLUG, __('Dashboard', EHSSL_TEXT_DOMAIN), __('Dashboard', EHSSL_TEXT_DOMAIN), EHSSL_MANAGEMENT_PERMISSION, EHSSL_MAIN_MENU_SLUG, array(&$this, 'handle_dashboard_menu_rendering'));
        add_submenu_page(EHSSL_MAIN_MENU_SLUG, __('Settings', EHSSL_TEXT_DOMAIN), __('Settings', EHSSL_TEXT_DOMAIN), EHSSL_MANAGEMENT_PERMISSION, EHSSL_SETTINGS_MENU_SLUG, array(&$this, 'handle_settings_menu_rendering'));
        add_submenu_page(EHSSL_MAIN_MENU_SLUG, __('Auto SSL', EHSSL_TEXT_DOMAIN), __('Auto SSL', EHSSL_TEXT_DOMAIN), EHSSL_MANAGEMENT_PERMISSION, EHSSL_AUTOSSL_MENU_SLUG, array(&$this, 'handle_autossl_menu_rendering'));
        do_action('ehssl_admin_menu_created');
    }

    public function handle_dashboard_menu_rendering()
    {
        include_once EASY_HTTPS_SSL_PATH. '/admin/ehssl-dashboard-menu.php';
        $this->dashboard_menu = new EHSSL_Dashboard_Menu();
    }

    public function handle_settings_menu_rendering()
    {
        include_once EASY_HTTPS_SSL_PATH. '/admin/ehssl-settings-menu.php';
        $this->settings_menu = new EHSSL_Settings_Menu();
    }
    
    public function handle_settings_menu_rendering_old()
    {
        include_once EASY_HTTPS_SSL_PATH. '/admin/ehssl-settings-menu-old.php';
        $this->settings_menu = new EHSSL_Settings_Menu_Old();
    }

    public function handle_autossl_menu_rendering()
    {
        include_once EASY_HTTPS_SSL_PATH. '/admin/ehssl-autossl-menu.php';
        $this->settings_menu = new EHSSL_AutoSSL_Menu();
    }

    public function plugin_admin_init()
    {
        global $httpsrdrctn_plugin_info;

        $httpsrdrctn_plugin_info = get_plugin_data(__FILE__, false);

        /* Call register settings function */
        if (isset($_GET['page']) && "ehssl_settings" == $_GET['page']) {
            $this->register_httpsrdrctn_settings();
        }
    }

    /**
     * Register settings function
     */
    public function register_httpsrdrctn_settings()
    {
        global $wpmu, $httpsrdrctn_options, $httpsrdrctn_plugin_info;

        $httpsrdrctn_option_defaults = array(
            'https' => 0,
            'https_domain' => 1,
            'https_pages_array' => array(),
            'force_resources' => 0,
            'plugin_option_version' => $httpsrdrctn_plugin_info["Version"],
        );

        // Install the option defaults.
        if (1 == $wpmu) {
            if (!get_site_option('httpsrdrctn_options')) {
                add_site_option('httpsrdrctn_options', $httpsrdrctn_option_defaults, '', 'yes');
            }

        } else {
            if (!get_option('httpsrdrctn_options')) {
                add_option('httpsrdrctn_options', $httpsrdrctn_option_defaults, '', 'yes');
            }
        }

        // Get options from the database.
        if (1 == $wpmu) {
            $httpsrdrctn_options = get_site_option('httpsrdrctn_options');
        } else {
            $httpsrdrctn_options = get_option('httpsrdrctn_options');
        }

        // Array merge incase this version has added new options.
        if (!isset($httpsrdrctn_options['plugin_option_version']) || $httpsrdrctn_options['plugin_option_version'] != $httpsrdrctn_plugin_info["Version"]) {
            $httpsrdrctn_options = array_merge($httpsrdrctn_option_defaults, $httpsrdrctn_options);
            $httpsrdrctn_options['plugin_option_version'] = $httpsrdrctn_plugin_info["Version"];
            update_option('httpsrdrctn_options', $httpsrdrctn_options);
        }
    }

    public function plugin_admin_head()
    {
        if (isset($_REQUEST['page']) && 'ehssl_settings' == $_REQUEST['page']) {
            wp_enqueue_style('ehssl_stylesheet', EASY_HTTPS_SSL_URL . '/css/style.css', null, wp_rand(1, 10000));
            wp_enqueue_script('ehssl_script', EASY_HTTPS_SSL_URL . '/js/script.js', array('jquery'), wp_rand(1, 10000));
        }
    }

} //End of class
