<?php
class dbresult
{
	private $mysql_result;
	private $id;
	public function __construct($result,$id)
	{
		$this->mysql_result = $result;
		$this->id = $id;
	}
	public function error()
	{
		return mysql_error();
	}
	public function num_rows()
	{
		return mysql_num_rows($this->mysql_result);
	}
	public function value($index=0)
	{
		if ($this->id > 0)
			return $this->id;
		return mysql_result($this->mysql_result, $index);
	}
	public function fetch_array()
	{
		return mysql_fetch_array($this->mysql_result);
	}
	public function fetch_assoc()
	{
		return mysql_fetch_assoc($this->mysql_result);
	}
};

class db  extends dbabs
{

//	private $connection;
	private $dbname;

	public function __construct($dbname = false, $dbhost = "127.0.0.1", $dbuser = "root", $dbpass = "root")
	{
		$this->connection = mysql_connect($dbhost, $dbuser, $dbpass, true);
		if($this->connection == FALSE)
			$this->status = -2;
		else
		{
			$this->status = 0;
			if ($dbname!=false)
				if ($this->select_db($dbname) == false)
					$this->status = -1;
		}
	}

	public function select_db($dbname=false)
	{
		if ($this->status == -1 || $this->status == -2)
			return false;
		$this->dbname = $dbname;
		$ret = mysql_select_db($dbname, $this->connection);
		if ($ret == false)
			return false;

		$this->query("SET NAMES 'utf8'");
		return true;
	}

	public function create_db($dbname)
	{
		//$ret = mysql_create_db($dbname, $this->connection);
		$sql = "CREATE DATABASE ".$dbname;
		$ret = mysql_query($sql, $this->connection);
		if ($ret == false)
		{
			$this->status = -1;
			return false;
		}
		return $this->select_db($dbname);
	}
	public function query($sql, $dbname=false)
	{
		if ($this->status == -1 || $this->status == -2)
			return false;
		if ($dbname == false)
			$dbname = $this->dbname;
		$mysql_result = mysql_db_query($dbname, $sql, $this->connection);
		if ($this->debug) error_log($sql.' '.$mysql_result);
		if ($mysql_result == false)
			return $mysql_result;
		$result = new dbresult($mysql_result,mysql_insert_id());
		return $result;
	}
	public function error()
	{
		error_log(mysql_error());
		return mysql_error();
	}
	public function close()
	{
		if ($this->status == -1 || $this->status == -2)
			return false;
		mysql_close($this->connection);
	}

	public function escape_string($string)
	{
		return mysql_real_escape_string($string);
	}
};
?>
