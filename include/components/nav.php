<?php
switch($page)
{
	case "project":
		$onclickprojectlist='return navadvertise("+this.id+","+this.name+");';
	break;
	case "dashboard":
	default:
		$onclickprojectlist="";
	break;
}
?>
	<script>
	var navdisplayprojectslist = function()
	{
		var projecttable = "";
		var indent = 0;
		var insertprojectrow = function()
		{
			var htmlstring = "<li class='project' data-projectid='"+this.id+"'>";
			htmlstring += "<a href='?page=project&projectid="+this.id+"' onclick='<?=$onclickprojectlist?>'>"+this.name+"</a>";
			htmlstring += "</li>";
			return htmlstring;
		};
		if (this.projects !== undefined)
		{
			$(this.projects).each(function ()
			{
				projecttable += insertprojectrow.call(this);
			});
		}
		else if (this.id !== undefined)
		{
			projecttable += insertprojectrow.call(this);
		}
		if (projecttable !== "")
		{
			$('#NavBar').find("#projects").prepend(projecttable);
			$('#NavBar').find("#projectsbutton").show();
		}
<?php
	if ($userrole !== "leader")
	{
?>
		else
		{
			$('#NavBar').find("#projectsbutton").hide();
		}
<?php
	}
?>
	}
	var navadvertise = function(id, name)
	{
		$(document).trigger("changeProject",[id, name]);
		return false;
	}
	var newproject = function ()
	{
		var options = {};
		options.masterid = "<?=$userid?>";
		options.name = $('#NavBar').find("input[name='name']").val();
		perform("ws/index.php", "saveProject",options,function ()
		{
			$('#NavBar').find("#projects").remove("li.project");
			navdisplayprojectslist.call(this);
		},{});
	}
	$(document).ready(function()
	{
		var found = false;
<?php
		switch ($userrole)
		{
			case "owner":
			case "leader":
			{
?>
		var options = {all:"true"};
		perform("ws/index.php", "getProject",options,navdisplayprojectslist,{});
<?php
			}
			break;
			default:
			{
?>
		var options = {masterid:<?=$userid?>};
		perform("ws/index.php", "getProject",options,navdisplayprojectslist,{});
<?php
			}
			break;
		}
?>
		//perform("ws/index.php", "getResource",{projectid:0},function(){if(this.userid==<?=$userid?>)found=true;}, function(){if(!found)perform("saveResource",{projectid:0,userid:<?=$userid?>,name:<?=$username?>}});
	});
		</script>
		<nav class="navbar navbar-default" id="NavBar">
			<div id="editProjectName" class="modal fade" role="dialog">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header"><strong><?=_("Create")?></strong><a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a></div>
						<div class="modal-body">
							<div class="input-group">
								<input type="text" class="form-control" placeholder="name" name="name" />
								<span class="input-group-btn">
									<button class="btn btn-default" data-dismiss="modal" type="button" onclick="newproject();"><i class="glyphicon glyphicon-ok-circle text-success"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("New")?></span></button>
									<button class="btn btn-default" data-dismiss="modal" type="button"><i class="glyphicon glyphicon-remove-circle text-danger"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("Cancel")?></span></button>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="container-fluid">
				<div class="navbar-header">
					<a class="navbar-brand" href="#">EurekaProject</a>
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#NavBarList" aria-expanded="false">
						<span class="sr-only">Toggle navigation</span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
						<span class="icon-bar"></span>
					</button>
				</div>
				<div class="navbar-collapse  collapse" id="NavBarList">
					<ul class="nav navbar-nav navbar-left">
						<li class="dropdown">
							<a href="?page=dashboard"><?=_("Dashboard")?></a>
						</li>
<?php
if ($userrole !== "developper")
{
?>
						<li class="dropdown" id="projectsbutton">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
								<?=_("Projects")?><span class="caret"></span>
							</a>
							<ul class="dropdown-menu" id="projects">
<?php
	if ($userrole === "leader")
	{
?>
								<li role="separator" class="divider"></li>
								<li>
									<a href="#" role="button" class="" data-toggle="modal" data-target="#editProjectName" aria-haspopup="true" aria-expanded="flase"><?=_("New")?></a>
								</li>
							</ul>
						</li>
						<li>
							<a href="?page=team"><?=_("Team")?></a>
						</li>
<?php
	}
	else
	{
?>
							</ul>
						</li>
<?php
	}
}
?>
					</ul>
					<ul class="nav navbar-nav navbar-left" id="OptionalNav">
					</ul>
					<ul class="nav navbar-nav navbar-right">
						<li class="dropdown">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
								<i class="glyphicon glyphicon-user"><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("user")?></span></i>
							</a>
							<ul class="dropdown-menu" id="resources">
								<li>
									<a href="?page=resourcesetting" role="button" class="" ><?=_("My settings")?></a>
								</li>
								<li>
									<a href="?logout" role="button" class="" ><?=_("Logout")?></a>
								</li>
							</ul>
						</li>
					</ul>
				</div>
			</div>
		</nav>
