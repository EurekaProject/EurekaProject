					<div id="TasksManager" class="container-fluid">
						<script>
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
	var runtask = false;
	var displaytask = function()
	{
		var tasktable = "";
		var tasklist = "";
		var indent = 0;
		var inserttaskrow = function()
		{
			var warning = "";
			if (this.spent !== undefined && this.spent !== null && this.estimate !== undefined && this.estimate !== null && parseInt(this.spent) >= parseInt(this.estimate))
			{
				warning = "bg-danger";
			}
			var htmlstring = "<div class='row "+warning+"' data-taskid='"+this.id+"'>";
			if (this.projectid !== undefined && this.projectid !== null)
			{
				projectid = this.projectid;
				var projectlist = $('#saveTask').find('select[name="projectid"]');
				var projectname = $(projectlist).find('option[value="'+this.projectid+'"]');
				project = $(projectname).text();
			}
			else
			{
				projectid = 0;
				project = '';
			}
			htmlstring += "<div class='<?=$filter["project"]?>' data-projectid='"+projectid+"'>"+project+"</div>";
			htmlstring += "<div class='<?=$filter["name"]?>' data-name='"+this.name+"'>";
			for (i = 0; i < indent; i++)
				htmlstring += "-";
			htmlstring += this.name;
			htmlstring += "</div>";
			
			if (this.userid !== undefined && this.userid !== null)
			{
				var userlist = $('#saveTask').find('select[name="userid"]');
				var username = $(userlist).find('option[value="'+this.userid+'"]');
				user = $(username).text();
			}
			else
				user = "";
			htmlstring += "<div class='<?=$filter["user"]?>' data-userid='"+this.userid+"'>"+user+"</div>";
			htmlstring += "<div class='<?=$filter["description"]?>'"
			if (this.description !== undefined && this.description !== "")
				htmlstring += " data-description='true'><a href='#' class='' tabindex='0' title='description' role='button' data-provide='popover' data-toggle='popover' title='"+this.name+"' data-content='"+this.description.htmlEncode()+"' data-trigger='focus'>"+this.description.substring(0,30).htmlEncode()+"</a>";
			else
				htmlstring += ">";
			//htmlstring += "<button type='button' class='btn btn-lg btn-danger' data-provide='popover' data-toggle='popover' title='Popover title' data-content='And here s some amazing content. It s very engaging. Right?'>Click to toggle popover</button>";
			htmlstring += "</div>";
			var estimate = "";
			var i_estimate = -1;
			if (this.estimate !== undefined && this.estimate !== null)
			{
				estimate = formattime(this.estimate);
				i_estimate = parseFloat(this.estimate);
			}
			else
			{
				estimate="";
			}
			htmlstring += "<div class='<?=$filter["estimate"]?>' data-estimate='"+this.estimate+"'>"+estimate+"</div>";
			var spent = "";
			var spent_class = "";
			if (this.spent !== undefined && this.spent !== null)
			{
				spent = formattime(this.spent);
				if (i_estimate > 0 &&  i_estimate < parseFloat(this.spent))
					spent_class = "bg-danger";
			}
			else
			{
				spent="";
			}
			htmlstring += "<div class='<?=$filter["spent"]?> "+spent_class+"' data-spent='"+this.spent+"'>"+spent+"</div>";
			var start = "";
			if (this.start !== undefined && this.start !== null)
			{
				var time = new Date(this.start);
				start = time.toLocaleDateString();
			}
			else
			{
				start="";
			}
			htmlstring += "<div class='<?=$filter["start"]?>' data-start='"+this.start+"'>"+start+"</div>";
			htmlstring += "<div class='<?=$filter["parent"]?>' data-parentid='"+this.parentid+"'></div>";
			htmlstring += "<div class='<?=$filter["state"]?>' data-state='"+this.state+"'>"+this.state+"</div>";
			htmlstring += "<div class='<?=$filter["padding"]?>'></div>";
			htmlstring += "<a href='#' class='glyphicon glyphicon-pencil invisible pull-right' data-toggle='modal' data-target='#newTask' onclick='settaskedition("+this.id+");'></a>";
			htmlstring += "</div>";
			indent++;
			$(this.children).each(function ()
			{
				htmlstring += inserttaskrow.call(this);
			});
			indent--;
			return htmlstring;
		};
		var inserttaskoption = function()
		{
			htmlstring = "<option value='"+this.id+"'>";
			for (i = 0; i < indent; i++)
				htmlstring += "-";
			htmlstring += this.name;
			htmlstring += "</option>";
			indent++;
			$(this.children).each(function ()
			{
				htmlstring += inserttaskoption.call(this);
			});
			indent--;
			return htmlstring;
		};
		if (this.tasks !== undefined)
		{
			$(this.tasks).each(function ()
			{
				tasktable += inserttaskrow.call(this);
				tasklist += inserttaskoption.call(this);
			});
		}
		else if (this.id !== undefined)
		{
			tasktable += inserttaskrow.call(this);
			tasklist += inserttaskoption.call(this);
		}
		$('#TasksManager').find("#tasks").prepend(tasktable);
		//$('#TasksManager').find("select[name='taskid']").prepend(tasklist);
		$('#TasksManager').find("select[name='parentid']").prepend(tasklist);
		$('[data-toggle="popover"]').popover();
		runtask = false;
	}
	var settaskedition = function(id, type)
	{
		var name = "";
		var userid = "";
		var projectid = "";
		var parentid = "";
		var estimate = "0";
		var description = "";
		var state = "open";
		var TasksManager = $('#TasksManager');
		if (id !== undefined)
		{
			var Task = $(TasksManager).find('div[data-taskid="'+id+'"]');
			name = $(Task).find('div[data-name]').data('name');
			userid = $(Task).find('div[data-userid]').data('userid');
			projectid = $(Task).find('div[data-projectid]').data('projectid');
			parentid = $(Task).find('div[data-parentid]').data('parentid');
			estimate = $(Task).find('div[data-estimate]').data('estimate');
			state = $(Task).find('div[data-state]').data('state');
			description = $(Task).find('div[data-description]').find('a').data('content');

			disabled = true;
		}
		else
		{
			if (type !== undefined)
				state = type;
			disabled = false;
		}
		var TaskForm = $('#saveTask');
		$(TaskForm)[0].reset();
		$(TaskForm).find('input[name="id"]').val(id);
		var id2 = $(TaskForm).find('input[name="id"]').val();
		$(TaskForm).find('input[name="name"]').val(name);
		$(TaskForm).find('select[name="userid"]').val(userid).change();
		$(TaskForm).find('select[name="projectid"]').val(projectid).change().prop("disabled", disabled);
		$(TaskForm).find('select[name="parentid"]').val(parentid).change();
		$(TaskForm).find('textarea[name="description"]').val(description);
		$(TaskForm).find('input[name="estimate"]').val(estimate).change();
		tasktype(state);
	}
	var savetask = function()
	{
		var projectrefresh = true;
		var userrefresh = true;
		var options = {};
		var value = "";
		var TasksManager = $('#TasksManager');
		var TaskForm = $('#saveTask');
		var name = $(TaskForm).find('input[name="name"]').val();
		options.name = name;
		value = $(TaskForm).find('input[name="id"]').val();
		if (value !== undefined && value !== null && value !== "")
			options.id = parseInt(value);
		value = $(TaskForm).find('select[name="state"]').val();
		if (value !== undefined && value !== null && value !== "")
			options.state = value;
		value = $(TasksManager).data("userid");
		if (value === undefined || value === "" || value === "all")
		{
			value = $(TaskForm).find('select[name="userid"]').val();
			userrefresh = false;
		}
		if (value !== undefined && value !== null && value !== "")
			options.userid = value;
		value = $(TaskForm).find('input[name="estimate"]').val();
		if (value !== undefined && value !== null && value !== "")
			options.estimate = parseFloat(value);
		value = $(TasksManager).data("projectid");
		if (value === undefined || value === "" || value === "all")
		{
			value = $(TaskForm).find('select[name="projectid"]').val();
			projectrefresh = false;
		}
		if (value !== undefined && value !== null && value !== "")
		{
			options.projectid = parseInt(value);
		}
<?php
if ($userrole === "master")
{
?>
		else if (options.state === "flowing")
		{
			options.projectid = "null";
			options.userid = "null";
		}
<?php
}
?>
		else if (options.userid == undefined)
		{
			alert("<?=_("select a project to create a task")?>");
			return false;
		}
		value = $(TaskForm).find('select[name="parentid"]').val();
		if (value !== undefined && value !== null && value !== "")
			options.parentid = parseInt(value);
		value = $(TaskForm).find('textarea[name="description"]').val();
		if (value !== undefined && value !== "")
			options.description = value;
		value = $(TaskForm).find('input[name="start"]').val();
		if (value !== undefined && value !== "")
			options.start = value;
		perform("ws/index.php", "saveTask", options, function(){},{});
		$(TaskForm)[0].reset();
		if (projectrefresh)
			$(document).trigger("changeProject",[options.projectid]);
		if (userrefresh)
			$(document).trigger("changeUser",[options.userid]);
	};
	var taskmng_refreshtask = function(options)
	{
		if (runtask)
			return;
		runtask = true;
		if (options === undefined)
			options = {};
		if (options.parentid === undefined)
			options.parentid = 0;
		var TasksManager = $('#TasksManager');
		if (options.userid === undefined && $(TasksManager).data("userid") !== undefined)
		{
			options.userid = $(TasksManager).data("userid");
		}
		if (options.projectid === undefined && $(TasksManager).data("projectid") !== undefined)
		{
			options.projectid = $(TasksManager).data("projectid");
		}
		$(TasksManager).find("#tasks").html("");
		perform("ws/index.php", "getTask",options,displaytask,{});
	};
	var displayprojectfortask = function()
	{
		var htmlstring = "";
		var insertprojectoption = function()
		{
			var htmlstring = "<option value='"+this.id+"' class='project'>";
			htmlstring += this.name;
			htmlstring += "</option>";
			return htmlstring;
		};
		if (this.projects !== undefined)
		{
			$(this.projects).each(function ()
			{
				htmlstring += insertprojectoption.call(this);
			});
		}
		else if (this.id !== undefined)
		{
			htmlstring += insertprojectoption.call(this);
		}
		$('#saveTask').find("select[name='projectid']").append(htmlstring);
	};
	tasktype = function(type)
	{
		switch(type)
		{
			case "standard":
				$("#saveTask").find('.input-state').removeClass('hidden');
				$("#saveTask").find('.input-parentid').removeClass('hidden');
				$("#saveTask").find('.input-start').addClass('hidden').val('');
				$("#saveTask").find(".input-estimate").removeClass('hidden');
				$("#saveTask").find("input[name='estimate']").val("0");
				$("#saveTask").find("select[name='estimateh']").removeClass('hidden');
				$("#saveTask").find("select[name='estimated']").removeClass('hidden');
				$("#saveTask").find("select[name='estimatew']").removeClass('hidden');
				$("#saveTask").find("#label-hours").removeClass('hidden');
				$("#saveTask").find("#label-days").removeClass('hidden');
				$("#saveTask").find("#label-weeks").removeClass('hidden');
				$("#saveTask").find("select[name='estimatepercent']").addClass('hidden');
				$("#saveTask").find(".input-estimate").removeClass("col-sm-4").addClass('col-sm-7');
			break;
			case "flowing":
				$("#saveTask").find('.input-state').addClass('hidden').find("select[name='state']").val('flowing').change();
				$("#saveTask").find('.input-parentid').addClass('hidden').val("0");
				$("#saveTask").find('.input-start').addClass('hidden');
				$("#saveTask").find(".input-estimate").removeClass('hidden');
				$("#saveTask").find("select[name='esitmatepercent']").removeClass('hidden');
				$("#saveTask").find("select[name='estimateh']").addClass('hidden');
				$("#saveTask").find("select[name='estimated']").addClass('hidden');
				$("#saveTask").find("select[name='estimatew']").addClass('hidden');
				$("#saveTask").find("#label-hours").addClass('hidden');
				$("#saveTask").find("#label-days").addClass('hidden');
				$("#saveTask").find("#label-weeks").addClass('hidden');
				$("#saveTask").find(".input-estimate").removeClass('col-sm-7').addClass('col-sm-4');
			break;
			case "milestone":
				$("#saveTask").find('.input-state').addClass('hidden').find("select[name='state']").val('milestone').change();
				$("#saveTask").find('.input-parentid').addClass('hidden').val("0");
				$("#saveTask").find('.input-start').removeClass('hidden');
				$("#saveTask").find(".input-estimate").addClass('hidden');
				$("#saveTask").find("input[name='estimate']").val('');
			break;
		}
		var projectid = $('#TasksManager').data("projectid");
		if (projectid !== undefined && projectid !== "all")
		{
			$("#saveTask").find('.input-projectid').addClass("hidden").find("select[name='projectid']").val(projectid).change();
		}
		var userid = $('#TasksManager').data("userid");
		if (userid !== undefined)
		{
			$("#saveTask").find('.input-userid').addClass("hidden").find("select[name='userid']").val(userid).change();
		}
	};
	var displayresourcefortask = function()
	{
		var htmlstring = "";
		var insertresourceoption = function()
		{
			var htmlstring = "<option value='"+this.userid+"' class='resource'>";
			htmlstring += this.name;
			htmlstring += "</option>";
			return htmlstring;
		};
		if (this.resources !== undefined)
		{
			$(this.resources).each(function ()
			{
				htmlstring += insertresourceoption.call(this);
			});
		}
		else if (this.userid !== undefined)
		{
			htmlstring += insertresourceoption.call(this);
		}
		$('#saveTask').find("select[name='userid']").prepend(htmlstring);
	};
	var taskmng_refreshresources = function(options)
	{
		var tempo = $('#saveTask').find("select[name='userid'] option").remove(".resource");
		perform("ws/index.php", "getResource",options,displayresourcefortask,{});
	};
	var taskmng_refreshprojects = function(options)
	{
		var tempo = $('#saveTask').find("select[name='projectid'] option").remove(".project");
		perform("ws/index.php", "getProject",options,displayprojectfortask,{});
	}
	$(document).ready(function()
	{
		$(document).on("changeProject", function(event, id) {
			$('#TasksManager').data("projectid", id);
			taskmng_refreshresources({projectid:id});
			taskmng_refreshtask({projectid:id,state:"open",spent:"full"});
			taskmng_refreshtask({projectid:id,state:"flowing"});
		});
		$(document).on("changeUser", function(event, id) {
			$('#TasksManager').data("userid", id);
			taskmng_refreshprojects({userid:id});
			taskmng_refreshtask({userid:id,projectid:"all",state:"open"}); 
			taskmng_refreshtask({userid:id,state:"flowing"});
		});
	});
						</script>
						<div class="row">
							<div class="container-fluid" id="tasks"></div>
							<div class="container-fluid">
								<div class="row">
									<div class="btn-group col-md-1">
										<a class="<?=$readonly?>" data-toggle="dropdown" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="flase"><i class="glyphicon glyphicon-plus-sign text-success"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("Add Resource")?></span></a>
										<ul class="dropdown-menu">
											<li><a href="#" onclick="settaskedition(undefined, 'standard');" data-toggle="modal" data-target="#newTask"><?=_("Standard Task")?></a></li>
											<li><a href="#" onclick="settaskedition(undefined, 'flowing');" data-toggle="modal" data-target="#newTask"><?=_("Flowing Task")?></a></li>
											<li><a href="#" onclick="settaskedition(undefined, 'milestone');" data-toggle="modal" data-target="#newTask"><?=_("Milestone")?></a></li>
										</ul>
									</div>
								</div>
							</div>
						</div>
						<div id="newTask" class="modal fade" role="dialog">
							<div class="modal-dialog">
								<!-- Modal content-->
								<div class="modal-content">
									<form action="ws/index.php" method="post" id="saveTask" class="form-horizontal" role="form">
										<div class="modal-header" style="padding-bottom:0;">
											<div class="container-fluid">
												<div class="row">
													<strong class="modal-title col-sm-2"><?=_("Task")?></strong>			
													<div class="col-sm-4">
														<div class="form-group form-group-sm small">
															<div class="input-group input-sm input-projectid">
																<span class="input-group-addon"><?=_("Project")?>:</span>
																<select name="projectid" class="form-control input-sm">
																	<option value=""><?=_("none")?></option>
																</select>
															</div>
														</div>
													</div>
													<div class="col-sm-1"></div>
													<div class="col-sm-3">
														<div class="form-group form-group-sm small">
															<div class="input-group input-state">
																<span class="input-group-addon"><?=_("State")?>:</span>
																<select name="state" class="form-control input-sm">
																	<option value="open"><?=_("open")?></option>
																	<option value="close"><?=_("close")?></option>
																	<option value="flowing" class="hidden" ><?=_("flowing")?></option>
																	<option value="milestone" class="hidden" ><?=_("milestone")?></option>
																</select>
															</div>
														</div>
													</div>
													<button type="button" class="close col-sm-1" data-dismiss="modal">&times;</button>
												</div>
											</div>
										</div>
										<div class="modal-body">
											<div class="container-fluid">
												<div class="row">
													<div class="form-group">
														<div class="col-sm-7">
															<div class="input-group">
																<span class="input-group-addon"><?=_("Name")?>:</span>
																<input type="text" name="name" class="form-control"/>
															</div>
														</div>
														<div class="col-sm-5 input-parentid">
															<div class="input-group">
																<span class="input-group-addon"><?=_("Parent")?>:</span>
																<select name="parentid" class="form-control">
																	<option value="0" selected><?=_("none")?></option>
																</select>
															</div>
														</div>
														<span class="col-sm-5 input-start hidden">
															<div class="input-group date" data-provide="datepicker" data-date-format="yyyy-mm-dd">
																<span class="input-group-addon"><?=_("Date")?>:</span>
																<input name="start" type="text" class="form-control" />
																<div class="input-group-addon"><span class="glyphicon glyphicon-th"></span></div>
															</div>
														</span>
													</div>
													<div class="form-group">
														<div class="col-sm-12">
														<div class="input-group">
															<span class="input-group-addon"><?=_("Description")?>:</span>
															<textarea name="description" class="form-control noresize"></textarea>
														</div>
														</div>
													</div>
													<div class="form-group">
														<div class='col-sm-7 input-estimate'>
															<div class="input-group input-group-sm">
																<span class="input-group-addon"><?=_("Estimate")?>:</span>
																<input type="hidden" name="estimate" placeholder="<?=_("Time")?>(<?=_("hours")?>)" class="form-control" onchange="taskmng_changeestimate1();"/>
																<script>
	var taskmng_changeestimate1 = function()
	{
		var estimate = parseInt($("input[name='estimate']").val());
		$("select[name='estimatepercent']").val(estimate).change();
		$("select[name='estimatew']").val(estimate / parseInt(<?=$MaxHoursPerDay?> * <?=$MaxDaysPerWeek?>));
		estimate %=  <?=$MaxHoursPerDay?> * <?=$MaxDaysPerWeek?>;
		$("select[name='estimated']").val(estimate / parseInt(<?=$MaxHoursPerDay?>));
		estimate %=  <?=$MaxHoursPerDay?>;
		$("select[name='estimateh']").val(estimate);
	}
	var taskmng_changeestimate2 = function()
	{
		var estimate = parseInt($("select[name='estimateh']").val());
		estimate += parseInt($("select[name='estimated']").val()) * <?=$MaxHoursPerDay?>;
		estimate += parseInt($("select[name='estimatew']").val()) * <?=$MaxHoursPerDay?> * <?=$MaxDaysPerWeek?>;
		$("input[name='estimate']").val(estimate);
	}
	var taskmng_changeestimate3 = function()
	{
		var estimate = parseInt($("select[name='estimatepercent']").val());
		$("input[name='estimate']").val(estimate);
	}
																</script>
																<select name='estimatew' class='form-control small' onchange="taskmng_changeestimate2();" aria-label="weeks">
		<?php
			for ($i = 0; $i <= 8; $i++)
			{
		?>
																	<option value="<?=$i?>"><?=$i?></option>
		<?php
			}
		?>
																</select>
																<span class="input-group-addon small" id="label-weeks">w</span>
																<select name='estimated' class='form-control small' onchange="taskmng_changeestimate2();" aria-label="days">
		<?php
			for ($i = 0; $i <= $MaxDaysPerWeek; $i++)
			{
		?>
																	<option value="<?=$i?>"><?=$i?></option>
		<?php
			}
		?>
																</select>
																<span class="input-group-addon small" id="label-days">d</span>
																<select name='estimateh' class='form-control small' onchange="taskmng_changeestimate2();" aria-label="hours">
		<?php
			for ($i = 0; $i <= $MaxHoursPerDay; $i++)
			{
		?>
																	<option value="<?=$i?>"><?=$i?></option>
		<?php
			}
		?>
																</select>
																<span class="input-group-addon small" id="label-hours">h</span>
																<select name='esitmatepercent' class='form-control hidden' onchange="taskmng_changeestimate3();">
		<?php
			for ($i = 1; $i <= $MaxHoursPerDay * $HourResolution; $i++)
			{
		?>
																	<option value='<?=$i?>'><?=intval($i * 100 / ($MaxHoursPerDay * $HourResolution))?>%</option>
		<?php
			}
		?>
																</select>
															</div>
														</div>
														<div class="input-userid col-sm-4 pull-right">
															<div class="input-group input-group-sm">
																<span class="input-group-addon"><?=_("Owner")?>:</span>
																<select name="userid" class="form-control">
																	<option value="null" selected><?=_("none")?></option>
																</select>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="modal-footer">
											<div class="input-group">
												<input type="hidden" name="id" value=""/>
												<span class="input-group-btn">
													<button type="button" class="btn btn-default" data-dismiss="modal" onclick="this.form.reset();"><i class="glyphicon glyphicon-remove-circle text-danger"></i></button>
													<button type="button" class="btn btn-default" data-dismiss="modal" onclick="savetask();"><i class="glyphicon glyphicon-ok-circle text-success"></i></button>
												</span>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>
					</div>
