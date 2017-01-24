<?php
	
class jsonPlugin
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

		fprintf($out,"[\n");
		
		
		$entities = $context->database->fetchAllEntities($context, $entityClass, $context->filter, null);	

		$total = count($entities);
		for($i =0; $i<$total; $i++) 
		{
			$entity = $entities[$i];
			fprintf($out,"\t{\n");
			$fieldCount = count($entity->fields);
			for($j =0; $j<$fieldCount; $j++) 
			{
				$field = $entity->fields[$j];
				$fieldName = $field->name;
				$fieldValue = $entity->$fieldName;	
				fprintf($out,"\t\t\"$fieldName\": \"$fieldValue\"");
				
				if ($j<$fieldCount)
				{
					fprintf($out,',');
				}
				fprintf($out,"\n");
			}

			fprintf($out,"\t}");
			if ($i<$total)
			{
				fprintf($out,',');
			}
			fprintf($out,"\n");
						
			$progress->update($i, $total);											
		}
  
		fprintf($out,"]");  
		fclose($out);
		$output = ob_get_clean();
		
		$fileName = $entityClass.'_list.json';	
		sendDownload($fileName, $output, null);
	}		
}
	

?>