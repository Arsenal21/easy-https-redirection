<?php

/* Parent class for all admin menu classes */
abstract class EHSSL_Admin_Menu
{
    /**
     * Shows postbox for settings menu
     *
     * @param string $id css ID for postbox
     * @param string $title title of the postbox section
     * @param string $content the content of the postbox
     */
    public function postbox($id, $title, $content)
    {
        echo 'Do not use this old method. Use new HTML code instead.';
    }

}