<?php 
function addMessage($message){
	if (!isset($GLOBALS['messages'])){
		$GLOBALS['messages'] = array();
	}
	array_push($GLOBALS['messages'],$message);
}
function makeCredentialQuery($user="?",$pass="?"){
	global $credentialAdminColumn,$credentialDatabase,$credentialTable,$credentialUserColumn,$credentialPassColumn;
	return "SELECT $credentialAdminColumn FROM $credentialTable WHERE $credentialUserColumn=$user AND $credentialPassColumn=$pass;";
}
function checkAdminLevel($requiredAdminLevel){
	global $credentialAdminColumn,$credentialServerType,$credentialServer,$credentialUsername,$credentialPassword,$credentialDatabase,$credentialTable,$credentialUserColumn,$credentialPassColumn,$username,$password,$seclevel,$secured,$masterUsername, $masterPassword,$messages;
	if ((!isset($requiredAdminLevel))||$requiredAdminLevel==null||$requiredAdminLevel==0){
		return true;
	}
	if (!isset($credentialServerType)){
		addMessage('Credential Server Type Not Set');
		return false;
	}
	if (isset($masterUsername)&&isset($masterPassword)&&$username==$masterUsername&&$password==$masterPassword){
		$seclevel = 10000;
		$secured = true;
		return true;
	}
	if ($credentialServerType=='file'){
		if (file_exists(dirname(__FILE__).'/settings/credentials.json')){
			$credlist = json_decode(file_get_contents(dirname(__FILE__).'/settings/credentials.json'),true);
			if (isset($credlist[$username]) && $credlist[$username]['password']==$password){
				$seclevel=$credlist[$username]['securityLevel'];
				if ($seclevel >= $requiredAdminLevel){
					return true;
				}
				else{
					addMessage('Security Level Too Low (File)');
					return false;
				}
			}
			else{
				addMessage('Username and Password Not Found In Credential File');
				return false;
			}
		}
		else{
			addMessage('Credential File Not Setup');
			return false;
		}
	}
	if ((!isset($credentialServer)) ||(!isset($credentialUsername)) ||(!isset($credentialPassword)) ||(!isset($credentialDatabase))){
		addMessage('Credential Server Information Not Setup');
		return false;
	}
	$db = openSQL($credentialServerType,$credentialServer,$credentialUsername,$credentialPassword,$credentialDatabase);
	$qrey = makeCredentialQuery();
	$exparams = array($username, $password);
	$prepared = prepareSQL($credentialServerType, $db, $qrey);
	$results = executePreparedSQL($credentialServerType, $db, $prepared, $exparams);
	if ($results != null && count($results)>0){
		if ($requiredAdminLevel!=null && $results[0][$credentialAdminColumn] > $requiredAdminLevel){
			$secured=true;
			$seclevel=$results[0][$credentialAdminColumn];
			return true;
		}
		else{
			addMessage('Security Level Too Low (SQL)');
			return false;
		}
	}
	addMessage('Username And Password Not Found On Server');
	return false;
}
?>