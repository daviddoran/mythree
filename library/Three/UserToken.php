<?php

namespace Three;

/**
 * A pseudorandom token sent to clients for authentication
 *
 * The user logs in using their Three credentials (username/password)
 * only once, these are saved to the database, and a token is returned to the client.
 * The client later sends the token to the API when checking the account balance.
 *
 * //Create and save a new token with "username" and "password"
 * $user_token = UserToken::create_from_credentials(Pimple $config, "username", "password");
 *
 * //Find a token by token text
 * $user_token = UserToken::find(Pimple $config, $user_token->token);
 *
 * //Returns array(username => ..., password => ...)
 * $credentials = $user_token->get_credentials();
 *
 * //Delete a token (e.g. when the user logs out)
 * $user_token->delete();
 */
class UserToken {
    /**
     * @var \Pimple
     */
    protected $config = null;

    /**
     * The pseudorandom token string
     *
     * @var string
     */
    public $token = null;

    /**
     * Private constructor.
     *
     * Need to use create_from_credentials(...) for new tokens.
     */
    private function __construct(\Pimple $config) {
        $this->config = $config;
    }

    public static function create_from_credentials(\Pimple $config, array $credentials) {
        if (empty($credentials["username"]) or empty($credentials["password"])) {
            throw new \Exception("Username or password empty (can't create token).");
        }

        $username = $credentials["username"];
        $password = $credentials["password"];

        if (function_exists("openssl_random_pseudo_bytes")) {
            $token = sha1(openssl_random_pseudo_bytes(32));
        } else {
            $token = sha1($username.uniqid());
        }

        $sql = "INSERT INTO user_token SET token=:token, username=:username, password=:password, created=:date";
        $stmt = $config["pdo"]->prepare($sql);

        $result = $stmt->execute(array(
            "token" => $token,
            "username" => $username,
            "password" => $password,
            "date" => gmdate("Y-m-d H:i:s")
        ));

        if ($result) {
            $new_token = new UserToken($config);
            $new_token->token = $token;
            return $new_token;
        }
        throw new \Exception("Failed to create new token.");
    }

    public static function find(\Pimple $config, $token) {
        $sql = "SELECT * FROM user_token WHERE token=:token";
        $stmt = $config["pdo"]->prepare($sql);
        $result = $stmt->execute(array("token" => $token));
        if ($result) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $user_token = new UserToken($config);
            $user_token->token = $row["token"];
            return $user_token;
        }
        throw new \Exception("Database query error finding token.");
    }

    public function get_credentials() {
        $sql = "SELECT username,password FROM user_token WHERE token=:token";
        $stmt = $this->config["pdo"]->prepare($sql);
        $stmt->execute(array("token" => $this->token));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) {
            return array("username" => $row["username"], "password" => $row["password"]);
        }
        throw new \Exception("Failed to get username and password.");
    }

    public function delete() {
        $sql = "DELETE FROM user_token WHERE token=:token";
        $stmt = $this->config["pdo"]->prepare($sql);
        $result = $stmt->execute(array("token" => $this->token));
        return ((bool) $result);
    }
}
