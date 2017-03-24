<?php

class Client extends Entity {
	function __construct($context) {
		$this->registerField('name')->asString(30)->showInGrid();
		$this->registerField('vat')->asString(12);
		$this->registerField('address')->asText();
		$this->registerField('city')->asString(20)->showInGrid();
		$this->registerField('zip_code')->asString(8);
		$this->registerField('country')->asCountry()->showInGrid();
		$this->registerField('email')->asEmail()->makeOptional()->showInGrid();
		$this->registerField('website')->asURL()->makeOptional();
		$this->registerField('phone')->asPhone()->makeOptional()->showInGrid();
		$this->registerField('fax')->asPhone()->makeOptional();
		$this->registerField('notes')->asText()->makeOptional();
		$this->registerField('discount')->asPercent()->makeOptional();
		$this->registerField('document_copies')->asInt()->makeOptional();
		$this->registerField('payment_day')->asInt()->makeOptional();
		//$this->registerField('maturity_date')->makeOptional();
		//$this->registerField('payment_method')->makeOptional();
		//$this->registerField('delivery_method')->makeOptional();
				
		parent::__construct($context);
	}

	function toString() {
		return $this->name;
	}
	
}

?>