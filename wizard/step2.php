<?php
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../include/');
session_name("EurekaProject");

require_once "settings.service.php";
$settings = new settings_service();

$MaxDaysPerWeek=$settings->get("MaxDaysPerWeek");
$MaxHoursPerDay=$settings->get("MaxHoursPerDay");
$HourResolution=$settings->get("HourResolution");

session_start();
$userid = $_SESSION["Auth"];
session_write_close();
if (isset($_REQUEST["action"]))
{
	
	switch($_REQUEST["action"])
	{
		case "save":
			require_once "resource.service.php";
			$resources = new resource_service();

			$resources->save(array("userid"=>$userid,"projectid"=>0,"name"=>$_REQUEST["name"],"roleid"=>$_REQUEST["roleid"]));
			$nextstep = "./step3.php";
		break;
	}
	header("Location: ".$nextstep);
	die;
}
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
		<form action="<?=$_SERVER["PHP_SELF"]?>" type="post" class="form-horizontal" role="form">
			<div class="form-group form-group-sm col-sm-4 small">
				<div class="form-group">
					<label for="name"><?=_("Pseudo")?>: </label>
					<input type="text" class="form-control" placeholder="pseudo" name="name" />
				</div>
				<input type="hidden" class="form-control" name="roleid" value="4" />
				<input type="hidden" class="form-control" name="action" value="save" />
			</div>
			<button class="btn btn-default" type="submit"><?=_("Next")?></button>
		</form>
	</body>
</html>

