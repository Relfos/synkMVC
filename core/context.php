<?php

class Context {
    public $hasLogin = false;  
	public $entityID = null;

	function __construct() {
		$this->hasLogin = isset($_SESSION['user_id']);		
		$this->modules = array ();
		
		$this->config = new Config();
				
		if ($this->hasLogin)
		{
			$databaseName = $this->loadVar('user_db', '');	
			$this->sql = new SQL($this->config);
			$this->sql->query('CREATE DATABASE IF NOT EXISTS '.$databaseName);
			$this->sql->selectDatabase($databaseName);
			
			$this->databaseName = $databaseName;
			
			$this->sql->query("CREATE TABLE IF NOT EXISTS enums (
			`name` VARCHAR(30) NOT NULL,
			`values` TEXT NOT NULL,
			`keys` TEXT NOT NULL,
			PRIMARY KEY (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");
			
			$result = $this->sql->query("SELECT count(*) as total FROM enums;");
			$row = $this->sql->fetchRow($result);
			if ($row['total'] == '0')
			{
				$this->createEnum('product_types', array('Produto', 'Serviço', 'Outro'));

				require_once ('countries.php');
				createCountriesEnum($this);
			}					
		}			
		
		$this->database = new Database($this->config);
	}
	
	public function prepare()
	{			
		$module = $this->loadVar('module', 'auth');
		$this->changeModule($module);
		
		$view = $this->loadVar('view', $this->module->defaultAction);
		$this->changeView($view);		
		
		$this->filter = $this->loadVar('filter', null);
		
		$this->loadMenus();
	}
	
	public function execute($action)
	{
		$controllerFile = 'controllers/'.$this->curModule.'.php';
		if (!file_exists($controllerFile))
		{
			echo "Could not find controller file for  ".$this->curModule;
			return;
		}
		
		require_once ($controllerFile);

		$controllerClass = ucfirst($this->curModule)."Controller";
		
		if (!class_exists($controllerClass, false))
		{
			echo "Could not find controller class for ".$this->curModule;
			return;
		}
		
		$controller = new $controllerClass($this);
		$this->controller = $controller;

		if (is_callable(array($controller, $action)))
		{
			$controller->$action($this);
		}
		else
		{
			echo "Invalid action $action on controller ".$this->curModule;
		}
	}
	
	public function createModule($name) {	
		$module = new Module($name);
		$this->modules[$name] = $module;
		
		return $module;
	}
	
	private function findMenuIndex($name)
	{
		$i = 0;
		foreach($this->menus as $menu) {
			if (strcmp($menu['title'], $name) == 0)
			{
				return $i;
			}
			$i++;
		}		
		
		$this->menus[] = array('title' => $name, 'items' => array());
		return $i;
	}
	
	function loadMenus()
	{
		$this->menus = array();
		
		$this->menus[] = array('title' => 'Dashboard', 'link' => "navigate('dashboard')");

		foreach($this->modules as $module) 
		{
			if (is_null($module->menu))
			{
				continue;
			}
			
			$link = $module->getLink();
			if (is_null($link))
			{
				continue;
			}
			
			$index = $this->findMenuIndex($module->menu);
			$this->menus[$index]['items'][] = array('label' => $module->title, 'link' => $link);
		}				
	}
	
	function createEnum($name, $valuesArray)
	{
		$values = '';
		$keys = '';
		$i = 0;
		foreach($valuesArray as $val) {
			
			if (is_array($val))
			{
				$value = $val['value'];
				$key = $val['key'];
			}
			else
			{
				$value = $val;
				$key = $i;				
			}
			
			if (strlen($values)>0)
			{
				$values .= '|';
				$keys .= '|';
			}
			
			$values .= $value;
			$keys .= $key;
			$i++;
		}
		$this->sql->query("INSERT INTO enums (`name`, `values`, `keys`) VALUES ('$name', '$values', '$keys')");			
	}
	
	function fetchEnum($name)
	{
		$row = $this->sql->fetchSingleRow("SELECT `values`, `keys` FROM enums WHERE `name` = '$name'");			
		$temp  = $row['values'];
		$names = explode("|", $temp);			
		
		$temp  = $row['keys'];
		$keys = explode("|", $temp);			

		$values = array();
		
		$i = 0;
		foreach($names as $name) {
			$entry = array( 'key' => $keys[$i], 'value' => $name, 'index' => $i);			
			$values[] = $entry;
			$i++;
		}		
		return $values;
	}
	
	function kill($error)
	{
		echo $error;
		die();
	}
	
	function changeModule($module)
	{
		$modules = $this->modules;

		if (array_key_exists($module, $modules))
		{
			$this->module = $modules[$module];		
		}
		else
		{
			die("Could not load module: ".$module);
			return;
		}
		
		$_SESSION['module'] = $module;
		$this->curModule = $module;
			
		
		$this->changeView($this->module->defaultAction);
	}

	function changeView($view)
	{
		$_SESSION['view'] = $view;
		$this->curView = $view;
	}

	function logIn($user_id, $user_db)
	{
		$_SESSION['user_id'] = $user_id;
		$_SESSION['user_db'] = $user_db;
		$this->hasLogin = true;
	}

	function logOut()
	{
		unset($_SESSION['user_id']);
		unset($_SESSION['user_db']);
		session_destroy();
		$this->hasLogin = false;
	}
	
	function reload()
	{
		echo "[RELOAD]";
	}

	function loadVar($name, $defaultValue)
	{
		if (isset($_REQUEST[$name]))
		{
			$result = $_REQUEST[$name];	
			
			if (strcmp($result, 'current') !=0)
			{
				return $result;	
			}			
		}

		if (isset($_SESSION[$name]))
		{
			return $_SESSION[$name];	
		}

		return $defaultValue;
	}

	function loadVarFromSession($name, $defaultValue)
	{
		if (isset($_SESSION[$name]))
		{
			return $_SESSION[$name];	
		}

		return $defaultValue;
	}

	function loadVarFromRequest($name, $defaultValue)
	{
		if (isset($_REQUEST[$name]))
		{
			return $_REQUEST[$name];	
		}

		return $defaultValue;
	}

	function addFilter($filter)
	{
		if (is_null($this->filter))
		{
			$this->filter = $filter;	
		}
		else
		{
			$this->filter .= ' AND '. $filter;	
		}
		
		$_SESSION['filter'] = $this->filter;
	}

	function removeFilters()
	{
		unset($_SESSION['filter']);
		$this->filter = null;
	}
	
	function log($text)
	{
		file_put_contents($this->config->logFile, "$text\n", FILE_APPEND | LOCK_EX);	
	}
	
}


?>