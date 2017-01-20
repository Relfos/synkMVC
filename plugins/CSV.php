<?php
	
class CSVPlugin
{	
	function __construct($context) {
	}
	
	public function export($context, $entityClass)
	{
		$progress = new ProgressBar();

		ob_start();
		$out = fopen('php://output', 'w');
		fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // write UTF8 BOM marker
		
		$entity = $context->database->createEntity($context, $entityClass);
		$headers = array();
		foreach($entity->fields as $field) {
			if (!$field->hidden)
			{
				$headers[] = $field->label;
			}			
		}
		fputcsv($out, $headers);
		
		$entities = $context->database->fetchAllEntities($context, $entityClass, $context->filter, null);	

		$total = count($entities);
		$i = 0;
		foreach($entities as $entity) {
			$values = array();
			
			foreach($entity->fields as $field) {
				if (!$field->hidden)
				{
					$fieldName = $field->name;
					$fieldValue = $entity->$fieldName;	
					$values[] = $fieldValue;					
				}			
			}

			fputcsv($out, $values);
			
			$i++;
			$progress->update($i, $total);											
		}
            					
		fclose($out);
		$output = ob_get_clean();
		
		$fileName = $entityClass.'_list.csv';	
		sendDownload($fileName, $output, null);
	}		
}
	

?>