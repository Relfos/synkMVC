<?php

class Document extends Entity {
	function __construct($context) {	
		$this->registerField('vat')->asString(20)->showInGrid();
		$this->registerField('date')->asDate()->showInGrid();
		$this->registerField('items')->asList('product');				
		parent::__construct($context);
	}

}

?>