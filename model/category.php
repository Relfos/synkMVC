<?php

class Category extends Entity {
	function __construct($context) {
		$this->registerField('name')->asString(30)->showInGrid()->setLabel('Nome');
		$this->registerField('parent')->asEntity('category')->showInGrid()->setLabel('Categoria Base');
		$this->registerField('description')->asText()->setLabel('Descrição')->makeOptional();
						
		parent::__construct($context);
	}

}

?>