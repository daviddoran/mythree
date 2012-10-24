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

function get_redirect_href($content) {
    if (preg_match('/location.href[\s]*=[\s]*["\'](.*?)["\'];/', $content, $match)) {
        return $match[1];
    }
    return false;
}

function get_ticket($content) {
    if (preg_match('/ticket=([a-zA-Z0-9_\-]+)/', $content, $match)) {
        return $match[1];
    }
    return false;
}

class ThreeAllowance {
    /**
     * @var int
     */
    public $date = null;
    /**
     * @var int
     */
    public $mobile_data = 0;
    /**
     * @var int
     */
    public $three_to_three_calls = 0;
    /**
     * @var int
     */
    public $flexi_units = 0;
    /**
     * @var int
     */
    public $evening_weekend_minutes = 0;
    /**
     * @var int
     */
    public $days_remaining = 0;
    /**
     * Flexi units included in the price plan (null is Unlimited)
     *
     * @var null|int
     */
    public $price_plan_flexi_units = null;

    /**
     * Check whether this price plan has unlimited flexi units
     *
     * @return bool
     */
    public function is_price_plan_flexi_units_unlimited() {
        return (null == $this->price_plan_flexi_units);
    }

    /**
     * Check whether at least $min_flex_units remain
     *
     * @param   int $min_flex_units
     * @return  bool
     */
    public function is_flexi_units_remaining($min_flex_units = 1) {
        if ($this->is_price_plan_flexi_units_unlimited()) {
            return true;
        }
        if ($this->flexi_units >= $min_flex_units) {
            return true;
        }
    }

    public function proportion_of_flexi_units_remaining() {
        if ($this->is_price_plan_flexi_units_unlimited()) {
            return 1;
        }
        if (0 == $this->price_plan_flexi_units) {
            return 0;
        }
        return ($this->flexi_units / $this->price_plan_flexi_units);
    }

    public function proportion_of_days_remaining() {
        //TODO: Decide the proper number of days to use (30.4 was used as a rough representation of a month)
        return ($this->days_remaining / 30.4);
    }
}

class AllowanceParseException extends Exception {
}

class ThreeAllowanceCheck {
    /**
     * @var Curl
     */
    protected $curl;

    /**
     * @var PDO
     */
    protected static $pdo = null;

    protected $price_plans = array(
        "mini_flex_max" => array(
            "flexi_units" => 100
        ),
        "classic_flex_max" => array(
            "flexi_units" => 350
        ),
        "super_flex_max" => array(
            "flexi_units" => 650
        ),
        "mega_flex_max" => array(
            "flexi_units" => 1000
        ),
        "ultimate_flex_max" => array(
            "flexi_units" => null
        ),
    );

    const LOGIN_TICKET_URL = "https://sso.three.ie/mylogin/?service=https%3A%2F%2Fmy3account.three.ie%2FThreePortal%2Fappmanager%2FThree%2FMy3ROI%3F_pageLabel%3DP33403896361331912377205%26_nfpb%3Dtrue%26&resource=portlet";
    const LOGIN_URL = "https://sso.three.ie/mylogin/?service=https%3A%2F%2Fmy3account.three.ie%2FThreePortal%2Fappmanager%2FThree%2FMy3ROI%3F_pageLabel%3DP33403896361331912377205%26_nfpb%3Dtrue%26&resource=portlet";
    const ALLOWANCE_URL_BASE = "https://my3account.three.ie/My_allowance?ticket=";

    public function __construct() {
    }

    /**
     * @param PDO $pdo
     */
    public static function setPDO(PDO $pdo) {
        self::$pdo = $pdo;
    }

