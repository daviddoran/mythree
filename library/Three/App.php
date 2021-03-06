<?php

namespace Three;

use \Exception;
use \Pimple;

class App {
    /**
     * @var Pimple
     */
    protected $config;

    public function __construct(Pimple $config) {
        $this->config = $config;
    }

    public function handle($action, $params) {
        try {
            switch ($action) {
                case "login":
                    $result = $this->login($params);
                    break;
                case "logout":
                    $result = $this->logout($params);
                    break;
                case "check_balance":
                    $result = $this->check_balance($params);
                    break;
                default:
                    throw new Exception("Requested action not recognised.");
                    break;
            }
        } catch (Exception $e) {
            $result = array("error" => array(
                "code" => "Error",
                "message" => $e->getMessage()
            ));
        }
        return json_encode($result);
    }

    public function login(array $params) {
        $username = (isset($params["username"]) ? $params["username"] : null);
        $password = (isset($params["password"]) ? $params["password"] : null);

        if (empty($username) or empty($password)) {
            throw new Exception("Username or password empty.");
        }

        $check = new AllowanceCheck($this->config);
        $login_succeeded = $check->check_login($username, $password);
        if ($login_succeeded) {
            $user_token = UserToken::create_from_credentials($this->config, array(
                "username" => $username,
                "password" => $password
            ));
            return array("user_token" => $user_token->token, "success" => true);
        }
        throw new Exception("Login to My3 system failed.");
    }

    public function logout(array $params) {
        if (empty($params["user_token"])) {
            throw new Exception("Missing user_token parameter.");
        }

        $user_token = UserToken::find($this->config, $params["user_token"]);
        if ($user_token->delete()) {
            return array("message" => "Logged out successfully.", "success" => true);
        }
        throw new Exception("Error deleting user token.");
    }

    public function check_balance(array $params) {
        if (empty($params["user_token"])) {
            throw new Exception("Missing user_token parameter.");
        }
        $user_token = UserToken::find($this->config, $params["user_token"]);
        if ($user_token) {
            $check = new AllowanceCheck($this->config);
            return array("balance" => $check->get_allowance($user_token), "success" => true);
        }
        throw new Exception("User token invalid.");
    }
}
