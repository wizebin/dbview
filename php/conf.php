<?php
$SETTINGS_DIR = dirname(__FILE__).'/settings/';
$SETTINGS_FILE = $SETTINGS_DIR.'settings.json';
$USING_ENVIRONMENT = false;
$ENVIRONMENT_PREFIX = "DBVIEW_";

if (!is_dir($SETTINGS_DIR)){
	mkdir($SETTINGS_DIR,0600,true);
}

$allowable = array('indexedOnly','rootOfPage','masterUsername','masterPassword','dbtype','server','database','user','pass','credentialServerType','credentialServer','credentialDatabase','credentialUsername','credentialPassword','credentialTable','credentialUserColumn','credentialPassColumn','credentialAdminColumn','userRO','passRO','mainTable','tableList');

function camelToUnderscore ($camel){
	return ltrim(strtoupper(preg_replace('/[A-Z]+/', '_$0', $camel)), '_');
}
function loadEnvironmentSettings(){
	global $allowable, $USING_ENVIRONMENT, $ENVIRONMENT_PREFIX;
	$settings = array();
	foreach($allowable as $setting){
		$env = getenv($ENVIRONMENT_PREFIX . camelToUnderscore($setting));
		if ($env !== false){
			$settings[$setting] = $env;
			$GLOBALS[$setting] = $env;
			$USING_ENVIRONMENT = true;
		}
	}
	return $settings;
}

function loadSettings($settingsFilename){
	$settingsFilename = $settingsFilename != null? $settingsFilename : $SETTINGS_FILE;
	global $allowable;
	$settings = array();
	$GLOBALS['cwd']=getcwd();
	if (!file_exists($settingsFilename)){
		
		$GLOBALS['systemConfigured']=false;
	}
	else{
		$GLOBALS['systemConfigured']=true;
		$content = file_get_contents($settingsFilename);
		$settings = json_decode($content,true);
		if ($settings==null)
			$settings = array();
		foreach($allowable as $val){
			if (array_key_exists($val,$settings)){
				$GLOBALS[$val]=$settings[$val];
			}
			else{
				$GLOBALS[$val]=null;
			}
		}
	}
	return $settings;
}
function loadEnvirontmentOrFileSettings($filename = null){
	$ret = loadEnvironmentSettings();

	if (count($ret) == 0){
		$ret = loadSettings();
	}
	else{
		$GLOBALS['systemConfigured']=true;
	}
	return $ret;
}
function saveSettings($settingsFilename = null){
	$settingsFilename = $settingsFilename != null? $settingsFilename : $SETTINGS_FILE;
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

loadEnvirontmentOrFileSettings();

//saveSettings($SETTINGS_FILE);
?>