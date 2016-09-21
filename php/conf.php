<?php
$SETTINGS_FILE = 'settings.json';

$allowable = array('dbtype','server','database','user','pass','apiroot','credentialServerType','credentialServer','credentialDatabase','credentialUsername','credentialPassword','credentialTable','credentialUserColumn','credentialPassColumn','credentialAdminColumn','userRO','passRO');

function loadSettings($settingsFilename){
	global $allowable;
	$settings = array();
	if (!file_exists($settingsFilename)){
		
	}
	else{
		$content = file_get_contents($settingsFilename);
		$settings = json_decode($content,true);
		if ($settings==null)
			$settings = array();
	}
	foreach($allowable as $val){
		if (array_key_exists($val,$settings)){
			$GLOBALS[$val]=$settings[$val];
		}
		else{
			$GLOBALS[$val]='';
		}
	}
	
}
function saveSettings($settingsFilename){
	global $allowable;
	$buffer = array();
	foreach($allowable as $val){
		if (isset($GLOBALS[$val])){
			$buffer[$val]=$GLOBALS[$val];
		}
		else{
			$buffer[$val]='';
		}
	}
	file_put_contents($settingsFilename,json_encode($buffer,JSON_PRETTY_PRINT));
}

loadSettings($SETTINGS_FILE);
saveSettings($SETTINGS_FILE);

/*
$dbtype=$settings['dbtype'];//'pgsql';//mysql,mssql,pgsql
$server=$settings['server'];//'127.0.0.1';
$database=$settings['database'];//'warehouse';
$user=$settings['user'];//'warehouse';
$pass=$settings['pass'];//'dispatch';
$apiroot=$settings['apiroot'];//'http://127.0.0.1/dbview/api/';
$credentialServerType=$settings['credentialServerType'];//'pgsql';
$credentialServer=$settings['credentialServer'];//'127.0.0.1';
$credentialDatabase=$settings['credentialDatabase'];//'warehouse';
$credentialUsername=$settings['credentialUsername'];//'warehouse';
$credentialPassword=$settings['credentialPasswoerd'];//'dispatch';
$credentialTable=$settings['credentialTable'];//'credentials';
$credentialUserColumn=$settings['credentialUserColumn'];//'username';
$credentialPassColumn=$settings['credentialPassColumn'];//'password';
$credentialAdminColumn=$settings['credentialAdminColumn'];//'securitylevel';
$userRO=$user;
$passRO=$pass;*/
?>