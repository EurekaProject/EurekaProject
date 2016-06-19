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
$readonly="";

$limit=""+$nbcolumns;
if ($offset > 0)
	$limit += " "+$offset;

$days="";
$weeks="";
$months="";
${$tab}="active";
$rowheader="<a href='#' class='small' data-toggle='modal' data-target='#addTime' onclick='selectuser(\"+this.userid+\");'>\"+this.name+\"</a>";
?>
	<body>
		<div class="container-fluid">
			<div class="row">
				<div ><!--class="panel panel-primary">-->
					<!--
					<div class="panel-heading text-center">
						<h4>
							<span class=""><?=_("Feed Back")?></span>
						</h4>
					</div>
					-->
					<div><!--class="panel-body"-->
						<div id="addTime" class="modal fade" role="dialog">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header"><strong><?=_("Add Time")?></strong><a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a></div>
									<div class="modal-body">
<?php
	require("components/timetracker.php");
?>
									</div>
								</div>
							</div>
						</div>
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
	$calendar->generateTable($tab, "UsersGrid");
?>
							<script>

	var userid = <?=$userid?>;
	var setuserrow = function(userid)
	{
		var showrow = false;
		var htmlstring = "";
		var inserttimerow = function ()
		{
			if (this.week !== undefined)
			{
				var week = parseInt(this.week);
				var td = $('#UsersGrid').find('#UserRow'+this.userid+' td[data-week='+this.year+'-'+week.pad(2)+']');
			}
			else if (this.month !== undefined)
			{
				var month = parseInt(this.month);
				var td = $('#UsersGrid').find('#UserRow'+this.userid+' td[data-month='+this.year+'-'+month.pad(2)+']');
			}
			else if (this.date !== undefined)
			{
				var week = parseInt(this.week);
				var td = $('#UsersGrid').find('#UserRow'+this.userid+' td[data-date='+this.date+']');
			}
			if ($(td).length > 0)
			{
				if (this.state === "vacation")
					$(td).addClass("bg-success");
				if (this.time !== undefined)
					$(td).prepend("<span class='badge'>"+this.time+"</span>");
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
		else
		{
			inserttimerow.call(this);
		}
		if (showrow)
		{
			$('#UsersGrid').find('#UserRow'+userid).removeClass('hidden');
		}
	}
	var refreshgridrow = function(userid)
	{
		var options = {
			userid:userid,
			limit:"<?=$limit?>",
			group:"<?=$tab?>",
		};
		$('#UsersGrid').find('td').html("");//"<a class='glyphicon glyphicon-pencil <?=$readonly?>' data-toggle='modal' data-target='#addTime' role='button'><span class='sr-only sr-only-focusable' aria-hidden='true'><?=_("Add Time")?></span></a>");
		perform("ws/index.php", "getTime",options,function(){setuserrow.call(this,userid)},{});
	}
	var selectuser = function(userid)
	{
		$(document).trigger("changeUser",userid);
	}
	var displayusersgrid = function()
	{
		var insertuser = function()
		{
			var htmlstring = "<?=$calendar->generateRow('UserRow"+this.userid+"', 'hidden', $rowheader)?>";
			$("#UsersGrid").append(htmlstring);
			refreshgridrow(this.userid);
		}
		if (this.resources !== undefined)
		{
			$(this.resources).each(function()
			{
				insertuser.call(this);
			});
		}
		else if (this.userid != undefined)
		{
				insertuser.call(this);
		}
	}
	var refreshteam = function()
	{
		var options = {
			all:"true",
			projectid:0,
		};
		perform("ws/index.php", "getResource",options,displayusersgrid,{});
	}
	$(document).ready(function()
	{
		$(document).on("changeTime",function (evt, time)
		{
			if (time.userid !== undefined)
				refreshgridrow(time.userid);
		});
		refreshteam();
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
