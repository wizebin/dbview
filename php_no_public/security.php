<?php 

function checkAdminLevel($requiredAdminLevel){
	if ((!isset($requiredAdminLevel))||$requiredAdminLevel==null||$requiredAdminLevel==0){
		return true;
	}
	if ((!isset($credentialServerType)) ||(!isset($credentialServer)) ||(!isset($credentialUsername)) ||(!isset($credentialPassword)) ||(!isset($credentialDatabase))){
		return false;
	}
	$db = openSQL($credentialServerType,$credentialServer,$credentialUsername,$credentialPassword,$credentialDatabase);
	$username = '';
	$password = '';
	$qrey = "SELECT $credentialAdminColumn FROM $credentialDatabase.$credentialTable WHERE $credentialUserColumn=? AND $credentialPassColumn=?";
	$exparams = array($credentialUsername, $credentialPassword);
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