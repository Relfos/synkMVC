<?php

function appendArgument($url, $name, $value) {
    if (strpos($url, '?') !== false) {
        $url .= '&';
    } else {
        $url .= '?';
    }

    return $url . $name . '=' . urlencode($value);
}

class ProgressBar
{
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