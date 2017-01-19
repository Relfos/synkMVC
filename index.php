<?php
require 'libs/Mustache/Autoloader.php';
Mustache_Autoloader::register();

error_reporting(E_ERROR | E_WARNING | E_PARSE);
//error_reporting(E_ERROR | E_PARSE);

session_start();

//echo 'session cleared!';session_destroy();die();

require_once('core/utils.php');
require_once('core/config.php');
require_once('core/context.php');
require_once('core/sql.php');
require_once('core/database.php');
require_once('core/entity.php');
require_once ("core/module.php");

$context = new Context();

$context->createModule('auth')->setTitle('Login');
$context->createModule('dashboard')->setTitle('Dashboard')->requireAuth();
$context->createModule('clients')->setTitle('Clientes')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('client');
$context->createModule('users')->setTitle('Utilizadores')->setMenu('Admin')->requireAuth()->setDefaultAction('grid')->setEntity('user');
$context->createModule('categories')->setTitle('Categorias')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('category');
$context->createModule('products')->setTitle('Produtos')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('product');	
$context->createModule('documents')->setTitle('Documentos')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('document');

$context->createModule('company')->setTitle('Empresa')->setMenu('Admin')->requireAuth();
$context->createModule('connectors')->setTitle('Conectores')->setMenu('Admin')->requireAuth();
$context->createModule('settings')->setTitle('Configuração')->setMenu('Admin')->requireAuth();

$context->prepare();

$action = $context->loadVarFromRequest('action', 'page');

if (strcmp($action, 'page') === 0) {
	$context->layout = file_get_contents('views/layout.html');
	$action = 'render';

}

require ('controllers/controller.php');
require ('controllers/model.php');

$context->execute($action);

?>