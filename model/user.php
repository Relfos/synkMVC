<?php

class User extends Entity {
	function __construct($context) {
		$this->dbName = $context->config->database;
		$this->tableName = 'users';
		
		$this->registerField('name')->asString(30)->showInGrid();
		$this->registerField('hash')->asString(40)->makeHidden();
		$this->registerField('database')->asString(80);
		$this->registerField('admin')->asBoolean();
				
		parent::__construct($context);
	}

	function toString() {
		return $this->name;
	}
	
}

?>