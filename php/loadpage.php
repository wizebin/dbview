<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=1;
	$required=array('page');
	include "secbase.php";
?><?php
global $ret;
$systemNotSetup = (isset($GLOBALS['systemConfigured']) && $GLOBALS['systemConfigured']==false);
if ($success||($systemNotSetup&&count($missing)==0)){
	$page = safeFilename($params['page']);
	$root = dirname(__FILE__).'/../php_pages/';
	
	if (isset($params['root'])){
		if ($params['root']=='home'){
			$root = $home;
		}
		else if ($params['root']=='here'){
			$root = dirname(__FILE__).'/';
		}
		else if ($params['root']=='settings'){
			$root = dirname(__FILE__).'/settings/';
		}
	}
	
	$page = $root.$page.'.php';
			
	if (file_exists($page)){
		ob_start();
		include($page);
		$output = ob_get_clean();
	
		$ret['RESULT']=$output;//file_get_contents($page);
		$ret['SUCCESS']=true;
	}
	else{
		$ret['RESULT']='<span style="display:block;font-size:2em;">Page <strong>'.$params['page'].'</strong> Unavailable!</span>';
		$ret['SUCCESS']=false;
	}
}
else{
	$ret['MISSING']=$missing;
}
echo(json_encode($ret));
?>