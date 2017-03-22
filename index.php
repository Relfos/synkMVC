<?php
//echo 'session cleared!';session_destroy();die();

require_once('core/init.php');

$checkPerm = function($user, $module, $action) {
	if ($module->name == 'users' && !$user->admin)	{
		return false;
	}
		
	return true;
};

$context = new Context();

$context->createModule('auth');
$context->createModule('dashboard')->setPermissions($checkPerm);
$context->createModule('clients')->setMenu('Modulos')->setPermissions($checkPerm)->setDefaultAction('grid')->setEntity('client');
$context->createModule('users')->setMenu('Admin')->setPermissions($checkPerm)->setDefaultAction('grid')->setEntity('user');
$context->createModule('categories')->setMenu('Modulos')->setPermissions($checkPerm)->setDefaultAction('grid')->setEntity('category');
$context->createModule('products')->setMenu('Modulos')->setPermissions($checkPerm)->setDefaultAction('grid')->setEntity('product');	
$context->createModule('documents')->setMenu('Modulos')->setPermissions($checkPerm)->setDefaultAction('grid')->setEntity('document');

$context->createModule('connectors')->setMenu('Admin')->setPermissions($checkPerm);
$context->createModule('settings')->setMenu('Admin')->setPermissions($checkPerm);

$context->execute();

?>