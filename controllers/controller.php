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
		$viewPath = 'views/'. $context->curModule.'/'.$viewFile;
	   
		if (!file_exists($viewPath))
		{
			$viewPath = 'views/common/'. $viewFile;
		}
		
		if (!file_exists($viewPath))
		{
			$moduleTemplate = "Could not load view '".$context->curView."' for ".$context->curModule;			
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
		
		if ($context->entityID != null)
		{
			$entity = $context->database->fetchEntity($context, $entityClass, $context->entityID);	
								
			$entities = array();
			$entities[] = $entity;
		}
		else
		{
			$entities = $context->database->fetchAllEntities($context, $entityClass, $context->filter);	
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
						if ($options[$n]['key'] == $fieldValue || $options[$n]['value'] == $fieldValue)
						{
							$options[$n]['selected'] = true;
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
						$fieldValue = '-';
					}
					else
					{
						$otherEntity = $context->database->fetchEntity($context, $field->entity, $fieldValue);	
						$fieldValue = $otherEntity->name;						
					}
				}
				
				$columns[] = array(
					'name' => $field->name, 
					'label' => $field->label, 
					'value' => $fieldValue, 
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
			
						
		$grid = array (
		  'headers' => $headers,		  
		  'rows' => $rows,
		  );	   
		  		
		$context->grid = $grid;
   }

} 


?>