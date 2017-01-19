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
	   
	   $sql = new SQL($context->config);
	   $dbName = $context->config->database;
	   $row = $sql->fetchSingleRow("SELECT * FROM $dbName.users WHERE name='$email'");
	   
	   if ($this->checkPassword($password, $row['hash']))
	   {
			$context->logIn($row['id'], $row['database']);
			$context->changeModule('dashboard');		   
			$context->reload();
	   }
	   else
	   {
			$context->warning = "Dados de login invalidos!" ;
			$this->render($context);
	   }	  	  
   }

   function logout($context)
   {
		$context->logOut();
		$context->changeModule('auth');		   
		$context->reload();
   }

} 


?>