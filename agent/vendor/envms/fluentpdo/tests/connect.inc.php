<?php
error_reporting(E_ALL | E_STRICT);
include dirname(__FILE__) . "/../src/FluentPDO.php";

$pdo = new PDO("mysql:dbname=fluentdb", "root");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
$fpdo = new FluentPDO($pdo);
//~ $software->debug = true;
