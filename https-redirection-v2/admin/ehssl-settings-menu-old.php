<?php

class EHSSL_Settings_Menu_Old extends EHSSL_Admin_Menu
{
    public $menu_page_slug = EHSSL_SETTINGS_MENU_SLUG;

    public function __construct()
    {
        $this->render_menu_page();
    }

    public function render_menu_page()
    {
        ?>
        <div class="wrap">
            <h2><?php _e("HTTPS Redirection Settings", EHSSL_TEXT_DOMAIN) ?></h2>
			<div class="notice notice-warning">
				<p>
				<?php _e('The HTTPS Redirection settings was upgraded and moved to the <strong>Easy HTTPS & SSL</strong> > <b>Settings</b> page with some new features. Click the link below to navigate to the new page.',EHSSL_TEXT_DOMAIN);?>
				<br>
				<br>
				<a class="button-primary" href="admin.php?page=ehssl_settings"><?php _e('Go to new settings page',EHSSL_TEXT_DOMAIN);?></a>
				</p>
			</div>
        </div>
        <?php
    }
}