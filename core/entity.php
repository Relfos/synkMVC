<?php

require_once('field.php');

class Entity
{
	public $id = 0;
	public $insertion_date = 0;
	public $tableName = '';
	public $fields = array();
	
	function __construct($context) {
		$className = get_class($this);
		
		$customTable = strlen($this->tableName)>0;
		if (!$customTable)
		{
			$tableName = strtolower($className."_data");
			$this->tableName = $tableName;			
		}

		$this->insertion_date = time();
		
		$query = "";
		foreach($this->fields as $field) {
			$fieldName = $field->name;
			$fieldType = $field->dbType;
			$this->$fieldName = $field->defaultValue;
			
			$query .= "`$fieldName` $fieldType NOT NULL, ";			
		}
		
		if (!$customTable)
		{
			$query = "CREATE TABLE IF NOT EXISTS $tableName (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`insertion_date` int(10) unsigned NOT NULL,
				$query
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8";
	
			$context->sql->query($query);			
		}
	}
	
	public function loadFromRow($row)
	{
		$this->id = $row['id'];
		$this->insertion_date = $row['insertion_date'];
		foreach($this->fields as $field) {
			$fieldName = $field->name;
			if (array_key_exists ($fieldName , $row ))
			{
				$this->$fieldName = $row[$fieldName];
			}
		}		
	}
	
	public function save($context)
	{	
		$tableName = $this->tableName;
		
		$query = "";
		$fieldList = "";
		$valueList = "";
		$i = 0;
		foreach($this->fields as $field) {
			$fieldName = $field->name;
			$fieldType = $field->dbType;	
			$fieldValue = $field->encodeValue($this->$fieldName);
									
			if ($i>0)
			{
				$query .= ', ';
				$fieldList .= ', ';
				$valueList .= ', ';
			}
			
			$query .= "$fieldName=$fieldValue";			
			$fieldList .= $fieldName;
			$valueList .= $fieldValue;
			
			$i++;
		}

		if ($this->id == 0)
		{
			$query = "INSERT INTO $tableName ($fieldList) VALUES($valueList)";	
			$context->sql->query($query);
		}
		else
		{
			$query = "UPDATE $tableName SET $query WHERE id=".$this->id;	
			$context->sql->query($query);
		}
		
	}
	
    public function registerField($name) {
		
		$field = new Field($name);
		$this->fields[] = $field;
		
		return $field;
    }
	
	public function findField($name)
	{
		foreach($this->fields as $field) {
			$fieldName = $field->name;
			if (strcmp($fieldName, $name) === 0)
			{
				return $field;
			}
		}
		
		return null;		
	}
	
}

?>