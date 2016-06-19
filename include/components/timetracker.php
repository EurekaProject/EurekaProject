						<div id="TimeTracker" class="container-fluid">
							<script>
		var runtime = false;
		var displaytime = function()
		{
			var response = this;
			var htmlstring = "";
			var date = "";
			var total= 0;
			var inserttimerow = function ()
			{
				var time = new Time(this.time);
				var htmlstring = "<div class='row'>";
				htmlstring += "<div class='col-xs-8'>"+this.task+"</div>";
				htmlstring += "<div class='col-xs-4'>"+time.toLocaleString()+"</div>";
				//htmlstring += "<div class='col-xs-4'>"+this.start+"</div>";
				//htmlstring += "<div class='col-xs-4'>"+this.delay+"</div>";
				htmlstring += "</div>";
				total += parseFloat(this.time);
				return htmlstring;
			}
			if (response.times !== undefined)
			{
				$(response.times).each(function()
				{
					htmlstring += inserttimerow.call(this,htmlstring);
				});
			}
			else if (response.time != undefined)
			{
				htmlstring += inserttimerow.call(this,htmlstring);
			}
			var time = new Time(total);
			$('#TimeTracker').find('#times').append(htmlstring);
			//htmlstring += "<div class='col-xs-4'>"+this.start+"</div>";
			//htmlstring += "<div class='col-xs-4'>"+this.delay+"</div>";
			$('#TimeTracker').find('#footer #total').val(time.toLocaleString());
			runtime = false;
			$(document).trigger("changeTime",this);
		};
		var displaytaskfortime = function()
		{
			var tasksoption = "";
			var taskslist = "";
			var indent = 0;
			var inserttasklist = function()
			{
				var htmlstring;
				htmlstring = "<li class='task'><a href='#' onclick='settimeedition("+this.id+");' role='button' data-toggle='modal' data-target='#addTimeConfirm'>"+this.name+"</a></li>";
				return htmlstring;
			};
			if (this.tasks !== undefined)
			{
				$(this.tasks).each(function ()
				{
					taskslist += inserttasklist.call(this);
				});
			}
			else if (this.id !== undefined)
			{
				taskslist += inserttasklist.call(this);
			}
			$('#TimeTracker').find("select[name='task']").prepend(tasksoption);
			$('#TimeTracker').find("#taskslist").prepend(taskslist);
		}
		var timetracker_refreshtime = function()
		{
			if (runtime)
				return;
			runtime = true;
			var dateText = $('#datepicker').datepicker('getFormattedDate');
			$('#TimeTracker').find("input[name='date']").val(dateText);
			var userid = parseInt($('#TimeTracker').find("input[name='userid']").val());
			$('#TimeTracker').find('#times').html("");
			perform("ws/index.php", "getTime",{userid:userid, date:dateText, group:"task"},displaytime,{});
			if (dateText === "now")
				var date = new Date();
			else
				var date = new Date(dateText);
			$('#TimeTracker').find('#header').val(date.toLocaleDateString());
		}
		var addTime = function(options)
		{
			if (options === undefined)
				options = {};
			if (options.taskid === undefined)
			{
				options.taskid = $('#TimeTracker').find("input[name='taskid']").val();
			}
			if (parseInt(options.taskid) > 0 || options.taskid === "vacation")
			{
				if (options.time === undefined)
					options.time =parseFloat($('#TimeTracker').find("select[name='time']").val());
				if (options.date === undefined)
					options.date = $('#TimeTracker').find("input[name='date']").val();
				if (options.userid === undefined)
					options.userid = parseInt($('#TimeTracker').find("input[name='userid']").val());
				perform("ws/index.php", "addTime",options,timetracker_refreshtime,{});
			}
			return false;
		}
		var settimeedition = function(taskid)
		{
			$('#TimeTracker').find("input[name='taskid']").val(""+taskid);
		}
		var timetracker_refeshtaskslist = function (userid)
		{
			$('#TimeTracker').find('#taskslist').remove(".task");
			perform("ws/index.php", "getTask",{parentid:0, userid:userid,state:'flowing'},displaytaskfortime,{});
			perform("ws/index.php", "getTask",{parentid:0, userid:"null", projectid:"null",state:'flowing'},displaytaskfortime,{});
			perform("ws/index.php", "getTask",{parentid:0, userid:userid,state:'open',projectid:"all"},displaytaskfortime,{});
			perform("ws/index.php", "getTask",{parentid:0, userid:"null", projectid:"null",state:'open'},displaytaskfortime,{});
		}
		$(document).ready(function() {
			$('#datepicker').on('changeDate',timetracker_refreshtime);
			$(document).on("changeUser", function(event, userid) {
				$(TimeTracker).find("input[name='userid']").val(userid);
				timetracker_refreshtime();
				timetracker_refeshtaskslist(userid);
				perform("ws/index.php", "saveResource",{userid:userid, projectid:0},function(){},{});
				$(TimeTracker).on("submit", addTime);
			});
		});
							</script>
							<form action="ws/index.php" method="post" id="addTime" class="row" role="form">
								<script>
	var setdatepickerday = function(day)
	{
		return true;
	}
								</script>
								<div id="time" class="">
									<div class="container-fluid">
										<div class="row">
											<div id="datepicker" class="input-group date" data-provide="datepicker" data-date="today" data-date-week-start="1" data-date-calendar-weeks="true" data-date-language="<?=substr($userlanguage, 0, 2);?>" data-date-end-date="0d" data-date-format="yyyy-mm-dd" data-date-today-btn="linked" data-date-today-highlight="true" data-date-days-of-week-highlighted="[0,6]" data-date-orientation="left" data-date-autoclose="true">
												<input type="text" class="form-control text-center strong" for="date" id="header" readonly/>
												<input name="date" type="hidden" class="form-control text-center strong" />
												<div class="input-group-addon"><span class="glyphicon glyphicon-th"></span></div>
											</div>
										</div>
										<input type="hidden" name="userid" value="" />
										<input type="hidden" name="action" value="addTime"/>
										<input type="hidden" name="taskid" value=""/>
										<div id="addTimeConfirm" class="modal fade" role="dialog">
											<div class="modal-dialog">
												<div class="modal-content">
													<div class="modal-header"><strong><?=_("Create")?></strong><a href="#" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></a></div>
													<div class="modal-body">
														<div class="input-group">
																<label for="time" class="control-label col-sm-4"><?=_("Time")?>:</label>
																<div class="col-sm-8 text-left">
																	<select id="timepicker" name="time" class="form-control"placeholder="time" >
