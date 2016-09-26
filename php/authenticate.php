<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=1;
	$required=array('username','password');
	include "secbase.php";
?><?php
global $ret;
if ($success){
	if (isset($GLOBALS['systemConfigured']) && $GLOBALS['systemConfigured']){
		$ret['SUCCESS']=true;
		$ret['SECURITY_LEVEL']=$GLOBALS['seclevel'];
		$ret['SYSTEM_READY']=true;
	}
	else{
		$ret['SUCCESS']=false;
		$ret['WARNING']='System Not Yet Configured';
		$ret['SYSTEM_READY']=false;
	}
}
else{
	$ret['MISSING']=$missing;
}
echo(json_encode($ret));
?>