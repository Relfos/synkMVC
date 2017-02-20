<?php

class mongoPlugin extends DatabasePlugin
{
	private $db;
	
	function __construct($context) 
	{
		$config = $context->config;
		
		$this->client = new MongoClient();
				
		parent::__construct($context);
	}

	// method overrides
	public function createDatabase($name)
	{
		$this->selectDatabase($name);
	}	 
		
	public function selectDatabase($name)
	{
		$this->db = $this->client->$name;
	}

	public function createTable($dbName, $table, $fields, $key = null)
	{
		$this->selectDatabase($dbName);

		$collection = $this->db->createCollection($table);		 
	 }
	 
	 public function getCount($dbName, $table, $condition = null)
	 {
		return $this->db->$table->count();
	 }
	 	 
	 public function fetchObject($dbName, $table, $condition)
	 {
		$this->selectDatabase($dbName);
		$query = $this->compileCondition($condition);
		//var_dump($query);die();
					
		$result = $this->db->$table->findOne($query);
		if (is_null($result))
		{
			return array();
		}

		$result = objectToArray($result);

		$result['id'] = $result['_id']['$id'];
		unset($result['_id']);
		
		//var_dump($result);die();
		return $result;
	 }
	
	 public function fetchAll($dbName, $table, $condition = null, $count = null, $offset = null)
	 {		 
		$this->selectDatabase($dbName);
		if (is_null($condition))
		{
			$result = $this->db->$table->find();			
		}	
		else
		{
			$query = $this->compileCondition($condition);
			$result = $this->db->$table->find($query);			
		}
		
		$result = objectToArray($result);
		foreach ($result as $key => $obj)
		{
			$result[$key]['id'] = $key;
		}
		
		return $result;
	 }
	 
	 public function deleteAll($dbName, $table, $condition = null)
	 {
		$this->selectDatabase($dbName);

		if (is_null($condition))
		{
			$this->db->$table->remove();			
		}
		else
		{
			$condition = $this->compileCondition($condition);
			$this->db->$table->remove($query);			
		}			
	 }
	
	public function saveObject($dbName, $table, $fields, $key, $value)
	{
		if ($key == 'id')
		{
			$key = '_id';
		}
		$fields[$key] = $value;

		$this->selectDatabase($dbName);
		$this->db->$table->save($fields);			
	}
	
	public function insertObject($dbName, $table, $fields)
	{
		$this->selectDatabase($dbName);
		//echo $this->context->getCallstack().'<br>';
		//echo $table.'<br>';
		//var_dump($fields);die();
		$this->db->$table->insert($fields);
		$newID = $field['_id']->{'$id'};
		return $newID;
	}
	
	//*******************				
	function compileCondition($condition)
	{
		if (!is_array($condition))
		{
			echo "invalid condition, must be an array!"; die ();
		}		
		
		$result = array();
		foreach ($condition as $key => $value)
		{
			if (is_array($value)) // terminal node
			{
				$outValue = $this->compileCondition($value);
			}		
			else
			{
				$outValue = $value;
			}
			
			switch ($key)
			{
				case 'like': $outKey ="like"; break;
				case 'eq': 
				case 'lt': 
				case 'gt': 
				case 'lte':
				case 'gte':
				case 'ne': 
				case 'and': 
				case 'or': $outKey = "\$$key"; break;
				default: $outKey = $key; break;
			}			

			if ($outKey == 'id')
			{
				$outKey = '_id';
				$outValue = $outValue[0];
				$outValue =  $outValue['$eq'];
				$outValue = new MongoId($outValue);
			}
			else
			if (is_array($outValue) && array_key_exists('$eq', $outValue[0]))
			{
				$outValue = $outValue[0];
				$outValue =  $outValue['$eq'];				
			}

			$item = array($outKey => $outValue);	
			
			if (is_array($value)) // terminal node		
			{
				return $item;
			}
			
			$result[] = $item;
		}
			
		return $result;
	}
}


?>