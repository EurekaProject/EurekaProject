<html>
<?php
if (!isset($insert))
{
?>
  <head>
		<link rel="stylesheet" href="./lib/css/normalize.css" >
    <script language="javascript" src="./lib/js/jquery-2.1.1.min.js"></script>
		<link rel="stylesheet" href="./lib/css/jquery-ui.min.css" >
    <script language="javascript" src="./lib/js/jquery-ui.min.js"></script>
		<link rel="stylesheet" href="./lib/css/bootstrap.min.css" >
		<script language="javascript" src="./lib/js/bootstrap.min.js" ></script >
		<script language="javascript" src="./lib/js/bootstrap-datepicker.js" ></script >
		<link rel="stylesheet" href="./lib/css/bootstrap-datepicker3.css" >
		<script language="javascript" src="./include/eureka.js" ></script >
		<link rel="stylesheet" href="./include/eureka.css" >
		<!--<link rel="stylesheet" type="text/css" href="css/common.css">-->
	</head>
<?php
}
if (isset($_REQUEST["projectid"]))
	$projectid=$_REQUEST["projectid"];
else
	$projectid=1;

$readonly = "disabled";
if ($userrole !== "leader")
{
	$resource = $resources->get(array("userid"=>$userid,"projectid"=>$projectid));
	if ($resource)
	{
		$username = $resource->get("name");
		$userrole = $resource->get("role");
	}
	if ($userrole === "master")
	{
		$readonly = "";
	}
}
else
{
	$readonly = "";
}
?>
	<body>
		<div class="container-fluid">
			<div class="panel panel-primary">
				<div class="panel-heading text-center">
					<div class="container">
						<h4>
							<span class=""><?=_("Projects Manager")?>:</span>
							<span id="projectname"></span>
						</h4>
					</div>
				</div>
				<div class="panel-body">
<?php
{
	include("components/project.php");
}
?>
				</div>
			</div>
			<div class="panel panel-primary" >
				<div class="panel-heading text-center"><h4><?=_("Tasks Manager")?></h4></div>
				<div class="panel-body">
<?php
{
	$filter = [ "project"=>"hidden", "name"=>"col-xs-2","user"=>"col-xs-1","description"=>"col-xs-4","start"=>"col-xs-1","estimate"=>"col-xs-2","spent"=>"col-xs-2","parent"=>"hidden","state"=>"hidden","padding"=>"hidden"];
	include("components/tasksmng.php");
}
?>
				</div>
			</div>
			<script>
		$(document).ready(function()
		{
			$(document).trigger("changeProject",[<?=$projectid?>]);
		});
			</script>
		</div>
	</body>
</html>
