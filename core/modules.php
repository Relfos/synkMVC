<?php

class Module
{
	public $name;
	public $title;
	public $needAuth = false;
	public $defaultAction = 'default';
	public $entity = null;
	
	function __construct($name) {
		$this->name = $name;	
		$this->title = $name.'???';	
	}
	
    public function requireAuth() {		
		$this->needAuth = true;		
		return $this;
    }
	
	public function setTitle($title)
	{
		$this->title = $title;	
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
	
}


?>