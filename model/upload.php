<?php

class Upload extends Entity {
	function __construct($context) {
		$this->registerField('name')->asString(60);
		$this->registerField('hash')->asString(40);
		$this->registerField('data')->asText();
		$this->registerField('size')->asInt();
		$this->registerField('thumb')->asText();
		$this->registerField('thumbsize')->asInt();						
		parent::__construct($context);
	}

	function toString() {
		return $this->name;
	}
	
}

?>