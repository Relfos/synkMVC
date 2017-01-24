<?php

class mysqlPlugin extends DatabasePlugin
{
	private $db;
	
	function __construct($context) 
	{
		$config = $context->config;
		
		$this->db = new mysqli($config->sqlHost, $config->sqlUser, $config->sqlPass);
		if ($this->db->connect_error)  
		{
			$this->failed = true;
			return;
		}		
				
		if (!mysqli_set_charset($this->db, "utf8"))
		{
			die($this->db->error);
		}					
		
		mb_internal_encoding('UTF-8');
		
		parent::__construct($context);
	}

	// method overrides
	public function createDatabase($name)
	 {
		$this->query("CREATE DATABASE IF NOT EXISTS `$name`"); 
	 }
	 
	 public function createTable($dbName, $table, $fields, $key = null)
	 {
		$this->selectDatabase($dbName);
		 
		$query = '';
		
		if (!$key)
		{
			$query .= '`id` int(10) unsigned NOT NULL AUTO_INCREMENT, ';
			$query .= '`insertion_date` int(10) unsigned NOT NULL, ';
			$key = 'id';
		}
		
		foreach($fields as $fieldName => $fieldType) 
		{		
			$query .= "`$fieldName` $fieldType NOT NULL, ";			
		}
		
		$query = "CREATE TABLE IF NOT EXISTS $table (						
			$query
			PRIMARY KEY (`$key`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		
		$this->query($query);		 
	 }
	 
	 public function getCount($dbName, $table, $condition = null)
	 {
		$this->selectDatabase($dbName);
		$query = "SELECT count(*) as total FROM `$table`";
		
		if ($condition != null && strlen(condition)>0)
		{
			$query .= " WHERE ".$condition;
		}
		
		$row = $this->fetchSingleRow($query);
		if (is_null($row))
		{
			return 0;
		}
		
		return intval($row['total']);		 
	 }
	 
	 public function fetchObject($dbName, $table, $condition = null)
	 {
		$this->selectDatabase($dbName);
		$query = "SELECT * FROM `$table` WHERE $condition";
		return $this->fetchSingleRow($query);
	 }
	
	 public function fetchAll($dbName, $table, $condition = null, $count = null, $offset = null)
	 {		 
		$this->selectDatabase($dbName);
		$query = "SELECT * FROM `$table`";
		
		if (!is_null($condition))
		{
			$query .= " WHERE $condition";
		}			
		
		if ($count)
		{
			if ($offset)
			{
				$query .= " LIMIT $offset , $count";
			}
			else
			{
				$query .= " LIMIT $count";
			}
		}		
		
		$result = $this->query($query);		
		$rows = array();
		while ($row = $this->fetchRow($result)) 
		{
			$rows[] = $row;
		}
		
		return $rows;
	 }
	 
	 public function deleteAll($dbName, $table, $condition = null)
	 {
		$this->selectDatabase($dbName);
		$query = "DELETE FROM ".$table;
		if ($condition != null && strlen($condition)>0)
		{
			$query .= " WHERE ".$condition;
		}
		
		$this->query($query);		 
	 }
		
	public function selectDatabase($name)
	{
		$this->query("USE $name;");
	}
	
	public function saveObject($dbName, $table, $fields, $condition)
	{
		$query = '';
		$i = 0;
		foreach($fields as $fieldName => $fieldValue) 
		{
			if ($i>0)
			{
				$query .= ', ';
			}
			
			$fieldValue = $this->encodeField($fieldValue);
			$query .= "$fieldName=$fieldValue";						
			$i++;
		}

		$this->selectDatabase($dbName);
		$query = "UPDATE $table SET $query WHERE $condition";	
		$this->query($query);
	}
	
	public function insertObject($dbName, $table, $fields)
	{
		$fieldList = "";
		$valueList = "";
		$i = 0;
		foreach($fields as $fieldName => $fieldValue) 
		{									
			if ($i>0)
			{
				$fieldList .= ', ';
				$valueList .= ', ';
			}
			
			$fieldList .= $fieldName;
			$valueList .= $this->encodeField($fieldValue);
			
			$i++;
		}
		
		$this->selectDatabase($dbName);
		$query = "INSERT INTO $table ($fieldList) VALUES($valueList)";	
		$this->query($query);
	}
	
	//*******************
	private function query($query)
	{
		if ($this->failed)
		{
			return null;
		}

		$this->context->log($query);
		$this->context->log($this->context->getCallstack());
		
		//echo $query."<br>";		die();
		$result = mysqli_query($this->db,$query);
		if(!$result) 
		{
			$this->failed = true;
			echo $this->db->error."<br>".$query; die();	
			return null;						
		}
		return $result;
	}
	
	private function fetchRow($result)
	{
		if (empty($result))	return null;

		$row = mysqli_fetch_assoc($result);
		return $row;
	}
	
	private function fetchSingleRow($query)
	{
		$query .= ' LIMIT 1';
		$result = $this->query($query);
		$row = $this->fetchRow($result);
		if ($row === false)
		{
			return null;
		}
		
		return $row;
	}
		
	private function encodeField($value)
	{
		if (is_numeric($value) || is_bool($value))
		{
			return $value;
		}
		
		$value = mysqli_real_escape_string($this->db, $value);
		
		return "'$value'";
	}
	
}


?>