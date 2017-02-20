<?php

class Module
{
	public $name;
	public $title;
	public $needAuth = false;
	public $defaultAction = 'default';
	public $entity = null;
	public $menu = null;
	
	function __construct($name) {
		$this->name = $name;	
		$this->title = $name.'???';	
	}
	
    public function requireAuth() {		
		$this->needAuth = true;		
		return $this;
    }
		
    public function setDefaultAction($action) {		
		$this->defaultAction = $action;		
		return $this;
    }
	
	public function setEntity($entity)
	{
		$this->entity = $entity;		
		return $this;		
	}

	public function setMenu($menu)
	{
		$this->menu = $menu;		
		return $this;		
	}

	public function getLink()
	{
		return "synkNav().setModule('".$this->name."').go();";
	}
	
	public function getTitle($context)
	{
		return $context->translate('module_'.$this->name);
	}
		
}


?>