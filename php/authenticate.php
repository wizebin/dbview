<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=1;
	$required=array('username','password');
	include "secbase.php";
?><?php
global $ret;
if ($success){
	$ret['SUCCESS']=true;
	$ret['SECURITY_LEVEL']=$GLOBALS['seclevel'];
}
else{
	$ret['MISSING']=$missing;
}
echo(json_encode($ret));
?>