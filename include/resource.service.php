<?php

require_once "common/db.class.php";

abstract class absresource implements Serializable
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
		return $this->data[$key];
	}
	function set($key, $value, $force = false)
	{
			return $this->data[$key] = $value;
	}
	function info($full=false)
	{
		return $this->data;
	}
};

class resource extends absresource
{
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
class resource_service
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
		}
		$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'resources';";
		$result = $this->db->query($sql);
		if (!$result || $result->num_rows() < 1)
		{
			$ret = $this->db->import(dirname(__FILE__)."/../db/resources.sql");
			if (isset($userid))
			{
				$this->save(array("userid"=>$userid,"name"=>"Administrator","projectid"=>0));
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
		$separators = ["WHERE","AND"];
		$separator = 0;
		$where="";
		$userid = "0";
		$projectid = "NULL";
		if (!isset($options["userid"]) || $options["userid"] === "null" || $options["userid"] === "")
		{
			error_log("resource::save : userid not set");
			return "userid not set";
		}
		else
		{
			$userid = $options["userid"];
			$where .= $separators[$separator]." `userid` = ".$userid." ";
			$separator = 1;
		}

		if (isset($options["roleid"]))
		{
			$roleid = $options["roleid"];
		}
		if (isset($options["projectid"]))
		{
			$projectid = $options["projectid"];
			$where .= $separators[$separator]." `projectid` = ".$projectid;
			$separator = 1;
		}
		else if (isset($options["project"]))
		{
			$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'resources';";
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
						$where .= $separators[$separator]." `projectid` = ".$projectid;
						$separator = 1;
					}
					else
					{
						$where .= $separators[$separator]." `projectid` IS NULL";
						$separator = 1;
						$projectid = "NULL";
					}
				}
			}
		}
		if (isset($options["name"]))
		{
			$name = $options["name"];
		}
		else
		{
			$sql = "SELECT * FROM `resources` WHERE `projectid` = 0 AND `userid` = ".$userid.";";
			$result = $this->db->query($sql);
			if ($result !== false && $result->num_rows() == 1)
			{
				$value =  $result->fetch_array();
				$name = $value["name"];
			}
			else
			{
				error_log("resource::save: name not available");
				return "name not available";
			}
		}
		if (isset($options["workhours"]))
		{
			global $HourResolution;
			$workhours = intval(floatval(str_replace(',',".",$options["workhours"]))*$HourResolution);
		}
		$sql = "SELECT * FROM `resources` ".$where;
		$result = $this->db->query($sql);
		if ($result == false || $result->num_rows() == 0)
		{
			global $HourResolution;
			global $MaxHoursPerDay;
			$userid = $options["userid"];
			if (!isset($roleid))
				$roleid = 1;
			if (!isset($projectid))
				$projectid = 0;
			if (!isset($name))
				$name = "external";
			if (!isset($workhours))
				$workhours = $MaxHoursPerDay*$HourResolution;

			$sql = "INSERT INTO `resources` (`userid`, `projectid`, `name`, `workhours`, `roleid`)".
				"VALUES (".$userid.", ".$projectid.", '".$name."', ".$workhours.", ".$roleid.");";
			//error_log($sql);
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("error on ".$sql);
				error_log("resource::create : internal error");
				return "resource::create : internal error";
			}
		}
		else
		{
			$separators = ["SET",","];
			$separator = 0;
			$sql = "UPDATE `resources` ";
			if (isset($workhours))
			{
				$sql .= $separators[$separator]." `workhours` = ".$workhours;
				$separator = 1;
			}
			if (isset($name))
			{
				$sql .= $separators[$separator]." `name` = '".$name."'";
				$separator = 1;
			}
			if (isset($roleid))
			{
				$sql .= $separators[$separator]." `roleid` = '".$roleid."'";
				$separator = 1;
			}
			$sql .= " ".$where.";";
			$result = $this->db->query($sql);
			//error_log($sql);
			if ($result === false)
			{
				error_log("error on ".$sql);
				error_log("resource::create : internal error");
				return "resource::create : internal error";
			}
		}
		$sql = "SELECT * FROM `resources` ".$where;
		$result = $this->db->query($sql);
		$value = $result->fetch_array();
		$resource = new resource($value, $this->db);
		
		return $resource;
	}

	private function _search($options, $cmd="SELECT `resources`.* ")
	{
		$separators = array( " WHERE ", " AND ");
		$separator = 0;
		$where="";
		$order="";
		$userid="";
		$projectid="NULL";
		$workhours="";
		$tables="";

		$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'roles';";
		$result = $this->db->query($sql);
		if ($result && $result->num_rows() == 1)
		{
			$where .= $separators[$separator]."`roleid` = `roles`.id";
			$separator = 1;
			$tables .= ',`roles`';
			$cmd .= ",`roles`.name AS 'role' ";
		}
		if (isset($options["userid"]))
		{
			$where .= $separators[$separator]."userid = ".$options["userid"];
			$separator = 1;
		}
		if (isset($options["name"]))
		{
			$where .= $separators[$separator]."name = ".$options["name"];
			$separator = 1;
		}
		if (isset($options["projectid"]))
		{
			$projectid = $options["projectid"];
			if ($projectid === "null")
				$where .= $separators[$separator]."`projectid` IS NULL";
			else
				$where .= $separators[$separator]."`projectid` = ".$projectid;
			$separator = 1;
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
						$where .= $separators[$separator]."`projectid` = ".$projectid;
						$separator = 1;
					}
					else
					{
						$where .= $separators[$separator]."`projectid` IS NULL";
						$separator = 1;
						$projectid = "NULL";
					}
				}
			}
		}
		if (isset($options["order"]))
		{
			$order = " ORDER BY `".$options["order"]."` ";
		}

		$sql = $cmd." FROM `resources` ".$tables.$where.$order.";";
		$result = $this->db->query($sql);
		//error_log($sql);
		if ($result === false)
		{
			error_log("error on ".$sql);
			return "no resource found";
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
		$result = $this->_search($options);
		if ($result !== false && gettype($result) !== "string")
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
				$resources = array();
				for($i = 0; $i < $nbrows; $i++)
				{
					$value = $result->fetch_array();
					$value["workhours"] /= $HourResolution;
					$resource = new resource($value,$this->db);
					if (isset($options["projectid"]) && $options["projectid"] != 0)
					{
						$sql="SELECT SUM(`workhours`) AS workhourstotal FROM `resources` where `userid` = ".$value["userid"]." AND `projectid` != 0;";
						$result2 = $this->db->query($sql);
						if ($result2 !== false && $result2->num_rows() > 0)
						{
							$value2=$result2->fetch_array();
							$resource->data["workhourstotal"] = $value2["workhourstotal"] / $HourResolution;
						}
						$sql="SELECT `workhours` FROM `resources` where `userid` = ".$value["userid"]." AND `projectid` = 0;";
						$result2 = $this->db->query($sql);
						if ($result2 !== false && $result2->num_rows() > 0)
						{
							$value2=$result2->fetch_array();
							$resource->data["workhoursmax"] = $value2["workhours"] / $HourResolution;
						}
						$sql="SELECT `estimate` ".
							"FROM `tasks` ".
							"WHERE `userid` = ".$value["userid"]." ".
								"AND `projectid` = ".$value["projectid"].
								"AND `state` = 'flowing';";
						$result3 = $this->db->query($sql);
						if ($result3 !== false)
						{
							$resource->data["workhoursfortask"] = $resource->data["workhours"];
							for ($i = 0; $i < $result3->num_rows(); $i++)
							{
								$value3=$result3->fetch_array();
								$resource->data["workhoursfortask"] -= $value3["estimate"];
							}
						}
					}
					$resources[] = $resource;
				}
				return $resources;
			}
			else
			{
				$value = $result->value($idx);
				$value["workhours"] /= $HourResolution;
				$resource = new resource($value,$this->db);
				if (isset($options["projectid"]) && $options["projectid"] != 0)
				{
					$sql="SELECT SUM(`workhours`) AS workhourstotal FROM `resources` where `userid` = ".$value["userid"]." AND `projectid` != 0;";
					$result2 = $this->db->query($sql);
					if ($result2 !== false && $result2->num_rows() > 0)
					{
						$value2=$result2->fetch_array();
						$resource->data["workhourstotal"] = $value2["workhourstotal"] / $HourResolution;
					}
					$sql="SELECT `workhours` FROM `resources` where `userid` = ".$value["userid"]." AND `projectid` = 0;";
					$result2 = $this->db->query($sql);
					if ($result2 !== false && $result2->num_rows() > 0)
					{
						$value2=$result2->fetch_array();
						$resource->data["workhoursmax"] = $value2["workhours"] / $HourResolution;
					}
					$sql="SELECT `estimate` ".
						"FROM `tasks` ".
						"WHERE `userid` = ".$value["userid"]." ".
							"AND `projectid` = ".$value["projectid"]." ".
							"AND `state` = 'flowing';";
					$result3 = $this->db->query($sql);
					if ($result3 !== false)
					{
						$resource->data["workhoursfortask"] = $resource->data["workhours"];
						for ($i = 0; $i < $result3->num_rows(); $i++)
						{
							$value3=$result3->fetch_array();
							$resource->data["workhoursfortask"] -= intval($value3["estimate"]) / $HourResolution;
						}
					}
				}
				return $resource;
			}
		}
		return false;
	}

	function rm($options=array())
	{
		$result = $this->_search($options, "DELETE");
		return $result;
	}
};
