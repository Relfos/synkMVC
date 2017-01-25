<?php

class User extends Entity {
	function __construct($context) {
		$this->dbName = $context->config->database;
		$this->tableName = 'users';
		
		$this->registerField('name')->asString(30)->showInGrid()->setLabel('Nome');
		$this->registerField('hash')->asString(40)->setLabel('Hash')->makeHidden();
		$this->registerField('database')->asString(80)->setLabel('Base de Dados');
				
		parent::__construct($context);
	}

}

?>