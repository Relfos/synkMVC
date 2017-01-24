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