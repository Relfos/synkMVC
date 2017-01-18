<?php

class Database
{	
	function __construct($config) {
		$sql = new SQL($config);
		
		$dbName = $config->database;
		$sql->query('CREATE DATABASE IF NOT EXISTS '.$dbName);
		$sql->selectDatabase($dbName);
		
		$sql->query("CREATE TABLE IF NOT EXISTS users (
		`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
		`name` VARCHAR(30) NOT NULL,
		`hash` VARCHAR(40) NOT NULL,
		`database` VARCHAR(80) NOT NULL,
		PRIMARY KEY (`id`)
		) ENGINE = InnoDB;");

		$result = $sql->query("SELECT count(*) as total FROM users;");
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
	
    public function fetchEntity($context, $name, $id) {
		
		$entity = $this->createEntity($context, $name);
		
		if ($id <= 0)
		{
			return $entity;
		}

		$tableName = $entity->tableName;
		$query = "SELECT * FROM ".$tableName." WHERE id=$id";

		$row = $context->sql->fetchSingleRow($query);
		
		$entity->loadFromRow($row);
		$entity->expand($context);
		
		return $entity;
    }
	
    public function fetchAllEntities($context, $name, $condition) {
		
		$entities = array();
				
		$templateEntity = $this->createEntity($context, $name);
		
		$tableName =  $templateEntity->tableName;
		
		
		$query = "SELECT * FROM ".$tableName;
		if ($condition != null && strlen(condition)>0)
		{
			$query .= " WHERE ".$condition;
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