<?php

class User extends Entity {
	function __construct($context) {
		$this->tableName = $context->config->database.'.users';
		
		$this->registerField('name')->asString(30)->showInGrid()->setLabel('Nome');
		$this->registerField('hash')->asString(40)->setLabel('Hash')->makeHidden();
		$this->registerField('database')->asString(80)->setLabel('Base de Dados');
				
		parent::__construct($context);
	}

}

?>