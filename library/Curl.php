<?php

/**
 * Persistent curl class (retains cookies between requests)
 */
class Curl {
    /**
     * @var resource
     */
    protected $curl_handle;

    public function __construct() {
        $this->curl_handle = curl_init();
        curl_setopt_array($this->curl_handle, array(
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => 1,
            CURLOPT_COOKIEFILE => "/dev/null"
        ));
    }

    /**
     * Simple GET request
     *
     * @param string $url
     * @return string
     */
    public function get($url) {
        return $this->request($url);
    }

    /**
     * Simple POST request
     *
     * @param string $url
     * @param array $post
     * @return string
     */
    public function post($url, $post = array()) {
        return $this->request($url, $post);
    }

    /**
     * Make a GET/POST curl request
     *
     * @param string $url
     * @param array $post
     * @return string
     */
    protected function request($url, $post = array()) {
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);

        if (!empty($post)) {
            curl_setopt($this->curl_handle, CURLOPT_POST, 1);
            curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, http_build_query($post, null, "&"));
        } else {
            curl_setopt($this->curl_handle, CURLOPT_POST, 0);
            curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, null);
        }
        return curl_exec($this->curl_handle);
    }
}