    public function getAllowance($username, $password) {
        $start_time = time();
        $token_result = $this->curl->get(self::LOGIN_TICKET_URL);

        $lt_token = null;
        if (preg_match_all('/name="lt" value="(.*?)"/', $token_result, $matches)) {
            $lt_token = $matches[1][0];
        }

        $login_html = $this->curl->post(self::LOGIN_URL, array(
            'username' => $username,
            'password' => $password,
            'lt'       => $lt_token
        ));

        $my_allowance_html = $this->curl->get("https://my3account.three.ie/My_allowance");
        $redirect_ticket = get_ticket($my_allowance_html);
        if ($redirect_ticket) {
            $allowance_url = self::ALLOWANCE_URL_BASE . $redirect_ticket;
            $allowance_html = $this->curl->get($allowance_url);

            $allowance = self::parse_allowance_table($allowance_html);
            $allowance->date = time();
            $price_plan = self::normalize_price_plan(self::parse_price_plan($allowance_html));
            if (isset($this->price_plans[$price_plan])) {
                $allowance->price_plan_flexi_units = $this->price_plans[$price_plan]["flexi_units"];
            }

            if (preg_match("/([0-9]+)[\s]+day/i", $allowance_html, $match)) {
                $allowance->days_remaining = intval($match[1]);
            }
        } else {
            throw new Exception("Could not get redirect session ticket to request current allowance.");
        }

        $this->log($username, $allowance, $start_time);
        return $allowance;
    }

    public function check_login($username, $password) {
        $token_result = $this->curl->get(self::LOGIN_TICKET_URL);

        $lt_token = null;
        if (preg_match_all('/name="lt" value="(.*?)"/', $token_result, $matches)) {
            $lt_token = $matches[1][0];
        }

        $login_html = $this->curl->post(self::LOGIN_URL, array(
            'username' => $username,
            'password' => $password,
            'lt'       => $lt_token
        ));

        if (preg_match("/login successful/i", $login_html)) {
            return true;
        }
        return false;
    }

    public function setCurl(Curl $curl) {
        $this->curl = $curl;
    }

    /**
     * @param $html
     * @return ThreeAllowance
     * @throws AllowanceParseException
     */
    protected static function parse_allowance_table ($html) {
        $pairs = array();

        $dom = new domDocument;
        @$dom->loadHTML($html);
        $dom->preserveWhiteSpace = false;
        $table = $dom->getElementById('allowanceRemBody');

        if (!$table) {
            throw new AllowanceParseException("Allowance table not found in HTML.");
        }

        $rows = $table->getElementsByTagName('tr');

        if (!$rows or $rows->length<3) {
            throw new AllowanceParseException("Allowance table should contain at least 3 rows.");
        }

        foreach ($rows as $row) {
            $cols = $row->getElementsByTagName('td');
            if (2 == $cols->length) {
                $label = trim($cols->item(0)->nodeValue);
                $value = trim($cols->item(1)->nodeValue);
                $pairs []= array(
                    "label" => $label,
                    "value" => $value
                );
            }
        }

        $allowance = new ThreeAllowance;

        foreach ($pairs as $pair) {
            if (preg_match("/3 to 3/i", $pair["label"])) {
                $allowance->three_to_three_calls = intval(str_replace(",", "", $pair["value"]));
            } else if (preg_match("/data/i", $pair["label"])) {
                $allowance->mobile_data = intval(str_replace(",", "", $pair["value"]));
            } else if (preg_match("/flexi/i", $pair["label"])) {
                $allowance->flexi_units = intval(str_replace(",", "", $pair["value"]));
            } else if (preg_match("/evening/i", $pair["label"]) and preg_match("/weekend/i", $pair["label"])) {
                $allowance->evening_weekend_minutes = intval(str_replace(",", "", $pair["value"]));
            }
        }

        return $allowance;
    }

    protected function parse_price_plan($html) {
        $regex = "@postpay price details.*?>(.*?)</a>@i";
        if (preg_match($regex, $html, $match)) {
            return $match[1];
        }
        return null;
    }

    protected function normalize_price_plan($price_plan) {
        if ($price_plan) {
            return strtolower(preg_replace("/[^a-zA-Z]+/", "_", $price_plan));
        }
        return null;
    }

    protected function log($username, ThreeAllowance $allowance, $start_datetime) {
        $sql = <<<SQL
INSERT INTO log
SET
log_username=?,
log_flexi_units=?,
log_three_to_three_calls=?,
log_evening_weekend_minutes=?,
log_days_remaining=?,
log_price_plan_flexi_units=?,
log_date_start=?,
log_date_end=?
SQL;

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(array(
            $username,
            $allowance->flexi_units,
            $allowance->three_to_three_calls,
            $allowance->evening_weekend_minutes,
            $allowance->days_remaining,
            $allowance->price_plan_flexi_units,
            gmdate("Y-m-d H:i:s", $start_datetime),
            gmdate("Y-m-d H:i:s")
        ));
    }
}
