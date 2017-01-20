<?php

function appendArgument($url, $name, $value) {
    if (strpos($url, '?') !== false) {
        $url .= '&';
    } else {
        $url .= '?';
    }

    return $url . $name . '=' . urlencode($value);
}


function fixNameCase($name)
{
	if (strlen($name) <= 0)
	{
		return $name;
	}

	$splitters = array(' ','.',"'",'-'); 
	
	$fixed = '';
	$blank = str_replace($splitters, '?', $name);
	$n = explode('?', $blank);
	
	foreach($n as $f) 
	{
		$fixed .= ucfirst(mb_strtolower($f)).' ';
	}
	
	for ($i=0;$i<strlen($fixed);$i++) {
		if ($fixed[$i]==' ') {
			if ($blank[$i]=='?') {
				$fixed[$i] = $name[$i];
			}
		}
	}
	return substr_replace($fixed,'', -1);		
}

function sendDownload($fileName, $data, $mimeType)
{
	if (is_null($mimeType))
	{
		$mimeType = 'application/octet-stream';
	}
	
	$size = strlen($data);
	
	if (isset($_REQUEST['ajax']))
	{
		$data = base64_encode($data);
		echo
"{
	\"mimetype\": \"$mimeType\",
	\"filename\": \"$fileName\",
	\"data\": \"$data\"
}";		
	}
	else
	{
		header('Content-Description: File Transfer');
		header('Content-Type: '.$mimeType);
		header('Content-Disposition: attachment; filename=' . $fileName); 
		header('Content-Transfer-Encoding: binary');
		header('Connection: Keep-Alive');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . $size);			
		echo $data;			
	}
	
}

class ProgressBar
{
	public $data = null;
	
	function __construct()
	{
		set_time_limit(0);
	
		if (!file_exists('tmp'))
		{	
			mkdir('tmp');	
		}	
			
		$this->fileName = "tmp/" . session_id() . ".bar";
		session_write_close();	
	}

	function update($current, $max)
	{
		$progress = floor(($current / $max) * 100);
		file_put_contents($this->fileName, $progress);
	}
}

?>