<?php

$host = "127.0.0.1";
$dbname = "three";
$username = "root";
$password = "";

return new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
