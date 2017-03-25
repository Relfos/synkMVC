<?php

class ModelController extends Controller {

	function __construct($context)
	{
	   parent::__construct($context);
	   
	   $className = get_class($this);
	   $this->entityClass = strtolower(str_replace('Controller', '', $className));
	}

	public function filter($context)
	{
		$entityClass = $_REQUEST['class'];
		$fieldName = $_REQUEST['field_name'];
		$fieldValue = $_REQUEST['field_value'];
		
		$entity = $context->database->createEntity($context, $entityClass);
		
	   //var_dump($_REQUEST);	   die();

		$field = $entity->findField($fieldName);
		
		if (is_null($field))
		{
			die("Invalid field for filtering");
		}
		else
		{						
			if (strpos($fieldValue, '*') !== false) 
			{
				$op = 'like';
			}			
			else
			{
				$op = 'eq';
			}
			
			$condition = array($fieldName => array($op => $fieldValue));

			$context->addFilter($condition);
			$this->render($context);
		}
	}

	public function unfilter($context)
	{
	   $id = $_REQUEST['id'];
	   //var_dump($_REQUEST);	   die();

	   $context->removeFilters();
	   
	   $this->render($context);
	}

	public function edit($context)
	{
	   $id = $_REQUEST['id'];
	   //echo "LOL2 ".$id; die();

	   $context->entityID = $id;
	   $context->changeView('edit');
	   $this->render($context);
	}
	
	public function remove($context)
	{
		$id = $_REQUEST['id'];
		$entityClass = $_REQUEST['class'];
		//var_dump($_REQUEST); die();
	   
		$entity = $context->database->fetchEntityByID($context, $entityClass, $id);
		$entity->remove($context);
	   
		$context->changeView('grid');
		$context->reload();
		$this->render($context);
	}

	public function clear($context)
	{
		$id = $_REQUEST['id'];
		$entityClass = $_REQUEST['class'];

		//var_dump($_REQUEST); die();
	   
		$context->database->clearEntities($context, $entityClass);	   
	}

	public function upload($context)
	{
		var_dump($_FILES);
		var_dump ($_REQUEST); die();
	}
	
	public function save($context)
	{			
		//var_dump ($_REQUEST); die();
	
		$id = $_REQUEST['id'];
		$entityClass = $_REQUEST['class'];
	   
		$entity = $context->database->fetchEntityByID($context, $entityClass, $id);
	   
		$row = $_REQUEST;
		foreach($entity->fields as $field) 
		{
			$fieldName = $field->name;
			
			if (array_key_exists ($fieldName , $row ))			
			{
				$entity->$fieldName = $row[$fieldName];
			}		
		}
	
		$entity->save($context);
	   
		$context->changeView('grid');
		$context->reload();
		$this->render($context);
   }
  
	public function json($context)
	{
		$entityClass = $_REQUEST['class'];
		
		if (isset($_REQUEST['term']))
		{
			$term = $_REQUEST['term'];
			$condition = array("name" => array('like' => $term));
		}	
		else
		{
			$condition = null;
		}
		
		
		echo '[';
		$entities = $context->database->fetchAllEntities($context, $entityClass, $condition);	
		$i = 0;
		
		if (!isset($_REQUEST['required']))
		{
			$none = $context->translate('system_none');
			echo '{"label" : "'.$none.'", "value": "0"}';
			$i++;
		}
			
		
		foreach($entities as $entity) {			
			if ($i>0)
			{
				echo ',';
			}
			
			$name = $entity->name;
			echo '{"label" :"'.$name.'", "value": "'.$entity->id.'"}';
			$i++;
		}
		echo ']';
	}
	
	public function export($context)
	{
		//var_dump ($_REQUEST); die();
		$entityClass = $_REQUEST['class'];
		$format = $_REQUEST['format'];
				
		$pluginFile = 'plugins/export/'.$format.'.php';
		if (file_exists($pluginFile))
		{
			require_once($pluginFile);	
		}
		else
		{
			echo "Could not load plugin '".$format."'";
			die();
		}		
		
		$pluginClass = ucfirst($format)."Plugin";
		$plugin = new $pluginClass($context);
		$plugin->export($context, $entityClass);
	}
  
} 


?>