<?php
require 'libs/Mustache/Autoloader.php';
Mustache_Autoloader::register();

error_reporting(E_ALL);
//error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ERROR | E_PARSE);

$sessionPath = getcwd(). '/tmp';
if (!file_exists($sessionPath)) {
	mkdir($sessionPath);
}
ini_set('session.save_path', $sessionPath);
session_start();

require_once('core/utils.php');

if (!file_exists("config.php"))
{
	$config = new stdClass();
	$config->logFile = null;
	$config->database = 'crm';
	$config->instanced = false;
	$config->dbPlugin = 'mysql';
	$config->sqlHost = 'localhost';
	$config->sqlUser = 'root';
	$config->sqlPass = '';
	$config->defaultUser = 'admin';
	$config->defaultPass = 'test';
	$config->defaultModule = 'auth';
	$config->defaultLanguage = 'pt';
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