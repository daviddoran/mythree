<?php

class UserToken {
    /**
     * @var PDO
     */
    protected static $pdo = null;

    public $token = null;
    public $username = null;
    public $password = null;

    public function __construct() {
    }

    public static function setPDO(PDO $pdo) {
        self::$pdo = $pdo;
    }

    public static function findByToken($token) {
        $sql = "SELECT * FROM user_token WHERE user_token_text = :user_token";
        $stmt = self::$pdo->prepare($sql);
        $result = $stmt->execute(array("user_token" => $token));
        if ($result) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ut = new UserToken;
            $ut->token = $row["user_token_text"];
            $ut->username = $row["user_token_username"];
            $ut->password = $row["user_token_password"];
            return $ut;
        } else {
            throw new Exception("Database query error finding token.");
        }
    }

    public function delete() {
        $sql = "DELETE FROM user_token WHERE user_token_text = :user_token";
        $stmt = self::$pdo->prepare($sql);
        $result = $stmt->execute(array("user_token" => $this->token));
        return ((bool) $result);
    }

    public function save() {
        $this->token = sha1($this->username.uniqid());

        $sql = "INSERT INTO user_token SET user_token_text=?, user_token_username=?, user_token_password=?, user_token_date=?";
        $stmt = self::$pdo->prepare($sql);

        $stmt->execute(array(
            $this->token,
            $this->username,
            $this->password,
            gmdate("Y-m-d H:i:s")
        ));

        return $this->token;
    }
}

require dirname(__DIR__) . "/database.php";

UserToken::setPDO($pdo);

$action = (isset($_REQUEST["action"]) ? $_REQUEST["action"] : null);

function json($obj) {
    header("Content-type: application/json;charset=utf-8");
    die(json_encode($obj));
}
function error($message, $code = "Error") {
    error_log("Three Allowance error: " . $message);
    json(array(
        "error" => array(
            "code" => $code,
            "message" => $message
        )
    ));
}

require dirname(__DIR__) . "/api2.php";

ThreeAllowanceCheck::setPDO($pdo);

switch ($action) {
    case "login":
        $username = $_POST["username"];
        $password = $_POST["password"];

        $check = new ThreeAllowanceCheck;
        $curl = new Curl();
        $curl->set_cookie_file_name(rtrim(sys_get_temp_dir(), "/")."/three_cookie_".uniqid().".txt");
        $check->setCurl($curl);
        $login_succeeded = $check->check_login($username, $password);
        if ($login_succeeded) {
            $ut = new UserToken;
            $ut->username = $username;
            $ut->password = $password;
            $ut->save();
            json(array(
                "user_token" => $ut->token
            ));
        } else {
            error("Three login failed.", "LoginFailed");
        }
        break;

    case "logout":
        $ut = UserToken::findByToken($_POST["user_token"]);
        if ($ut->delete()) {
            json(array("result" => "Deleted"));
        } else {
            error("Error deleting user token.");
        }
        break;

    case "check_balance":
        $ut = UserToken::findByToken($_GET["user_token"]);
        if ($ut) {
            $check = new ThreeAllowanceCheck();
            Curl::$debugging = true;
            $curl = new Curl();
            $curl->set_cookie_file_name(rtrim(sys_get_temp_dir(), "/")."/three_cookie_".uniqid().".txt");
            $check->setCurl($curl);
            $allowance = $check->getAllowance($ut->username, $ut->password);
            json($allowance);
        } else {
            error("User token invalid.", "UserTokenInvalid");
        }
        break;

    default:
        json(array(
            "error" => array(
                "code" => "ActionInvalid",
                "message" => "Action must be specified."
            )
        ));
        break;
}