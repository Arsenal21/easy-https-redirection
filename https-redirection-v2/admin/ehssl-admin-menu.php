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
        ?>
        <div id="<?php echo $id; ?>" class="postbox">
            <div class="handlediv" title="Click to toggle"><br /></div>
            <h3 class="hndle"><span><?php echo $title; ?></span></h3>
            <div class="inside">
            <?php echo $content; ?>
            </div>
        </div>
        <?php
}

}