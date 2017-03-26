<?php
require 'libs/Mustache/Autoloader.php';
Mustache_Autoloader::register();

error_reporting(E_ERROR | E_PARSE);

include_once("config.php");

$header = file_get_contents('views/header.html');
$content = file_get_contents('views/404.html');
$body = file_get_contents('views/body.html');

$body = str_replace('$body', $content, $body);	
$body  = str_replace('$body', $body, $header);	

$m = new Mustache_Engine;

$data = new stdClass();
$data->config = new Config();
echo $m->render($body, $data);	
?>