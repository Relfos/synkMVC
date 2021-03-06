<?php

class Field
{
	public $name;
	public $dbType;
	public $formType = 'text';
	public $formClass = '';
	public $controlType = 'input';
	public $required = true;
	public $defaultValue = '';
	public $grid = false;
	public $hidden = false;
	public $enum = null;
	public $entity = null;
	public $unit = null;
	
	function __construct($name) {
		$this->name = $name;
	}
	
	function makeOptional()
	{
		$this->required = false;
		return $this;
	}
	
	function setDefaultValue($val)
	{
		$this->defaultValue = $val;
		return $this;
	}
	
	function showInGrid()
	{
		$this->grid = true;
		return $this;
	}
	
	function makeHidden()
	{
		$this->hidden = true;
		return $this;
	}
	
	function asDate()
	{
		$this->dbType = 'datetime';
		$this->defaultValue = date('Y-m-d');
		$this->formType = 'date';
		return $this;
	}

	function asList($entity)
	{
		$this->dbType = 'mediumtext';
		$this->defaultValue = '';
		$this->formType = 'table';
		$this->entity = $entity;
		return $this;
	}

	function asInt()
	{
		$this->dbType = 'int';
		$this->defaultValue = '0';
		$this->formType = 'number';
		return $this;
	}
	
	function asFloat()
	{
		$this->dbType = 'float';
		$this->defaultValue = '0';
		return $this;
	}

	function asPercent()
	{
		$this->unit = '%';		
		return $this->asFloat();
	}

	function asMoney()
	{
		$this->unit = '€';
		return $this->asFloat();
	}

	function asString($maxLength)
	{
		$this->dbType = "varchar($maxLength)";
		$this->defaultValue = '';
		return $this;
	}

	function asText()
	{
		$this->dbType = "text";
		$this->defaultValue = '';
		$this->controlType = 'textarea';
		return $this;
	}

	function asEnum($name)
	{
		$this->dbType = "varchar(30)";
		$this->enum = $name;
		$this->defaultValue = '';
		return $this;
	}
	
	function asFile()
	{
		$this->dbType = "int";		
		$this->defaultValue = '';
		$this->formType = 'file';
		return $this;
	}
	
	function asImage()
	{
		$this->asFile();
		return $this;
	}

	function asTime()
	{
		$this->dbType = "int";
		$this->defaultValue = '0';
		return $this;
	}

	function asEntity($name)
	{
		$this->dbType = "int unsigned";
		$this->entity = $name;
		$this->defaultValue = '0';
		return $this;
	}

	function asBoolean()
	{
		$this->dbType = "tinyint(1)";
		$this->defaultValue = '0';
		$this->required = false;
		$this->formType = "checkbox";
		return $this;
	}

	function asEmail()
	{
		$this->formType = 'email';
		return $this->asString(40);
	}

	function asCountry()
	{		
		$this->asEnum('country');
		$this->defaultValue = 'PT';
		return $this;
	}

	function asURL()
	{
		$this->formType = 'url';
		return $this->asString(200);
	}

	function asPhone()
	{
		return $this->asString(13);
	}

	function asInteger()
	{
		$this->dbType = 'int';
		$this->defaultValue = '0';
		return $this;
	}	
	
	/*function encodeValue($context, $value)
	{
		$value = $context->database->escapeString($value);
		if (strpos($this->dbType, 'varchar') !== false || strpos($this->dbType, 'text') !== false )
		{
			return "'$value'";
		}
		else
		{
			return $value;	
		}		
	}*/
}


?>