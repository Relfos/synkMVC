<?php

require_once 'libs/PHPExcel.php';
	
class ExcelPlugin
{	
	function __construct($context) {
	}

	private function num2alpha($n)
	{
		for($r = ""; $n >= 0; $n = intval($n / 26) - 1)
			$r = chr($n%26 + 0x41) . $r;
		return $r;
	}	
	
	public function export($context, $entityClass)
	{
		$progress = new ProgressBar();

		$objPHPExcel = new PHPExcel();		
					
		$objPHPExcel->getProperties()->setCreator("Synkdata")
							 ->setLastModifiedBy("Synkdata")
							 ->setTitle("$entityClass List")
							 ->setSubject("$entityClass List")
							 ->setDescription("$entityClass List")
							 ->setKeywords("$entityClass")
							 ->setCategory("$entityClass List");
							 
		$sheet = $objPHPExcel->setActiveSheetIndex(0);
		
		$entity = $context->database->createEntity($context, $entityClass);
		$i = 0;
		foreach($entity->fields as $field) {
			if (!$field->hidden)
			{
				$sheet->setCellValue($this->num2alpha($i).'1', $field->label);
				$i++;
			}			
		}
		
		$entities = $context->database->fetchAllEntities($context, $entityClass, $context->filter, null);	
		$i = 1;
		$total = count($entities);
		foreach($entities as $entity) {
			$data = array();
			
			$i++;
			$j = 0;
			foreach($entity->fields as $field) {
				if (!$field->hidden)
				{
					$fieldName = $field->name;
					$fieldValue = $entity->$fieldName;	
					$sheet->setCellValue($this->num2alpha($j).$i, $fieldValue);
					$j++;					
				}			
			}

			$progress->update($i, $total);	
		}
            			
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');	
		ob_start();
		$objWriter->save('php://output');
		$excelOutput = ob_get_clean();
		
		$fileName = $entityClass.'_list.xlsx';
		sendDownload($fileName, $excelOutput, null);
	}		
}
	

?>