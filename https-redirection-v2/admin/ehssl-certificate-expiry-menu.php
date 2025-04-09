<?php

class EHSSL_Certificate_Expiry_Menu extends EHSSL_Admin_Menu {
	public $menu_page_slug = EHSSL_CERTIFICATE_EXPIRY_MENU_SLUG;

	// Specify all the tabs of this menu in the following array.
	public $dashboard_menu_tabs = array(
		'expiring-certificates' => 'Expiring Certificates',
		'expiry-notification'    => 'Expiry Notification',
	);

	public function __construct() {
		$this->render_menu_page();
	}

	public function get_current_tab() {
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : array_keys( $this->dashboard_menu_tabs )[0];

		return $tab;
	}

	/**
	 * Renders our tabs of this menu as nav items
	 */
	public function render_page_tabs() {
		$current_tab = $this->get_current_tab();
		foreach ( $this->dashboard_menu_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->menu_page_slug . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
		}
	}

	/**
	 * The menu rendering goes here
	 */
	public function render_menu_page() {
		$tab = $this->get_current_tab();

		?>
        <div class="wrap">
            <h2><?php _e( "Certificate Expiry", 'https_redirection' ) ?></h2>
            <p>
		        <?php _e( 'Use this page to configure all settings related to certificate renewal, such as when certificates are considered due and overdue, who receives notifications, and how.', 'https_redirection' ); ?>
            </p>
            <h2 class="nav-tab-wrapper"><?php $this->render_page_tabs(); ?></h2>
            <div id="poststuff">
                <div id="post-body">
					<?php
					switch ( $tab ) {
						case 'expiring-certificates':
							$this->render_expiring_certificates_tab();
							break;
						case 'expiry-notification';
						default:
							$this->render_email_notification_tab();
							break;
					}
					?>
                </div>
            </div>
			<?php $this->documentation_link_box(); ?>
        </div><!-- end or wrap -->
		<?php
	}

