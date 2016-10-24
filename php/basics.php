<?php include_once ("conf.php");
$GLOBALS['dberrors'] = array();$lasterror="";$lastid=null;$lastaffected=null;
function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    array_push($GLOBALS['dberrors'],"$errstr ($errno) IN $errfile ON LINE $errline");
}
set_error_handler("exception_error_handler");
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
    $dbh = @pg_connect($connectionstring);
	//if ($dbh === false){
		//noteError("Failed to connect to PGSQL: ". pg_last_error());
	//}
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
	$result = $db->query($query);
	
	if($result==null){
		noteError($query . " iquery error ".mysqli_errno($db)." : ".mysqli_error($db));
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

function escapeNoQuoteMYSQL($dbHandle, $data){
	if ($data==null) return null;
	return mysqli_real_escape_string($dbHandle, $data);
}
function escapeNoQuotePGSQL($dbHandle, $data){
	if ($data==null) return null;
	return pg_escape_string($dbHandle, $data);
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

function listTablesMYSQL($db, $database){
	$catalog='';
	if ($database!=null)
		$catalog= " IN $database";
	return executeMYSQL($db, "SHOW TABLES$catalog;");
}
function listTablesPGSQL($db, $database){
	$catalog='';
	if ($database!=null)
		$catalog=" AND table_catalog = '$database'";
	return executePGSQL($db, "select * from information_schema.tables where table_schema = 'public'$catalog;");
}
function listTablesMSSQL($db ,$database){
	
}

function describeTableMYSQL($db, $table){
	return executeMYSQL($db, "SELECT `COLUMN_NAME` as 'column_name' FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`=DATABASE() AND `TABLE_NAME`='$table';");
}
function describeTablePGSQL($db, $table){
	return executePGSQL($db, "select * from INFORMATION_SCHEMA.COLUMNS where table_name = '$table';");
}
function describeTableMSSQL($db, $table){

}

function listIndexedMYSQL($db, $table){
	return executeMYSQL($db, "SELECT table_schema as 'table_schema', index_name as 'index_name', column_name as 'column_name' FROM information_schema.statistics WHERE table_name = '$table' AND table_schema = DATABASE()");
}
function listIndexedPGSQL($db, $table){
	return executePGSQL($db, "select t.relname as table_name, i.relname as index_name, a.attname as column_name from pg_class t, pg_class i, pg_index ix, pg_attribute a where t.oid = ix.indrelid and i.oid = ix.indexrelid and a.attrelid = t.oid and a.attnum = ANY(ix.indkey) and t.relkind = 'r' and t.relname like '$table' order by t.relname, i.relname;");
}
function listIndexedMSSQL($db, $table){
	
}


$verbMap = array('eq'=>'=','not_eq'=>'!=','lt'=>'<','gt'=>'>','gteq'=>'>=','lteq'=>'<=');
$valModifyingVerbMap = array('cont'=>'LIKE','start'=>'LIKE','end'=>'LIKE','i_cont'=>'ILIKE');//ilike only works with postgresql
$specialVerbMap = array('present','blank','null','not_null');
function getFilterFromDataMYPGSQL($db, $unescapedcol, $word, $unescapedval, $ismysql){
	global $verbMap,$valModifyingVerbMap,$specialVerbMap;
	$col = $ismysql?escapeIdentifierMYSQL($db,$unescapedcol):escapeIdentifierPGSQL($db,$unescapedcol);
	$verb = '';
	if (array_key_exists($word,$verbMap)){
		$verb = $verbMap[$word];
		$val = $ismysql?escapeMYSQL($db,$unescapedval):escapePGSQL($db,$unescapedval);
		return $col . ' ' . $verb . ' ' . $val;
	}
	else if (array_key_exists($word,$valModifyingVerbMap)){
		$modifiedval = $unescapedval;
		$verb = $valModifyingVerbMap[$word];
		if ($word=='cont'){
			$modifiedval = '%'.$unescapedval.'%';
		}
		else if ($word=='icont'){
			$modifiedval = '%'.$unescapedval.'%';
			if ($ismysql)
				$verb = 'LIKE';
		}
		else if ($word=='start'){
			$modifiedval = $unescapedval.'%';
		}
		else if ($word=='end'){
			$modifiedval = '%'.$unescapedval;
		}
		
		$val = $ismysql?escapeMYSQL($db,$modifiedval):escapePGSQL($db,$modifiedval);
		return $col . ' ' . $verb . ' ' . $val;
	}
	else if (in_array($word,$specialVerbMap)){
		if ($word=='present'){
			return "($col" . " IS NOT NULL)";
		}
		else if ($word=='blank'){
			return "($col" . '==' . "'' OR $col IS NULL)";
		}
		else if ($word=='null'){
			return $col . ' IS NULL';
		}
		else if ($word=='not_null'){
			return $col . ' IS NOT NULL';
		}
	}
	
	return null;
}

function listWithParamsMYSQL($db, $table, $page = 0, $pagesize = 100, $filterlist = array(), $sortlist = array()){
	$filterStatement = "";
	$sortStatement = "";
	$limitStatement = "";
	
	$filterFinalList = array();
	$sortFinalList = array();
	
	if (is_array($filterlist) && count($filterlist) > 0){
		if (array_key_exists('sub',$filterlist)&&array_key_exists('verb',$filterlist)){
			$filterlist = array($filterlist);
		}
		foreach($filterlist as $filter){
			$addfilter = getFilterFromDataMYPGSQL($db, $filter['sub'], $filter['verb'], isset($filter['obj'])?$filter['obj']:'',true);
			if ($addfilter != null){
				array_push($filterFinalList,$addfilter);
			}
		}
	}
	if (count($filterFinalList)>0){
		$filterStatement = 'WHERE ' . implode(' AND ',$filterFinalList);
	}
	
	if (is_array($sortlist) && count($sortlist) > 0){
		if (array_key_exists('col',$sortlist)){
			$sortlist = array($sortlist);
		}
		foreach($sortlist as $sort){
			$col = escapeIdentifierMYSQL($db,$sort['col']);
			if (isset($sort['direction'])){
				$dir = $sort['direction'];
				if (strtolower($dir)=='desc'){
					array_push($sortFinalList,$col . ' DESC');
				}
				else{
					array_push($sortFinalList,$col . ' ASC');
				}
			}
			else{
				array_push($sortFinalList,$col);
			}
		}
	}
	
	if (count($sortFinalList)>0){//MAY NEED TO DO INNER QUERY IF LIMIT IS ALSO INCLUDED
		$sortStatement = "ORDER BY " . implode(', ',$sortFinalList);
	}
	
	if ($page!=null && $pagesize !=null && is_numeric($page) && is_numeric($pagesize)){ 
		$limitStatement = "LIMIT ".round($pagesize)." OFFSET ". round($page*$pagesize);
	}
	
	$qrey = "SELECT * FROM $table $filterStatement $sortStatement $limitStatement;";
	return executeMYSQL($db, $qrey);
}
function listWithParamsPGSQL($db, $table, $page = 0, $pagesize = 100, $filterlist = array(), $sortlist = array()){
	$filterStatement = "";
	$sortStatement = "";
	$limitStatement = "";
	
	$filterFinalList = array();
	$sortFinalList = array();
	
	if (is_array($filterlist) && count($filterlist) > 0){
		if (array_key_exists('sub',$filterlist)&&array_key_exists('verb',$filterlist)){
			$filterlist = array($filterlist);
		}
		foreach($filterlist as $filter){
			$addfilter = getFilterFromDataMYPGSQL($db, $filter['sub'], $filter['verb'], isset($filter['obj'])?$filter['obj']:'',false);
			if ($addfilter != null){
				array_push($filterFinalList,$addfilter);
			}
		}
	}
	if (count($filterFinalList)>0){
		$filterStatement = 'WHERE ' . implode(' AND ',$filterFinalList);
	}
	
	if (is_array($sortlist) && count($sortlist) > 0){
		if (array_key_exists('col',$sortlist)){
			$sortlist = array($sortlist);
		}
		foreach($sortlist as $sort){
			$col = escapeIdentifierPGSQL($db,$sort['col']);
			if (isset($sort['direction'])){
				$dir = $sort['direction'];
				if (strtolower($dir)=='desc'){
					array_push($sortFinalList,$col . ' DESC');
				}
				else{
					array_push($sortFinalList,$col . ' ASC');
				}
			}
			else{
				array_push($sortFinalList,$col);
			}
		}
	}
	
	if (count($sortFinalList)>0){//MAY NEED TO DO INNER QUERY IF LIMIT IS ALSO INCLUDED
		$sortStatement = "ORDER BY " . implode(', ',$sortFinalList);
	}
	
	if ($page!=null && $pagesize !=null && is_numeric($page) && is_numeric($pagesize)){ 
		$limitStatement = "LIMIT ".round($pagesize)." OFFSET ". round($page*$pagesize);
	}
	
	$qrey = "SELECT * FROM $table $filterStatement $sortStatement $limitStatement;";
	return executePGSQL($db, $qrey);
}
function listWithParamsMSSQL($db, $table, $page = 0, $pagesize = 100, $filterlist = array(), $sortlist = array()){
	
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

function listTablesSQL($SQLType, $dbHandle, $database){
	switch($SQLType){
		case 'mysql':
			return listTablesMYSQL($dbHandle, $database);
		break;
		case 'mssql':
			return listTablesMSSQL($dbHandle, $database);
		break;
		case 'pgsql':
		case 'postgres':
			return listTablesPGSQL($dbHandle, $database);
		break;
		default:
			return -1;
	}
}
function describeTableSQL($SQLType, $dbHandle, $table){
	switch($SQLType){
		case 'mysql':
			return describeTableMYSQL($dbHandle, $table);
		break;
		case 'mssql':
			return describeTableMSSQL($dbHandle, $table);
		break;
		case 'pgsql':
		case 'postgres':
			return describeTablePGSQL($dbHandle, $table);
		break;
		default:
			return -1;
	}
}
function listIndexedSQL($SQLType, $dbHandle, $table){
	switch($SQLType){
		case 'mysql':
			return listIndexedMYSQL($dbHandle, $table);
		break;
		case 'mssql':
			return listIndexedMSSQL($dbHandle, $table);
		break;
		case 'pgsql':
		case 'postgres':
			return listIndexedPGSQL($dbHandle, $table);
		break;
		default:
			return -1;
	}
}
function listWithParamsSQL($SQLType, $db, $table, $page = 0, $pagesize = 100, $filterlist = array(), $sortlist = array()){
	switch($SQLType){
		case 'mysql':
			return listWithParamsMYSQL($db, $table, $page, $pagesize, $filterlist, $sortlist);
		break;
		case 'mssql':
			return listWithParamsMSSQL($db, $table, $page, $pagesize, $filterlist, $sortlist);
		break;
		case 'pgsql':
		case 'postgres':
			return listWithParamsPGSQL($db, $table, $page, $pagesize, $filterlist, $sortlist);
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
	else{
		if (!isset($dbtype))array_push($GLOBALS['dberrors'],'MISSING dbtype configuration');
		if (!isset($server)) array_push($GLOBALS['dberrors'],'MISSING server configuration');
		if (!isset($database)) array_push($GLOBALS['dberrors'],'MISSING database configuration');
		if (!isset($user)) array_push($GLOBALS['dberrors'],'MISSING database username configuration');
		if (!isset($pass)) array_push($GLOBALS['dberrors'],'MISSING database password configuration');
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
function listTablesConf($db, $database){
	global $dbtype;
	return listTablesSQL($dbtype, $db, $database);
}
function describeTableConf($db, $table){
	global $dbtype;
	return describeTableSQL($dbtype, $db, $table);
}
function listIndexedConf($db, $table){
	global $dbtype;
	return listIndexedSQL($dbtype, $db, $table);
}
function listWithParamsConf($db, $table, $page = 0, $pagesize = 100, $filterlist = array(), $sortlist = array()){
	global $dbtype;
	return listWithParamsSQL($dbtype, $db, $table, $page, $pagesize, $filterlist, $sortlist);
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