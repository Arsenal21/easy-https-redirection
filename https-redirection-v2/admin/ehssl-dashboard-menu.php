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
        ?>
        <div class="wrap">
        <div id="poststuff"><div id="post-body">
        <?php

        $this->render_dashboard_menu_tabs();
        echo "<br />";
        $tab_keys = array_keys($this->dashboard_menu_tabs);
        switch ($tab) {
            case $tab_keys[1]:
                //include_once('file-to-handle-this-tab-rendering.php');
                //call_function_to_render_tab2();
                $this->postbox("test123", "Tab 2 Postbox heading", "Some test content to show how this function can be used to output postbox");
                break;
            default:
                //include_once('file-to-handle-this-tab-rendering.php');
                //call_function_to_render_tab1();
                $this->postbox("test456", "Tab 1 Postbox heading", "Some test content to show how this function can be used to output postbox");
                break;
        }
        ?>
        </div></div>
        </div><!-- end or wrap -->
        <?php
}

} //end class