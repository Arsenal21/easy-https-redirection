<?php

class EHSSL_SSL_Utils
{
    /**
     * Get the parsed SSL info if have any
     *
     * @return array|bool The parsed SSL information array.
     */
    public static function get_parsed_ssl_info()
    {
        $info = self::get_ssl_info();
        if (! $info) {
            return false;
        }

        $certinfo = array(
            "Issued To" => array(
                "Common Name (CN)" => isset($info['subject']['CN']) ? $info['subject']['CN'] : "N/A",
                "Organization (O)" => isset($info['subject']['O']) ? $info['subject']['O'] : "N/A",
                "Organizational Unit (OU)" => isset($info['subject']['OU']) ? $info['subject']['OU'] : "N/A",
            ),
            "Issued By" => array(
                "Common Name (CN)" => isset($info['issuer']['CN']) ? $info['issuer']['CN'] : "N/A",
                "Organization (O)" => isset($info['issuer']['O']) ? $info['issuer']['O'] : "N/A",
                "Organizational Unit (OU)" => isset($info['issuer']['OU']) ? $info['issuer']['OU'] : "N/A",
            ),
            "Validity Period" => array(
                "Issued On" => isset($info['validFrom_time_t']) ? self::parse_timestamp($info['validFrom_time_t']) : "N/A",
                "Expires On" => isset($info['validTo_time_t']) ? self::parse_timestamp($info['validTo_time_t']) : "N/A",
            ),
            // "SHA-256 Fingerprint" => array(
            //     "Certificate" => "",
            //     "Public Key" => "",
            // ),
        );

        return $certinfo;
    }

    /**
     * Retrieves the SSL info if have any
     *
     * @return array|bool The SSL information array.
     */
    public static function get_ssl_info()
    {
        if ( ! is_ssl() ) {
            // No SSL found!
            return false;   
        }

        // URL to test
        $url = home_url();

        $orignal_parse = parse_url($url, PHP_URL_HOST);

        $get = stream_context_create(array("ssl" => array("capture_peer_cert" => true)));
        $read = stream_socket_client("ssl://" . $orignal_parse . ":443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $get);
        $cert = stream_context_get_params($read);
        $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

        return $certinfo;
    }

    /**
     * Format the timestamp from milliseconds.
     *
     * @param int|string $timestamp Datetime in milliseconds.
     * 
     * @return string Formatter timestamp
     */
    public static function parse_timestamp($timestamp)
    {
        $timestamp = (int) $timestamp;

        // Get the timezone
        // $timezone = new DateTimeZone(self::get_timezone());

        // Create a DateTime object from the timestamp
        $dateTime = new DateTime();
        $dateTime->setTimestamp($timestamp);
        // $dateTime->setTimezone($timezone);

        // $formatted_date_time = $dateTime->format('l, F j, Y \a\t g:i:s A');
        $formatted_date_time = $dateTime->format('l, F j, Y');

        // Display the formatted date and time
        return $formatted_date_time;
    }

    /* 
    public static function get_timezone()
    {
        $gmt_offset = (int) esc_attr(get_option('gmt_offset'));
        if (isset($gmt_offset) && !empty($gmt_offset)) {
            return 'Etc/GMT' . ($gmt_offset >= 0 ? '+' : '') . $gmt_offset;
        }

        $timezone_string = esc_attr(get_option('timezone_string'));
        if (isset($timezone_string) && !empty($timezone_string)) {
            return $timezone_string;
        } 

        // Get the timezone offset in seconds
        $timezoneOffsetSeconds = date('Z');

        // Get the timezone abbreviation
        $timezoneAbbreviation = timezone_name_from_abbr('', $timezoneOffsetSeconds, 0);

        return $timezoneAbbreviation;
    }
    */

}