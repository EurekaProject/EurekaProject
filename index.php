<?php
/**
 * Copyright
 * 
 * 2016-2017 Marc Chalain <marc.chalain@gmail.com>
 **/
/**
		This file is part of EurekaProject.

    EurekaProject is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    EurekaProject is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with EurekaProject.  If not, see <http://www.gnu.org/licenses/>.
 **/
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/include/');
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/user/include/');

session_name("EurekaProject");
session_start();
if (!isset($_SESSION['eurekadb']) && file_exists("./config"))
{
	$lines = file("./config", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lines as $line)
	{
		list($var, $value) = explode("=",$line);
		$$var = $value;
	}
	if (isset($eurekadb))
	{
		$_SESSION['eurekadb'] = $eurekadb;
	}
}
if (!isset($_SESSION['eurekadb']) && isset($_REQUEST['eurekadb']))
	$_SESSION['eurekadb'] = $_REQUEST['eurekadb'];
if (isset($_SESSION['eurekadb']) && !isset($_REQUEST['eurekadb']))
	unset($_SESSION['eurekadb']);
session_write_close();
require_once "settings.service.php";
$settings = new settings_service();

session_start();
if (isset($_REQUEST["logout"]) || (isset($_SESSION['Auth']) && $_SESSION['Auth'] == false))
{
	$_SESSION['Auth'] = false;
	unset($_SESSION['Auth']);
	session_unset();
	session_regenerate_id(true);
	session_destroy();
}
if (!isset($_SESSION['Auth']))
{
	$authpath = $settings->get("AuthToolPath");
	if (preg_match("/^Error:/",$authpath) == 0)
		header("Location: ".$authpath);
	else
		header("Location: ./wizard/install.php");
	die;
}

$userid = $_SESSION['Auth'];
session_write_close();

$MaxDaysPerWeek=$settings->get("MaxDaysPerWeek");
$MaxHoursPerDay=$settings->get("MaxHoursPerDay");
$HourResolution=$settings->get("HourResolution");

require_once "resource.service.php";
$resources = new resource_service();
$resource = $resources->get(array("userid"=>$userid,"projectid"=>0));

if (gettype($resource) !== "object")
{
	$username="set your pseudo";
	$userrole = "developper";
	$page="resourcesetting";
}
else
{
	$username = $resource->get("name");
	$userrole = $resource->get("role");
	$page="dashboard";
}

$userlanguage = $settings->get("Language");
if (preg_match("/^Error:/",$userlanguage) == 1)
	$userlanguage = "fre";

?>
<html>
  <head>
		<link rel="stylesheet" href="./lib/css/normalize.css" >
    <script language="javascript" src="./lib/js/jquery-2.1.1.min.js"></script>
		<link rel="stylesheet" href="./lib/css/bootstrap.min.css" >
		<script language="javascript" src="./lib/js/bootstrap.min.js" ></script >
		<script language="javascript" src="./lib/js/bootstrap-datepicker.js" ></script >
		<link rel="stylesheet" href="./lib/css/bootstrap-datepicker3.css" >
		<script language="javascript" src="./include/eureka.js" ></script >
		<link rel="stylesheet" href="./include/eureka.css" >
		<!--<link rel="stylesheet" type="text/css" href="css/common.css">-->
	</head>
	<body>
<?php
session_start();
if (isset($_REQUEST["page"]))
	$page=$_REQUEST["page"];
else if (isset($_SESSION["page"]))
	$page=$_SESSION["page"];
$_SESSION["page"]=$page;
session_write_close();
$insert=true;
include("components/nav.php");
?>
<?php
include("./".$page.".php");
?>
	</body>
</html>
