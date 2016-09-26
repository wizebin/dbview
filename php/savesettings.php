<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=100;
	$required=array('page','data');
	include "secbase.php";
?><?php
global $ret;
$systemNotSetup = (isset($GLOBALS['systemConfigured']) && $GLOBALS['systemConfigured']==false);
if ($success||($systemNotSetup)){
	$page = safeFilename($params['page']);
	$root = dirname(__FILE__).'/settings/';
	$page = $root.$page.'.json';
	$res = file_put_contents($page,$params['data']);
	if ($res){
		$ret['SUCCESS']=true;
	}
	else{
		$ret['RESULT']='<span style="display:block;font-size:2em;">CANNOT SAVE <strong>'.$params['page'].'</strong></span>';
		$ret['SUCCESS']=false;
	}
}
else{
	$ret['MISSING']=$missing;
}
echo(json_encode($ret));
?>