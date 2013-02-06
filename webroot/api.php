<?php

require "../vendor/autoload.php";

function json($obj) {
    header("Content-type: application/json;charset=utf-8");
    return json_encode($obj);
}

function error($message, $code = "Error") {
    error_log("Three Allowance error: " . $message);
    return json(array(
        "error" => array("code" => $code, "message" => $message)
    ));
}

if (file_exists("../config.php")) {
    $config = (include "../config.php");
} else {
    die(error("Config file doesn't exist.", "ServerError"));
}

$app = new Three\App($config);
header("Content-Type: application/json;charset=utf-8");
echo $app->handle(
    (isset($_REQUEST["action"]) ? $_REQUEST["action"] : null),
    $_REQUEST
);
