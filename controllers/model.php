<?php

class ModelController extends BaseController {

	function __construct()
	{
	   parent::__construct();
	   
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
			$fieldValue = $field->encodeValue($fieldValue);
			
			
			if (strpos($fieldValue, '*') !== false) 
			{
				$fieldValue = str_replace('*', '%', $fieldValue);
				$filter = "$fieldName like $fieldValue";
			}			
			else
			{
				$filter = "$fieldName = $fieldValue";
			}
			
			$context->addFilter($filter);
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
	
	public function save($context)
	{	
		var_dump ($_REQUEST); die();
	
		$id = $_REQUEST['id'];
		$entityClass = $_REQUEST['class'];
	   
		$entity = $context->database->fetchEntity($context, $entityClass, $id);
	   
		$row = $_REQUEST;
		foreach($entity->fields as $field) {
			$fieldName = $field->name;
			
			if (array_key_exists ($fieldName , $row ))			
			{
				$entity->$fieldName = $row[$fieldName];
			}		
		}
	
	
		$entity->save($context);
	   
		$context->changeView('grid');
		$this->render($context);
   }
  
	public function json($context)
	{
		$entityClass = $_REQUEST['class'];
		
		echo '[';
		$entities = $context->database->fetchAllEntities($context, $entityClass, null);	
		$i = 0;
		
		if (!isset($_REQUEST['required']))
		{
			echo '{"label" :"Nenhum", "value": 0}';
			$i++;
		}
			
		
		foreach($entities as $entity) {			
			if ($i>0)
			{
				echo ',';
			}
			
			$name = $entity->name;
			echo '{"label" :"'.$name.'", "value": '.$entity->id.'}';
			$i++;
		}
		echo ']';
	}
  
} 


?>