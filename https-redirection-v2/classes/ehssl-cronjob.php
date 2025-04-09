<?php

class EHSSL_Cronjob{

	public function __construct() {
		add_action('ehssl_daily_cron_event', array( &$this, 'handle_daily_cron_event' ) );
	}

	public function handle_daily_cron_event(){
		EHSSL_SSL_Utils::check_and_save_current_cert_info();
	}
}

new EHSSL_Cronjob();