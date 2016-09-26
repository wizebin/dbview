<?php
$SETTINGS_DIR = dirname(__FILE__).'/settings/';
$SETTINGS_FILE = $SETTINGS_DIR.'settings.json';

if (!is_dir($SETTINGS_DIR)){
	mkdir($SETTINGS_DIR,0600,true);
}

$allowable = array('indexedOnly','rootOfPage','masterUsername','masterPassword','dbtype','server','database','user','pass','credentialServerType','credentialServer','credentialDatabase','credentialUsername','credentialPassword','credentialTable','credentialUserColumn','credentialPassColumn','credentialAdminColumn','userRO','passRO','mainTable','tableList');

function loadSettings($settingsFilename){
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
//saveSettings($SETTINGS_FILE);
?>