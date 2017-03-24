<?php

//error_reporting(E_ERROR | E_PARSE);

function make_thumb($src, $extension) 
{
	/* read the source image */
	switch ($extension) {
		case 'jpg':
		case 'jpeg':
			$source_image = imagecreatefromjpeg($src);
			break;
		case 'png':
			$source_image = imagecreatefrompng($src);
			break;
		case 'gif':
			$source_image = imagecreatefromgif($src);
			break;
		default: {
			return "";
		}
	}
	
	$width = imagesx($source_image);
	$height = imagesy($source_image);
	
	$desired_width = 64;
	/* find the "desired height" of this thumbnail, relative to the desired width  */
	$desired_height = floor($height * ($desired_width / $width));
	
	/* create a new, "virtual" image */
	$virtual_image = imagecreatetruecolor($desired_width, $desired_height);
	
	/* copy source image at a resized size */
	imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
	
	/* create the  thumbnail image to its destination */	
	ob_start();
	imagejpeg($virtual_image);
	$imageString = ob_get_clean();	
	
	return $imageString;
}

function upload_file($context, $fileName, $tempName)
{	
	$hash = md5_file($tempName);

	$condition = array('hash' => array('eq' => $hash));
	$entity = $context->database->fetchEntity($context, "upload", $condition);
	if ($entity->exists) {
		$skipped = true;
	}
	else {	
		$data = file_get_contents($tempName);
		$size = strlen($data);
		$data = base64_encode($data);	
		$ext = pathinfo($fileName, PATHINFO_EXTENSION);
		
		$thumb = make_thumb($tempName, $ext);
		$thumbSize = strlen($thumb);
		$thumb = base64_encode($thumb);
		
		file_put_contents("dump.txt", $data);
		
		$entity = $context->database->createEntity($context, "upload");
		$entity->hash = $hash;
		$entity->data = $data;
		$entity->name = $fileName;
		$entity->size = $size;
		$entity->thumb = $thumb;
		$entity->thumbsize = $thumbSize;
		$entity->save($context);
		
		$skipped = false;
	}
	
	return array('id' => $entity->id, 'name' => $fileName, 'hash' => $hash, 'skipped' => $skipped);	
}

require_once('core/init.php');

$checkPerm = function($user, $module, $action) {
	if ($module->name == 'users' && !$user->admin)	{
		return false;
	}
		
	return true;
};

$context = new Context();

	
if(isset($_FILES["target"]))
{
	$ret = array();

	$error =$_FILES["target"]["error"];
	//You need to handle  both cases
	//If Any browser does not support serializing of multiple files using FormData() 
	if(!is_array($_FILES["target"]["name"])) //single file
	{
		$ret[] = upload_file($context, $_FILES["target"]["name"], $_FILES["target"]["tmp_name"]);
	}
	else  //Multiple files, file[]
	{
	  $fileCount = count($_FILES["target"]["name"]);
	  for($i=0; $i < $fileCount; $i++)
	  {
		  $ret[] = upload_file($context, $_FILES["target"]["name"][$i], $_FILES["target"]["tmp_name"][$i]);
	  }	
	}
	
	$result = json_encode($ret);
	//file_put_contents("dump.txt", $result);
    echo $result;
 }
 ?>