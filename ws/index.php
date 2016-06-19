<?php
header("Access-Control-Allow-Origin: *");
session_name("EurekaProject");
require_once("tasks.class.php");

$tasks = new tasks();
if (isset($_REQUEST["envelope"]))
{
  $message = $_REQUEST["envelope"];
  $response = $tasks->parse($message);
  $message = json_encode($response);
  //error_log("webs : ".$message);
  echo $message;
}
?>
