					<div id="ProjectsManager" class="container-fluid" data-projectid="">
					<script>
	var estimate = 0;
	var spent = 0;
	var displayproject = function()
	{
		var project = {};
		if (this.projects !== undefined)
		{
			project = this.projects[0];
		}
		else if (this.id !== undefined)
		{
			project = this;
		}
		var ProjectDiv = $('#ProjectsManager');
		$(ProjectDiv).data("projectid", project.id);
		$("#projectname").html(this.name);
		if (this.description !== undefined && this.description !== null)
		{
			$(ProjectDiv).find("#description").html(project.description);
			$(ProjectDiv).find("textarea[name='description']").val(project.description);
		}
		if (project.spent !== undefined && project.spent !== null)
			$(ProjectDiv).find("input[name='spent']").val(project.spent);
		if (project.estimate !== undefined && project.estimate !== null)
			$(ProjectDiv).find("input[name='estimate']").val(project.estimate);
		if (project.end !== undefined && project.estimate !== null)
			$(ProjectDiv).find("input[name='end']").val(project.end);
	}
	var savedescription = function()
	{
		var ProjectDiv = $('#ProjectsManager');
		var id = $(ProjectDiv).data("projectid");
		var Description = $(ProjectDiv).find("textarea[name='description']");
		perform("ws/index.php", "saveProject",{id:id,description:$(Description).val()},displayproject,{});
	}
	var computetime = function(time)
	{
		var ret = {};
		ret.week = parseInt(time / (<?=$MaxHoursPerDay?>*<?=$MaxDaysPerWeek?>));
		time %= <?=$MaxHoursPerDay?> * <?=$MaxDaysPerWeek?>;
		ret.day = parseInt(time / <?=$MaxHoursPerDay?>);
		time %= <?=$MaxHoursPerDay?>;
		ret.hour = time;
		return ret;
	}
	var formattime = function(time)
	{
		var json = computetime(time);
		return json.week+"w "+json.day+"d "+json.hour+"h";
	}
	var displaytimes = function()
	{
		if (this.tasks !== undefined)
		{
			$(this.tasks).each(function()
			{
				var t_spent = 0;
				if (this.spent !== undefined && this.spent !== null)
				{
					t_spent = parseFloat(this.spent);
					spent += t_spent;
				}
				if (this.estimate !== undefined && this.estimate !== null
					&& this.state === 'open')
				{
					var t_estimate = parseFloat(this.estimate);
					if (t_spent < t_estimate)
						estimate += t_estimate - t_spent;
				}
			});
		}
		if (this.estimate !== undefined && this.estimate !== null
			&& this.state === 'open')
		{
			var tempo = parseFloat(this.estimate);
			estimate += tempo;
			if (this.spent !== undefined && this.spent < tempo)
				estimate -= this.spent;
		}
		if (this.spent !== undefined && this.spent !== null)
			spent += parseFloat(this.spent);
		$('#ProjectsManager').find("#estimate").html(formattime(estimate));
		$('#ProjectsManager').find("#spent").html(formattime(spent));
		project_refreshenddate();
	}
	var displaymilestone = function()
	{
		var insertmilestone = function()
		{
			var ProjectDiv = $('#ProjectsManager');
			var Milestone = $(ProjectDiv).find("#milestone");
			var date = new Date(this.start);
			Milestone.html(date.toLocaleDateString());
		}
		if (this.tasks !== undefined)
		{
			insertmilestone.call(this.tasks[0]);
		}
		if (this.start !== undefined && this.start !== null)
		{
			insertmilestone.call(this);
		}
	}
	var displayresources = function()
	{
		var resourceslist = "";
		var insertresourcerow = function()
		{
			var workhours = 0;
			workhours = parseInt(this.workhours*100/<?=$MaxHoursPerDay?>);
			var workhourstotal = parseInt(this.workhourstotal*100/<?=$MaxHoursPerDay?>);
			if (this.workhourstotal > this.workhoursmax)
				var color = "bg-danger";
			else
				var color = "";
			if (this.workhoursfortask !== undefined)
			{
				workhoursfortask = parseInt(this.workhoursfortask*100/<?=$MaxHoursPerDay?>);
				workhoursteam += workhoursfortask;
			}
			else
				workhoursteam += workhours;
			var htmlstring = "<div class='row "+color+"' data-userid='"+this.userid+"'>";
			htmlstring += "<div class='col-xs-3'>"+this.name+"</div>";
			htmlstring += "<div class='col-xs-4'>"+this.role+"</div>";
			htmlstring += "<div class='col-xs-2'>"+workhours+"%</div>";
			htmlstring += "<div class='col-xs-2'>"+workhourstotal+"%</div>";
<?php
	if ($readonly === "")
	{
?>
 			htmlstring += "<a href='#' class='glyphicon glyphicon-pencil invisible pull-right' data-toggle='modal' data-target='#addResource' onclick='setresource("+this.userid+","+this.workhours+");'></a>";
<?php
	}
?>
			htmlstring += "</div>";
			return htmlstring;
		}
		if (this.resources !== undefined)
		{
			$(this.resources).each(function ()
			{
				resourceslist += insertresourcerow.call(this);
			});
		}
		else if (this.userid !== undefined)
		{
			resourceslist += insertresourcerow.call(this);
		}
		var MainDiv = $('#ResourcesManager');
		$(MainDiv).find("#resourceslist").prepend(resourceslist);
		project_refreshenddate();
	}
	var project_refreshresources = function(options)
	{
		$('#ResourcesManager').find("#resourceslist").html("");
		workhoursteam = 0;
		perform("ws/index.php", "getResource",options,displayresources,{});
	}
	var project_refreshtimes = function(id)
	{
		if (id === undefined)
			id = $('#ProjectsManager').data("projectid");
		estimate = 0;
		spent = 0;
		cost = 0;
		perform("ws/index.php", "getTask",{projectid:id,state:"open",spent:"full"},displaytimes,{});
		perform("ws/index.php", "getTask",{projectid:id,state:"close",spent:"full"},displaytimes,{});
	}
	project_refreshenddate = function(id)
	{
		if (estimate == 0)
			return;
		if (id === undefined)
			id = $('#ProjectsManager').data("projectid");
		var date = new Date();
		
		var json = {};
		if (workhoursteam > 0)
		{
			json = computetime(estimate*100/workhoursteam);
			json.day += json.week * 7;
			json.hour += json.day * 24;
			var endtime = date.getTime() + (json.hour*60*60*1000);
			date.setTime(endtime);
			var weekday = date.getDay();
			if (weekday == 0)
				date.setTime(date.getTime() + (24*60*60*1000));
			if (weekday == 6)
				date.setTime(date.getTime() + (48*60*60*1000));
			$('#ProjectsManager').find("#enddate").html(date.toLocaleDateString());
		}
		else
			$('#ProjectsManager').find("#enddate").html("not enought resource");
	}
	var project_refreshproject = function(id)
	{
		if (id === undefined)
			id = $('#ProjectsManager').data("projectid");
		perform("ws/index.php", "getProject",{id:id},displayproject,{});
		perform("ws/index.php", "getTask",{projectid:id,state:"milestone",idx:0},displaymilestone,{});
		project_refreshresources({projectid:id});
		project_refreshtimes(id);
	}
	$(document).ready(function()
	{
		$(document).on("changeProject", function(event, id) { project_refreshproject(id); });
	});
						</script>
						<div id="editProjectDescription" class="modal fade" role="dialog">
							<div class="modal-dialog">
								<div class="modal-content">
									<div class="modal-header"><strong><?=_("Description")?></strong><a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a></div>
									<div class="modal-body">
										<div class="input-group">
											<textarea type="text" rows="5" class="form-control noresize" placeholder="description..." name="description" ></textarea>
											<span class="input-group-btn">
												<button class="btn btn-default" data-dismiss="modal" role="button" onclick="savedescription();">
													<i class="glyphicon glyphicon-ok-circle text-success"></i>
												</button>
												<span class="input-group-btn"></span>
												<button class="btn btn-default" data-dismiss="modal" type="button" ><i class="glyphicon glyphicon-remove-circle text-danger"></i></button>
											</span>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class='container-fluid'>
							<div class="row">
								<a class='' data-toggle="collapse" href="#collapseProjectInfo" aria-expanded="false" aria-controls="collapseProjectInfo"><span class="glyphicon glyphicon-collapse-down"></span></a>
							</div>
							<div class='row collapse' id='collapseProjectInfo'>
								<div class='col-md-7'>
									<div class='form-group has-feedback'>
										<div id='description' class="form-control jumbotron"></div>
