<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=1;
	$required=array('page');
	include "secbase.php";
?><?php
global $ret;
if ($success){
	$page = safeFilename($params['page']);
	if ($page=='settings'||$page=='credentials'){//blacklist settings for non admin... a bit hacky tbh
		if ($seclevel<100){
			$ret['SECURED']=false;
			$ret['ERRORS']=array('Security Level Too Low');
			$success=false;
		}
	}
	if ($success){
		$root = dirname(__FILE__).'/settings/';
		$page = $root.$page.'.json';
				
		if (file_exists($page)){
			$ret['RESULT']=file_get_contents($page);
			$ret['SUCCESS']=true;
		}
		else{
			$ret['RESULT']='<span style="display:block;font-size:2em;">Page <strong>'.$params['page'].'</strong> Unavailable!</span>';
			$ret['SUCCESS']=false;
		}
	}
}
else{
	$ret['MISSING']=$missing;
	$ret['SQLERROR']=$lasterror;
}
echo(json_encode($ret));
?>