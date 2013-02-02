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