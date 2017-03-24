<?php

class Category extends Entity {
	function __construct($context) {
		$this->registerField('name')->asString(30)->showInGrid();
		$this->registerField('parent')->asEntity('category')->showInGrid();
		$this->registerField('description')->asText()->makeOptional();
						
		parent::__construct($context);
	}

	function toString() {
		return $this->name;
	}
	
}

?>