<?php

/**
 * Class for curl handling.
 *
 * @package    fileconverter_unowebconv
 * @copyright  2020 Sven Patrick Meier <sven.patrick.meier@team-parallax.com>
 */
namespace fileconverter_unowebconv;


class curl_handler
{
    public static function fetch_url_data($url, $raw = false)
    {
        if (!$url || !is_string($url)) {
            return false;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }
        $ch = (new curl_handler)->configure_curl_session($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $err = curl_errno($ch);
        if ($err) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $result = utf8_encode($response);
        if ($raw) {
            return $result;
        }
        return json_decode($result);
    }

    public static function post_conversion($url, $data)
    {
        if (!$url || !is_string($url)) {
            return false;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }
        $ch = (new curl_handler)->configure_curl_session($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'accept: application/json',
            'Content-Type: multipart/form-data'
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($ch);
        $err = curl_errno($ch);
        if ($err) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        $result = utf8_encode($response);
        return json_decode($result);
    }

    public static function get_http_response_code($url)
    {
        if (!$url || !is_string($url)) {
            return false;
        }
        $ch = curl_init($url);
        if ($ch === false) {
            return false;
        }
        $ch = self::configure_curl_session($ch);
        curl_setopt($ch, CURLOPT_HEADER, true);    // get headers
        curl_setopt($ch, CURLOPT_NOBODY, true);    // no body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        $err = curl_errno($ch);
        if ($err) {   // should be 0
            curl_close($ch);
            return false;
        }
        // note: php.net documentation shows this returns a string, but really it returns an int
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }


    private static function configure_curl_session($curl_handler, $follow_redirects = true)
    {
        if ($follow_redirects) {
            curl_setopt($curl_handler, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($curl_handler, CURLOPT_MAXREDIRS, 10);  // max redirects
        } else {
            curl_setopt($curl_handler, CURLOPT_FOLLOWLOCATION, false);
        }
        curl_setopt($curl_handler, CURLOPT_CONNECTTIMEOUT, 10);    // timeout in seconds to wait
        // pretend we're a regular browser
        curl_setopt(
            $curl_handler,
            CURLOPT_USERAGENT,
            "Mozilla/5.0 (Windows NT 6.0) AppleWebKit/537.1 (KHTML, like Gecko) Chrome/21.0.1180.89 Safari/537.1"
        );
        return $curl_handler;
    }
}