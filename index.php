<?php
require 'libs/Mustache/Autoloader.php';
Mustache_Autoloader::register();

error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ERROR | E_PARSE);
session_start();

//echo 'session cleared!';session_destroy();die();

require_once('core/config.php');
require_once('core/context.php');
require_once('core/sql.php');
require_once('core/database.php');
require_once('core/entity.php');
require_once ("core/modules.php");

$context = new Context();
$context->database = new Database($context->config);

function executeAction($context, $action)
{
	$controllerFile = 'controllers/'.$context->curModule.'.php';
	if (!file_exists($controllerFile))
	{
		echo "Could not find controller file for  ".$context->curModule;
		return;
	}
	
	require_once ($controllerFile);

	$controllerClass = ucfirst($context->curModule)."Controller";
	
	if (!class_exists($controllerClass, false))
	{
		echo "Could not find controller class for ".$context->curModule;
		return;
	}
	
	$controller = new $controllerClass;

	if (is_callable(array($controller, $action)))
	{
		$controller->$action($context);
	}
	else
	{
		echo "Invalid action $action on controller ".$context->curModule;
	}
}

$action = $context->loadVarFromRequest('action', 'page');


if (strcmp($action, 'page') === 0) {
	$context->layout = file_get_contents('views/layout.html');
	$action = 'render';

}

require ('controllers/base.php');
require ('controllers/model.php');
executeAction($context, $action);

?>