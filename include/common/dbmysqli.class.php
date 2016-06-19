<?php

// Class to get result
class dbresult extends dbresultabs
{
	private $mysql_result;
	private $id;

	public function __construct($result,$id=-1, $connection = false)
	{
		$this->mysqli_result = $result;
		$this->id = $id;
		$this->connection = $connection;
	}

	// get error
	public function error()
	{
		return mysqli_error($this->connection);
	}

	// get number of rows from result
	public function num_rows()
	{
		return mysqli_num_rows($this->mysqli_result);
	}

	// get value from a row or a collumn
	public function value($row=0,$col=-1)
	{
		if($this->id > 0)
		{
			return $this->id;
		}

		// check number of row to know if request is possible
		$numrows = mysqli_num_rows($this->mysqli_result);

		if($numrows > 0)
		{
			while ($row-- > 0)
			{
				error_log("row ".$row);
				mysqli_fetch_array($this->mysqli_result);
			}
			$resrow = mysqli_fetch_array($this->mysqli_result);
			if (count($resrow) === 1)
				return $resrow[0];

			if ($col > -1 && isset($resrow[$col]))
			{
				return $resrow[$col];
			}
			return $resrow;
		}

		return false;
	}

	// get result as array
	public function fetch_array()
	{
		return mysqli_fetch_array($this->mysqli_result, MYSQLI_ASSOC);
	}
	public function fetch_assoc()
	{
		return mysqli_fetch_assoc($this->mysqli_result);
	}
};

// class for MySQL
class db extends dbabs
{

//	private $connection;
	private $dbname;
	private $data_connection = [];

	// Init db connection
	public function __construct($dbname = "", $dbhost = "127.0.0.1", $dbuser = "root", $dbpass = "root")
	{
		$this->data_connection['host'] = $dbhost;
		$this->data_connection['user'] = $dbuser;
		$this->data_connection['pass'] = $dbpass;
		$this->connection = $this->connect();
		$this->status = -1;

		if($this->connection == FALSE)
		{
//			$this->data_connection['name'] = $dbname;
			$this->status = -2;
		}
		else
		{
			$this->dbname = $dbname;
			if ($dbname !== "")
			{
				if($this->select_db($dbname) == true)
				{
					$this->status = 0;
				}
			}
			else
			{
				$this->status = 0;
			}

			//$this->query("SET NAMES 'utf8'");
			@mysqli_set_charset($this->connection, "utf8");
		}
	}

	private function connect()
	{
		$this->connection = @mysqli_connect($this->data_connection['host'], $this->data_connection['user'], $this->data_connection['pass']);
		if(!$this->connection)
		{
			error_log('database connection on '.$this->data_connection['host'].' failed, error : '.mysqli_connect_error());
			if ($this->data_connection['host'] === "127.0.0.1")
				error_log("please check the mysqld option --skip-networking");
		}
		return $this->connection;
	}

	// Select Database
	public function select_db($dbname=false)
	{

		if($this->status == -1 || $this->status == -2)
		{
			$this->connect();
		}
		$ret = @mysqli_select_db( $this->connection, $dbname);

		if ($ret == false)
		{
			error_log('database '.$dbname.' not available on '.$this->data_connection['host']);
			return false;
		}
		$this->dbname = $dbname;
		$this->status = 0;
		//$this->query("SET NAMES 'utf8'");
		@mysqli_set_charset($this->connection, "utf8");
		return true;
	}

	public function create_db($dbname)
	{
		if($this->status == -1 || $this->status == -2)
		{
			$this->connection = $this->connect();
			if(!$this->connection)
			{
				die("connection failed in create_db(), can\'t create database '".$dbname."', error : ".$this->error());
			}
		}

		$ret = mysqli_query($this->connection, "CREATE DATABASE ".$dbname );

		if (!$ret)
		{
			error_log("database ".$dbname." not created error : ".$this->error());
			error_log("check privilege on the data directory for mysql user");
			$this->status = -1;
			return false;
		}

		$this->select_db($dbname);

		if($this->status == 0)
		{
			return true;
		}
		return false;
	}

	// Do request and change databse if required
	public function query($sql, $dbname=false)
	{
		if ($this->status == -1 || $this->status == -2)
			return false;
		// Change db if required
		if (($dbname != false) && ($dbname != $this->dbname))
		{
			$dbname = $this->dbname;
			$this->setdb($dbname);
		}
		// Do query
		$mysql_result = mysqli_query($this->connection, $sql);
		//error_log($sql.' '.$mysql_result);
		if ($mysql_result === false)
		{
			return $mysql_result;
		}
		if (($mysql_result === true) && (mysqli_insert_id($this->connection) == null))
		{
			return true;
		}
		// Put result in dbresult object
		$result = new dbresult($mysql_result,mysqli_insert_id($this->connection), $this->connection);
		return $result;
	}

	// Get last error
	public function error()
	{
		if (!!$this->connection)
		{
			error_log(mysqli_error($this->connection));
			return mysqli_error($this->connection);
		}
		else
		{
			error_log(mysqli_connect_error());
			return mysqli_connect_error();
		}
		return "unknown";
	}

	// Close SQL connection
	public function close()
	{
		if ($this->status == -1 || $this->status == -2)
			return false;
		@mysqli_close($this->connection);
	}

	public function escape_string($string)
	{
		if ($this->connection)
			return mysqli_real_escape_string($this->connection, $string);
		return "";
	}

	public function cheat(){
		return $this->connection;
	}

};
?>
