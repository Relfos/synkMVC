<?php

class Client extends Entity {
	function __construct($context) {
		$this->registerField('name')->asString(30)->showInGrid()->setLabel('Nome');
		$this->registerField('vat')->asString(12)->setLabel('NIF');
		$this->registerField('address')->asText()->setLabel('Morada');
		$this->registerField('city')->asString(20)->showInGrid()->setLabel('Cidade');
		$this->registerField('zip_code')->asString(8)->setLabel('Código Postal');
		$this->registerField('country')->asCountry()->setLabel('País');
		$this->registerField('email')->asEmail()->makeOptional()->showInGrid()->setLabel('Email');
		$this->registerField('website')->asURL()->makeOptional()->setLabel('Website');
		$this->registerField('phone')->asPhone()->makeOptional()->showInGrid()->setLabel('Telefone');
		$this->registerField('fax')->asPhone()->makeOptional()->setLabel('Fax');
		$this->registerField('notes')->asText()->makeOptional()->setLabel('Notas');
		$this->registerField('discount')->asPercent()->makeOptional()->setLabel('Desconto');
		$this->registerField('document_copies')->asInt()->makeOptional()->setLabel('Cópias de Documento');
		$this->registerField('payment_day')->asInt()->makeOptional()->setLabel('Dia de Pagamento');
		//$this->registerField('maturity_date')->makeOptional();
		//$this->registerField('payment_method')->makeOptional();
		//$this->registerField('delivery_method')->makeOptional();
				
		parent::__construct($context);
	}

}

?>