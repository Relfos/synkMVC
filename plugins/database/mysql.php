<?php

class mysqlPlugin extends DatabasePlugin
{
	private $db;
	private $currentDB;
	
	function __construct($context) 
	{
		$config = $context->config;
		
		$this->client = new mysqli($config->sqlHost, $config->sqlUser, $config->sqlPass);
		if ($this->client->connect_error)  
		{
			$this->fail("Unable to connect to db");
			return;
		}		
				
		if (!mysqli_set_charset($this->client, "utf8"))
		{
			die($this->client->error);
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
		
		if ($condition != null)
		{
			$condition = $this->compileCondition($condition);
			$query .= " WHERE $condition";
		}
		
		$row = $this->fetchSingleRow($query);
		if (is_null($row))
		{
			return 0;
		}
		
		return intval($row['total']);		 
	 }
	 
	 public function fetchObject($dbName, $table, $condition)
	 {
		$this->selectDatabase($dbName);
		$condition = $this->compileCondition($condition);
		$query = "SELECT * FROM `$table` WHERE $condition";
		return $this->fetchSingleRow($query);
	 }
	
	 public function fetchAll($dbName, $table, $condition = null, $count = null, $offset = null)
	 {		 
		$this->selectDatabase($dbName);
		$query = "SELECT * FROM `$table`";
		
		if (!is_null($condition))
		{
			$condition = $this->compileCondition($condition);
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
		if (!is_null($condition))
		{
			$condition = $this->compileCondition($condition);
			$query .= " WHERE $condition";
		}
		
		$this->query($query);		 
	 }
		
	public function selectDatabase($name)
	{
		if ($this->currentDB == $name)
		{
			return;
		}
		$this->currentDB = $name;
		$this->query("USE $name;");
	}
	
	public function saveObject($dbName, $table, $fields, $key, $value)
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
			$query .= "`$fieldName`=$fieldValue";						
			$i++;
		}
	
		$this->selectDatabase($dbName);
		$value = $this->encodeField($value);
		$query = "UPDATE $table SET $query WHERE $key = $value";		
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
			
			$fieldList .= "`$fieldName`";
			$valueList .= $this->encodeField($fieldValue);
			
			$i++;
		}
		
		$this->selectDatabase($dbName);
		$query = "INSERT INTO `$table` ($fieldList) VALUES($valueList)";	
		$this->query($query);
		
		$newID = mysqli_insert_id($this->client);
		return $newID;
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
		$result = mysqli_query($this->client, $query);
		if(!$result) 
		{
			$this->fail($this->client->error."<br>".$query);
			die();	
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
		
		$value = mysqli_real_escape_string($this->client, $value);
		
		return "'$value'";
	}
	
	private function compileExpression($expr)
	{
		foreach ($expr as $key => $value)
		{
			if ($key == 'like')
			{
				$value = "%$value%";
			}
			
			$value = $this->encodeField($value);
						
			switch ($key)
			{
				case 'like': $op ="like"; break;
				case 'eq': $op = '='; break;
				case 'lt': $op = '<'; break;
				case 'gt': $op = '>'; break;
				case 'lte': $op = '<='; break;
				case 'gte': $op = '>='; break;
				case 'ne': $op = '<>'; break;
				default: echo "invalid operator [$key]"; die();
			}			
			
			return "$op $value";
		}
		
		return null;
	}

	private function compileCondition($condition, $separator = null)
	{
		if (!is_array($condition))
		{			
			echo "invalid condition $condition, must be an array!"; 
			echo $this->context->getCallstack();
			die ();
		}		
		
		$i = 0;
		$result = '';
		foreach ($condition as $key => $value)
		{
			if (is_null($separator)) // terminal node			
			{
				if ($key == 'or')
				{
					return $this->compileCondition($value, 'OR');
				}

				if ($key == 'and')
				{
					return $this->compileCondition($value, 'AND');
				}
		
				$expr = $this->compileExpression($value);
				return "`$key` $expr";
			}
			
			if ($i>0)
			{
				$result .= " $separator ";
			}

			$subCond = $this->compileCondition($value);
			$result .= " $subCond";
			$i++;
		}
		
		return "($result)";
	}

}


?>