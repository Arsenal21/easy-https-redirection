<?php

if ( !class_exists('Easy_HTTPS_SSL') ) {
    class Easy_HTTPS_SSL
    {
        public $plugin_url;
        public $plugin_path;
        public $plugin_configs;//TODO - does it need to be static?
        public $admin_init;
        public $debug_logger;

        public function __construct() {
            $this->load_configs();
            $this->define_constants();
            $this->includes();
            $this->loader_operations();

            // Register action hooks.
            add_action('plugins_loaded', array(&$this, 'plugins_loaded_handler'));
            add_action('init', array(&$this, 'init_action_handler'), 0);
            add_action("init", array($this, "ehssl_init_time_tasks"));
            add_action('admin_notices', array(&$this, 'easy_https_plugin_admin_notices'));

            // Trigger EHSSL plugin loaded action.
            do_action('ehssl_loaded');
        }

        public function plugin_url() {
            if ($this->plugin_url) {
                return $this->plugin_url;
            }

            return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
        }

        public function plugin_path() {
            if ($this->plugin_path) {
                return $this->plugin_path;
            }

            return $this->plugin_path = untrailingslashit(plugin_dir_path(__FILE__));
        }

        public function load_configs() {
            include_once 'classes/ehssl-config.php';
            $this->plugin_configs = EHSSL_Config::get_instance();
        }

        public function define_constants() {
            define('EASY_HTTPS_SSL_URL', $this->plugin_url());
            define('EASY_HTTPS_SSL_PATH', $this->plugin_path());
            define('EHSSL_TEXT_DOMAIN', 'https_redirection');
            define('EHSSL_MANAGEMENT_PERMISSION', 'add_users');
            define('EHSSL_MENU_SLUG_PREFIX', 'ehssl');
            define('EHSSL_MAIN_MENU_SLUG', 'ehssl');
            define('EHSSL_SETTINGS_MENU_SLUG', 'ehssl_settings');
            define('EHSSL_CERTIFICATE_EXPIRY_MENU_SLUG', 'ehssl_certificate_expiry');
            define('EHSSL_SSL_MGMT_MENU_SLUG', 'ehssl-ssl-mgmt');
        }

        public function includes() {
            //Load common files for everywhere
            include_once EASY_HTTPS_SSL_PATH . '/classes/ehssl-debug-logger.php';
            include_once EASY_HTTPS_SSL_PATH . '/classes/utilities/ehssl-utils.php';
            include_once EASY_HTTPS_SSL_PATH . '/classes/utilities/ehssl-ssl-utils.php';
            include_once EASY_HTTPS_SSL_PATH . '/classes/ehssl-cronjob.php';
            include_once EASY_HTTPS_SSL_PATH . '/classes/ehssl-custom-post-types.php';
            include_once EASY_HTTPS_SSL_PATH . '/classes/ehssl-email-handler.php';

            if (is_admin()) { //Load admin side only files
                include_once EASY_HTTPS_SSL_PATH. '/admin/ehssl-admin-init.php';
            } else { 
                //Load front end side only files
            }
        }

        public function loader_operations() {
            //Initialize the various classes and objects.
            $this->debug_logger = new EHSSL_Logger();
            if (is_admin()) {
                $this->admin_init = new EHSSL_Admin_Init();
            }
        }

        public static function plugin_activate_handler() {
	        wp_schedule_event(time(), 'daily', 'ehssl_daily_cron_event');
        }

        public static function plugin_deactivate_handler() {
	        wp_clear_scheduled_hook('ehssl_daily_cron_event');
        }

        public static function plugin_uninstall_handler() {
            //NOP.
        }

        public function do_db_upgrade_check() {
            //Check if DB needs to be updated
            // if (is_admin()) { 
            //     if (get_option('ehssl_db_version') != EASY_HTTPS_SSL_DB_VERSION) {
            //         //include_once ('file-name-installer.php');
            //         //easy_https_run_db_upgrade();
            //     }
            // }
        }

        public function plugins_loaded_handler() { 
            // Runs when plugins_loaded action gets fired
            if (is_admin()) { // Do admin side plugins_loaded operations
                $this->do_db_upgrade_check();
                // $this->settings_obj = new EHSSL_Settings_Page();//Initialize settings menus
            }
        }

        public function init_action_handler() {
            //Lets run... Main plugin operation code goes here
            // Set up localization
            // $this->load_plugin_textdomain();

            global $httpsrdrctn_options;
            if (empty($httpsrdrctn_options)) {
                $httpsrdrctn_options = get_option('httpsrdrctn_options');
            }

            //Mixed content fix feature. Do force resource embedded using HTTPS.
            if (isset($httpsrdrctn_options['force_resources']) && $httpsrdrctn_options['force_resources'] == '1') {
                // Handle the appropriate content filters to force the static resources to use HTTPS URL.
                if (is_admin()) {
                    add_action("admin_init", array($this, "ehssl_start_buffer"));
                } else {
                    add_action("init", array($this, "ehssl_start_buffer"));
                }
                add_action("shutdown", array($this, "ehssl_end_buffer"));
            }

			// Register custom post types.
			EHSSL_Custom_Post_Types::get_instance()->register_custom_post_types();
        }

		public function easy_https_plugin_admin_notices() {
			$missing_extensions = EHSSL_Utils::get_missing_extensions();
			if (!empty($missing_extensions)){
				$output = '<div class="notice notice-error">';
				$output .= '<p><b>'.__('NOTE:', 'https_redirection').'</b> ';
				$output .= __('The following php extensions are missing which is required by this plugin to work properly. Contact you hosting provider enable this.', 'https_redirection');
				$output .= '<ol>';
				foreach ($missing_extensions as $ext){
					$output .= '<li>'. $ext .'</li>';
				}
				$output .= '</ol>';
				$output .= '</p>';
				$output .= '</div>';

				echo $output;
			}
		}

        public function ehssl_start_buffer(){
            ob_start(array($this, "ehssl_the_content"));
        }

        public function ehssl_end_buffer() {
            if (ob_get_length()) {
                ob_end_flush();
            }
        }

        public function ehssl_the_content($content) {
            global $httpsrdrctn_options;
            if (empty($httpsrdrctn_options)) {
                $httpsrdrctn_options = get_option('httpsrdrctn_options');
            }

            $current_page = sanitize_post($GLOBALS['wp_the_query']->get_queried_object());
            // Get the page slug
            $slug = str_replace(home_url() . '/', '', get_permalink($current_page));
            $slug = rtrim($slug, "/"); //remove trailing slash if it's there

            if ($httpsrdrctn_options['force_resources'] == '1' && $httpsrdrctn_options['https'] == 1) {
                if ($httpsrdrctn_options['https_domain'] == 1) {
                    $content = $this->ehssl_filter_content($content);
                } else if (!empty($httpsrdrctn_options['https_pages_array'])) {
                    $pages_str = '';
                    $on_https_page = false;
                    foreach ($httpsrdrctn_options['https_pages_array'] as $https_page) {
                        $pages_str .= preg_quote($https_page, '/') . '[\/|][\'"]|'; //let's add page to the preg expression string in case we'd need it later
                        if ($https_page == $slug) { //if we are on the page that is in the array, let's set the var to true
                            $on_https_page = true;
                        } else { //if not - let's replace all links to that page only to https
                            $http_domain = home_url();
                            $https_domain = str_replace('http://', 'https://', home_url());
                            $content = str_replace($http_domain . '/' . $https_page, $https_domain . '/' . $https_page, $content);
                        }
                    }
                    if ($on_https_page) { //we are on one of the https pages
                        $pages_str = substr($pages_str, 0, strlen($pages_str) - 1); //remove last '|'
                        $content = $this->ehssl_filter_content($content); //let's change everything to https first
                        $http_domain = str_replace('https://', 'http://', home_url());
                        $https_domain = str_replace('http://', 'https://', home_url());
                        //now let's change all inner links to http, excluding those that user sets to be https in plugin settings
                        $content = preg_replace('/<a .*?href=[\'"]\K' . preg_quote($https_domain, '/') . '\/((?!' . $pages_str . ').)(?=[^\'"]+)/i', $http_domain . '/$1', $content);
                        $content = preg_replace('/' . preg_quote($https_domain, '/') . '([\'"])/i', $http_domain . '$1', $content);
                    }
                }
            }
            return $content;
        }

        /**
         * Function that changes "http" embeds to "https"
         */
        public function ehssl_filter_content($content) {
            //filter buffer
            $home_no_www = str_replace("://www.", "://", get_option('home'));
            $home_yes_www = str_replace("://", "://www.", $home_no_www);
            $http_urls = array(
                str_replace("https://", "http://", $home_yes_www),
                str_replace("https://", "http://", $home_no_www),
                "src='http://",
                'src="http://',
            );
            $ssl_array = str_replace("http://", "https://", $http_urls);
            //now replace these links
            $str = str_replace($http_urls, $ssl_array, $content);

            //replace all http links except hyperlinks
            //all tags with src attr are already fixed by str_replace

            $pattern = array(
                '/url\([\'"]?\K(http:\/\/)(?=[^)]+)/i',
                '/<link .*?href=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
                '/<meta property="og:image" .*?content=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
                '/<form [^>]*?action=[\'"]\K(http:\/\/)(?=[^\'"]+)/i',
            );
            $str = preg_replace($pattern, 'https://', $str);
            return $str;
        }

        public function ehssl_init_time_tasks() {
            $this->ehssl_load_language();
        }

        public function ehssl_load_language() {
            // Internationalization
            load_plugin_textdomain('https_redirection', false, EASY_HTTPS_SSL_PATH . '/languages/');
        }

    } // End of class.

} // End of class not exists check.

$GLOBALS['ehssl'] = new Easy_HTTPS_SSL();
