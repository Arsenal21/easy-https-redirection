<?php

class EHSSL_AutoSSL_Menu extends EHSSL_Admin_Menu
{
    public $menu_page_slug = EHSSL_SSL_MGMT_MENU_SLUG;

    // Specify all the tabs of this menu in the following array.
    public $dashboard_menu_tabs = array('tab1' => 'TAB 1', 'tab2' => 'TAB 2');

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
        <h2><?php _e("SSL Management", EHSSL_TEXT_DOMAIN) ?></h2>
        <h2 class="nav-tab-wrapper"><?php $this->render_page_tabs(); ?></h2>
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

    public function render_tab_1(){
        //Render tab 1
        ?>
        <div class="postbox">
            <h3 class="hndle"><label for="title">Tab 1 Heading</label></h3>
            <div class="inside">
            <p>Oh hello there! You are in tab 1. Tab 1 looks good right? Yes, I love it.</p>
            </div><!-- end of inside -->
        </div><!-- end of postbox -->
        <?php
    }

    public function render_tab_2(){
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

} // End class