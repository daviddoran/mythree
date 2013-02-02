<?php

class Curl {
    protected $cookieFile = null;
    protected $counter = 0;
    public static $debugging = false;

    public function __construct() {
    }

    public function set_cookie_file_name($cookie_file_name) {
        $this->cookieFile = $cookie_file_name;
        return $this;
    }

    public function get($url) {
        return $this->_request($url);
    }

    public function post($url, $post = array()) {
        return $this->_request($url, $post);
    }

    protected function _request($url, $post = array()) {
        $curl_connection = curl_init($url);
        //set options
        curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl_connection, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
        curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($curl_connection, CURLOPT_COOKIEFILE, $this->cookieFile);
        //set data to be posted
        if ($post) {
            $post_string = http_build_query($post, null, "&");
            curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
        }

        self::debug("Requesting " . $url);
        //perform our request
        $result = curl_exec($curl_connection);

        if (self::$debugging) {
            //file_put_contents($this->counter."-". preg_replace("/[^a-zA-Z0-9_\-]+/", "_", curl_getinfo($curl_connection, CURLINFO_EFFECTIVE_URL)) . ".html", $result);
        }

        self::debug(" ... Done. \n");

        curl_close($curl_connection);
        $this->counter++;
        return $result;
    }

    protected static function debug($message) {
        if (self::$debugging) {
            error_log($message);
        }
    }
}