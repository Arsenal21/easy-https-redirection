<?php
/*
 * Logs debug data to a file. Here is an example usage
 * global $ehssl;
 * $ehssl->debug_logger->log_debug("Log messaged goes here");
 */
class EHSSL_Logger
{
    public $log_folder_path;
    public $default_log_file = 'ehssl-log.txt';
    public $debug_enabled = false;
    public $debug_status = array('SUCCESS', 'STATUS', 'NOTICE', 'WARNING', 'FAILURE', 'CRITICAL');
    public $section_break_marker = "\n----------------------------------------------------------\n\n";
    public $log_reset_marker = "-------- Log File Reset --------\n";

    public function __construct()
    {
        $this->log_folder_path = EASY_HTTPS_SSL_PATH . '/logs';
        //TODO - check config and if debug is enabled then set the enabled flag to true
        $this->debug_enabled = true;
    }

    public function get_debug_timestamp()
    {
        return '[' . date('m/d/Y g:i A') . '] - ';
    }

    public function get_debug_status($level)
    {
        $size = count($this->debug_status);
        if ($level >= $size) {
            return 'UNKNOWN';
        } else {
            return $this->debug_status[$level];
        }
    }

    public function get_section_break($section_break)
    {
        if ($section_break) {
            return $this->section_break_marker;
        }
        return "";
    }

    public function append_to_file($content, $file_name)
    {
        if (empty($file_name)) {
            $file_name = $this->default_log_file;
        }

        $debug_log_file = $this->log_folder_path . '/' . $file_name;
        $fp = fopen($debug_log_file, 'a');
        fwrite($fp, $content);
        fclose($fp);
    }

    public function reset_log_file($file_name = '')
    {
        if (empty($file_name)) {
            $file_name = $this->default_log_file;
        }

        $debug_log_file = $this->log_folder_path . '/' . $file_name;
        $content = $this->get_debug_timestamp() . $this->log_reset_marker;
        $fp = fopen($debug_log_file, 'w');
        fwrite($fp, $content);
        fclose($fp);
    }

    public function log_debug($message, $level = 0, $section_break = false, $file_name = '')
    {
        if (!$this->debug_enabled) {
            return;
        }

        //Timestamp
        $content = $this->get_debug_timestamp();
        //Debug status
        $content .= $this->get_debug_status($level);
        $content .= ' : ';
        $content .= $message . "\n";
        $content .= $this->get_section_break($section_break);
        $this->append_to_file($content, $file_name);
    }
}
