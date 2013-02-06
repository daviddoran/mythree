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
    $config = (include "../config.php");
} else {
    error("Config file doesn't exist.", "ServerError");
}

$app = new ThreeApp($config);
header("Content-Type: application/json;charset=utf-8");
echo $app->handle(
    (isset($_REQUEST["action"]) ? $_REQUEST["action"] : null),
    $_REQUEST
);
