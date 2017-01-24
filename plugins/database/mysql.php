<?php

class SQL
{
	public $db;
	
	function __construct($config) 
	{
		$this->db = new mysqli($config->sqlHost, $config->sqlUser, $config->sqlPass);
		if ($this->db->connect_error)  
		{
			$this->failed = true;
			return;
		}		
				
		if (!mysqli_set_charset($this->db, "utf8"))
		{
			die($this->db->error);
		}					
		
		mb_internal_encoding('UTF-8');
	}
		
	/*function selectDatabase($name)
	{
		if ($this->failed)
		{
			return;
		}
		mysqli_select_db($this->db, $name) or die($this->db->error);
	}*/
	
	function query($query)
	{
		if ($this->failed)
		{
			return null;
		}

		//echo $query."<br>";		die();
		$result = mysqli_query($this->db,$query);
		if(!$result) 
		{
			$this->failed = true;
			die($this->db->error."<br>".$query);	
			return null;						
		}
		return $result;
	}
	
	function getRowCount($result)
	{
		if ($result === false)
		{
			return 0;
		}
		return mysqli_num_rows($result);
	}
	
	public function fetchRow($result)
	{
		if (empty($result))	return null;

		$row = mysqli_fetch_assoc($result);
		return $row;
	}
	
	public function fetchSingleRow($query)
	{
		$query .= ' LIMIT 1';
		$result = $this->query($query);
		$row = $this->fetchRow($result);
		if ($row === false)
		{
			return null;
		}
		
		return $row;
	}
	
	public function escapeString($val)
	{
		return mysqli_real_escape_string($this->db, $val);
	}
}


?>