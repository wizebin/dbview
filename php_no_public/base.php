<?php
global $ret;
$missing = array();
$ret=array('SUCCESS'=>false);
$input=file_get_contents('php://input');
$jn=json_decode($input,true);	
if(count($jn)>0){$params=$jn;}
else if(count($_POST)>0){$params=$_POST;}
else if(count($_GET)>0){$params=$_GET;}
$success=true;
if (isset($required))foreach($required as $param){if(!isset($params[$param])){$success=false;array_push($missing,$param);}
}?>