<?php

function __autoload($classname) {
    require dirname(__DIR__)."/library/".$classname.".php";
}

function json($obj) {
    header("Content-type: application/json;charset=utf-8");
    die(json_encode($obj));
}
function error($message, $code = "Error") {
    error_log("Three Allowance error: " . $message);
    json(array(
        "error" => array("code" => $code, "message" => $message)
    ));
}

if (file_exists("../config.php")) {
    $pdo = (include "../config.php");
} else {
    error("Config file doesn't exist.", "ServerError");
}

UserToken::setPDO($pdo);
ThreeAllowanceCheck::setPDO($pdo);

$action = (isset($_REQUEST["action"]) ? $_REQUEST["action"] : null);

switch ($action) {
    case "login":
        $username = (isset($_POST["username"]) ? $_POST["username"] : null);
        $password = (isset($_POST["password"]) ? $_POST["password"] : null);

        if (empty($username) or empty($password)) {
            error("Username or password empty.", "LoginFailed");
        }

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
            json(array("user_token" => $ut->token));
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
        error("Action must be specified.", "ActionInvalid");
        break;
}