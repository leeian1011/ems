<?php
session_start();
error_reporting(0);
include('includes/dbconnection.php');
function check_login($attempt)
{
	if(!isset($_SESSION['odmsaid']))
	{	
		header("Location: /hevent/index.php");
	}
	if(!isset($_SESSION['status']))
	{	
		header("Location: /hevent/index.php");
	}
  $status = $_SESSION['status'];
  if ($attempt=="dashboard_admin") {
      if ($status!="1") {
		    header("Location: /hevent/index.php");
      }
  } else if ($attempt=="dashboard_event_admin") {
      if ($status!="0") {
		    header("Location: /hevent/index.php");
      }
  }
}
?>
