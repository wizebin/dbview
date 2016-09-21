<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=1;
	$required=array('page');
	include "secbase.php";
?><?php
global $ret;
if ($success){
	$page = safeFilename($page);
	$root = 'pages/';
	if ($params['root']=='home'){
		$root = $home;
	}
	else if ($params['root']=='here'){
		$root = '';
	}
	else if ($params['root']=='settings'){
		$root = 'settings/';
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
		$ret['SUCCESS']=true;
	}
}
else{
	$ret['MISSING']=$missing;
}
echo(json_encode($ret));
?>