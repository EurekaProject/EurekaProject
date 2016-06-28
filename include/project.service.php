<?php

require_once "common/db.class.php";

abstract class absproject implements Serializable
{
	var $data;
	public function serialize()
	{
		return serialize($this->data);
	}
	public function unserialize($data)
	{
		$this->data = unserialize($data);
	}
	function __construct($options)
	{
		$this->data = $options;
	}
	function get($key, $force=false)
	{
		if (!$force && array_key_exists($key, $this->data))
			return $this->data[$key];
		else
			return $this->_get($key);
	}
	function set($key, $value, $force = false)
	{
		if ($force)
			return $this->_set($key, $value);
		else
			$this->data[$key] = $value;
	}
	function info()
	{
		return $this->data;
	}
	abstract protected function _get($key);
	abstract protected function _set($key, $value);
};

class project extends absproject
{
	protected $db;
	function __construct($options, $db)
	{
		parent::__construct($options);
		$this->db = $db;
	}
	function capabilities()
	{
		return ["create",];
	}
	protected function _get($key)
	{
		if (isset($this->data["id"]))
		{
			$sql = "SELECT ".$key." FROM `projects` WHERE id = ".$this->data["id"].";";
			$result = $this->db->query($sql);
			if ($result !== false && $result->num_rows() == 1)
			{
				$this->data[$key] = $result->value()[$key];
				return $this->data[$key];
			}
		}
		return false;
	}
	protected function _set($key, $value)
	{
		switch ($key)
		{
			case "id":
				error_log("task::_set id is not alterable");
				return false;
			default:
				$this->data[$key] = $value;
				$sql = "SHOW COLUMNS FROM `projects` LIKE '".$key."';";
				$result = $this->db->query($sql);
				if ($result !== false || $result->num_rows() == 1)
				{
					$sql = "UPDATE `projects` SET `".$key."`='".$value."' WHERE id = ".$this->data["id"].";";
					$result = $this->db->query($sql);
					if ($result !== false && $result->num_rows() == 1)
					{
						return true;
					}
				}
				return false;
		}
	}
	function save()
	{
		$sql = "SHOW COLUMNS FROM `projects`;";
		$result = $this->db->query($sql);
		if ($result !== false && $result->num_rows() > 0)
		{
			$update = "";
			$separators = ["",","];
			$separator = 0;
			for ($i = 0; $i < $result->num_rows(); $i++)
			{
				$key = $result->fetch_array()["Field"];
				if (isset($this->data[$key]))
				{
					if ($result->fetch_array()["Type"] === "int")
					{
						$update .= $separators[$separator]."`".$key."`=".$this->data[$key]."";
					}
					else
					{
						$update .= $separators[$separator]."`".$key."`='".$this->data[$key]."'";
					}
					$separator = 1;
				}
			}

			$sql = "UPDATE `projects` SET ".$update." WHERE id = ".$this->data["id"].";";
			$result = $this->db->query($sql);
			if ($result !== false && $result->num_rows() == 1)
			{
				return true;
			}
		}
		return false;
	}
};

/**
 * @brief: factory of tasks
 * 
 * @desc: search, returns, delete, create tasks
 * each function accepts an aray as argument. This
 * array contains one or more pairs (key/value).
 * The accepted keys are:
 * - id : id inside the terminals database
 * - idx : the index of one terminal inside a list.
 * - order : the field to sort the terminals.
 * 
 * @param dbname : the name of the database to store and read informations.
 * @param dbhost : the name of the server running the database.
 **/
class project_service
{
	var $dbname;
	function __construct($eurekadb = "root:root@127.0.0.1/eureka_project")
	{
		list($dbuser,$dbhost) = explode('@',$eurekadb);
		list($dbuser,$dbpass) = explode(':',$dbuser);
		list($dbhost,$dbname) = explode('/',$dbhost);
		session_start();
		if (isset($_SESSION['eurekadb']))
		{
			$eurekadb = $_SESSION['eurekadb'];
			list($dbuser,$dbhost) = explode('@',$eurekadb);
			list($dbuser,$dbpass) = explode(':',$dbuser);
			list($dbhost,$dbname) = explode('/',$dbhost);
		}
		session_write_close();
		if (isset($_REQUEST['eurekadb']))
		{
			$eurekadb = $_REQUEST['eurekadb'];
			list($dbuser,$dbhost) = explode('@',$eurekadb);
			list($dbuser,$dbpass) = explode(':',$dbuser);
			list($dbhost,$dbname) = explode('/',$dbhost);
		}
		$this->dbname = $dbname;
		$this->db = new db("",$dbhost,$dbuser,$dbpass);
		if ($this->db->status == 0)
		{
			if ($this->db->select_db($this->dbname) == false)
			{
				$this->db->create_db($this->dbname);
			}
			$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'projects';";
			$result = $this->db->query($sql);
			if (!$result || $result->num_rows() < 1)
			{
				$ret = $this->db->import(dirname(__FILE__)."/../db/projects.sql");
			}
		}
	}

