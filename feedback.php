<html>
<?php
require_once("common/calendar.class.php");

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
		<script language="javascript" src="./include/eureka.js" ></script >
		<link rel="stylesheet" href="./include/eureka.css" >
		<!--<link rel="stylesheet" type="text/css" href="css/common.css">-->
		<style>
table {
  table-layout: fixed; 
  *margin-left: -150px;/*ie7*/
}
td, th {
  vertical-align: top;
  border-top: 1px solid #ccc;
  padding:10px;
  width:150px;
}
		</style>
	</head>
<?php
$userid=0;
if (isset($$_REQUEST["userid"]))
	$userid = $_REQUEST["userid"];
}
$tab = "weeks";
if (isset($_REQUEST["tab"]))
	$tab = $_REQUEST["tab"];
$nbcolumns = 10;
if (isset($_REQUEST["nbcolumns"]))
	$nbcolumns = $_REQUEST["nbcolumns"];
$offset = 0;
if (isset($_REQUEST["offset"]))
{
	$offset = $_REQUEST["offset"];
}

$limit=""+$nbcolumns;
if ($offset > 0)
	$limit += " "+$offset;

$days="";
$weeks="";
$months="";
${$tab}="active";

?>
	<body>
		<div class="container-fluid">
			<div class="row">
				<div id="FeedBack" ><!--class="panel panel-primary">-->
					<!--
					<div class="panel-heading text-center">
						<h4>
							<span class=""><?=_("Feed Back")?></span>
						</h4>
					</div>
					-->
					<div><!--class="panel-body"-->
						<div class="container-fluid">
							<ul class="nav nav-tabs">
								<li role="presentation" id="Days"   class="<?=$days?>"><a href="?tab=days"><?=_("Days")?></a></li>
								<li role="presentation" id="Weeks"  class="<?=$weeks?>"><a href="?tab=weeks"><?=_("Weeks")?></a></li>
								<li role="presentation" id="Months" class="<?=$months?>"><a href="?tab=months"><?=_("Months")?></a></li>
							</ul>
<?php
	$calendar = new calendar($nbcolumns, $offset);
	$calendar->bootstrap();
	$calendar->fixedcolumn();
	$calendar->generateTable($tab, "TasksGrid");
?>
							<script>
Number.prototype.pad = function(size)
{
	return String("00000"+this).substr(-size);
}

	var userid = <?=$userid?>;
	var settaskrow = function(taskid)
	{
		var showrow = false;
		var htmlstring = "";
		var inserttimerow = function ()
		{
			if (this.week !== undefined)
			{
				var week = parseInt(this.week);
				var td = $('#TasksGrid').find('#TaskRow'+this.taskid+' td[data-week='+this.year+'-'+week.pad(2)+']');
			}
			else if (this.month !== undefined)
			{
				var month = parseInt(this.month);
				var td = $('#TasksGrid').find('#TaskRow'+this.taskid+' td[data-month='+this.year+'-'+month.pad(2)+']');
			}
			else if (this.date !== undefined)
			{
				var week = parseInt(this.week);
				var td = $('#TasksGrid').find('#TaskRow'+this.taskid+' td[data-date='+this.date+']');
			}
			if ($(td).length > 0)
			{
				$(td).html("<span class='badge'>"+this.time+"</span>");
				showrow = true;
			}
		}
		var htmlstring = "";
		if (this.times !== undefined)
		{
			$(this.times).each(function()
			{
				inserttimerow.call(this);
			});
		}
		else if (this.time != undefined)
		{
			inserttimerow.call(this);
		}
		if (showrow)
		{
			$('#TasksGrid').find('#TaskRow'+taskid).removeClass('hidden');
		}
	}
	var refreshgridrow = function(taskid)
	{
		var options = {
			userid:userid,
			limit:"<?=$limit?>",
			group:"<?=$tab?>",
			taskid:taskid,
		};
		perform("ws/index.php", "getTime",options,function(){settaskrow.call(this,taskid)},{});
	}
	var displaytasksgrid = function()
	{
		var inserttask = function()
		{
			var htmlstring = "<?=$calendar->generateRow('TaskRow"+this.id+"', 'hidden', "<span class='small'>\"+this.project+\": \"+this.name+\"</span>")?>";
			$("#TasksGrid").append(htmlstring);
			refreshgridrow(this.id);
		}
		if (this.tasks !== undefined)
		{
			$(this.tasks).each(function()
			{
				inserttask.call(this);
			});
		}
		else if (this.id != undefined)
		{
				inserttask.call(this);
		}
	}
	var refreshproject = function(id)
	{
		var options = {
			userid:userid,
			order:"projectid",
		};
		if (id !== undefined)
			options.projectid = id;
		perform("ws/index.php", "getTask",options,displaytasksgrid,{});
	}
	var displayprojectsgrid = function()
	{
		if (this.projects !== undefined)
		{
			$(this.projects).each(function()
			{
				var htmlstring = "<tr id='ProjectRow"+this.id+"' class='hidden'><th scope='row' class='fixed-column'><span class='small'>"+this.name+"</span></th>";
				htmlstring += "<td> </td>";
				htmlstring += "</tr>";
				$("#TasksGrid").append(htmlstring);
				refreshproject(this.id);
			});
		}
		else if (this.id != undefined)
		{
			var htmlstring = "<tr id='ProjectRow"+this.id+"' class='hidden'><th scope='row' class='fixed-column'><span class='small'>"+this.name+"</span></th>";
			htmlstring += "<td> </td>";
			htmlstring += "</tr>";
			$("#TasksGrid").append(htmlstring);
			refreshproject(this.id);
		}
	}
	$(document).ready(function()
	{
		refreshproject();
		//var options = {all:"true"};
		//perform("ws/index.php", "getProject",options,displayprojectsgrid,{});
	});

							</script>
						</div>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
