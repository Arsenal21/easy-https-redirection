<?php

class EHSSL_SSL_Utils {

	public static function get_current_domain() {
		return parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Get the parsed SSL info if any to display in the dashboard.
	 */
	public static function get_parsed_current_ssl_info_for_dashbaord() {
		$domain = self::get_current_domain();

		$info = self::get_ssl_info( $domain );

		if ( empty($info) ) {
			return false;
		}

		$certinfo = array(
			"Issued To"       => array(
				"Common Name (CN)"         => isset( $info['subject']['CN'] ) ? $info['subject']['CN'] : "N/A",
				"Organization (O)"         => isset( $info['subject']['O'] ) ? $info['subject']['O'] : "N/A",
				"Organizational Unit (OU)" => isset( $info['subject']['OU'] ) ? $info['subject']['OU'] : "N/A",
			),
			"Issued By"       => array(
				"Common Name (CN)"         => isset( $info['issuer']['CN'] ) ? $info['issuer']['CN'] : "N/A",
				"Organization (O)"         => isset( $info['issuer']['O'] ) ? $info['issuer']['O'] : "N/A",
				"Organizational Unit (OU)" => isset( $info['issuer']['OU'] ) ? $info['issuer']['OU'] : "N/A",
			),
			"Validity Period" => array(
				"Issued On"  => isset( $info['validFrom_time_t'] ) ? self::parse_timestamp( $info['validFrom_time_t'] ) : "N/A",
				"Expires On" => isset( $info['validTo_time_t'] ) ? self::parse_timestamp( $info['validTo_time_t'] ) : "N/A",
			),
			// "SHA-256 Fingerprint" => array(
			//     "Certificate" => "",
			//     "Public Key" => "",
			// ),
		);

		return $certinfo;
	}

	public static function get_all_saved_certificates_info() {
		$certs_info = get_posts(array(
			'numberposts' => -1,
			'post_type' => 'ehssl_certs_info',
		));

		//		$domain = self::get_current_domain();
		//
		//		$domains = [
		////			'alphasciencelabbd.com',
		////			'dhumketux.com',
		////			'studentshopbd.com',
		////			'ssltest.adilarham.com',
		////			'plugin-demo.com',
		//			$domain
		//		];
		//
		//		$certificates = [];
		//		foreach ( $domains as $domain ) {
		//			$certificates[] = self::get_parsed_ssl_info($domain);
		//		}
		//
		//		return $certificates;

		$data = [];
		foreach ( $certs_info as $cert_info ) {
			$data[] = array(
				'id' => get_post_meta($cert_info->ID, 'id', true ),
				'label' => get_post_meta($cert_info->ID, 'label', true ),
				'issuer' => get_post_meta($cert_info->ID, 'issuer', true ),
				'issued_on' => get_post_meta($cert_info->ID, 'issued_on', true ),
				'expires_on' => get_post_meta($cert_info->ID, 'expires_on', true ),
				'status' => get_post_meta($cert_info->ID, 'status', true ),
			);
		}

		return $data;
	}

	/**
	 * Retrieves the SSL info if have any
	 *
	 * @return array|bool The SSL information array.
	 */
	public static function get_ssl_info( $domain ) {
		$get = stream_context_create( array(
			"ssl" => array(
				"capture_peer_cert" => true,
				// "verify_peer" => false,       // Disable verification for testing
				// "verify_peer_name" => false,  // Disable hostname verification
				// "allow_self_signed" => true,  // Allow self-signed certs
			)
		) );

		$client = @stream_socket_client( "ssl://" . $domain . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get );

		$certinfo = [];
		if ( $client ) {
			$cert     = stream_context_get_params( $client );
			$certinfo = openssl_x509_parse( $cert['options']['ssl']['peer_certificate'] );
		}

		return $certinfo;
	}

	public static function get_parsed_ssl_info($domain) {
		$cert_info = self::get_ssl_info( $domain );

		if ( !empty($cert_info) ) {
			$valid_from = $cert_info['validFrom_time_t'];
			$valid_to   = $cert_info['validTo_time_t'];

			// Get certificate issuer.
			$issuer_arr = array();
			$issuer_arr[] = isset($cert_info['issuer']['O'])  ? $cert_info['issuer']['O']  : '';
			$issuer_arr[] = isset($cert_info['issuer']['CN']) ? $cert_info['issuer']['CN'] : '';
			$issuer_arr[] = isset($cert_info['issuer']['C'])  ? $cert_info['issuer']['C']  : '';
			$issuer_arr = array_filter($issuer_arr);

			if (empty($issuer_arr)){
				$issuer = 'Unknown';
			} else {
				$issuer = implode(', ', $issuer_arr);
			}

			$subject    = isset($cert_info['subject']['CN']) ? $cert_info['subject']['CN'] : $domain;

			return array(
				'id'         => substr( md5( $domain ), 0, 7 ), // Generate a short ID
				'label'      => $subject,
				'issuer'     => $issuer,
				'issued_on'  => $valid_from,
				'expires_on' => $valid_to,
				'status'     => self::get_certificate_status( $valid_to ),
			);
		} else {
			return array(
				'id'         => substr( md5( $domain ), 0, 7 ), // Generate a short ID
				'label'      => $domain,
				'issuer'     => '-',
				'issued_on'  => '-',
				'expires_on'   => '-',
				'status'     => 'expired',
			);
		}
	}

	public static function get_certificate_status( $expiryDate ) {
		$expiry = new DateTime( date(get_option('date_format'), $expiryDate) );
		$now    = new DateTime();
		$diff   = $now->diff( $expiry );

		if ( $expiry < $now ) {
			return 'expired';
		} elseif ( $diff->days <= 7 ) {
			return 'critical';
		} elseif ( $diff->days <= 30 ) {
			return 'warning';
		} else {
			return 'active';
		}
	}

	public static function parse_timestamp( $timestamp ) {
		$timestamp = (int) $timestamp;

		$dateTime = new DateTime();
		$dateTime->setTimestamp( $timestamp );

		$formatted_date_time = $dateTime->format( get_option('date_format') . ' \a\t ' . get_option( 'time_format' ) );

		return $formatted_date_time;
	}

	public static function check_and_save_current_cert_info() {
		$domain = self::get_current_domain();

		$cert = self::get_parsed_ssl_info($domain);

		$ssl_info_hash = md5( $cert['id'] . $cert['issuer'] . $cert['issued_on'] );

		$posts = get_posts( array(
			'post_type'      => 'ehssl_certs_info',
			'title'          => $ssl_info_hash,
			'posts_per_page' => 1, // We only need one post
			'exact'          => true, // Ensure an exact title match
			'suppress_filters' => true, // Bypass filters for more predictable results
		) );

		if ( empty( $posts ) ) {
			EHSSL_Logger::log( 'Scanning for SSL certificate info...', true);

			$post_id = wp_insert_post( array(
				'post_title'    => $ssl_info_hash,
				'post_content'  => '',
				'post_status'   => 'publish',
				'post_type'     => 'ehssl_certs_info',
			) );

			if ( is_wp_error( $post_id ) ) {
				EHSSL_Logger::log($post_id->get_error_message(), false);
				return;
			}

			update_post_meta($post_id, 'id', $cert['id']);
			update_post_meta($post_id, 'label', $cert['label']);
			update_post_meta($post_id, 'issuer', $cert['issuer']);
			update_post_meta($post_id, 'issued_on', $cert['issued_on']);
			update_post_meta($post_id, 'expires_on', $cert['expires_on']);
			update_post_meta($post_id, 'status', $cert['status']);

			EHSSL_Logger::log( 'New certificate info captured. ID: ' . $cert['id'], true);
		}

	}

}