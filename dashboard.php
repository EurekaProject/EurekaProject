<html>
<?php
if (!isset($insert))
{
?>
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
<?php
}
$readonly = "";
?>
	<body>
		<div class="container-fluid">
			<ul class="nav navbar-nav navbar-left" id='dashboardnav'>
						<li>
							<a href="?page=feedback"><?=_("Feedback")?></a>
						</li>
			</ul>
			<script>
$(document).ready(function()
{
	var nav = $('#NavBarList');
	if ($(nav).length > 0)
	{
		$('#dashboardnav').replaceAll($(nav).find('#OptionalNav'));
		
	}
});
			</script>
			<div class="col-md-6">
				<div class="panel panel-primary" >
					<div class="panel-heading text-center"><h4><?=_("Tasks Manager")?></h4></div>
					<div class="panel-body">
<?php
{
	$filter = [ "project"=>"col-xs-3", "name"=>"col-xs-3","user"=>"hidden","description"=>"col-xs-4","start"=>"hidden","estimate"=>"hidden","spent"=>"hidden","parent"=>"hidden","state"=>"hidden","padding"=>"col-xs-1"];
	include("components/tasksmng.php");
}
?>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="panel panel-primary">
					<div class="panel-heading text-center"><h4><?=_("Time Tracker")?></h4></div>
					<div class="panel-body">
<?php
{
	include("components/timetracker.php");
}
?>
					</div>
				</div>
			</div>
			<script>
$(document).ready(function()
{
	$(document).trigger('changeUser',[<?=$userid?>]);
});
			</script>
		</div>
	</body>
</html>
