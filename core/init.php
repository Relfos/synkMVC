<?php
require 'libs/Mustache/Autoloader.php';
Mustache_Autoloader::register();

error_reporting(E_ALL);
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ERROR | E_PARSE);

session_start();

require_once('core/utils.php');

if (!file_exists("config.php"))
{
	$config = new stdClass();
	$config->logFile = null;
	$config->database = 'synk';
	$config->instanced = false;
	$config->dbPlugin = 'mysql';
	$config->sqlHost = 'localhost';
	$config->sqlUser = 'root';
	$config->sqlPass = '';
	saveConfiguration($config);	
}

require_once('config.php');

$config = new Config();

require_once('core/context.php');
require_once('core/database.php');
require_once('core/entity.php');
require_once("core/module.php");
require_once('core/controller.php');
require_once('core/model.php');


?>