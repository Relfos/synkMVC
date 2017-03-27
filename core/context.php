<?php
class Context {
    public $hasLogin = false;  
	public $isDownload = false;
	public $entityID = null;
	public $outputTarget;
	public $templateStack = array();
	
	public $user = null;
	
	public $text = null;
	
	public $menus = array();
	
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
				require_once ('enums.php');
				$this->createEnum("country", getCountryList());
				$this->createEnum('product_type', array('product', 'service', 'other'));
			}					
			
			$user_id = $_SESSION['user_id'];
			$this->user = $this->database->fetchEntityByID($this, 'user', $user_id);		
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
	
	public function prepare($action)
	{			
		if ($this->database->failed)
		{
			$this->hasLogin = false;
			$this->changeModule('settings');	
			return;
		}			

		$module = $this->loadVarFromRequest('module', $this->config->defaultModule);
		if ($module == 'current') {
			$module = $_SESSION['module'];
		}
		
		$this->changeModule($module);
		
		$view = $this->loadVar('view', $this->module->defaultAction);
				
		if ($this->hasLogin && $this->module->name == 'auth' && $this->curView == $this->module->defaultAction && $action != 'logout') {
			$this->changeModule($this->config->defaultModule);
		}

		if (!$this->module->hasAccess($this, $view))
		{
			$this->changeModule('auth');
			$this->changeView('forbidden');		
		}
		else
		{
			$this->changeView($view);		
			$this->filter = $this->loadVar('filter', null);	
		}							

		if (!file_exists('.htaccess'))
		{
			$htaccessData = "RewriteEngine on\n";
			foreach($this->modules as $module) {
				$name = $module->name;
				$htaccessData .= "RewriteRule $name index.php?module=$name\n";
			}
			$htaccessData .= 'RewriteRule ^(api)\/(\w+)\/(\w*)\/(\d*) index.php?module=$2&action=api&call=$3&id=$4 [QSA,L]'."\n";
			$htaccessData .= 'RewriteRule ^(api)\/(\w+)\/(\w*) index.php?module=$2&action=api&call=$3 [QSA,L]'."\n";
			//Determine the RewriteBase automatically/dynamically
			$htaccessData .= 'RewriteCond $0#%{REQUEST_URI} ^([^#]*)#(.*)\1$'."\n";
			$htaccessData .= 'RewriteRule ^.*$ - [E=BASE:%2]'."\n";
			//if request is not for a file
			$htaccessData .= 'RewriteCond %{REQUEST_FILENAME} !-d'."\n";
			//if request is not for a directory
			$htaccessData .= 'RewriteCond %{REQUEST_FILENAME} !-f'."\n";
			//forward it to 404.php in current directory
			$htaccessData .= 'RewriteRule . %{ENV:BASE}/404.php [L]'."\n";	
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
		
		$this->prepare($action);
			
		if ($action == 'api')
		{
			$call = $this->loadVarFromRequest('call', 'list');
			$this->module->API($this, $call);
			die();
		}
		
		$this->loadMenus();

		ob_start();
		$this->runController($action);
		$this->terminate();
	}
	
	private function terminate() {
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
			$this->kill("Invalid action $action on controller ".$this->module->name);
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
	
	public function addMenu($name, $link = null) {
		if (is_null($link)) {			
			$this->menus[] = array('title' => $name, 'items' => array());
		} else {
			$this->menus[] = array('title' => $name, 'link' => $link);
		}
		$index = $this->findMenuIndex($name);
		return $index;
	}
	
	public function addMenuLink($index, $label, $link) {
		$this->menus[$index]['items'][] = array('label' => $label, 'link' => $link);
	}
	
	private function loadMenus()
	{					
		foreach($this->modules as $module) 
		{
			if (is_null($module->menu))
			{
				continue;
			}
							
			$link = $module->getLink();
			if (is_null($link)) {
				continue;
			}
			
			if (!$module->hasAccess($this, $module->defaultAction)){
				continue;
			}

			$index = $this->findMenuIndex($module->menu);
			$this->menus[$index]['items'][] = array('label' => $module->getTitle($this), 'action' => $link, 'link' => '#');
		}				
	}
	
	function createEnum($name, $valuesArray)
	{
		$values = '';
		foreach($valuesArray as $val) {		
			$value = $val;
			
			if (strlen($values)>0)
			{
				$values .= '|';
			}
			
			$values .= $value;
		}
		
		$dbFields = array('name' => $name, 'values' => $values);

		$dbName = $this->dbName;		
		$this->database->insertObject($dbName, 'enums', $dbFields);
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
		$values = explode("|", $temp);		
		return $values;
	}
	
	function kill($error)
	{
		$this->error = $error;		
		$this->pushTemplate("404");	      		
		$this->render();		
		//$this->terminate();		die();
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
		
		$this->module = $modules[$module];
		
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
		$this->user = $this->database->fetchEntityByID($this, 'user', $user_id);
		$this->loadMenus();
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
			return $_REQUEST[$name];
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
	
	public function render() 
	{
		//echo "menus...";var_dump($this->menus); die();
		$layoutTemplate = '';
		
		if (!is_null($this->controller)) {
			$this->controller->beforeRender($this);
		}
		//var_dump($context->templateStack); die();

		$total = count($this->templateStack);
		for ($i=$total-1; $i>=0; $i--)
		{			
			$fileName = 'views/'.$this->templateStack[$i].'.html';
			
			if (file_exists($fileName))
			{
				$body = file_get_contents($fileName);
			}
			else
			{
				$layoutTemplate = "Error loading $fileName, the file was not found!";
				break;
			}
			
			
			$layoutTemplate = str_replace('$body', $layoutTemplate, $body);	
		}
						
		if (!is_null($this->controller)) {
			$this->controller->afterRender($this, $layoutTemplate);
		}
		
		$m = new Mustache_Engine;
		echo $m->render($layoutTemplate, $this);	
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