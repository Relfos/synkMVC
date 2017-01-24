<?php

abstract class DatabasePlugin
{	
	public $failed = false;
	public $context = null;
	
	// abstract methods
	abstract public function createDatabase($name);
	abstract public function createTable($dbName, $table, $fields, $key);
	abstract public function getCount($dbName, $table, $condition = null);
	abstract public function fetchObject($dbName, $table, $condition = null);
	abstract public function fetchAll($dbName, $table, $condition = null, $count = null, $offset = null);
	abstract public function deleteAll($dbName, $table, $condition = null);
	abstract public function insertObject($dbName, $table, $fields);
	abstract public function saveObject($dbName, $table, $fields, $condition);
	
	function __construct($context) {		
		
		$this->context = $context;
		
		if ($this->failed)
		{
			return;
		}
		
		$dbName = $context->config->database;
		$this->createDatabase($dbName);		
		
		if ($this->failed)
		{
			return;
		}

		$this->createTable($dbName, 'users', array(
			'id' => 'integer unsigned',
			'name' => 'varchar(30)',
			'hash' => 'varchar(40)',
			'database' => 'varchar(80)'
		));

		$total = $this->getCount($dbName, 'users');
		if ($total == '0')
		{
			$user = $this->createEntity($context, 'user');			
			$user->name = 'admin@synkdata.com';
			$user->hash = $this->getPasswordHash('test');
						
			if ($config->instanced)
			{
				$user->database = 'crm'. uniqid();	
			}
			else
			{
				$user->database = $config->database;
			}
			
			$user->save($context);
			//$sql->query("INSERT INTO $dbName.users (`name`, `hash`, `database`) VALUES ('$user_name', '$user_hash', '$user_db');");			
		}			
	}
	
	public function createEntity($context, $entityClass)
	{
		require_once('model/'.$entityClass.'.php');
		$className = ucfirst($entityClass);
		$result = new $className($context);
		return $result;
	}
	
	public function fetchEntityByID($context, $entityClass, $id) 
	{
		return $this->fetchEntity($context, $entityClass, "id=$id");
	}
	
    public function fetchEntity($context, $entityClass, $condition)
	{	
		$entity = $this->createEntity($context, $entityClass);
		
		if (is_null($condition))
		{
			return $entity;
		}

		$tableName = $entity->tableName;
		$dbName =  $entity->dbName;
		
		$row = $this->fetchObject($dbName, $tableName, $condition);
		
		if (!is_null($row))
		{
			$entity->loadFromRow($row);
			$entity->expand($context);
		}
				
		return $entity;
    }
		
    public function fetchAllEntities($context, $entityClass, $condition = null, $pagination = null) 
	{		
		$entities = array();
				
		$templateEntity = $this->createEntity($context, $entityClass);
		
		$tableName =  $templateEntity->tableName;
		$dbName =  $templateEntity->dbName;
				
		if (!is_null($pagination))
		{
			$items_per_page = $pagination['items'];
			$page = intval($pagination['page']);
			$page--;
			if ($page<0) 
			{				
				$page = 0;
			}
			$offset = $page * $items_per_page;
		}
		else
		{
			$items_per_page = null;
			$offset = null;
		}
		
		$rows = $this->fetchAll($dbName, $tableName, $condition, $items_per_page, $offset);
		$total = count($rows);
		for ($i=0; $i<$total; $i++)
		{			
			$entity = $this->createEntity($context, $entityClass);
			$entity->loadFromRow($rows[$i]);
			$entity->expand($context);
			$entities[] = $entity;
		}
					
		return $entities;
    }
	
    public function getEntityCount($context, $entityClass, $condition) 
	{					
		$templateEntity = $this->createEntity($context, $entityClass);
		$tableName =  $templateEntity->tableName;
		$dbName = $templateEntity->dbName;

		return $this->getCount($dbName, $tableName);
    }
	
	public function clearEntities($context, $entityClass, $condition)
	{
		$templateEntity = $this->createEntity($context, $entityClass);
		$tableName =  $templateEntity->tableName;
		$dbName = $templateEntity->dbName;
		
		$this->deleteAll($dbName, $tableName);	
	}

	function getPasswordHash($password)
	{
		if(!defined('CRYPT_MD5') || !constant('CRYPT_MD5')) {
			// does not support MD5 crypt - leave as is
			if(defined('CRYPT_EXT_DES') && constant('CRYPT_EXT_DES')) {
				return crypt(strtolower(md5($password)),
					"_.012".substr(str_shuffle('./ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), -4));
			}
			// plain crypt cuts password to 8 chars, which is not enough
			// fall back to old md5
			return strtolower(md5($password));
		}
		return @crypt(strtolower(md5($password)));
	}
	
}

?>