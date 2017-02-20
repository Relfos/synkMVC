<?php
//echo 'session cleared!';session_destroy();die();

require_once('core/init.php');

$context = new Context();

$context->createModule('auth')->setTitle('Login');
$context->createModule('dashboard')->setTitle('Dashboard')->requireAuth();
$context->createModule('clients')->setTitle('Clientes')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('client');
$context->createModule('users')->setTitle('Utilizadores')->setMenu('Admin')->requireAuth()->setDefaultAction('grid')->setEntity('user');
$context->createModule('categories')->setTitle('Categorias')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('category');
$context->createModule('products')->setTitle('Produtos')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('product');	
$context->createModule('documents')->setTitle('Documentos')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('document');

$context->createModule('connectors')->setTitle('Conectores')->setMenu('Admin')->requireAuth();
$context->createModule('settings')->setTitle('Configuração')->setMenu('Admin')->requireAuth();

$context->execute();

?>