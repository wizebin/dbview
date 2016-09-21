<?php include ("conf.php");
$dberrors = array();$lasterror="";$lastid=null;$lastaffected=null;
function noteError($errString){
	global $dberrors,$lasterror;
	$lasterror=$errString;
	array_push($dberrors, $lasterror);
}
//MAKE POSTGRES CONNECTION STRING
function makepgstring($server, $username, $password, $database, $port){
	$connectionstring = "";
    if ($server=="") $connectionstring .="host=" . "localhost ";
    else $connectionstring .= "host=" . $server . " ";
    if ($username!="") $connectionstring .= "user=" . $username . " ";
    if ($password!="") $connectionstring .= "password=" . $password . " ";
    if ($database!="") $connectionstring .="dbname=" . $database . " ";
    if ($port!=null) $connectionstring .="port=" . $port . " ";
	$connectionstring .= "connect_timeout=5";
	return $connectionstring;
}
function openMYSQL($server, $username, $password, $database = '', $port = null){
	$usePort = $port==null?ini_get("mysqli.default_port"):$port;
	$dbh =  new mysqli($server,$username,$password,$database,$usePort);
	if ($dbh->connect_errno){
		noteError("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
	}
	else{
		mysqli_set_charset($dbh,'utf8');
	}
	return $dbh;
}
function openPGSQL($server, $username, $password, $database = '', $port = null){
	$connectionstring = makepgstring($server, $username, $password, $database, $port);
    $dbh = pg_connect($connectionstring);
	if ($dbh === false){
		noteError("Failed to connect to PGSQL: ". pg_last_error());
	}
	return $dbh;
}
function openMSSQL($server, $username, $password, $database = '', $port = null){
	//server should be serverName\instanceName, port
	$connectionInfo = array( "Database"=>$database, "UID"=>$username, "PWD"=>$password);
	$conn = sqlsrv_connect( $server, $connectionInfo);
	if ($conn){
		return $conn;
	}
	else{
		noteError("Failed to connect to MSSQL: " . sqlsrv_errors());
	}
	return $conn;
}
function executeMYSQL($db, $query){
	$result = $idb->query($query);
	
	if($result==null){
		noteError("iquery error ".mysqli_errno($idb)." : ".mysqli_error($idb));
	}
	else if ($result && gettype($result)!='boolean'){
		$retval = array();
		
		$data = mysqli_fetch_assoc($result);
		while($data != false){
			array_push($retval,$data);
			$data = mysqli_fetch_assoc($result);
		}
		mysqli_free_result($result);
		
		return $retval;
	}
	$lastid = mysqli_insert_id($db);//store insert ID
	$lastaffected = mysqli_affected_rows($db);
	return $result;
}
function executePGSQL($db, $query){
	$result = pg_query($db,$query);
    if($result==null){
		noteError("Failed To Execute PGSQL: ".pg_last_error($db));
		return $result;
    }
	$ret = array();
	$lastres = pg_fetch_array($result, null, PGSQL_ASSOC);
	while($lastres != null){
		array_push($ret,$lastres);
		$lastres = pg_fetch_array($result, null, PGSQL_ASSOC);
	}
	$lastaffected = pg_affected_rows($result);
	//$lastid = pg_last_oid($result);
	//if (pg_num_rows($result)==null && count($ret)==0){
		//not a select, return a bool
		//$ret=($result!=null);
	//}
	pg_free_result($result);
	return $ret;
}
function executeMSSQL($db, $query){
	$result = sqlsrv_query($db,$query);
	if (!$result){
		noteError("Failed to execute MSSQL: " . sqlsrv_errors(SQLSRV_ERR_ERRORS)); 
		return $result;
	}
	$ret = array();
	$lastres = sqlsrv_fetch_array($result);
	while($lastres != null){
		array_push($ret,$lastres);
		$lastres = sqlsrv_fetch_array($result);
	}
	
	$lastaffected = sqlsrv_rows_affected($result);
	$lastid = sqlsrv_get_field($resource, 0); 
	
	sqlsrv_free_stmt($result);
	return $ret;
	
}

function getTypeLetter($intype){
	$buftype = gettype($intype);
	if ($buftype=='string')return 's';
	switch($buftype){
		case 'string':
			return 's';
		case 'integer':
		case 'boolean':
			return 'i';
		case 'float':
			return 'd';
		case 'NULL':
		case 'unknown type':
		case 'resource':
		case 'object':
		case 'array':
			return 's';
		default:
			return 's';
	}
}
function makeTypeArray($ray){
	return array_map("getTypeLetter",$ray);
}

function prepareMYSQL($db, $query){
	return mysqli_prepare($db, $query);
}
function preparePGSQL($db, $query){
	$val = 1;
	$pos = strpos($query,'?');
	while($pos!=false){
		$query = substr_replace($query,"\$$val",$pos,1);
		$val+=1;
		$pos = strpos($query,'?');
	}
	return pg_prepare($db, "", $query); //blank statement name
}
function prepareMSSQL($db, $query){
	return sqlsrv_prepare($db, $query);
}
function executePreparedMYSQL($db, $prepared, $params){
	$types = makeTypeArray($params);
	$res = mysqli_stmt_bind_param($prepared,$types,$params);
	if (!res){
		noteError('FAILED TO BIND TO MYSQL: ' . mysqli_stmt_error($prepared));
		return false;
	}
	$res = mysqli_stmt_execute($prepared);
	if (!res){
		noteError('FAILED TO EXECUTE PREPARED MYSQL: ' . mysqli_stmt_error($prepared) . " " . mysqli_stmt_sqlstate($prepared));
		return false;
	}
	$lastaffected = mysqli_stmt_affected_rows($prepared);
	$lastid = mysqli_stmt_insert_id($prepared);
	
	$metaResults = mysqli_stmt_result_metadata($prepared);
    $fields = $metaResults->fetch_fields();
    $statementParams='';
     //build the bind_results statement dynamically so I can get the results in an array
    foreach($fields as $field){
         if(empty($statementParams)){
             $statementParams.="\$column['".$field->name."']";
         }else{
             $statementParams.=", \$column['".$field->name."']";
         }
    }
    $statment="mysqli_stmt_bind_result($prepared,$statementParams);";
    eval($statment);
	$ret = array();
    while($stmt->fetch()){
		array_push($ret,$column);
    }
	return $ret;
	
}
function executePreparedPGSQL($db, $prepared, $params){
	$result = pg_execute($db, "", $params);
    if($result==null){
		noteError("Failed To Execute PGSQL: ".pg_last_error($db));
		return $result;
    }
	$ret = array();
	$lastres = pg_fetch_array($result, null, PGSQL_ASSOC);
	while($lastres != null){
		array_push($ret,$lastres);
		$lastres = pg_fetch_array($result, null, PGSQL_ASSOC);
	}
	$lastaffected = pg_affected_rows($result);
	$lastid = pg_last_oid($result);
	pg_free_result($result);
    return $ret; 
}
function executePreparedMSSQL($db, $prepared, $params){
	
}

function closePreparedMYSQL($prepared){
	mysqli_stmt_close($prepared);
}
function closePreparedPGSQL($prepared){
	//nop- not a resource to close
}
function closePreparedMSSQL($prepared){

}

function closeMYSQL($db){
	mysqli_close($db);
}
function closePGSQL($db){
	pg_close($db);
}
function closeMSSQL($db){
	sqlsrv_close($db);
}

///$SQLType can be ['mysql','pgsql','mssql'];
///I would normally use a function pointer map...
function openSQL($SQLType, $server, $username, $password, $database = '', $port = null){
	switch($SQLType){
		case 'mysql':
			return openMYSQL($server, $username, $password, $database, $port);
		break;
		case 'mssql':
			return openMSSQL($server, $username, $password, $database, $port);
		break;
		case 'pgsql':
		case 'postgres':
			return openPGSQL($server, $username, $password, $database, $port);
		break;
		default:
			return -1;
		break;
	}
}
function executeSQL($SQLType, $dbHandle, $query){
	switch($SQLType){
		case 'mysql':
			return executeMYSQL($dbHandle, $query);
		break;
		case 'mssql':
			return executeMSSQL($dbHandle, $query);
		break;
		case 'pgsql':
		case 'postgres':
			return executePGSQL($dbHandle, $query);
		break;
		default:
			return -1;
		break;
	}
}
function closeSQL($SQLType, $dbHandle){
	switch($SQLType){
		case 'mysql':
			return closeMYSQL($dbHandle);
		break;
		case 'mssql':
			return closeMSSQL($dbHandle);
		break;
		case 'pgsql':
		case 'postgres':
			return closePGSQL($dbHandle);
		break;
		default:
			return -1;
		break;
	}
}
function prepareSQL($SQLType, $dbHandle, $query){
	switch($SQLType){
		case 'mysql':
			return prepareMYSQL($dbHandle,$query);
		break;
		case 'mssql':
			return prepareMSSQL($dbHandle,$query);
		break;
		case 'pgsql':
		case 'postgres':
			return preparePGSQL($dbHandle,$query);
		break;
		default:
			return -1;
		break;
	}
}
function executePreparedSQL($SQLType, $dbHandle, $prepared, $params){
	switch($SQLType){
		case 'mysql':
			return executePreparedMYSQL($dbHandle, $prepared, $params);
		break;
		case 'mssql':
			return executePreparedMSSQL($dbHandle, $prepared, $params);
		break;
		case 'pgsql':
		case 'postgres':
			return executePreparedPGSQL($dbHandle, $prepared, $params);
		break;
		default:
			return -1;
		break;
	}
}

function escapeMYSQL($dbHandle, $data){
	if ($data==null) return null;
	return "'" . mysqli_real_escape_string($dbHandle, $data) . "'";
}
function escapePGSQL($dbHandle, $data){
	if ($data==null) return null;
	return "'" . pg_escape_string($dbHandle, $data) . "'";
}
function escapeMSSQL($dbHandle, $data){
	if ($data==null) return null;
	if(is_numeric($data))
        return $data;
    $unpacked = unpack('H*hex', $data);
    return '0x' . $unpacked['hex'];
}

function escapeIdentifierMYSQL($dbHandle, $data){
	if ($data==null) return null;
	return mysqli_real_escape_string($dbHandle, $data);
}
function escapeIdentifierPGSQL($dbHandle, $data){
	if ($data==null) return null;
	return pg_escape_string($dbHandle, $data);
}
function escapeIdentifierMSSQL($dbHandle, $data){
	if ($data==null) return null;
	return safeFilename($data);
}

function escapeSQL($SQLType, $dbHandle, $data){
	switch($SQLType){
		case 'mysql':
			return escapeMYSQL($dbHandle, $data);
		break;
		case 'mssql':
			return escapeMSSQL($dbHandle, $data);
		break;
		case 'pgsql':
		case 'postgres':
			return escapePGSQL($dbHandle, $data);
		break;
		default:
			return -1;
	}
}
function escapeIdentifierSQL($SQLType, $dbHandle, $data){
	switch($SQLType){
		case 'mysql':
			return escapeIdentifierMYSQL($dbHandle, $data);
		break;
		case 'mssql':
			return escapeIdentifierMSSQL($dbHandle, $data);
		break;
		case 'pgsql':
		case 'postgres':
			return escapeIdentifierPGSQL($dbHandle, $data);
		break;
		default:
			return -1;
	}
}

function openConf(){
	global $dbtype,$server,$database,$user,$pass;
	if (isset($dbtype)&&isset($server)&&isset($database)&&isset($user)&&isset($pass)){
		return openSQL($dbtype,$server,$user,$pass,$database);
	}
	return null;
}
function executeConf($db, $query){
	global $dbtype;
	return executeSQL($dbtype, $db, $query);
}
function closeConf($db){
	global $dbtype;
	return closeSQL($dbtype,$db);
}
function escapeConf($db, $data){
	global $dbtype;
	return escapeSQL($dbtype, $db, $data);
}
function escapeIdentifierConf($db, $data){
	global $dbtype;
	return escapeIdentifierSQL($dbtype, $db, $data);
}

function listTablesMYSQL($db, $database){
	return executeMYSQL($db, "SHOW TABLES IN " . $database);
}
function listTablesPGSQL($db, $database){
	return executePGSQL($db, "select * from information_schema.tables where table_schema = 'public' AND table_catalog = '$database';");
}
function listTablesMSSQL($db ,$database){
	
}

function describeTableMYSQL($db, $table){
	return executeMYSQL($db, "DESCRIBE " . $table);
}
function describeTablePGSQL($db, $table){
	return executePGSQL($db, "select column_name, data_type, character_maximum_length from INFORMATION_SCHEMA.COLUMNS where table_name = '$table';");
}
function describeTableMSSQL($db, $table){

}

function safeFilename($pageName){
	$ret = preg_replace('/[^a-zA-Z_\- \.]/',"",$pageName);
	if ($ret=='.' || $ret == '..') return '';
	return $ret;
}
function sendpost($url, $content){
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_HTTPHEADER,
			array("Content-type: application/json"));
	curl_setopt($curl, CURLOPT_POST, true);
	curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

	$json_response = curl_exec($curl);

	$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

	if ( $status != 200 ) {
		global $lastweberror;
		$lastweberror = "Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl);
		curl_close($curl);
		return null;
	}

	curl_close($curl);

	$response = json_decode($json_response, true);
	return $response;
}
?>