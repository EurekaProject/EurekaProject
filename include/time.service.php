<?php

require_once "common/db.class.php";

abstract class abstime implements Serializable
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

class time extends abstime
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
class time_service
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
			$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'time';";
			$result = $this->db->query($sql);
			if (!$result || $result->num_rows() < 1)
			{
				$ret = $this->db->import(dirname(__FILE__)."/../db/time.sql");
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
		global $HourResolution;
		if (isset($options["date"]) && $options["date"] !== "")
		{
			$date = "'".$options["date"]."'";
		}
		else
		{
			$date = "DATE(NOW())";
		}
		if (!isset($options["userid"]))
		{
			error_log("time::create : userid not set");
			return "userid not set";
		}
		else
		{
			$userid = $options["userid"];
		}
		if (isset($options["task"]))
		{
			if ($options["task"] === "vacation")
				$sql = "SELECT * FROM `tasks` WHERE state = '".$options["task"]."' AND userid = ".$userid.";";
			else
				$sql = "SELECT * FROM `tasks` WHERE name = '".$options["task"]."';";
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				if ($result->num_rows() == 1)
				{
					$value = $result->value();
					$taskid = $value["id"];
					if (isset($value["start"]))
						$taskstart = $value["start"];
				}
				else if ($options["task"] === "vacation")
				{
					$sql = "INSERT INTO `tasks` (`name`, `userid`, `state`) VALUES ('vacation',".$userid.",'vacation');";
					$result = $this->db->query($sql);
					if ($result !== false)
					{
						$taskid = $result->value();
					}
				}
			}
		}
		if (isset($options["taskid"]))
		{
			if ($options["taskid"] === "vacation")
				$sql = "SELECT * FROM `tasks` WHERE state = '".$options["taskid"]."' AND userid = ".$userid.";";
			else
				$sql = "SELECT * FROM `tasks` WHERE id = '".$options["taskid"]."';";
			$result = $this->db->query($sql);
			if ($result !== false)
			{
				if ($result->num_rows() == 1)
				{
					$value = $result->value();
					$taskid = $value["id"];
					if (isset($value["start"]))
						$taskstart = $value["start"];
				}
				else if ($options["taskid"] === "vacation")
				{
					$sql = "INSERT INTO `tasks` (`name`, `userid`, `state`) VALUES ('vacation',".$userid.",'vacation');";
					$result = $this->db->query($sql);
					if ($result !== false)
					{
						$taskid = $result->value();
					}
				}
			}
		}
		if (!isset($taskid))
		{
			error_log("time::create : taskid not set");
			return "taskid not set";
		}
		$sql = "SELECT `time`, `tasks.start` AS taskstart FROM `time`, `tasks` ".
			"WHERE date = ".$date." ".
			"AND taskid = ".$taskid." ".
			"AND time.userid = ".$userid." ".
			"AND taksid = tasks.id;";
		$result = $this->db->query($sql);
		if ($result == false || $result->num_rows() == 0)
		{
			$time = floatval($options["time"]) * $HourResolution;
			$sql = "INSERT INTO `time` (`taskid`, `userid`, `date`, `time` )".
				"VALUES (".$taskid.", ".$userid.", ".$date.", ".$time.");";
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("error on: ".$sql);
				error_log("time::create : date not available");
				return "date not available";
			}
		}
		else
		{
			$value = $result->value();
			$taskstart = $value["taskstart"];
			$time = floatval($options["time"])*$HourResolution + $value["time"];
			$sql = "UPDATE `time` SET `time` = '".$time."' WHERE date = ".$date." AND taskid = ".$taskid." AND userid = ".$userid.";";
			//error_log($sql);
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("time::create : date not available");
				return "date not available";
			}
		}
		if (!isset($taskstart) || $taskstart == "null")
		{
			$sql = "UPDATE `tasks` SET `start` = ".$date.
				" WHERE id = ".$taskid.";";
			//error_log($sql);
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("time::create : task start date not available");
				return "task start date not available";
			}
		}
		$options["taskid"] = $taskid;
		//$options["date"] = $date;
		$result = $this->_search($options);
		$value = $result->value(0);
		$value["time"] /= $HourResolution;
		$time = new time($value, $this->db);
		
		return $time;
	}

	private function _search($options, $cmd="SELECT time.userid, taskid, tasks.name AS task, tasks.state AS state, SUM(`time`) AS time ")
	{
		$separators = array( "WHERE", "AND");
		$separator = 1;
		$groupseparators = array(" GROUP BY ", ", ");
		$gseparator = 0;
		$userid="";
		$taskid="";
		$date="";
		$year="";
		$group="";
		$dateselect="";
		$order="";
		$limit="";
		if (isset($options["userid"]))
		{
			$userid = $separators[$separator]." (time.userid = ".$options["userid"]." OR time.userid = NULL)";
			$separator = 1;
		}
		if (isset($options["taskid"]))
		{
			$taskid = $separators[$separator]." taskid = ".$options["taskid"]." ";
			$separator = 1;
		}
		if (isset($options["projectid"]))
		{
			$taskid = $separators[$separator]." `tasks`.id = taskid AND `tasks`.projectid = ".$options["projectid"]." ";
			$separator = 1;
			$group .= $groupseparators[$gseparator]." taskid";
			$gseparator = 1;
		}
		if (isset($options["date"]))
		{
			if ($options["date"] === "now")
				$date = $separators[$separator]." `date` = DATE(NOW()) ";
			else
				$date = $separators[$separator]." `date` = '".$options["date"]."' ";
			$separator = 1;
			$dateselect = ", `date`";
		}
		if (isset($options["group"]))
		{
			switch ($options["group"])
			{
				case "days":
					$group .= $groupseparators[$gseparator]." `date`";
					$gseparator = 1;
					$dateselect = ", `date`";
				break;
				case "months":
					$group .= $groupseparators[$gseparator]." MONTH(`date`)";
					$gseparator = 1;
					if (isset($options["date"]))
					{
						if ($options["date"] === "now")
							$date = $separators[$separator]." YEAR(`date`) = YEAR(NOW()) ";
						else
							$date = $separators[$separator]." YEAR(`date`) = YEAR('".$options["date"]."') ";
						$separator = 1;
					}
					$dateselect = ", MONTH(`date`) AS `month`, YEAR(`date`) AS `year` ";
				break;
				case "weeks":
					$group .= $groupseparators[$gseparator]." WEEKOFYEAR(`date`)";
					$gseparator = 1;
					if (isset($options["date"]))
					{
						if ($options["date"] === "now")
						{
							$date = $separators[$separator]." YEAR(`date`) = YEAR(NOW()) ";
							$separator = 1;
							$date .= $separators[$separator]." MONTH(`date`) = MONTH(NOW()) ";
						}
						else
						{
							$date = $separators[$separator]." YEAR(`date`) = YEAR('".$options["date"]."') ";
							$separator = 1;
							$date .= $separators[$separator]." MONTH(`date`) = MONTH('".$options["date"]."') ";
						}
						$separator = 1;
					}
					$dateselect = ", WEEKOFYEAR(`date`) AS `week`, YEAR(`date`) AS `year` ";
				break;
				case "project":
					$group .= $groupseparators[$gseparator]." `tasks`.projectid";
					$gseparator = 1;
				break;
				case "task":
					$group .= $groupseparators[$gseparator]." taskid";
					$gseparator = 1;
				break;
			}
		}
		if (isset($options["week"]))
		{
			if (!isset($year))
			{
				$year = $separators[$separator]." YEAR(`date`) = YEAR(NOW()) ";
				$separator = 1;
			}
			$date = $separators[$separator]." WEEKOFYEAR(`date`) = '".$options["week"]."' ";
			$separator = 1;
		}
		if (isset($options["order"]))
		{
			$order = " ORDER BY `".$options["order"]."` DESC ";
		}
		if (isset($options["limit"]))
		{
			list($limit,$offset)=explode(" ",$options["limit"]." ");
			$limit = " LIMIT ".$limit." ";
			if (isset($offset) && $offset !== "")
				$limit .= " OFFSET ".$offset." ";
			$order = " ORDER BY `date` DESC ";
		}

		$sql = $cmd.$dateselect." FROM `tasks`, `time` WHERE tasks.id = taskid ".$userid.$taskid.$date.$group.$order.$limit.";";
		error_log($sql);
		$result = $this->db->query($sql);
		if ($result === false)
		{
			error_log("error on ".$sql);
			return "no time spent";
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
				$times = array();
				for($i = 0; $i < $nbrows; $i++)
				{
					$value = $result->fetch_array();
					if (isset($value["time"]))
					{
						$value["time"] /= $HourResolution;
						$time = new time($value,$this->db);
						$times[] = $time;
					}
				}
				return $times;
			}
			else
			{
				$value = $result->value($idx);
				if (isset($value["time"]))
				{
					$value["time"] /= $HourResolution;
					$times = new time($value,$this->db);
					return $times;
				}
			}
		}
		return $result;
	}

	function rm($options=array())
	{
		$result = $this->_search($options, "DELETE");
		return $result;
	}
};
