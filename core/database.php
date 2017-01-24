<?php

class Database
{	
	function __construct($context) {
		$sql = $context->sql;
		$config = $context->config;
		
		if ($sql->failed)
		{
			return;
		}
		
		$dbName = $config->database;
		$sql->query('CREATE DATABASE IF NOT EXISTS '.$dbName);
		
		if ($sql->failed)
		{
			return;
		}

		$sql->query("CREATE TABLE IF NOT EXISTS $dbName.users (
		`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(30) NOT NULL,
		`hash` VARCHAR(40) NOT NULL,
		`database` VARCHAR(80) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE = InnoDB;");

		$result = $sql->query("SELECT count(*) as total FROM $dbName.users;");
		$row = $sql->fetchRow($result);
		if ($row['total'] == '0')
		{

			$user_name = 'admin@synkdata.com';
			$user_pass = 'test';
			
			if ($config->instanced)
			{
				$user_db = 'crm'. uniqid();	
			}
			else
			{
				$user_db = $config->database;
			}
			
			$user_hash = $this->getPasswordHash($user_pass);

			$sql->query("INSERT INTO $dbName.users (`name`, `hash`, `database`) VALUES ('$user_name', '$user_hash', '$user_db');");			
		}			
	}
	
	public function createEntity($context, $name)
	{
		require_once('model/'.$name.'.php');
		$className = ucfirst($name);
		$result = new $className($context);
		return $result;
	}
	
	public function fetchEntityByID($context, $name, $id) 
	{
		return $this->fetchEntity($context, $name, "id=$id");
	}
	
    public function fetchEntity($context, $name, $condition)
	{	
		$entity = $this->createEntity($context, $name);
		
		if (is_null($condition))
		{
			return $entity;
		}

		$tableName = $entity->tableName;
		$dbName = $context->databaseName;
		$query = "SELECT * FROM $tableName WHERE $condition";

		$row = $context->sql->fetchSingleRow($query);
		
		if (!is_null($row))
		{
			$entity->loadFromRow($row);
			$entity->expand($context);
		}
				
		return $entity;
    }
		
    public function fetchAllEntities($context, $name, $condition, $pagination) {
		
		$entities = array();
				
		$templateEntity = $this->createEntity($context, $name);
		
		$tableName =  $templateEntity->tableName;
		$dbName = $context->databaseName;
		
		$query = "SELECT * FROM $tableName";
		if ($condition != null && strlen(condition)>0)
		{
			$query .= " WHERE ".$condition;
		}
		
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
			$query .= " LIMIT $offset , $items_per_page";
		}
		
		$result = $context->sql->query($query);
		
		while ($row = $context->sql->fetchRow($result)) {
			$entity = $this->createEntity($context, $name);
			$entity->loadFromRow($row);
			$entity->expand($context);
			$entities[] = $entity;
		}
					
		return $entities;
    }
	
    public function getEntityCount($context, $name, $condition) 
	{					
		$templateEntity = $this->createEntity($context, $name);
		$tableName =  $templateEntity->tableName;
		$dbName = $context->databaseName;
				
		$query = "SELECT count(*) as total FROM ".$tableName;
		if ($condition != null && strlen(condition)>0)
		{
			$query .= " WHERE ".$condition;
		}
		
		$row = $context->sql->fetchSingleRow($query);
		if (is_null($row))
		{
			return 0;
		}
		
		return intval($row['total']);
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