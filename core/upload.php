<?php

error_reporting(E_ERROR | E_PARSE);

function make_thumb($src) 
{
	$desired_width = 80;

	/* read the source image */
	$source_image = imagecreatefromjpeg($src);
	$width = imagesx($source_image);
	$height = imagesy($source_image);
	
	/* find the "desired height" of this thumbnail, relative to the desired width  */
	$desired_height = floor($height * ($desired_width / $width));
	
	/* create a new, "virtual" image */
	$virtual_image = imagecreatetruecolor($desired_width, $desired_height);
	
	/* copy source image at a resized size */
	imagecopyresampled($virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height);
	
	/* create the  thumbnail image to its destination */	
	ob_start();
	imagejpeg($virtual_image, $dest);
	$imageString = ob_get_clean();	
	
	return $imageString;
}

function upload_file($dbName, $sql, $fileName, $tempName)
{
	$hash = md5_file($tempName);

	$result = $sql->query("SELECT count(*) as total FROM $dbName.uploads where `hash` = '$hash'");
	$row = $sql->fetchRow($result);
	if ($row['total'] == '0')
	{
		$data = file_get_contents($tempName);
		$size = strlen($data);
		$data = base64_encode($data);	
		
		$thumb = make_thumb($tempName);
		$thumbsize = strlen($thumb);
		$thumb = base64_encode($thumb);
		
		file_put_contents("dump.txt", $data);
		
		$sql->query("INSERT INTO $dbName.uploads (`hash`, `data`, `name`, `size`, `thumb`, `thumbsize`) VALUES ('$hash', '$data', '$fileName', $size, '$thumb', $thumbsize)");	
		
		//move_uploaded_file($tempName, $destFile);	
		$skipped = false;
	}
	else
	{
		$skipped = true;
	}
	
	return array('name' => $fileName, 'location' => $hash, 'skipped' => $skipped);	
}

require_once('core/config.php');
require_once('core/sql.php');

$config = new Config();
$sql = new SQL($config);

if (isset($_REQUEST['db']))
{
	$dbName = $_REQUEST['db'];
}
else
{
	$dbName = $config->database;	
}


$sql->query("CREATE TABLE IF NOT EXISTS $dbName.uploads (
`hash` VARCHAR(40) NOT NULL,
`name` VARCHAR(60) NOT NULL,
`data` MEDIUMTEXT NOT NULL,
`size` INT NOT NULL,
`thumb` TEXT NOT NULL,
`thumbsize` INT NOT NULL,
PRIMARY KEY (`hash`)
) ENGINE = InnoDB;");

		
if(isset($_FILES["target"]))
{
	$ret = array();

	$error =$_FILES["target"]["error"];
	//You need to handle  both cases
	//If Any browser does not support serializing of multiple files using FormData() 
	if(!is_array($_FILES["target"]["name"])) //single file
	{
		$ret[] = upload_file($dbName, $sql, $_FILES["target"]["name"], $_FILES["target"]["tmp_name"]);
	}
	else  //Multiple files, file[]
	{
	  $fileCount = count($_FILES["target"]["name"]);
	  for($i=0; $i < $fileCount; $i++)
	  {
		  $ret[] = upload_file($dbName, $sql, $_FILES["target"]["name"][$i], $_FILES["target"]["tmp_name"][$i]);
	  }	
	}
	
	$result = json_encode($ret);
	//file_put_contents("dump.txt", $result);
    echo $result;
 }
 ?>