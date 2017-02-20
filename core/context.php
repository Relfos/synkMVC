<?php

class Context {
    public $hasLogin = false;  
	public $isDownload = false;
	public $entityID = null;
	public $outputTarget;
	public $templateStack = array();
	
	public $text = null;
	
	function __construct() {
		$this->hasLogin = isset($_SESSION['user_id']);		
		$this->modules = array ();
		
		$this->outputTarget = $this->loadVarFromRequest('target', 'main');
			
		$this->config = new Config();

		$this->language = $this->loadVar('language', $this->config->defaultLanguage);
		
		$this->initDatabase();
			
		if ($this->hasLogin)
		{
			$dbName = $this->loadVar('user_db', '');
			$this->dbName = $dbName;

			$fields = array(
				'name' => 'varchar(30)',
				'values' => 'text',
				'keys' => 'text',
			);
			
			$this->database->createTable($dbName, 'enums', $fields, 'name');
											
			/*$this->sql->query("CREATE TABLE IF NOT EXISTS $databaseName.enums (
			`name` VARCHAR(30) NOT NULL,
			`values` TEXT NOT NULL,
			`keys` TEXT NOT NULL,
			PRIMARY KEY (`name`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8");*/
			
			$total = $this->database->getCount($dbName, 'enums');			
			if ($total == '0')
			{
				$this->createEnum('product_types', array('Produto', 'Serviço', 'Outro'));

				require_once ('countries.php');
				createCountriesEnum($this);
			}					
		}	
		else
		{
			$this->dbName = $this->config->database;
		}		
		
		$this->database->prepare();
	}
	
	public function initDatabase()
	{
		$sqlPlugin = $this->config->sqlPlugin;
		
		$pluginPath = 'plugins/database/'.$sqlPlugin.'.php';
		if (!file_exists($pluginPath))
		{
			echo 'Missing database plugin: '.$sqlPlugin;
			die();
		}
		require_once($pluginPath);
		
		$dbClassName = $this->config->sqlPlugin.'Plugin';
		$this->database = new $dbClassName($this);
	}
	
	public function prepare()
	{			
		if ($this->database->failed)
		{
			$this->hasLogin = false;
			$this->changeModule('settings');	
			return;
		}			
	
		$module = $this->loadVar('module', $this->config->defaultModule);
		$this->changeModule($module);
		
		$view = $this->loadVar('view', $this->module->defaultAction);
		$this->changeView($view);		
		
		if ($this->module->needAuth && !$this->hasLogin)
		{
			$this->changeModule('auth');
		}
		else
		{
			$this->filter = $this->loadVar('filter', null);	
		}							
		
		if (!file_exists('.htaccess'))
		{
			$htaccessData = "RewriteEngine on\n";
			foreach($this->modules as $module) {
				$name = $module->name;
				$htaccessData .= "RewriteRule $name index.php?module=$name\n";
			}
			$htaccessData .= 'RewriteRule ^(api)\/(\w+)\/(\w*) core/api.php?module=$2&action=$3 [QSA,L]'."\n";
			file_put_contents('.htaccess', $htaccessData);
		}
		
		$this->module->title = $this->module->getTitle($this);
	}
	
	public function execute($action = null)
	{
		if (is_null($action))
		{
			$action = $this->loadVarFromRequest('action', 'page');

			if ($action == 'page') 
			{
				$this->pushTemplate('header');
				$this->pushTemplate('body');
				$action = 'render';
			}
		}
		
		$this->prepare();
			
		if ($action == 'api')
		{
			$call = $this->loadVarFromRequest('call', 'list');
			$this->module->API($this, $call);
			die();
		}
		
		$this->loadMenus();

		ob_start();
		$this->runController($action);
		$html = ob_get_contents();
		ob_end_clean();

		if (isset($_REQUEST['json']) && !$this->isDownload)
		{
			$json_array=array(
			'target' => $this->outputTarget,
			'module'=> $this->module->name,						
			'title'=> $this->module->getTitle($this),
			'content'=>$html
			);
			echo json_encode($json_array);	
		}
		else
		{
			echo $html;
		}		
	}
	
