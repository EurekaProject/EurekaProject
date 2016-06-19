						<div class="container-fluid">
							<script type="text/javascript">
	var saveresource = function()
	{
		var options = {projectid:0};
		options.userid = $("#ResourceSetting").find("input[name='userid']").val();
		options.workhours = $("#ResourceSetting").find("select[name='workhours']").val();
		options.roleid = $("#ResourceSetting").find("select[name='roleid']").val();
		options.name = $("#ResourceSetting").find("input[name='name']").val();
		perform("ws/index.php", "saveResource",options,function() { resource_refreshresource(options.userid);} ,{});
	}
	var setname = function()
	{
		if (this.name !== undefined)
		{
			var MainDiv = $('#ResourceSetting');
			$(MainDiv).find("input[name='userid']").val(this.userid);
			$(MainDiv).find("input[name='name']").val(this.name);
			$(MainDiv).find("select[name='roleid']").val(this.roleid).change();
			$(MainDiv).find("select[name='workhours']").val(this.workhours).change();
		}
	}
	var resource_refreshresource = function(id)
	{
		perform("ws/index.php", "getResource",{userid:id,projectid:0},setname,{});
	}
	$(document).ready(function()
	{
			resource_refreshresource(<?=$userid?>);
	});
							</script>
							<form method="POST" class='form-horizontal' id="ResourceSetting">
								<div class="row">
									<input type="hidden" name="userid" class="form-control" value="<?=$userid?>" />
									<div class="form-group">
										<div class="col-sm-4">
											<div class="input-group">
												<span class="input-group-addon"><?=_("Name")?></span>
												<input type="text" name="name" class="form-control" value="" />
											</div>
										</div>
										<div class="col-sm-4">
											<div class="input-group">
												<span class="input-group-addon"><?=_("Role")?></span>
												<select name="roleid" class="form-control <?=$readonly?>">
													<option value="1" selected><?=_("Developper")?></option>
													<option value="2"><?=_("Master")?></option>
													<option value="3"><?=_("Owner")?></option>
													<option value="4"><?=_("Leader")?></option>
												</select>
											</div>
										</div>
										<div class="col-sm-4">
											<div class="input-group">
												<span class="input-group-addon"><?=_("Work hours")?></span>
												<select name="workhours" class="form-control">
<?php
	for ($i = 1; $i <= $MaxHoursPerDay * $HourResolution; $i++)
	{
		$h = $i / $HourResolution;
?>
													<option value='<?=$h?>'><?=intval($h * 100 / $MaxHoursPerDay)?>%</option>
<?php
	}
?>
												</select>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-4"></div>
									<div class="col-md-4">
										<div class="form-group">
											<button type="button" class="btn btn-primary btn-block" onclick="saveresource();"><span class="glyphicon glyphicon-floppy-save"></span> <?=_("Save")?></button>
										</div>
									</div>
									<div class="col-md-4"></div>
								</div>
							</form>
						</div>
