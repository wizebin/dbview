<?php
header('Content-type: application/json');
$home = $_SERVER['DOCUMENT_ROOT'];
global $ret,$required,$seclevel,$userid;
$missing = array();$secured=false;
$ret=array('SUCCESS'=>false);
if(isset($securityRequired) && $securityRequired>0){
	if (!(isset($required))){
		$required=array();
	}
	if(!(isset($required['username']))){
		array_push($required,'username');
	}
	if(!(isset($required['password']))){
		array_push($required,'password');
	}
}
$input=file_get_contents('php://input');
$jn=json_decode($input,true);	
if(count($jn)>0){$params=$jn;}
else if(count($_POST)>0){$params=$_POST;}
else if(count($_GET)>0){$params=$_GET;}
$success=true;
if (isset($required))foreach($required as $param){if(!isset($params[$param])){$success=false;array_push($missing,$param);}}
include("basics.php");
include("security.php");
if($success){
	if (isset($securityRequired) && $securityRequired>0){
		$username=$params['username'];
		$password=$params['password'];
		$verified = checkAdminLevel($securityRequired);
		if ($verified){
			$success=true;
			$ret['SECURED']=true;
		}
		else{
			$success=false;
			$ret['SECURED']=false;
			$ret['ERROR']='UNAUTHENTICATED';
		}
		
	}
	else{
		$ret['SECURED']=false;
	}
	//ELSE UNSECURED
}
else{
	$ret['ERROR']='MISSING PARAMETERS';
	$ret['MISSING']=$missing;
}
?>