	private function runController($action)
	{
		$controllerFile = 'controllers/'.$this->module->name.'.php';
		if (file_exists($controllerFile))
		{
			require_once ($controllerFile);
			
			$controllerClass = ucfirst($this->module->name)."Controller";
		}
		else
		{
			if (!is_null($this->module->entity))
			{
				$controllerClass = "ModelController";
			}
			else
			{
				echo "Could not find controller file for  ".$this->module->name;
				return;	
			}			
		}
						
		if (!class_exists($controllerClass, false))
		{
			echo "Could not find controller class for ".$this->module->name;
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
			echo "Invalid action $action on controller ".$this->module->name;
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
			if ($menu['title'] == $name)
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
		
		$this->menus[] = array('title' => 'Dashboard', 'link' => "synkNav().setModule('dashboard').go();");

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
			$this->menus[$index]['items'][] = array('label' => $module->getTitle($this), 'link' => $link);
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
		
		$dbFields = array('name' => $name, 'values' => $values,'keys' => $keys);

		$dbName = $this->dbName;		
		$this->database->insertObject($dbName, 'enums', $dbFields);
		//$this->sql->query("INSERT INTO enums (`name`, `values`, `keys`) VALUES ('$name', '$values', '$keys')");			
	}
	
	function fetchEnum($name)
	{
		$dbName = $this->dbName;
		$cond = array("name" => array('eq' => $name));
		$row = $this->database->fetchObject($dbName, 'enums',  $cond);			
		//var_dump($row); die();
		
		if (is_null($row))
		{
			return array();
		}
		
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
		$_SESSION['page'] = 1;
		
		$this->removeFilters();
		
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
		$this->outputTarget = 'body_content';
		$this->pushTemplate('body');
	}
	
	public function pushTemplate($fileName)
	{
		$this->templateStack[] = $fileName;		
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
			$this->filter = array('and' => array($this->filter, $filter));
		}
				
		$_SESSION['filter'] = $this->filter;
	}

	function removeFilters()
	{
		unset($_SESSION['filter']);
		$this->filter = null;
	}
	
	public function log($text)
	{
		if (!is_null($this->config->logFile))
		{
			file_put_contents($this->config->logFile, "$text\n", FILE_APPEND | LOCK_EX);				
		}
	}
		
	public function sendDownload($fileName, $data, $mimeType)
	{
		$this->isDownload = true;
		
		if (is_null($mimeType))
		{
			$mimeType = 'application/octet-stream';
		}
		
		$size = strlen($data);
		
		if (isset($_REQUEST['json']))
		{
			$data = base64_encode($data);
			echo
	"{
		\"mimetype\": \"$mimeType\",
		\"filename\": \"$fileName\",
		\"content\": \"$data\"
	}";		
		}
		else
		{
			header('Content-Description: File Transfer');
			header('Content-Type: '.$mimeType);
			header('Content-Disposition: attachment; filename=' . $fileName); 
			header('Content-Transfer-Encoding: binary');
			header('Connection: Keep-Alive');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: ' . $size);			
			echo $data;			
		}	
	}
	
	public function getCallstack()
	{
		ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean(); 		
		return $trace;
	}
		
   public function getPluginList($pluginType, $selectedOption = null)
   {
		$pluginList = array();
		foreach (glob("plugins/$pluginType/*.php") as $file) 
		{
			$extensionName = pathinfo($file, PATHINFO_FILENAME);
			$pluginList[] = array('name' => $extensionName, 'type' => $pluginType, 'selected' => $selectedOption == $extensionName);						
		}	
		return $pluginList;
   }
  
	public function getTranslations()
	{
	   if (is_null($this->text))
	   {
		   require_once('language/translation_'.$this->language.'.php');
		   $this->text = loadTranslation();
	   }
	   
	   return $this->text;
	}
	
	public function translate($key)
	{
	   if (is_null($this->text))
	   {
		   $this->getTranslations();
	   }
	   
	   if (array_key_exists($key, $this->text))
	   {
			return $this->text[$key];   
	   }   
	   
	   return "(?$key?)";
   }
}


?>