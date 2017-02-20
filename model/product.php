<?php

class Product extends Entity {
	function __construct($context) {
		$this->registerField('name')->asString(30)->showInGrid()->setLabel('Nome');
		$this->registerField('category')->asEntity('category')->showInGrid()->setLabel('Categoria');
		$this->registerField('type')->asEnum('product_type')->showInGrid()->setLabel('Tipo');
		$this->registerField('reference')->asString(30)->showInGrid()->setLabel('Referência');
		$this->registerField('description')->asText()->setLabel('Descrição')->makeOptional();
		$this->registerField('price')->asMoney()->showInGrid()->setLabel('Preço');
		$this->registerField('picture')->asImage()->showInGrid()->setLabel('Foto')->makeOptional();
		
		//$this->registerField('unit')->asEnum('product_units')->setLabel('Unidade');
						
		parent::__construct($context);
	}

}

?>