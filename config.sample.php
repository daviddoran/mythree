<?php

use Three\Curl;

$config = new Pimple;

$config["db_host"] = "127.0.0.1";
$config["db_name"] = "three";
$config["db_username"] = "root";
$config["db_password"] = "";

$config["pdo"] = $config->share(function ($c) {
    return new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']}", $c["db_username"], $c["db_password"]);
});

$config["curl"] = function () {
    return new Curl();
};

return $config;
