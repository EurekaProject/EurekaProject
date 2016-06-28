<?php
require_once "common/db.class.php";

class settings_service
{
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
		$sql = "SHOW TABLES FROM `".$this->dbname."` LIKE 'settings';";
		$result = $this->db->query($sql);
		if (!$result || $result->num_rows() < 1)
		{
			$ret = $this->db->import(dirname(__FILE__)."/../db/settings.sql");
			require_once "project.service.php";
			$service = new project_service();
			require_once "task.service.php";
			$service = new task_service();
			require_once "resource.service.php";
			$service = new resource_service();
			require_once "time.service.php";
			$service = new time_service();
		}
	}

	function _destruct()
	{
		if ($this->db)
			$this->db->close();
	}

	function set($options = array(), $value = "")
	{
		if (gettype($options) == "string")
		{
			$keyarg = $options;
			$options = array();
			$options[$keyarg] = $value;
		}
		foreach ($options as $key=>$value)
		{
			$sql = "SELECT `value` from `settings` WHERE `key` = '".$key."';";
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("error on ".$sql);
				return "no resource found";
			}
			if ($result->num_rows() > 1) 
			{
				error_log("error on ".$sql);
				return "too many response";
			}
			else if ($result->num_rows() == 0) 
			{
				$sql = "INSERT INTO `settings` (`key`, `value`) VALUES ('".$key."','".$value."');";
			}
			else
			{
				$sql = "UPDATE `settings` SET `value` = '".$value."' WHERE `key` = '".$key."';";
			}
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log($sql);
				error_log("settings::create : internal error");
				return "settings::create : internal error";
			}
		}
		return $options;
	}

	function get($options=array())
	{
		$keyarg = false;
		if (gettype($options) == "string")
		{
			$keyarg = $options;
			$options = array();
			$options[$keyarg] = "";
		}
		foreach ($options as $key=>$value)
		{
			$sql = "SELECT `value` from `settings` WHERE `key` = '".$key."';";
			$result = $this->db->query($sql);
			if ($result === false)
			{
				error_log("error on ".$sql);
				return "Error: no resource found";
			}
			if ($result->num_rows() == 0) 
			{
				error_log("error on ".$sql);
				return "Error: no response available";
			}
			if ($result->num_rows() > 1) 
			{
				error_log("error on ".$sql);
				return "Error: too many response";
			}
			$value = $result->fetch_array()["value"];
			if (intval($value) > 0)
				$options[$key]=intval($value);
			else
				$options[$key]=$value;
		}
		if ($keyarg)
			return $options[$keyarg];
		return $options;
	}
};
?>
