<?php

class Field
{
	public $name;
	public $label;
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
		$this->label = $name;
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
	
	function setLabel($val)
	{
		$this->label = $val;
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
		$this->dbType = "varchar(40)";		
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
		return $this;
	}

	function asEmail()
	{
		$this->formType = 'email';
		return $this->asString(40);
	}

	function asCountry()
	{
		return $this->asString(20);
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
	
	function encodeValue($value)
	{
		if (strpos($this->dbType, 'varchar') !== false || strpos($this->dbType, 'text') !== false )
		{
			return "'$value'";
		}
		else
		{
			return $value;	
		}		
	}
	
}


?>