<?php
for ($i = 1; $i <= $MaxHoursPerDay; $i++)
{
?>
																		<option value='<?=$i-0.5?>'><?=($i-1)?>:30</option>
																		<option value='<?=$i?>'><?=($i)?>:00</option>
<?php
}
?>
																	</select>
																</div>
																<span class="input-group-btn">
																	<button class="btn btn-default" data-dismiss="modal" type="button"><i class="glyphicon glyphicon-remove-circle text-danger"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("Cancel")?></span></button>
																	<button class="btn btn-default" data-dismiss="modal" type="button" onclick="addTime();"><i class="glyphicon glyphicon-ok-circle text-success"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("New")?></span></button>
																</span>
														</div>
													</div>
												</div>
											</div>
										</div>
										<div class="row">
											<div class="container-fluid" id="times"></div>
											<div class="container-fluid">
												<div class="row">
													<div class="btn-group col-md-1">
														<a class="" data-toggle="dropdown" class="dropdown-toggle" role="button" aria-haspopup="true" aria-expanded="flase"><i class="glyphicon glyphicon-plus-sign text-success"></i><span class="sr-only sr-only-focusable" aria-hidden="true"><?=_("Add Time")?></span></a>
														<ul class="dropdown-menu" id="taskslist">
															<li role="separator" class="divider"></li>
															<li>
																<a href='#' onclick='settimeedition("vacation");' role='button' data-toggle='modal' data-target='#addTimeConfirm'><?=_("vacation")?></a>
															</li>
														</ul>
													</div>
												</div>
											</div>
										</div>
										<div class="row" id="footer">
											<div class='input-group'>
												<div class='input-group-addon'>total</div>
												<input type='text' class='form-control text-right' readonly id='total' value=''/>
											</div>
										</div>
									</div>
								</div>
							</form>
						</div>
