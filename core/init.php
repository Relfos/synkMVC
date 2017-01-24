<?php
require 'libs/Mustache/Autoloader.php';
Mustache_Autoloader::register();

error_reporting(E_ALL);
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ERROR | E_PARSE);

session_start();


require_once('config.php');

$config = new Config();

require_once('core/utils.php');
require_once('core/context.php');
require_once('core/database.php');
require_once('core/entity.php');
require_once("core/module.php");
require_once('core/controller.php');
require_once('core/model.php');


?>