<?php
	if ($readonly === "")
	{
?>
										<a role="button" class="glyphicon glyphicon-pencil form-control-feedback invisible" aria-label="Edit" data-toggle="modal" data-target="#editProjectDescription" aria-haspopup="true" aria-expanded="flase" aria-hidden="true">
											<span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("Edit")?></span>
										</a>
<?php
	}
?>
									</div>
								</div>
								<script>
		var workhoursteam = 0;
		var addresource = function()
		{
			var options = {};
			options.projectid = $("#ProjectsManager").data("projectid");
			options.userid = $("#addResource").find("select[name='userid']").val();
			options.roleid = $("#addResource").find("select[name='roleid']").val();
			options.workhours = $("#addResource").find("select[name='workhours']").val();
			perform("ws/index.php", "saveResource",options,function() { refreshresources(projectid);} ,{});
		}
		var setresource = function(userid, workhours)
		{
			$("#addResource").find("select[name='userid']").val(userid);
			$("#addResource").find("select[name='workhours']").val(workhours);
		}
		var setteamlist = function()
		{
			var resourceslist = "";
			var insertresourceoption = function()
			{
				var htmlstring = "<option value='"+this.userid+"' class='resource'>";
				htmlstring += "<span>"+this.name+"</span>";
				htmlstring += "</option>";
				return htmlstring;
			}
			if (this.resources !== undefined)
			{
				$(this.resources).each(function ()
				{
					resourceslist += insertresourceoption.call(this);
				});
			}
			else if (this.userid !== undefined)
			{
				resourceslist += insertresourceoption.call(this);
			}
			var MainDiv = $('#ResourcesManager');
			$(MainDiv).find("select[name='userid']").append(resourceslist);
		}
		$(document).ready(function()
		{
			var options = {projectid:0};
			perform("ws/index.php", "getResource",options,setteamlist,{});
		});
								</script>
								<div class='col-md-5' id="ResourcesManager">
									<div id="addResource" class="modal fade" role="dialog">
										<div class="modal-dialog">
											<div class="modal-content">
												<div class="modal-header"><strong><?=_("Add resource")?></strong><a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a></div>
												<div class="modal-body">
													<div class="form-group">
														<div class="input-group">
															<span class="input-group-addon"><?=_("Add: ")?></span>
															<select name="userid" class="form-control">
															</select>
															<span class="input-group-addon"></span>
															<select name="roleid" class="form-control">
																<option value="1"><?=_("Developper")?></option>
																<option value="2"><?=_("Master")?></option>
																<option value="3"><?=_("Owner")?></option>
															</select>
															<span class="input-group-addon"></span>
															<select name="workhours" class="form-control">
	<?php
		for ($i = 1; $i < ($MaxHoursPerDay * $HourResolution) + 1; $i++)
		{
			$h = $i / $HourResolution;
	?>
																<option value='<?=$h?>'><?=intval($h * 100 / $MaxHoursPerDay)?>%</option>
	<?php
		}
	?>
															</select>
															<span class="input-group-btn">
																<button class="btn btn-default" data-dismiss="modal" type="button" onclick="addresource();"><i class="glyphicon glyphicon-ok-circle text-success"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("New")?></span></button>
																<button class="btn btn-default" data-dismiss="modal" type="button"><i class="glyphicon glyphicon-remove-circle text-danger"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("Cancel")?></span></button>
															</span>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class='container-fluid'>
										<div class='container-fluid'>
											<div class='row'>
												<div class='col-xs-3'><?=_("Name")?></div>
												<div class='col-xs-4'><?=_("Role")?></div>
												<div class='col-xs-2'><?=_("Project")?></div>
												<div class='col-xs-2'><?=_("Total")?></div>
												<div class='col-xs-1'></div>
											</div>
										</div>
										<div class='container-fluid' id="resourceslist">
										</div>
										<div class='container-fluid'>
											<div class="row">
												<div class='col-xs-5'>
													<a class="<?=$readonly?>" data-toggle="modal" data-target="#addResource" role="button"><i class="glyphicon glyphicon-plus-sign text-success"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("Add Resource")?></span></a>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class='row'>
							<div class='col-sm-3 col-xs-6'>
								<span class=''><?=_("Estimate")?>:</span>
								<a id='estimate' href='#' class='' onclick='project_refreshtimes();'></a>
							</div>
							<div class='col-sm-3 col-xs-6'>
								<span class=''><?=_("Spent")?>:</span>
								<a id='spent' href='#' class='' onclick='project_refreshtimes();'></a>
							</div>
							<div class='col-sm-3 col-xs-6'>
								<span class=''><?=_("Estimate end date")?>:</span>
								<a id='enddate' href='#' class='' onclick='project_refreshenddate();'></a>
							</div>
							<div class='col-sm-3 col-xs-6'>
								<span class=''><?=_("Next Milestone")?>:</span>
								<a id='milestone' href='#' class='' onclick='perform("ws/index.php", "getTask",{projectid:id,state:"milestone"},displaymilestone,{});'></a>
							</div>
						</div>
					</div>
