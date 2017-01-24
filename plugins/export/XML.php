<?php
	
class XMLPlugin
{	
	function __construct($context) {
	}
	
	public function export($context, $entityClass)
	{
		$progress = new ProgressBar();

		$listName = ucfirst($entityClass).'List';
		ob_start();

		$out = fopen('php://output', 'w');
		fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // write UTF8 BOM marker

		fprintf($out,"<$listName>\n");
		
		
		$entities = $context->database->fetchAllEntities($context, $entityClass, $context->filter, null);	

		$total = count($entities);
		$i = 0;
		foreach($entities as $entity) 
		{
			fprintf($out,"\t<$entityClass>\n");
			foreach($entity->fields as $field) {
				if (!$field->hidden)
				{
					$fieldName = $field->name;
					$fieldValue = $entity->$fieldName;	
					fprintf($out,"\t\t<$fieldName>$fieldValue</$fieldName>\n");
				}			
			}

			fprintf($out,"\t</$entityClass>\n");
			
			$i++;
			$progress->update($i, $total);											
		}
  
		fprintf($out,"</'$listName>\n");  
		fclose($out);
		$output = ob_get_clean();
		
		$fileName = $entityClass.'_list.xml';	
		$context->sendDownload($fileName, $output, null);
	}		
}
	

?>