<?php

require_once('field.php');

$entity_init = array();

class Entity
{
	public $id = 0;
	public $insertion_date = 0;
	public $tableName = null;
	public $dbName = null;
	public $fields = array();
	public $exists = false;
	
	function __construct($context) {		
		$className = get_class($this);
		$this->className = $className;
		$dbName = $context->dbName;
		
		if (is_null($this->tableName))
		{
			$this->tableName = strtolower($className."_data");	
		}

		if (is_null($this->dbName))
		{
			$this->dbName = $context->dbName;			
		}

		$this->insertion_date = time();
		
		$dbFields = array();
		foreach($this->fields as $field) 
		{
			$fieldName = $field->name;
			$fieldType = $field->dbType;
			$this->$fieldName = $field->defaultValue;
		
			$dbFields[$fieldName] = $fieldType;
		}
		
		global $entity_init;
		
		if (!array_key_exists ($className, $entity_init))
		{
			$entity_init[$className] = true;			
			$context->database->createTable($this->dbName, $this->tableName, $dbFields);			
		}
	}
	
	public function getFields($skipHidden = false)
	{
		$result = array();
		foreach($this->fields as $field) 
		{
			if ($skipHidden && $field->hidden)
			{
				continue;
			}
			
			$fieldName = $field->name;
			$fieldValue = $this->$fieldName;
					
			$result[$fieldName] = $fieldValue;
		}
		return $result;
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
		$dbName = $context->dbName;
		foreach($this->fields as $field) {
			$fieldName = $field->name;			
			
			if ($field->formType == 'file')
			{
				$fieldData = $fieldName.'_thumb';				
				$hash = $this->$fieldName;
				if (strlen($hash)>0)
				{
					$cond = array("hash" => array('eq' => $hash));
					$row = $context->database->fetchObject($dbName, 'uploads', $cond);	
					$thumb = $row['thumb'];
					//echo $hash; die();
					$this->$fieldData = $thumb;					
				}
			}
		}				
	}
	
	public function save($context)
	{	
		$dbName = $this->dbName;
		$tableName = $this->tableName;
		
		$dbFields = $this->getFields();
			
		if ($this->exists)
		{
			$context->database->saveObject($dbName, $tableName, $dbFields, 'id', $this->id);
		}
		else
		{			
			$dbFields['insertion_date'] = $this->insertion_date;
			$this->id = $context->database->insertObject($dbName, $tableName, $dbFields);
			$this->exists = true;
		}		
	}
	
	public function remove($context)
	{	
		if ($this->exists)
		{		
			$tableName = $this->tableName;
			$dbName = $this->dbName;
			
			$cond = array('id' => array('eq' => $this->id));
			$context->database->deleteAll($dbName, $tableName, $cond);
			
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
	
	public function translateField($context, $field)
	{
		return $context->translate('entity_'. strtolower($this->className) .'_'.$field->name);
	}
	
}

?>