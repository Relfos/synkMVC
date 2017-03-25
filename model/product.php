<?php

class Product extends Entity {
	function __construct($context) {
		$this->registerField('name')->asString(30)->showInGrid();
		$this->registerField('category')->asEntity('category')->showInGrid();
		$this->registerField('type')->asEnum('product_type')->showInGrid();
		$this->registerField('reference')->asString(30)->showInGrid();
		$this->registerField('description')->asText()->makeOptional();
		$this->registerField('price')->asMoney()->showInGrid();
		$this->registerField('picture')->asImage()->showInGrid()->makeOptional();
		
		//$this->registerField('unit')->asEnum('product_units')->setLabel('Unidade');
						
		parent::__construct($context);
	}

	function toString() {
		return $this->name;
	}
	
}

?>