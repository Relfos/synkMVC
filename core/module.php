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
	
	public function API($context, $method)
	{
		
		$error = false;
		$content = '';
		
		$entityClass = $this->entity;
		if (is_null($entityClass))
		{
			$error = true;
			$content = "method $method not supported for module ".$this->name;
		}
		else
		{			
			switch ($method)
			{
				case "get":
				{
					if (isset($_REQUEST['id']))
					{
						$entityID = $_REQUEST['id'];
						$entity = $context->database->fetchEntityByID($context, $entityClass, $entityID);	
						if ($entity->exists)
						{						
							$content = $entity->getFields();
						}
						else
						{
							$error = true;
							$content = "ID does not exist";
						}
					}
					else
					{
						$error = true;
						$content = "id is required for method $method";
					}
					break;
				}
				
				case "list":
				{
					$entities = $context->database->fetchAllEntities($context, $entityClass);	
					$content = array();
					foreach ($entities as $entity)
					{
						$fields = $entity->getFields();						
						array_push($content, $fields);
					}					
					break;
				}

				case "insert":
				{
					$entity = $context->database->createEntity($context, $entityClass);	
					
					foreach($entity->fields as $field) 
					{
						$fieldName = $field->name;
						
						if (isset($_REQUEST[$fieldName]))
						{
							$entity->$fieldName = $_REQUEST[$fieldName]; //$field->defaultValue;
						}
						else
						{
							$error = true;
							$content = $fieldName." is required for method $method";
							break;
						}									
					}
					
					if (!$error)
					{
						$entity->save($context);
						$content = $entity->id;
					}
					
					break;
				}

				case "delete":
				{
					if (isset($_REQUEST['id']))
					{
						$entityID = $_REQUEST['id'];
						$entity = $context->database->fetchEntityByID($context, $entityClass, $entityID);	
						if ($entity->exists)
						{						
							$content = array();
							$entity->remove($context);
						}
						else
						{
							$error = true;
							$content = "ID does not exist";
						}
					}
					else
					{
						$error = true;
						$content = "id is required for method $method";
					}
					break;
				}

				default:
				{
					$error = true;
					$content = "API method $method is invalid";
					break;
				}
			}	
		}

		$val = $error ? 'error' : 'ok';
		$result = array(
			'result' => $val,
			'content' => $content
		);
		
		echo json_encode($result);
	}
}


?>