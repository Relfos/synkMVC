<?php

class SettingsController extends Controller {

   function __construct($context)
   {
	   parent::__construct($context);
   }
   
   public function save($context)
   {
		//var_dump($_REQUEST);die();	   
	   
		foreach($_REQUEST as $key => $value) 
		{
			if (property_exists($context->config, $key))
			{
				$context->config->$key = $value;
			}
		}
		$context->saveConfiguration();
	   
		if ($context->database->failed)
		{
			$context->initDatabase();		
			
			if ($context->database->failed)
			{
				$context->warning = 'A configuração da base de dados está incorreta!';
			}
			else
			{
				$context->changeModule('auth');
			}		   
		}
	   
	   $this->render($context);
   }
   
   public function beforeRender($context)
   {
		$dbTab = array('name' => 'database', 'label'  => 'Base de Dados', 'active' => false, 'fields' => 'sqlPlugin,sqlHost,sqlUser,sqlPass,instanced');
	   
		if ($context->database->failed)
		{
		   $selectedTabName = 'database';		   
		   $selectedTab = $dbTab;
		}
		else
		{			
			$selectedTabName = $context->loadVar('tab', 'system');
			$_SESSION['tab'] = $selectedTabName;		   
			
			$tabs = array();
			$tabs[] = array('name' => 'company', 'label'  => 'Empresa', 'active' => false, 'fields' => '');
			$tabs[] = $dbTab;
			$tabs[] = array('name' => 'entities', 'label'  => 'Entidades', 'active' => false, 'fields' => '');
			$tabs[] = array('name' => 'plugins', 'label'  => 'Plugins', 'active' => false, 'fields' => '');			
			$tabs[] = array('name' => 'system', 'label'  => 'Sistema', 'active' => false, 'fields' => '');

	   		$entities = array();
			foreach($context->modules as $module) 
			{
			   if ($module->entity)
			   {
				   $entities[] = array('title' => $module->title, 'name' => $module->entity, 'module' => $module->name);
			   }
			}
			$context->modelList = $entities;

			$pluginList = array();
			foreach (glob('plugins/export/*.php') as $file) 
			{
				$extensionName = pathinfo($file, PATHINFO_FILENAME);
				$pluginList[] = array('name' => $extensionName, 'type' => 'export');
			}	
			$context->pluginList = $pluginList;
			
			$total = count($tabs);
			for ($i=0; $i<$total; $i++)
			{
			   if ($tabs[$i]['name'] == $selectedTabName)
			   {
				   $selectedTab = $tabs[$i];
				   $tabs[$i]['active'] = true;
				   break;
			   }
			}	
		   
			if (!$selectedTab)
			{
				$i = 0;
				$selectedTab = $tabs[$i];
				$tabs[$i]['active'] = true;		   
			}			

			$context->tabs = $tabs;
		}
		
		$context->selectedTab = $selectedTab;
	   	  
	  		
		$context->pushTemplate('settings/'.$selectedTab['name']);
	 }
} 


?>