	public function render_expiring_certificates_tab() {
		if ( isset( $_POST['ehssl_scan_for_ssl_submit'] ) ){

			if (!check_admin_referer('ehssl_scan_for_ssl_nonce')){
				wp_die('Nonce verification failed!');
			}

			EHSSL_SSL_Utils::check_and_save_current_cert_info();

			echo '<div class="notice notice-success"><p>'. __('Success fully scanned for available SSL certificates.', 'https_redirection') .'</p></div>';
		}

        $certs_info = EHSSL_SSL_Utils::get_all_saved_certificates_info();
		?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e( "Expiring Certificates", 'https_redirection' ); ?></label>
            </h3>
            <div class="inside">
                <form action="" method="post">
		            <?php wp_nonce_field('ehssl_scan_for_ssl_nonce') ?>

                    <div class="ehssl-blue-box">
                        <div><?php _e('Click the button to manually scan for available SSL certificates.', 'https_redirection') ?></div>
                        <br>
                        <input type="submit" class="button-primary" value="<?php _e('Scan Now', 'https_redirection') ?>" name="ehssl_scan_for_ssl_submit">
                    </div>
                </form>

                <?php if (!empty($certs_info)) { ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Label</th>
                        <th>Issuer</th>
                        <th>Issued on</th>
                        <th>Expires on</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <?php foreach ($certs_info as $cert){
                        $formatted_issued_on_date = EHSSL_SSL_Utils::parse_timestamp( $cert['issued_on'] );
                        $formatted_expires_on = EHSSL_SSL_Utils::parse_timestamp( $cert['expires_on'] );
                        ?>
                        <tr>
                            <td><?php esc_attr_e($cert['id']) ?></td>
                            <td><?php esc_attr_e($cert['label']) ?></td>
                            <td><?php esc_attr_e($cert['issuer']) ?></td>
                            <td><?php esc_attr_e($formatted_issued_on_date) ?></td>
                            <td><?php esc_attr_e($formatted_expires_on) ?></td>
                            <td><?php esc_attr_e(ucfirst($cert['status'])) ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <?php } else { ?>
                <p class="description">
                    <?php _e('No SSL certificate info found.', 'https_redirection') ?>
                </p>
                <?php } ?>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->
		<?php
	}

	public function render_email_notification_tab() {
		global $httpsrdrctn_options;

		// Save data for settings page.
		if ( isset( $_POST['ehssl_expiry_notification_settings_form_submit'] ) && check_admin_referer( 'ehssl_expiry_notification_settings_nonce' ) ) {
			$httpsrdrctn_options['enable_expiry_notification']               = isset( $_POST['enable_expiry_notification'] ) ? esc_attr( $_POST['enable_expiry_notification'] ) : 0;
			$httpsrdrctn_options['enable_expiry_notification_email_to']      = isset( $_POST['enable_expiry_notification_email_to'] ) ? sanitize_email( $_POST['enable_expiry_notification_email_to'] ) : '';
			$httpsrdrctn_options['enable_expiry_notification_email_subject'] = isset( $_POST['enable_expiry_notification_email_subject'] ) ? sanitize_text_field( $_POST['enable_expiry_notification_email_subject'] ) : '';
			$httpsrdrctn_options['enable_expiry_notification_email_body']    = isset( $_POST['enable_expiry_notification_email_body'] ) ? sanitize_text_field( $_POST['enable_expiry_notification_email_body'] ) : '';

			update_option( 'httpsrdrctn_options', $httpsrdrctn_options )

			?>
            <div class="notice notice-success">
                <p><?php _e( "Settings Saved.", 'https_redirection' ); ?></p>
            </div>
			<?php
		}

		$is_expiry_notification_enabled = isset( $httpsrdrctn_options['enable_expiry_notification'] ) ? esc_attr( $httpsrdrctn_options['enable_expiry_notification'] ) : 0;
		$expiry_notification_email_to   = isset( $httpsrdrctn_options['enable_expiry_notification_email_to'] ) ? sanitize_email( $httpsrdrctn_options['enable_expiry_notification_email_to'] ) : '';
		$expiry_notification_email_sub  = isset( $httpsrdrctn_options['enable_expiry_notification_email_subject'] ) ? sanitize_text_field( $httpsrdrctn_options['enable_expiry_notification_email_subject'] ) : '';
		$expiry_notification_email_body = isset( $httpsrdrctn_options['enable_expiry_notification_email_body'] ) ? sanitize_textarea_field( $httpsrdrctn_options['enable_expiry_notification_email_body'] ) : '';

		?>
        <div class="postbox">
            <h3 class="hndle"><label
                        for="title"><?php _e( "Expiry Notification", 'https_redirection' ); ?></label></h3>
            <div class="inside">
                <p>
					<?php _e( 'Use this page to configure all settings related to certificate renewal, such as when certificates are considered due and overdue, who receives notifications, and how.', 'https_redirection' ); ?>
                </p>
                <form method="post" action="">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Enable Certificate Expiry Notification', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox" name="enable_expiry_notification"
                                       value="1" <?php if ( '1' == $is_expiry_notification_enabled ) {
									echo "checked=\"checked\" ";
								} ?> />
                                <br/>
                                <p class="description"><?php _e( "Check this option to enable sending certificate expiry notification.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Expiry Notification Email To', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="email"
                                       name="enable_expiry_notification_email_to" style="width:50%;"
                                       placeholder="<?php esc_attr_e( get_option( 'admin_email' ) ) ?>"
                                       value="<?php esc_attr_e( $expiry_notification_email_to ) ?>"/>
                                <br/>
                                <p class="description"><?php _e( "Certificate expiry notification email recipient. Default to site admin email.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Expiry Notification Email Before Days', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number"
                                       name="enable_expiry_notification_email_before_days"
                                       placeholder="7"
                                       value="<?php esc_attr_e( 7 ) ?>"/>
                                <br/>
                                <p class="description"><?php _e( "Enter the number of days before the expiry notification email to send.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Expiry Notification Email Subject', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" name="enable_expiry_notification_email_subject" style="width:50%;"
                                       value="<?php esc_attr_e( $expiry_notification_email_sub ) ?>"/>
                                <br/>
                                <p class="description"><?php _e( "Certificate expiry notification email subject.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Expiry Notification Email Body', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <textarea name="enable_expiry_notification_email_body" style="width:75%;"
                                          rows="7"><?php esc_attr_e( $expiry_notification_email_body ) ?></textarea>
                                <br/>
                                <p class="description"><?php _e( "Certificate expiry notification email body.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                    </table>

					<?php wp_nonce_field( 'ehssl_expiry_notification_settings_nonce' ); ?>
                    <p class="submit">
                        <input type="submit" name="ehssl_expiry_notification_settings_form_submit"
                               class="button-primary" value="<?php _e( 'Save Changes' ) ?>"/>
                    </p>
                </form>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->
		<?php
	}


} // End class