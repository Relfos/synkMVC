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
		   $progress = '0';
	   }
	   
	   echo $progress;
   }

   public function beforeRender($context)
   {
	   
   }
   
	public function afterRender($context, $layoutTemplate) 
	{
		if (strpos($layoutTemplate, '{{#grid.') !== false) {
			$this->loadGrid($context);
		}	   
	}
	   
   public function render($context)
   {
		$viewFile = $context->curView;
		$viewPath = $context->module->name.'/'.$viewFile;
	   
		if (!file_exists('views/'. $viewPath.'.html'))
		{
			$viewPath = 'common/'. $viewFile;
		}
		
		
		if (!file_exists('views/'. $viewPath.'.html'))
		{
			$context->kill("Could not load view '".$context->curView."' for ".$context->module->name);
			return false;
		}

		$context->pushTemplate($viewPath);			
		      		
		$context->render();
		return true;
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
				$headers[] = array('name' => $field->name, 'label' => $entity->translateField($context, $field), 'visible' => $field->grid);	
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
				
				$extra_attributes = ($field->formType=='checkbox' && $fieldValue=='1') ? 'checked="true"' : '';				
				
				if (strcmp($field->formType, 'file') == 0)
				{
					$isUpload = true;
					$fieldData = $fieldName.'_thumb';
					if (isset($entity->$fieldData)) {
						$thumb = $entity->$fieldData;
					}
					else {
						$thumb = null;
					}
					
					if (is_null($thumb)) {
						$maskedValue = '-';
					}
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
					$enumValues = $context->fetchEnum($field->enum);
					$opLen = count($enumValues);
					
					$options = array();
					foreach ($enumValues as $enumVal)
					{
						$enumSelected = ($enumVal == $fieldValue);
						$translateKey = 'enum_'.$field->enum.'_'.$enumVal;
						$enumTranslation = $context->translate($translateKey);
						
						$op = array(
							'key' => $enumVal,
							'value' => $enumTranslation,
							'selected' => $enumSelected							
						);
																		
						if ($enumSelected)
						{
							$maskedValue = $enumTranslation;							
						}
						
						$options[] = $op;
					}									
				}
				else
				{
					$options = array();
				}					

				$items = array();
				if (!is_null($field->entity))
				{
					$entityID = $fieldValue;
					$isTable = $field->formType == 'table';
					
					if ($isTable) {
						$entityList = explode(',', $entityID);
						foreach ($entityList as $id) {
							if (strlen($id)==0) {
								break;
							}
							$otherEntity = $context->database->fetchEntityByID($context, $field->entity, $id);	
							$otherThumb = $otherEntity->toImage();
							$items[] = array("id" => $id, "label" => $otherEntity->toString(), "thumb" => $otherThumb);
						}
						
						$maskedValue = '';
					}				
					else {
						if (strlen($fieldValue) == 0 || strcmp($fieldValue, '0')===0)
						{
							$maskedValue = '-';
						}
						else
						{
							$otherEntity = $context->database->fetchEntityByID($context, $field->entity, $fieldValue);	
							$maskedValue = $otherEntity->toString();						
						}						
					}
				}
				else
				{
					$isTable = false;
					$entityID = null;
				}
				
				if ($field->formType == 'date') {
					$fieldValue = date("Y-m-d", 1010310);
				}
				
				$columns[] = array(
					'name' => $field->name, 
					'label' => $entity->translateField($context, $field), 
					'value' => $fieldValue, 
					'maskedValue' => $maskedValue,
					'visible' => $field->grid, 
					'type' => $field->formType, 
					'class' => $field->formClass, 
					'control' => $field->controlType, 
					'required' => $required,
					'extra_attributes' => $extra_attributes,
					'options' => $options,
					'odd' => $odd,
					'entity' => $field->entity,
					'entityID' => $entityID,
					'thumb' => $thumb,
					'unit' => $field->unit,
					'isUpload' => $isUpload,
					'isTable' => $isTable,
					'items' => $items,
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
	
		$exports = array();
		foreach (glob('plugins/export/*.php') as $file) 
		{
			$extensionName = pathinfo($file, PATHINFO_FILENAME);
			require_once($file);
			$exports[] = array('format' => $extensionName, 'label' => $extensionName);
		}	
		
		$errorMessage = null;
		
		if (count($rows) == 0)
		{
			$errorMessage = $context->translate('grid_error_empty');
			$errorMessage= str_replace('$name', $context->module->getTitle($context), $errorMessage);			
		}
		
		$grid = array (
		  'headers' => $headers,		  
		  'rows' => $rows,
		  'pages' => $pages,
		  'exports' => $exports,
		  'error' => $errorMessage
		  );	   
		  		
		$context->grid = $grid;
   }

} 


?>