	function _destruct()
	{
		if ($this->db)
			$this->db->close();
	}

	/**
	 * @brief : create ONE entry into the database.
	 * 
	 * @desc : the creation needs at least an "ip".
	 * "type" will be search with the "USER AGENT" of the caller.
	 * 
	 * @return : false on error otherwise the terminal element created.
	 **/
	function save($options = array())
	{
		if (!isset($options["name"]) && !isset($options["id"]))
		{
			error_log("project::create : name not set");
			return "name not set";
		}
		$description = "";
		if (isset($options["description"]))
			$description = $this->db->escape_string($options["description"]);
		
		if (isset($options["name"]))
			$sql = "SELECT id FROM `projects` WHERE name = '".$options["name"]."';";
		else
			$sql = "SELECT id FROM `projects` WHERE id = '".$options["id"]."';";
		$result = $this->db->query($sql);
		if ($result !== false && $result->num_rows() > 0)
		{
			$value = $result->value(0);
			$sql = "UPDATE `projects` SET `description` = '".$description."' WHERE id = ".$value["id"].";";
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				$id = $value["id"];
			}
		}
		else
		{
			$sql = "INSERT INTO `projects` (`name`, `description` )".
				"VALUES ('".$options["name"]."', '".$description."');";
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				$id = $result->value();
				if (isset($options["masterid"]))
				{
					$sql = "INSERT INTO `resources` (`userid`, `projectid`, `roleid` )".
						"VALUES (".$options["masterid"].", ".$id.", "."2".");";
					$result = $this->db->query($sql);
				}
			}
		}
		if (isset($id))
		{
			$sql = "SELECT * FROM `projects` WHERE id = ".$id.";";
			$result = $this->db->query($sql);
			$value = $result->value();
			$project = new project($value, $this->db);
		}
		else
		{
			error_log("project::create : name not available");
			return "name not available";
		}
		return $project;
	}

	private function _search($options, $cmd="SELECT *")
	{
		$separators = array( "WHERE", "AND", "OR");
		$separator = 0;
		$name="";
		$id="";
		$parentid="";
		$order="";
		if (isset($options["name"]))
		{
			$name = $separators[$separator]." name = '".$options["name"]."' ";
			$separator = 1;
		}
		if (isset($options["id"]))
		{
			$id .= $separators[$separator]." id = ".$options["id"]." ";
			$separator = 1;
		}
		if (isset($options["projectid"]))
		{
			$id .= $separators[$separator]." id = ".$options["projectid"]." ";
			$separator = 1;
		}
		if (isset($options["masterid"]))
		{
			$userid = $options["masterid"];
		}
		if (isset($options["userid"]))
		{
			$userid = $options["userid"];
		}
		if (isset($userid))
		{
			$sql="SELECT `projectid` FROM `resources` WHERE `userid` = ".$userid.";";
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("project::_search error on ".$sql);
				return false;
			}
			for ($i = 0; $i < $result->num_rows(); $i++)
			{
				$value = $result->fetch_array();
				$id .= $separators[$separator]." id = ".$value["projectid"]." ";
				$separator = 2;
			}
			$separator = 1;
		}
		else
		{
			$id .= $separators[$separator]." id != 0 ";
			$separator = 1;
		}
		if (isset($options["order"]))
		{
			if ($options["order"] !== "parent")
				$order = " ORDER BY `".$options["order"]."` ";
		}

		$sql = $cmd." FROM `projects` ".$name.$id.$order.";";
		$result = $this->db->query($sql);
		if ($result === false)
		{
			error_log("project::_search error on ".$sql);
		}
		return $result;
	}

	/**
	 * @brief : returns one or more tasks.
	 * 
	 * @return : false on error otherwise the terminal element created.
	 **/
	function get($options=array())
	{
		$result = $this->_search($options);
		if ($result !== false)
		{
			$idx=-1;
			if (isset($options["idx"]))
			{
				$idx = $options["idx"];
			}
			$nbrows = $result->num_rows();
			if ($nbrows === 1)
				$idx = 0;

			if ($idx == -1)
			{
				$projects = array();
				for($i = 0; $i < $nbrows; $i++)
				{
					$value = $result->fetch_array();
					$project = new project($value,$this->db);
					$projects[] = $project;
				}
				return $projects;
			}
			else
			{
				$value = $result->value($idx);
				$projects = new project($value,$this->db);
				return $projects;
			}
		}
		return "project not available";
	}

	function rm($options=array())
	{
		$idx=-1;
		if (isset($options["idx"]))
		{
			$idx = $options["idx"];
		}

		if ($idx == -1)
		{
			$result = $this->_search($options, "DELETE");
		}
		else
		{
			$result = $this->_search($options);
			$value = $result->value($idx);
			$sql = "DELETE FROM `projects` WHERE id=".$value["id"].";";
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("error on ".$sql);
			}
		}
		return $result;
	}
};
