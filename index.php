<?php
//echo 'session cleared!';session_destroy();die();

require_once('core/init.php');

$context = new Context();

$context->createModule('auth');
$context->createModule('dashboard')->requireAuth();
$context->createModule('clients')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('client');
$context->createModule('users')->setMenu('Admin')->requireAuth()->setDefaultAction('grid')->setEntity('user');
$context->createModule('categories')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('category');
$context->createModule('products')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('product');	
$context->createModule('documents')->setMenu('Modulos')->requireAuth()->setDefaultAction('grid')->setEntity('document');

$context->createModule('connectors')->setMenu('Admin')->requireAuth();
$context->createModule('settings')->setMenu('Admin')->requireAuth();

$context->execute();

?>