<?php

class Sale extends Entity {
	function __construct($context) {
		$this->isWritable = false;
		
		$this->registerField('product')->asEntity("product");
		$this->registerField('quantity')->asInt();
				
		parent::__construct($context);
	}

}

?>