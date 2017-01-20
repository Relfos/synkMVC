<?php

class Controller {

   function __construct($context)
   {
   }
   
   public function progress($context)
   {
	   $progressFile  = "tmp/" . session_id() . ".bar";
	   if (file_exists($progressFile))
	   {
			$progress = file_get_contents($progressFile);
	   }
	   else
	   {
		   $progress = 0;
	   }
	   
	   echo $progress;
   }

   public function render($context)
   {
		$viewFile = $context->curView.'.html';
		$viewPath = 'views/'. $context->module->name.'/'.$viewFile;
	   
		if (!file_exists($viewPath))
		{
			$viewPath = 'views/common/'. $viewFile;
		}
		
		if (!file_exists($viewPath))
		{
			$moduleTemplate = "Could not load view '".$context->curView."' for ".$context->module->name;			
		}
		else
		{
			$moduleTemplate = file_get_contents($viewPath);			
		}
      
		if (isset($context->layout))
		{
			$layoutTemplate = str_replace('$body', $moduleTemplate, $context->layout);	
		}
		else
		{
			$layoutTemplate = $moduleTemplate;
		}
				
		if (strpos($layoutTemplate, '{{#grid.') !== false) {
			$this->loadGrid($context);
		}	   
		
		$m = new Mustache_Engine;
		echo $m->render($layoutTemplate, $context);	   
   }

   public function paginate($context)
   {
	   //var_dump($_REQUEST); die();
	   
	   $page = $_REQUEST['page'];
	   $_SESSION['page'] = $page;
	   $context->page = $page;
	   
	   $this->render($context);
   }

   private function loadGrid($context)
   {
		$entityClass = $context->module->entity;
	   
		if ($entityClass == null)
		{
			return;
		}
				
		//debug_print_backtrace();
		$entity = $context->database->createEntity($context, $entityClass);

		$headers = array();
		foreach($entity->fields as $field) {
			if (!$field->hidden)
			{
				$headers[] = array('name' => $field->name, 'label' => $field->label, 'visible' => $field->grid);	
			}			
		}

		$rows = array();
		
		$itemsPerPage = 20;
		$currentPage = $context->loadVar('page', '1');
		if ($context->entityID != null)
		{
			$entity = $context->database->fetchEntityByID($context, $entityClass, $context->entityID);	
								
			$entities = array();
			$entities[] = $entity;
			
			$total = 1;
		}
		else
		{			
			$total = $context->database->getEntityCount($context, $entityClass, $context->filter);							
			$entities = $context->database->fetchAllEntities($context, $entityClass, $context->filter, array('page' => $currentPage, 'items' => $itemsPerPage));	
		}
					
		foreach($entities as $entity) {
			
			$columns = array();
			
			$i = 0;
			foreach($entity->fields as $field) {
				if ($field->hidden)
				{
					continue;
				}

				$fieldName = $field->name;
				$fieldValue = $entity->$fieldName;
				$maskedValue = $fieldValue;
				$required = $field->required;
				$odd = ($i % 2) != 0;						
				
				if (strcmp($field->formType, 'file') == 0)
				{
					$isUpload = true;
					$fieldData = $fieldName.'_thumb';
					$thumb = $entity->$fieldData;
				}
				else
				{
					$isUpload = false;
					$thumb = null;
				}

				if (strcmp($field->controlType, 'textarea') == 0)
				{
					$hasContent = true;
				}
				else
				{
					$hasContent = false;
				}
				
				if (!is_null($field->enum))
				{
					$options = $context->fetchEnum($field->enum);
					$opLen = count($options);
					for ($n=0; $n<$opLen; $n++)
					{
						$op = $options[$n];
						if ($op['key'] == $fieldValue || $op['value'] == $fieldValue)
						{
							$options[$n]['selected'] = true;
						}
						
						if ($fieldValue == $op['key'])
						{
							$maskedValue = $op['value'];
						}
					}
					
					
				}
				else
				{
					$options = array();
				}					

				if (!is_null($field->entity))
				{
					$entityID = $fieldValue;
					if (strcmp($fieldValue, '0')===0)
					{
						$maskedValue = '-';
					}
					else
					{
						$otherEntity = $context->database->fetchEntityByID($context, $field->entity, $fieldValue);	
						$maskedValue = $otherEntity->name;						
					}
				}
				
				$columns[] = array(
					'name' => $field->name, 
					'label' => $field->label, 
					'value' => $fieldValue, 
					'maskedValue' => $maskedValue,
					'visible' => $field->grid, 
					'type' => $field->formType, 
					'class' => $field->formClass, 
					'control' => $field->controlType, 
					'required' => $required,
					'options' => $options,
					'odd' => $odd,
					'entity' => $field->entity,
					'entityID' => $entityID,
					'thumb' => $thumb,
					'unit' => $field->unit,
					'isUpload' => $isUpload,
					'hasContent' => $hasContent
					);
					
				$i++;
			}
						
			$rows[] = array('columns' => $columns, 'rowID' => $entity->id, 'class' => $entityClass);
		}
			
	
		$totalPages = floor($total / $itemsPerPage);
		$pages = array();
		$pages[] = array("id" => '«', 'disabled'=> $currentPage <= 1); 
		for ($j=1; $j<=$totalPages; $j++)
		{
			$pages[] = array("id" => $j, "selected" => $currentPage == $j, 'disabled'=> false);
		}
		$pages[] = array("id" => '»', 'disabled'=> $currentPage >= $totalPages);
		
		if (count($pages)<=2)
		{
			$pages = null;
		}
		
		$grid = array (
		  'headers' => $headers,		  
		  'rows' => $rows,
		  'pages' => $pages
		  );	   
		  		
		$context->grid = $grid;
   }

} 


?>