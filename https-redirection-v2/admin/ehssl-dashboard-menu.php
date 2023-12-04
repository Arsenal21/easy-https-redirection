<?php

class EHSSL_Dashboard_Menu extends EHSSL_Admin_Menu
{
    public $menu_page_slug = EHSSL_MAIN_MENU_SLUG;

    /* Specify all the tabs of this menu in the following array */
    public $dashboard_menu_tabs = array('tab1' => 'Status', 'tab2' => 'Tab Two');

    public function __construct()
    {
        $this->render_menu_page();
    }

    public function get_current_tab()
    {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : array_keys($this->dashboard_menu_tabs)[0];
        return $tab;
    }

    /**
     * Renders our tabs of this menu as nav items
     */
    public function render_page_tabs()
    {
        $current_tab = $this->get_current_tab();
        foreach ($this->dashboard_menu_tabs as $tab_key => $tab_caption) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->menu_page_slug . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
        }
    }

    /**
     * The menu rendering goes here
     */
    public function render_menu_page()
    {
        $tab = $this->get_current_tab();

        ?>
        <div class="wrap">
        <h2><?php _e("Dashboard", EHSSL_TEXT_DOMAIN)?></h2>
        <h2 class="nav-tab-wrapper"><?php $this->render_page_tabs();?></h2>
        <div id="poststuff"><div id="post-body">
        <?php

        $tab_keys = array_keys($this->dashboard_menu_tabs);
        switch ($tab) {
            case $tab_keys[1]:
                //include_once('file-to-handle-this-tab-rendering.php');//If you want to include a file
                $this->render_tab_2();
                break;
            default:
                //include_once('file-to-handle-this-tab-rendering.php');//If you want to include a file
                $this->render_tab_1();
                break;
        }
        ?>
        </div></div>
        </div><!-- end or wrap -->
        <?php
}

    public function render_tab_1()
    {
        $this->widget_ssl_status();
        
        if (is_ssl()) {
            $this->widget_ssl_info();
        }
    }

    public function render_tab_2()
    {
        //Render tab 1
        ?>
        <div class="postbox">
            <h3 class="hndle"><label for="title">Tab 2 Heading</label></h3>
            <div class="inside">
            <p>Oh hello there! You are in tab 2. Tab 2 looks good right? Sweet tab 2.</p>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->
        <?php
    }

    public function widget_ssl_status(){
        ?>
        <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e("SSL Status", EHSSL_TEXT_DOMAIN); ?></label></h3>
            <div class="inside">
                <table class="form-table">
                    <tr valign="top">
                        <th><?php _e("SSL Status", EHSSL_TEXT_DOMAIN);?></th>
                        <td> : </td>
                        <td><?php is_ssl() ? _e("Enabled", EHSSL_TEXT_DOMAIN) : _e("Unavailable", EHSSL_TEXT_DOMAIN)?></td>
                    </tr>
                </table>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->
        <?php
    }

    public function widget_ssl_info(){
        $ssl_info = EHSSL_SSL_Utils::get_parsed_ssl_info();
        ?>
            <div class="postbox">
            <h3 class="hndle"><label for="title"><?php _e("SSL Information", EHSSL_TEXT_DOMAIN); ?></label></h3>
            <div class="inside">
            <table>
                <?php foreach ($ssl_info as $section => $fields) { ?>
                    <tr valign="top" style="margin: 24px 0;">
                        <td scope="row">
                            <b style="font-weight: bold;">
                                <?php _e($section, EHSSL_TEXT_DOMAIN);?>
                            </b>
                        </td>
                        <th></th>
                        <th></th>
                    </tr>
                    <?php foreach ($fields as $field => $value) { ?>
                    <tr valign="top">
                        <td><?php _e($field, EHSSL_TEXT_DOMAIN);?></td>
                        <td> : </td>
                        <td><?php echo $value?></td>
                    </tr>
                    <?php } ?>
                <?php } ?>
            </table>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->

        <!-- <div class="postbox">
            <h3 class="hndle"><label for="title">Site SSL Status</label></h3>
            <div class="inside">
            <pre>
                <?php // print_r(EHSSL_SSL_Utils::get_parsed_ssl_info());?>
                <hr>
                <?php // print_r(EHSSL_SSL_Utils::get_ssl_info());?>
            </pre>
            </div>
        </div> -->
        <?php
    }
} //end class