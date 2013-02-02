<?php

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

    const LOGIN_TICKET_URL = "https://sso.three.ie/mylogin/?service=https%3A%2F%2Fmy3account.three.ie%2FThreePortal%2Fappmanager%2FThree%2FMy3ROI";
    const LOGIN_URL = "https://sso.three.ie/mylogin/?service=https%3A%2F%2Fmy3account.three.ie%2FThreePortal%2Fappmanager%2FThree%2FMy3ROI";
//    const LOGIN_TICKET_URL = "https://sso.three.ie/mylogin/?service=https%3A%2F%2Fmy3account.three.ie%2FThreePortal%2Fappmanager%2FThree%2FMy3ROI%3F_pageLabel%3DP33403896361331912377205%26_nfpb%3Dtrue%26&resource=portlet";
//    const LOGIN_URL = "https://sso.three.ie/mylogin/?service=https%3A%2F%2Fmy3account.three.ie%2FThreePortal%2Fappmanager%2FThree%2FMy3ROI%3F_pageLabel%3DP33403896361331912377205%26_nfpb%3Dtrue%26&resource=portlet";
    //https://my3account.three.ie/ThreePortal/appmanager/Three/My3ROI
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

        $this->curl->post(self::LOGIN_URL, array(
            'username' => $username,
            'password' => $password,
            'lt'       => $lt_token
        ));

        $my_allowance_html = $this->curl->get("https://my3account.three.ie/My_allowance");
        $redirect_ticket = self::get_ticket($my_allowance_html);
        if ($redirect_ticket) {
            $allowance_url = self::ALLOWANCE_URL_BASE . $redirect_ticket;
            $allowance_html = $this->curl->get($allowance_url);

            $allowance = self::parse_allowance_table($allowance_html);
            $allowance->current_spend = self::parse_current_spend($allowance_html);
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

    protected static function parse_price_plan($html) {
        $regex = "@postpay price details.*?>(.*?)</a>@i";
        if (preg_match($regex, $html, $match)) {
            return $match[1];
        }
        return null;
    }

    protected static function parse_current_spend($html) {
        $regex = '@&euro;[\s]*([0-9\.]+)@i';
        if (preg_match_all($regex, $html, $matches)) {
            return max($matches[1]);
        }
        return null;
    }

    protected static function normalize_price_plan($price_plan) {
        if ($price_plan) {
            return strtolower(preg_replace("/[^a-zA-Z]+/", "_", $price_plan));
        }
        return null;
    }

    protected static function get_ticket($content) {
        if (preg_match('/ticket=([a-zA-Z0-9_\-]+)/', $content, $match)) {
            return $match[1];
        }
        return false;
    }

    protected function log($username, ThreeAllowance $allowance, $start_datetime) {
        $sql = 'INSERT INTO log
                SET log_username=?,
                    log_flexi_units=?, log_price_plan_flexi_units=?,
                    log_three_to_three_calls=?, log_evening_weekend_minutes=?,
                    log_days_remaining=?, log_current_spend=?,
                    log_date_start=?, log_date_end=?';
        $stmt = self::$pdo->prepare($sql);
        $stmt->execute(array(
            $username,
            $allowance->flexi_units,
            $allowance->price_plan_flexi_units,
            $allowance->three_to_three_calls,
            $allowance->evening_weekend_minutes,
            $allowance->days_remaining,
            $allowance->current_spend,
            gmdate("Y-m-d H:i:s", $start_datetime),
            gmdate("Y-m-d H:i:s")
        ));
    }
}