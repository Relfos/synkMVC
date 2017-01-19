<?php

require_once('field.php');

$entity_init = array();

class Entity
{
	public $id = 0;
	public $insertion_date = 0;
	public $tableName = '';
	public $fields = array();
	public $exists = false;
	
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
		
		global $entity_init;
		
		if (!$customTable)
		{
			if (!array_key_exists ($className, $entity_init))
			{
				$entity_init[$className] = true;
				$query = "CREATE TABLE IF NOT EXISTS $tableName (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`insertion_date` int(10) unsigned NOT NULL,
					$query
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8";
			
				$context->sql->query($query);							
			}
		}
	}
	
	public function loadFromRow($row)
	{
		if (is_null($row))
		{
			return;
		}
		
		$this->id = $row['id'];
		$this->insertion_date = $row['insertion_date'];
		
		foreach($this->fields as $field) {
			$fieldName = $field->name;
			if (array_key_exists ($fieldName , $row ))
			{
				$this->$fieldName = $row[$fieldName];
			}
		}		
		
		$this->exists = true;
	}
	
	public function expand($context)
	{
		foreach($this->fields as $field) {
			$fieldName = $field->name;			
			
			if ($field->formType == 'file')
			{
				$fieldData = $fieldName.'_thumb';				
				$hash = $this->$fieldName;
				if (strlen($hash)>0)
				{
					$row = $context->sql->fetchSingleRow("SELECT thumb FROM uploads WHERE `hash` = '$hash'");	
					$thumb = $row['thumb'];
					//echo $hash; die();
					$this->$fieldData = $thumb;					
				}
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
			$fieldValue = $field->encodeValue($context, $this->$fieldName);
									
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

		if ($this->exists)
		{
			$query = "UPDATE $tableName SET $query WHERE id=".$this->id;	
			$context->sql->query($query);
		}
		else
		{			
			$query = "INSERT INTO $tableName ($fieldList) VALUES($valueList)";	
			$context->sql->query($query);
			
			$this->exists = true;
		}		
	}
	
	public function remove($context)
	{		
		if ($this->exists)
		{		
			$tableName = $this->tableName;
		
			$query = "DELETE FROM $tableName WHERE id=".$this->id;	
			$context->sql->query($query);
			
			$this->exists = false;
			$this->id = 0;			
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