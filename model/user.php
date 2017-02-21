<?php

class User extends Entity {
	function __construct($context) {
		$this->dbName = $context->config->database;
		$this->tableName = 'users';
		
		$this->registerField('name')->asString(30)->showInGrid();
		$this->registerField('hash')->asString(40)->makeHidden();
		$this->registerField('database')->asString(80);
				
		parent::__construct($context);
	}

}

?>