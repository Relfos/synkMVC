<?php

class SQL
{
	public $db;
	
	function __construct($config) {
		$this->db = new mysqli($config->host, $config->user, $config->pass) or die($this->db->error);

		if (!mysqli_set_charset($this->db, "utf8"))
		{
			die($this->db->error);
		}					
	}
	
	function selectDatabase($name)
	{
		mysqli_select_db($this->db, $name) or die($this->db->error);
	}
	
	function query($query)
	{
		//echo $query."<br>";		
		$result = mysqli_query($this->db,$query);
		if(!$result) die($this->db->error."<br>".$query);	
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
		if (empty($result))	return false;

		$row = mysqli_fetch_assoc($result);
		return $row;
	}
	
	public function fetchSingleRow($query)
	{
		$query .= ' LIMIT 1';
		$result = $this->query($query);
		return $this->fetchRow($result);
	}
	
}


?>