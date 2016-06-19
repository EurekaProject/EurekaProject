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
		<script language="javascript" src="./include/eureka.js" ></script >
		<link rel="stylesheet" href="./include/eureka.css" >
		<!--<link rel="stylesheet" type="text/css" href="css/common.css">-->
	</head>
<?php
}
?>
	<body>
		<div class="container-fluid">
			<div class="col-md-12">
				<div class="panel panel-primary">
					<div class="panel-heading text-center">
						<h4>
							<span class=""><?=_("Resource Settings")?></span>
						</h4>
					</div>
					<div class="panel-body">
<?php
{
	include("components/resource.php");
}
?>
					</div>
				</div>
			</div>
		</div>
	</body>
</html>
