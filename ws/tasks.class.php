<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../include');
require_once("settings.service.php");
$settings = new settings_service();

$MaxDaysPerWeek=$settings->get("MaxDaysPerWeek");
$MaxHoursPerDay=$settings->get("MaxHoursPerDay");
$HourResolution=$settings->get("HourResolution");

require_once("project.service.php");
require_once("task.service.php");
require_once("time.service.php");
require_once("resource.service.php");

class tasks
{
	private $tasksmng;
	private $timesmng;
	private $projectsmng;
	private $resourcesmng;

	public function __construct()
	{
		$this->tasksmng = new task_service();
		$this->timesmng = new time_service();
		$this->projectsmng = new project_service();
		$this->resourcesmng = new resource_service();
	}

	private function _formatProject($entry, $full=true)
	{
		$data = array(
			"id"=>$entry->get("id"),
			"name"=>$entry->get("name"),
		);
		$data = array_merge($data, $entry->info($full));
		$data["status"] = "OK";
		return $data;
	}

	private function _formatTask($entry, $full=true)
	{
		$data = array(
			"id"=>$entry->get("id"),
			"name"=>$entry->get("name"),
		);
		$data = array_merge($data, $entry->info($full));
		$data["status"] = "OK";
		return $data;
	}

	private function _formatTime($entry, $full=true)
	{
		$data = array(
		);
		$data = array_merge($data, $entry->info($full));
		$data["status"] = "OK";
		return $data;
	}

	private function _formatResource($entry, $full=true)
	{
		$data = array(
		);
		$data = array_merge($data, $entry->info($full));
		$data["status"] = "OK";
		return $data;
	}

