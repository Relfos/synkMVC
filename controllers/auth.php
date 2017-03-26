<?php

class AuthController extends Controller {

   function __construct($context)
   {
	   parent::__construct($context);
   }

	public function checkPassword($password, $user_hash)
	{		
		$password_md5 = md5($password);

	    if(empty($user_hash)) return false;
		
	    return crypt(strtolower($password_md5), $user_hash) == $user_hash;
	}
   
   function login($context)
   {
       $email = $context->loadVarFromRequest('email', '');
	   $password = $context->loadVarFromRequest('password', '');
	   
	   $dbName = $context->config->database;
	   $cond = array("name" => array('eq' => $email));
	   $row = $context->database->fetchObject($dbName, 'users',  $cond);
	   
	   if ($this->checkPassword($password, $row['hash']))
	   {
			if ($context->config->instanced) {
				$dbName = $row['database'];
			} else {
				$dbName = $context->config->database;
			}
			$context->logIn($row['id'], $dbName);
			$context->changeModule($context->config->defaultModule);
			$context->reload();
	   }
	   else
	   {
			$context->warning = "Dados de login invalidos!" ;			
	   }	  	  
	   
	   $this->render($context);
   }

   function logout($context)
   {
		$context->logOut();
		$context->changeModule($context->config->defaultModule);
		$context->reload();
		$this->render($context);
   }

   function error404($context) 
   {   
		$context->pushTemplate("header");
		$context->pushTemplate("body");
		$context->kill("Ficheiro não encontrado");
   }
   
} 


?>