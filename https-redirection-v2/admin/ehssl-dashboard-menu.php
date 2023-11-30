<?php

class EHSSL_Dashboard_Menu extends EHSSL_Admin_Menu
{
    public $dashboard_menu_page_slug = EHSSL_MAIN_MENU_SLUG;

    /* Specify all the tabs of this menu in the following array */
    public $dashboard_menu_tabs = array('tab1' => 'Tab One', 'tab2' => 'Tab Two');

    public function __construct()
    {
        $this->render_dashboard_menu_page();
    }

    public function get_current_tab()
    {
        $tab = isset($_GET['tab']) ? $_GET['tab'] : array_keys($this->dashboard_menu_tabs)[0];
        return $tab;
    }

    /**
     * Renders our tabs of this menu as nav items
     */
    public function render_dashboard_menu_tabs()
    {
        $current_tab = $this->get_current_tab();
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->dashboard_menu_tabs as $tab_key => $tab_caption) {
            $active = $current_tab == $tab_key ? 'nav-tab-active' : '';
            echo '<a class="nav-tab ' . $active . '" href="?page=' . $this->dashboard_menu_page_slug . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
        }
        echo '</h2>';
    }

    /**
     * The menu rendering goes here
     */
    public function render_dashboard_menu_page()
    {
        $tab = $this->get_current_tab();

        $this->render_dashboard_menu_tabs();        
        ?>
        <div class="wrap">
        <div id="poststuff"><div id="post-body">
        <?php

        echo "<br />";
        $tab_keys = array_keys($this->dashboard_menu_tabs);
        switch ($tab) {
            case $tab_keys[1]:
                //include_once('file-to-handle-this-tab-rendering.php');//If you want to include a file
                $this->render_dashboard_tab2();
                break;
            default:
                //include_once('file-to-handle-this-tab-rendering.php');//If you want to include a file
                $this->render_dashboard_tab1();
                break;
        }
        ?>
        </div></div>
        </div><!-- end or wrap -->
        <?php
    }

    public function render_dashboard_tab1(){
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

    public function render_dashboard_tab2(){
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

} //end class