<?php

class Document extends Entity {
	function __construct($context) {		
		$this->registerField('name')->asString(30)->showInGrid();
		$this->registerField('hash')->asString(40)->makeHidden();
		$this->registerField('database')->asString(80);
				
		parent::__construct($context);
	}

}

?>