<?php

class SettingsController extends Controller {

   function __construct($context)
   {
	   parent::__construct($context);
   }

   public function page($context)
   {
		$_SESSION['tab'] = $_REQUEST['tab'];
		$this->render($context);
   }
   
   public function render($context)
   {
	   $selectedTabName = $context->loadVar('tab', 'system');
	   $tabs = array();
	   $tabs[] = array('name' => 'system', 'label'  => 'Sistema', 'active' => false);
	   $tabs[] = array('name' => 'database', 'label'  => 'Base de Dados', 'active' => false);
	   $tabs[] = array('name' => 'entities', 'label'  => 'Entidades', 'active' => false);
	   $tabs[] = array('name' => 'plugins', 'label'  => 'Plugins', 'active' => false);
	   
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
	   $context->selectedTab = $selectedTab;
	   parent::render($context);
   }
} 


?>