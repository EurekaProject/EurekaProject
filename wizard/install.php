<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../include/');
session_name("EurekaProject");

require_once "settings.service.php";
$settings = new settings_service();

$MaxDaysPerWeek=$settings->get("MaxDaysPerWeek");
$MaxHoursPerDay=$settings->get("MaxHoursPerDay");
$HourResolution=$settings->get("HourResolution");

session_start();
if (isset($_SESSION["Auth"]))
{
	$nextstep = "./step2.php";
	header("Location: ".$nextstep);
	die;
}
session_write_close();
if (isset($_REQUEST["authtool"]))
{
	
	if (isset($_REQUEST["authtoolpath"]) && $_REQUEST["authtoolpath"] !== "")
		$path = $_REQUEST["authtoolpath"];
	else
		$path = "../user";
	switch($_REQUEST["authtool"])
	{
		case "eurekauser":
			error_log("AuthToolPath: ".$path."/signin.php?site=EurekaProject&url=".dirname(dirname($_SERVER["PHP_SELF"]))."/");
			$settings->set("AuthToolPath",$path."/signin.php?site=EurekaProject&url=".dirname(dirname($_SERVER["PHP_SELF"]))."/");
			$nextstep = $path."/user.php?action=create";
		break;
	}
	header("Location: ".$nextstep);
	die;
}
?>
<!DOCTYPE html>
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
		<form action="<?=$_SERVER["PHP_SELF"]?>" type="post" class="form-horizontal" role="form">
			<div class="form-group form-group-sm col-sm-4 small">
				<label for="authtool" class="control-label col-sm-4"><?=_("Athentication tool")?>:</label>
				<div class="radio">
					<label>
						<input type="radio" name="authtool" value="eurekauser" />
						Eureka user authentication tool
					</label>
				</div>
				<input type="text" class="form-control" placeholder="tool location" name="authtoolpath" />
			</div>
			<button class="btn btn-default" type="submit"><?=_("Next")?></button>
		</form>
	</body>
</html>
