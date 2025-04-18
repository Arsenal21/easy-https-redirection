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

			echo '<div class="notice notice-success"><p>'. __('SSL certificate scan completed successfully.', 'https_redirection') .'</p></div>';
		}

        // TODO: debug purpose only
        //if (isset($_GET['delete-certs'])){
        //    EHSSL_SSL_Utils::delete_all_certificate_info();
        //}

        $certs_info = EHSSL_SSL_Utils::get_all_saved_certificates_info();
		?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e( "Certificates", 'https_redirection' ); ?></label>
            </h3>
            <div class="inside">
                <form action="" method="post">
		            <?php wp_nonce_field('ehssl_scan_for_ssl_nonce') ?>

                    <div class="ehssl-blue-box">
                        <div><?php _e('Click the Scan button to manually scan for available SSL certificates.', 'https_redirection') ?></div>
                        <br>
                        <input type="submit" class="button-primary" value="<?php _e('Scan Now', 'https_redirection') ?>" name="ehssl_scan_for_ssl_submit">
                    </div>
                </form>

                <?php if (!empty($certs_info)) { ?>
                <table class="widefat striped">
                    <thead>
                    <tr>
                        <th><?php _e('ID', 'https_redirection') ?></th>
                        <th><?php _e('Label', 'https_redirection') ?></th>
                        <th><?php _e('Issuer', 'https_redirection') ?></th>
                        <th><?php _e('Issued on', 'https_redirection') ?></th>
                        <th><?php _e('Expires on', 'https_redirection') ?></th>
                        <th><?php _e('Status', 'https_redirection') ?></th>
                    </tr>
                    </thead>
                    <?php foreach ($certs_info as $cert){
                        $formatted_issued_on_date = EHSSL_Utils::parse_timestamp( $cert['issued_on'] );
                        $formatted_expires_on = EHSSL_Utils::parse_timestamp( $cert['expires_on'] );
	                    $formatted_ssl_status = ucfirst(EHSSL_SSL_Utils::get_certificate_status($cert['expires_on']));
                        ?>
                        <tr>
                            <td><?php esc_attr_e($cert['id']) ?></td>
                            <td><?php esc_attr_e($cert['label']) ?></td>
                            <td><?php esc_attr_e($cert['issuer']) ?></td>
                            <td><?php esc_attr_e($formatted_issued_on_date) ?></td>
                            <td><?php esc_attr_e($formatted_expires_on) ?></td>
                            <td><?php esc_attr_e($formatted_ssl_status) ?></td>
                        </tr>
                    <?php } ?>
                </table>
                <?php } else { ?>
                <p class="description">
                    <?php _e('No SSL certificate information found.', 'https_redirection') ?>
                </p>
                <?php } ?>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->
		<?php
	}

	public function render_email_notification_tab() {
        $settings = get_option('httpsrdrctn_options', array());

		if ( isset( $_POST['ehssl_expiry_notification_settings_form_submit'] ) && check_admin_referer( 'ehssl_expiry_notification_settings_nonce' ) ) {
			$settings['ehssl_enable_expiry_notification']             = isset( $_POST['ehssl_enable_expiry_notification'] ) ? esc_attr( $_POST['ehssl_enable_expiry_notification'] ) : '';
			$settings['ehssl_expiry_notification_email_content_type'] = isset( $_POST['ehssl_expiry_notification_email_content_type'] ) ? sanitize_text_field( $_POST['ehssl_expiry_notification_email_content_type'] ) : 'text';
			$settings['ehssl_expiry_notification_email_before_days']  = isset( $_POST['ehssl_expiry_notification_email_before_days'] ) ? intval( sanitize_text_field( $_POST['ehssl_expiry_notification_email_before_days'] ) ) : 'text';
			$settings['ehssl_expiry_notification_email_from']         = isset( $_POST['ehssl_expiry_notification_email_from'] ) ? $_POST['ehssl_expiry_notification_email_from'] : '';
			$settings['ehssl_expiry_notification_email_to']           = isset( $_POST['ehssl_expiry_notification_email_to'] ) ? sanitize_email( $_POST['ehssl_expiry_notification_email_to'] ) : '';
			$settings['ehssl_expiry_notification_email_subject']      = isset( $_POST['ehssl_expiry_notification_email_subject'] ) ? sanitize_text_field( $_POST['ehssl_expiry_notification_email_subject'] ) : '';
			$settings['ehssl_expiry_notification_email_body']         = isset( $_POST['ehssl_expiry_notification_email_body'] ) ? wp_kses_post( $_POST['ehssl_expiry_notification_email_body'] ) : '';

			update_option( 'httpsrdrctn_options', $settings )

			?>
            <div class="notice notice-success">
                <p><?php _e( "Settings Saved.", 'https_redirection' ); ?></p>
            </div>
			<?php
		}

		$expiry_notification_enabled = isset( $settings['ehssl_enable_expiry_notification'] ) ? sanitize_text_field( $settings['ehssl_enable_expiry_notification'] ) : 0;
		$expiry_notification_email_content_type = isset( $settings['ehssl_expiry_notification_email_content_type'] ) ? sanitize_text_field( $settings['ehssl_expiry_notification_email_content_type'] ) : '';

        $expiry_notification_email_before_days = isset( $settings['ehssl_expiry_notification_email_before_days'] ) ? sanitize_text_field( $settings['ehssl_expiry_notification_email_before_days'] ) : '';
		if (empty($expiry_notification_email_before_days)){
			$expiry_notification_email_before_days = 7;
		}

        $expiry_notification_email_from   = isset( $settings['ehssl_expiry_notification_email_from'] ) ? $settings['ehssl_expiry_notification_email_from'] : '';
        if (empty($expiry_notification_email_from)){
	        $expiry_notification_email_from = get_bloginfo( 'name' ) . ' <'.get_option( 'admin_email' ).'>';
        }

        $expiry_notification_email_to   = isset( $settings['ehssl_expiry_notification_email_to'] ) ? sanitize_email( $settings['ehssl_expiry_notification_email_to'] ) : '';

		$expiry_notification_email_sub  = isset( $settings['ehssl_expiry_notification_email_subject'] ) ? sanitize_text_field( $settings['ehssl_expiry_notification_email_subject'] ) : '';
		if (empty($expiry_notification_email_sub)){
			$expiry_notification_email_sub = 'Certificate Expiry Notification';
		}

        $expiry_notification_email_body = isset( $settings['ehssl_expiry_notification_email_body'] ) ? wp_kses_post( $settings['ehssl_expiry_notification_email_body'] ) : '';
        if (empty($expiry_notification_email_body)){
            $expiry_notification_email_body = 'Dear Admin' . "\r\n\r\n"
                                              . 'This mail is to inform you that your SSL certificate issued by {issuer} '
                                              . 'is about to expire on {expiry_datetime}.' . "\r\n\r\n"
                                              . 'Thanks';
        }

		?>
        <div class="postbox">
            <h3 class="hndle">
                <label for="title"><?php _e( "Notification Email Settings", 'https_redirection' ); ?></label>
            </h3>
            <div class="inside">
                <form method="post" action="">
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Enable Certificate Expiry Notification', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="checkbox"
                                       name="ehssl_enable_expiry_notification"
                                       value="1"
                                    <?php echo !empty($expiry_notification_enabled) ? 'checked="checked"' : '' ?>
                                />
                                <br/>
                                <p class="description"><?php _e( "Check this option to enable sending certificate expiry notification.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Email Content Type', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <select name="ehssl_expiry_notification_email_content_type" class="ehssl-width-default">
                                    <option value="text" <?php echo ($expiry_notification_email_content_type == 'text') ? 'selected' : '' ?>><?php _e('Plain Text', 'https_redirection') ?></option>
                                    <option value="html" <?php echo ($expiry_notification_email_content_type == 'html') ? 'selected' : '' ?>><?php _e('HTML', 'https_redirection') ?></option>
                                </select>
                                <br/>
                                <p class="description"><?php _e( "Choose which format of email to send.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Notification Email Before Days', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="number"
                                       name="ehssl_expiry_notification_email_before_days"
                                       class="ehssl-width-default"
                                       value="<?php esc_attr_e( $expiry_notification_email_before_days ) ?>"
                                       required
                                />
                                <br/>
                                <p class="description"><?php _e( "Enter the number of days before the expiry notification email to send. Default is 7 days.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Notification Email From', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                       name="ehssl_expiry_notification_email_from"
                                       class="ehssl-width-50"
                                       value="<?php esc_attr_e( $expiry_notification_email_from ) ?>"
                                />
                                <br/>
                                <p class="description"><?php _e( "This is the email address that will be used to send the email to the recipient. This name and email address will appear in the from field of the email.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Notification Email To', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="email"
                                       name="ehssl_expiry_notification_email_to"
                                       class="ehssl-width-50"
                                       value="<?php esc_attr_e( $expiry_notification_email_to ) ?>"
                                       required
                                />
                                <br/>
                                <p class="description"><?php _e( "Certificate expiry notification email recipient.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Notification Email Subject', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text"
                                       name="ehssl_expiry_notification_email_subject"
                                       class="ehssl-width-50"
                                       value="<?php esc_attr_e( $expiry_notification_email_sub ) ?>"
                                       required
                                />
                                <br/>
                                <p class="description"><?php _e( "Certificate expiry notification email subject.", 'https_redirection' ); ?></p>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label>
									<?php _e( 'Notification Email Body', 'https_redirection' ); ?>
                                </label>
                            </th>
                            <td>
                                <?php if ($expiry_notification_email_content_type == 'html') {
	                                add_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
                                    ?>
                                    <div class="ehssl-width-75">
                                        <?php
                                        wp_editor(
                                            html_entity_decode( $expiry_notification_email_body ),
                                            'ehssl_expiry_notification_email_body',
                                            array(
                                                'textarea_name' => 'ehssl_expiry_notification_email_body',
                                                'teeny'         => true,
                                                'media_buttons' => false,
                                                'textarea_rows' => 12,
                                            )
                                        );
                                        ?>
                                    </div>
                                    <?php
	                                remove_filter( 'wp_default_editor', array( $this, 'set_default_editor' ) );
                                } else { ?>
                                    <textarea
                                            name="ehssl_expiry_notification_email_body"
                                            class="ehssl-width-75"
                                            rows="10"
                                            required
                                    ><?php esc_attr_e( $expiry_notification_email_body ) ?></textarea>
                                    <br/>
                                <?php } ?>
                                <p class="description"><?php _e( "Certificate expiry notification email body.", 'https_redirection' ); ?></p>
                                <?php echo EHSSL_Email_handler::get_merge_tags_hints() ?>
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

	public function set_default_editor( $r ) {
		$r = 'html';
		return $r;
	}


} // End class