	public function parse($message)
	{
		$response = array();
		$response["body"] = array();
		if (!isset($message["body"]))
			return $response;
		$actionlist = $message["body"];
		foreach ($actionlist as $action=>$argumentslist)
		{
			$response["body"][$action."Response"] = array();
			switch ($action)
			{
				case 'saveTask':
				{
					if (isset($argumentslist["id"]))
					{
						$return = $this->tasksmng->update($argumentslist);
					}
					else
					{
						$return = $this->tasksmng->create($argumentslist);
					}
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["tasks"] = array();
						foreach ($return as $entry)
						{
							$info = array();
							$info["id"] = $entry->get("id");
							$response["body"][$action."Response"]["tasks"][] = $info;
							$response["body"][$action."Response"]["tasks"]["name"] = $argumentslist["name"];
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$response["body"][$action."Response"]["id"] = $return->get("id");
						$response["body"][$action."Response"]["result"] = 1;
						$response["body"][$action."Response"]["name"] = $argumentslist["name"];
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}
				}
				break;
				case 'getTask':
				{
					$return = $this->tasksmng->get($argumentslist);
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["tasks"] = array();
						foreach ($return as $entry)
						{
							$infos = $this->_formatTask($entry);
							$argumentlist["taskid"] = $entry->get("id");
							if (isset($argumentlist["times"]))
							{
								$time = $this->timesmng->get($argumentlist);
								$infos["times"] = array();
								if (gettype($time) === "array")
								{
									foreach ($time as $entry2)
									{
										$infos["times"][] = $this->_formatTime($entry2);
									}
								}
								else if ($time)
								{
									$infos["times"][] = $this->_formatTime($time);
								}
							}
							$response["body"][$action."Response"]["tasks"][] = $infos;
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$infos = $this->_formatTask($return);
						$argumentlist["taskid"] = $return->get("id");
						if (isset($argumentlist["times"]))
						{
							$time = $this->timesmng->get($argumentlist);
							$infos["times"] = array();
							if (gettype($time) === "array")
							{
								foreach ($time as $entry2)
								{
									$infos["times"][] = $this->_formatTime($entry2);
								}
							}
							else if ($time)
							{
								$infos["times"][] = $this->_formatTime($time);
							}
						}
						$response["body"][$action."Response"] = array_merge($response["body"][$action."Response"], $infos);
						$response["body"][$action."Response"]["result"] = "OK";
						$response["body"][$action."Response"]["result"] = 1;
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}
				}
				break;
				case 'getProject':
				{
					$return = $this->projectsmng->get($argumentslist);
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["projects"] = array();
						foreach ($return as $entry)
						{
							$infos = $this->_formatProject($entry);
							$response["body"][$action."Response"]["projects"][] = $infos;
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$infos = $this->_formatProject($return);
						$response["body"][$action."Response"] = array_merge($response["body"][$action."Response"], $infos);
						$response["body"][$action."Response"]["result"] = "OK";
						$response["body"][$action."Response"]["result"] = 1;
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}
				}
				break;
				case 'saveProject':
				{
					$return = $this->projectsmng->save($argumentslist);
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["projects"] = array();
						foreach ($return as $entry)
						{
							$infos = $this->_formatProject($entry);
							$response["body"][$action."Response"]["projects"][] = $infos;
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$infos = $this->_formatProject($return);
						$response["body"][$action."Response"] = array_merge($response["body"][$action."Response"], $infos);
						$response["body"][$action."Response"]["result"] = "OK";
						$response["body"][$action."Response"]["result"] = 1;
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}
				}
				break;
				case 'getResource':
				{
					$return = $this->resourcesmng->get($argumentslist);
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["resources"] = array();
						foreach ($return as $entry)
						{
							$infos = $this->_formatResource($entry);
							$response["body"][$action."Response"]["resources"][] = $infos;
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$infos = $this->_formatResource($return);
						$response["body"][$action."Response"] = array_merge($response["body"][$action."Response"], $infos);
						$response["body"][$action."Response"]["result"] = "OK";
						$response["body"][$action."Response"]["result"] = 1;
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}
				}
				break;
				case 'saveResource':
				{
					$return = $this->resourcesmng->save($argumentslist);
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["resources"] = array();
						foreach ($return as $entry)
						{
							$infos = $this->_formatResource($entry);
							$response["body"][$action."Response"]["resources"][] = $infos;
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$infos = $this->_formatResource($return);
						$response["body"][$action."Response"] = array_merge($response["body"][$action."Response"], $infos);
						$response["body"][$action."Response"]["result"] = "OK";
						$response["body"][$action."Response"]["result"] = 1;
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}
				}
				break;
				case "getTime":
				{
					$return = $this->tasksmng->get($argumentslist);
					if (gettype($return) === "array")
					{
						if (!isset($argumentlist["group"]))
							$argumentlist["group"] = "task";
					}
					else if ($return && gettype($return) === "object")
					{
						if (!isset($argumentlist["taskid"]))
							$argumentlist["taskid"] = $return->get("id");
						$userid = $return->get("userid");
						if (!isset($argumentlist["userid"]) && $userid)
							$argumentlist["userid"] = $userid;
					}
					$return = $this->timesmng->get($argumentslist);
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["times"] = array();
						foreach ($return as $entry)
						{
							$infos = $this->_formatTime($entry,false);
							$response["body"][$action."Response"]["times"][] = $infos;
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$infos = $this->_formatTime($return,false);
						$response["body"][$action."Response"] = array_merge($response["body"][$action."Response"], $infos);
						$response["body"][$action."Response"]["result"] = 1;
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}

				}
				break;
				case "addTime":
				{
					$return = $this->tasksmng->get($argumentslist);
					if (gettype($return) === "array")
					{
						if (!isset($argumentlist["group"]))
							$argumentlist["group"] = "task";
					}
					else if ($return && gettype($return) === "object")
					{
						if (!isset($argumentlist["taskid"]))
							$argumentlist["taskid"] = $return->get("id");
						$userid = $return->get("userid");
						if (!isset($argumentlist["userid"]) && $userid)
							$argumentlist["userid"] = $userid;
					}
					$return = $this->timesmng->create($argumentslist);
					if (gettype($return) === "array")
					{
						$response["body"][$action."Response"]["times"] = array();
						foreach ($return as $entry)
						{
							$infos = $this->_formatTime($entry,false);
							$response["body"][$action."Response"]["times"][] = $infos;
						}
						$response["body"][$action."Response"]["result"] = 1;
					}
					else if ($return && gettype($return) === "object")
					{
						$infos = $this->_formatTime($return,false);
						$response["body"][$action."Response"] = array_merge($response["body"][$action."Response"], $infos);
						$response["body"][$action."Response"]["result"] = 1;
					}
					else
					{
						if (gettype($return) === "string")
							$response["body"][$action."Response"]["error"] = $return;
						$response["body"][$action."Response"]["result"] = 0;
					}

				}
				break;
			}
		}
		return $response;
	}
};

?>
