<?php

require_once "common/db.class.php";

abstract class abstask implements Serializable
{
	var $data = [];
	var $children;
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
		if (gettype($options) === "array")
		{
			foreach ($options as $key=>$value)
			{
				if ($value !== "null" && $value !== NULL)
					$this->data[$key] = $value;
			}
		}
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
	function children($children = false)
	{
		if ($children)
			$this->children = $children;
		else
			return $this->children;
		return true;
	}
	function info()
	{
		return $this->data;
	}
	abstract protected function _get($key);
	abstract protected function _set($key, $value);
	abstract function save();
};

class task extends abstask
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
		switch ($key)
		{
			case "parent":
				if (isset($this->data["parentid"]))
				{
					$sql = "SELECT name FROM `tasks` WHERE id = ".$this->data["parentid"].";";
					$result = $this->db->query($sql);
					if ($result !== false && $result->num_rows() == 1)
					{
						$this->data["parent"] = $result->value()["name"];
						return $this->data["parent"];
					}
				}
				return false;
			break;
			default:
				if (isset($this->data["id"]))
				{
					$sql = "SELECT ".$key." FROM `tasks` WHERE id = ".$this->data["id"].";";
					$result = $this->db->query($sql);
					if ($result !== false && $result->num_rows() == 1)
					{
						$this->data[$key] = $result->value()[$key];
						return $this->data[$key];
					}
				}
				return false;
		}
	}
	protected function _set($key, $value)
	{
		switch ($key)
		{
			case "id":
				error_log("task::_set id is not alterable");
				return false;
			case "parent":
				$sql = "SELECT id FROM `tasks` WHERE name = ".$value.";";
				$result = $this->db->query($sql);
				if ($result !== false && $result->num_rows() == 1)
				{
					$this->data[$key] = $value;
					$key = "parentid";
					$value = $result->value()["id"];
					//continue on the default 
				}
				else
					return false;
				//continue on the default 
			default:
				$this->data[$key] = $value;
				$sql = "SHOW COLUMNS FROM `tasks` LIKE '".$key."';";
				$result = $this->db->query($sql);
				if ($result !== false || $result->num_rows() == 1)
				{
					$sql = "UPDATE `tasks` SET `".$key."`='".$value."' WHERE id = ".$this->data["id"].";";
					$result = $this->db->query($sql);
					if ($result !== false && $result->num_rows() == 1)
					{
						return true;
					}
				}
				return false;
		}
	}
	protected function _info($full)
	{ return ["KO"]; }
	function save()
	{
		$sql = "SHOW COLUMNS FROM `tasks`;";
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

			$sql = "UPDATE `tasks` SET ".$update." WHERE id = ".$this->data["id"].";";
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
class task_service
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
			$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'tasks';";
			$result = $this->db->query($sql);
			if (!$result || $result->num_rows() < 1)
			{
				$ret = $this->db->import(dirname(__FILE__)."/../db/tasks.sql");
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
	function create($options = array())
	{
		if (!isset($options["name"]) || $options["name"] === "")
		{
			error_log("task::create : name not set");
			return "name not set";
		}
		$select = "";
		$parentid = 0;
		$date = "NULL";
		$state = "open";
		$userid = "NULL";
		$estimate = "NULL";
		if (isset($options["projectid"]) && $options["projectid"] !== "null")
		{
			$projectid = $options["projectid"];
			$select .= " AND projectid = ".$projectid;
		}
		else if (isset($options["project"]))
		{
			$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'projects';";
			$result = $this->db->query($sql);
			if ($result && $result->num_rows() == 1)
			{
				$sql = "SELECT * FROM `projects` WHERE name = '".$options["project"]."';";
				$result = $this->db->query($sql);
				if ($result !== false)
				{
					if ($result->num_rows() == 1)
					{
						$projectid = $result->value()["id"];
						$select .= " AND projectid = ".$projectid;
					}
					else
					{
						error_log("task::create : project not available");
						return "project not available";
					}
				}
			}
		}
		if (isset($options["parent"]))
		{
			$sql = "SELECT * FROM `tasks` WHERE name = '".$options["parent"]."';";
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				if ($result->num_rows() == 1)
				{
					$valueparent = $result->fetch_array();
					if (!isset($projectid) || $valueparent["projectid"] === $projectid)
					{
						$parentid = $result->value()["id"];
						$projectid = $valueparent["projectid"];
						$select .= " AND projectid = ".$projectid;
						$select .= " AND parentid = ".$parentid;
					}
					else
					{
						error_log("task::create : parent ".$options["parentid"]." not assigned to the same project");
						return "parent not assigned to the same project";
					}
				}
				else
				{
					error_log("task::create : to many parents available");
					return "to many parents available";
				}
			}
		}
		else if (isset($options["parentid"]) && $options["parentid"] != 0)
		{
			$sql = "SELECT * FROM `tasks` WHERE id = '".$options["parentid"]."';";
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				if ($result->num_rows() == 1)
				{
					$valueparent = $result->fetch_array();
					if (!isset($projectid) || $valueparent["projectid"] === $projectid)
					{
						$parentid = $result->value()["id"];
						$projectid = $valueparent["projectid"];
						$select .= " AND projectid = ".$projectid;
						$select .= " AND parentid = ".$parentid;
					}
					else
					{
						error_log("task::create : parent ".$options["parentid"]." not assigned to the same project");
						return "parent not assigned to the same project";
					}
				}
				else
				{
					error_log("task::create : parent not available");
					return "parent not available";
				}
			}
		}
		if (isset($options["state"]))
		{
			switch($options["state"])
			{
				case "close":
				{
					if (isset($options["end"]))
						$date = "'".$options["end"]."'";
					else
						$date = "DATE(NOW())";
				}
				break;
				case "milestone":
				{
					if (isset($options["start"]))
						$date = "'".$options["start"]."'";
					else if (isset($options["end"]))
						$date = "'".$options["end"]."'";
					else if (isset($options["date"]))
						$date = "'".$options["date"]."'";
					else
						$date = "DATE(NOW())";
				}
			}
			$state = $options["state"];
		}
		if (isset($options["estimate"]))
		{
			$estimate = $options["estimate"];
			error_log(gettype($estimate));
			error_log($estimate);
		}
		if (isset($options["userid"]))
		{
			if (isset($projectid))
			{
				$sql = "SELECT userid FROM `resources` WHERE userid=".$options["userid"]." AND projectid=".$projectid;
				$result = $this->db->query($sql);
				if ($result !== false && $result->num_rows() > 0)
					$userid = $options["userid"];
				else
				{
					error_log($sql);
					error_log("task::create : resource not set on the project");
					return "resource not set on the project";
				}
			}
			else
				$userid = $options["userid"];
			if ($estimate === "NULL" && $state === "open")
			{
				error_log("task::create : zero time for estimate not available");
				return "zero time for estimate not available";
			}
		}
		if (!isset($projectid))
		{
			$projectid = "NULL";
			$select = "AND projectid IS NULL";
		}
		
		$sql = "SELECT * FROM `tasks` WHERE name = '".$options["name"]."' ".$select.";";
		$result = $this->db->query($sql);
		if ($result === false || $result->num_rows() == 0)
		{
			$description = "";
			if (isset($options["description"]))
				$description = $this->db->escape_string($options["description"]);
			if ($projectid === "NULL" && $userid === "NULL" && !isset($options["force"]))
			{
				$error = "at least userid or projectid must be defined";
				error_log("task::create : at least userid or projectid must be defined");
				return $error;
			}
			$sql = "INSERT INTO `tasks` (`name`, `parentid`, `description`, `userid`, `projectid`, `estimate`, `state`, `start` )".
				"VALUES ('".$options["name"]."', '".$parentid."', '".$description."', ".$userid.", ".$projectid.", ".$estimate.", '".$state."', ".$date.");";
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				$value = $result->value();
				$sql = "SELECT * FROM `tasks` WHERE id = ".$value.";";
				$result = $this->db->query($sql);
				$value = $result->value();
				$task = new task($value, $this->db);
				
				return $task;
			}
			else
			{
				error_log($sql);
				error_log("task::create : name not available");
				return "name not available";
			}
		}
		else
		{
			//error_log($sql);
			error_log("task::create : task existing");
			return "task existing";
		}
		return false;
	}

	/**
	 * @brief : create ONE entry into the database.
	 * 
	 * @desc : the creation needs at least an "ip".
	 * "type" will be search with the "USER AGENT" of the caller.
	 * 
	 * @return : false on error otherwise the terminal element created.
	 **/
	function update($options = array())
	{
		if (!isset($options["id"]))
		{
			error_log("task::update : id not set");
			return "id not set";
		}
		$sql = "SELECT * FROM `tasks` WHERE id = ".$options["id"].";";
		$result = $this->db->query($sql);
		if ($result === false || $result->num_rows() == 0)
		{
			error_log("task::update : id not set");
			return "id not available";
		}
		else
		{
			$update = "";
			$separators = ["",","];
			$separator = 0;
			$value = $result->fetch_array();

			if (isset($options["parentid"]) && $options["parentid"] != 0)
			{
				$sql = "SELECT * FROM `tasks` WHERE id = '".$options["parentid"]."';";
				$result = $this->db->query($sql);
				if ($result !== false)
				{
					if ($result->num_rows() == 1)
					{
						$valueparent = $result->fetch_array();
						if ($valueparent["projectid"] == $value["projectid"])
						{
							$update .= $separators[$separator]." `parentid` = ".$result->value()["id"];
							$separator = 1;
						}
						else
						{
							error_log("task::update : parent ".$options["parentid"]." not assigned to the same project");
							return "parent not assigned to the same project";
						}
					}
					else
					{
						error_log("task::update : parent not available");
						return "parent not available";
					}
				}
			}
			else if (isset($options["parent"]))
			{
				$sql = "SELECT * FROM `tasks` WHERE name = '".$options["parent"]."';";
				$result = $this->db->query($sql);
				if ($result !== false)
				{
					if ($result->num_rows() == 1)
					{
						$valueparent = $result->fetch_array();
						if ($valueparent["projectid"] == $value["projectid"])
						{
							$update .= $separators[$separator]." `parentid` = ".$result->value()["id"];
							$separator = 1;
						}
						else
						{
							error_log("task::update : parent ".$options["parent"]." not assigned to the same project");
							return "parent not assigned to the same project";
						}
					}
					else
					{
						error_log("task::update : to many parents available");
						return "to many parents available";
					}
				}
			}
			if (isset($options["userid"]))
			{
				$update .= $separators[$separator]." `userid` = ".$options["userid"];
				$separator = 1;
				if (!isset($options["estimate"]) && $value["estimate"] === "null" &&
					$value["sate"] === "open")
				{
					error_log("task::create : zero time for estimate not available");
					return "zero time for estimate not available";
				}
			}
			if (isset($options["projectid"]))
			{
				$projectid = intval($options["projectid"]);
				if ($projectid > 0)
				{
					$update .= $separators[$separator]." `projectid` = ".$projectid;
					$separator = 1;
				}
			}
			else if (isset($options["project"]))
			{
				$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'projects';";
				$result = $this->db->query($sql);
				if ($result && $result->num_rows() == 1)
				{
					$sql = "SELECT * FROM `projects` WHERE name = '".$options["project"]."';";
					$result = $this->db->query($sql);
					if ($result !== false)
					{
						if ($result->num_rows() == 1)
						{
							$update .= $separators[$separator]." `projectid` = ".$result->value()["id"];
							$separator = 1;
						}
						else
						{
							error_log("task::update : project not available");
							return "project not available";
						}
					}
				}
			}
			if (isset($options["estimate"]))
			{
				$update .= $separators[$separator]." `estimate` = ".$options["estimate"];
				$separator = 1;
			}
			if (isset($options["description"]))
			{
				$update .= $separators[$separator]." `description` = '".$this->db->escape_string($options["description"])."'";
				$separator = 1;
			}
			if (isset($options["name"]))
			{
				$update .= $separators[$separator]." `name` = '".$options["name"]."'";
				$separator = 1;
			}
			if (isset($options["state"]))
			{
				switch($options["state"])
				{
					case "close":
					{
						if (isset($options["end"]))
							$date = "'".$options["end"]."'";
						else
							$date = "DATE(NOW())";
						$update .= $separators[$separator]." `end` = ".$date;
						$separator = 1;
					}
					break;
					case "milestone":
					{
						if (isset($options["start"]))
							$date = "'".$options["start"]."'";
						else if (isset($options["end"]))
							$date = "'".$options["end"]."'";
						else if (isset($options["date"]))
							$date = "'".$options["date"]."'";
						else
							$date = "DATE(NOW())";
						$update .= $separators[$separator]." `start` = ".$date;
						$separator = 1;
						$update .= $separators[$separator]." `end` = ".$date;
					}
				}
				$update .= $separators[$separator]." `state` = '".$options["state"]."'";
				$separator = 1;
			}

			$sql = "UPDATE `tasks` SET ".$update." WHERE id = ".$options["id"].";";
			//error_log($sql);
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				$value = $options["id"];
				$sql = "SELECT * FROM `tasks` WHERE `id` = ".$value.";";
				$result = $this->db->query($sql);
				$value = $result->value();
				$task = new task($value, $this->db);
				
				return $task;
			}
			else
			{
				error_log("Error on ".$sql);
				error_log("task::update : name not available");
				return "name not available";
			}
		}
		return false;
	}

	private function _search($options, $cmd="SELECT `tasks`.* ")
	{
		$separators = array( "WHERE", "AND");
		$separator = 0;
		$name="";
		$id="";
		$state="";
		$parentid="";
		$userid="";
		$projectid="";
		$start="";
		$order="";
		$group = "";
		$tables="";
		$time="";
		if (isset($options["name"]))
		{
			$name = $separators[$separator]." `name` = '".$options["name"]."' ";
			$separator = 1;
		}
		if (isset($options["spent"]))
		{
			if ($options["spent"] !== "full")
			{
				$time .= $separators[$separator]." `time`.`taskid` = `tasks`.id ";
				$cmd .= ", `time`.time AS `spent`";
				$tables .= ",`time`";
				$separator = 1;
				$group = "GROUP BY `tasks`.id";
			}
		}
		if (isset($options["id"]))
		{
			$id = $separators[$separator]." `id` = ".$options["id"]." ";
			$separator = 1;
		}
		if (isset($options["state"]))
		{
			$state = $separators[$separator]." `state` = '".$options["state"]."' ";
			$separator = 1;
			if ($options["state"] === " milestone")
			{
				$start = $separators[$separator]." `start` > CURDATE() ";
			}
		}
		if (isset($options["parentid"]))
		{
			$parentid = $separators[$separator]." `parentid` = ".$options["parentid"]." ";
			$separator = 1;
		}
		if (isset($options["projectid"]))
		{
			if ($options["projectid"] === "null")
			{
				$projectid = $separators[$separator]." `projectid` IS NULL ";
				$separator = 1;
			}
			else if ($options["projectid"] !== "all")
			{
				$projectid = $separators[$separator]." `projectid` = ".$options["projectid"]." ";
				$separator = 1;
				$projectid .= $separators[$separator]." `projectid` = `projects`.id ";
				$cmd .= ", `projects`.name AS project";
				$tables .= ",`projects`";
				$separator = 1;
			}
		}
		else
		{
			$projectid .= $separators[$separator]." `projectid` = `projects`.id ";
			$cmd .= ", `projects`.name AS project";
			$tables .= ",`projects`";
			$separator = 1;
		}
		if (isset($options["userid"]))
		{
			if ($options["userid"] === "null")
				$userid = $separators[$separator]." `userid` IS NULL ";
			else
				$userid = $separators[$separator]." `userid` = ".$options["userid"]." ";
			$separator = 1;
		}
		if (isset($options["parent"]))
		{
			$sql = "SELECT id FROM `tasks` WHERE `name` = ".$options["parent"].";";
			$result = $this->db->query($sql);
			if ($result !== false && $result->num_rows() == 1)
			{
				$value = $result->value();
				$parentid = $separators[$separator]." `parentid` = ".$value["id"]." ";
				$separator = 1;
			}
			else
			{
				error_log("task::_search : to many parents available (".$options["parent"].")");
			}
		}
		if (isset($options["order"]))
		{
			if ($options["order"] !== "parent")
				$order = " ORDER BY `".$options["order"]."` ";
		}

		$sql = $cmd." FROM `tasks` ".$tables." ".$name.$time.$id.$state.$parentid.$userid.$projectid.$start.$order.$group.";";
		//error_log($sql);
		$result = $this->db->query($sql);
		if ($result === false)
		{
			error_log("error on ".$sql);
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
		global $HourResolution;
		if (!isset($options["parentid"]) && !isset($options["parent"]))
		{
			$options["parentid"] = 0;
		}
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
				$tasks = array();
				for($i = 0; $i < $nbrows; $i++)
				{
					$value = $result->fetch_array();
					if ((isset($options["spent"]) && $options["spent"] === "full") || isset($options["full"]))
					{
						$sql = "SELECT (SUM(`time`) / ".$HourResolution.") AS `spent` FROM `time` WHERE `taskid` = ".$value["id"]." GROUP BY `taskid`;";
						$result2 = $this->db->query($sql);
						if ($result2 && $result2->num_rows() == 1)
						{
							$value = array_merge($value, $result2->fetch_array());
						}
					}
					$task = new task($value,$this->db);
					$tasks[] = $task;
				}
				return $tasks;
			}
			else
			{
				$value = $result->value($idx);
				if ((isset($options["spent"]) && $options["spent"] === "full") || isset($options["full"]))
				{
					$sql = "SELECT (SUM(`time`) / ".$HourResolution.") AS `spent` FROM `time` WHERE `taskid` = ".$value["id"]." GROUP BY `taskid`;";
					$result2 = $this->db->query($sql);
					if ($result2 && $result2->num_rows() == 1)
					{
						$value = array_merge($value, $result2->fetch_array());
					}
				}
				$tasks = new task($value,$this->db);
				return $tasks;
			}
		}
		return false;
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
			$sql = "DELETE FROM `tasks` WHERE id=".$value["id"].";";
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("error on ".$sql);
			}
		}
		return $result;
	}
};
