<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=10;
	$required=array('verb');
	include "secbase.php";
?><?php
	$ret = array();
	global $lastid, $lastaffected;
	if (isset($params['table'])&&isset($GLOBALS['mainTable'])){
		//in the future check black list for table if we are listing tables
		//for now just check main table against this table
		if ($params['table']!=$GLOBALS['mainTable']){
			$success=false;
			$ret['FAILURE']='You may only access ' . $GLOBALS['mainTable'];
		}
	}
	if ($success){
	
		//$seclevel = current security level
		$requestMethod = $params['verb'];
		
		$requestScheme = $_SERVER['REQUEST_SCHEME'];//http or https
		$remoteAddress = $_SERVER['REMOTE_ADDR'];//requester IP
		
		if ($requestScheme == 'https'){
			$ret['HTTPS']=true;
		}
		else{
			$ret['HTTPS']=false;
		}
		
		$db = openConf();
		if ($db==null){
			$ret['SUCCESS']=false;
		}
		else{
			switch($requestMethod){
				case 'get':
					//table,idlabel,id
					
					$table = escapeIdentifierConf($db, $params['table']);
					$idlabel = escapeIdentifierConf($db, $params['idlabel']);
					$id = escapeConf($db, $params['id']);
					
					$qrey = "SELECT * FROM $table WHERE $idlabel = $id";
					$results = executeConf($db, $qrey);
					
					//if (is_array($results)){
						$ret['RESULT']=$results;
					//}
					$ret['SUCCESS']=true;
					
					break;
				case 'list':
					//table,sortby,filters,pagesize,page
					
					$filters = isset($params['filters'])?json_decode($params['filters'],true):array(); // ['id'=>123]
					$sortby = isset($params['sortby'])?json_decode($params['sortby'],true):array(); //['id'=>'DESC']
					$page = isset($params['page'])?$params['page']:0;
					$pagesize = isset($params['pagesize'])?$params['pagesize']:0;
					$table = escapeIdentifierConf($db, $params['table']);
					
					$results = listWithParamsConf($db, $table, $page, $pagesize, $filters, $sortby);
					
					//if ($results){
						$ret['RESULT']=$results;
						//$ret['LASTQREY']=$GLOBALS['LASTQREY'];
					//}
					$ret['SUCCESS']=true;
				
					break;
				case 'update':
					//table,idlabel,id,data
					
					$table = escapeIdentifierConf($db, $params['table']);
					$idlabel = escapeIdentifierConf($db, $params['idlabel']);
					$id = escapeConf($db, $params['id']);
					
					$data = json_decode($params['data'],true);
					
					$sets = "";
					
					if (count($data)>0){
						$sets = " SET ";
						$setlist = array();
						foreach($sortby as $key => $val){
							array_push($setlist,escapeIdentifierConf($db,$key) . " = " . escapeConf($db,$val));
						}
						$sets .= implode(", ",$sortedlist);
					}
					
					$qrey = "UPDATE $table $sets WHERE $idlabel = $id";
					$results = executeConf($db, $qrey);
					
					//if ($results){
						$ret['RESULT']=$results;
						$ret['AFFECTED']=$lastaffected;
					//}
					$ret['SUCCESS']=true;
					
					break;
				case 'create':
					//table,data
					
					$table = escapeIdentifierConf($db, $params['table']);
					
					$data = json_decode($params['data'],true);
					
					$keys = "";
					$vals = "";
					
					if (is_array($data) && count($data)>0){
						$keylist = array();
						$vallist = array();
						
						foreach($data as $key => $val){
							array_push($keylist,escapeIdentifierConf($db,$key));
							array_push($vallist,escapeConf($db,$val));
						}
						
						$keys = implode(",",$keylist);
						$vals = implode(", ",$vallist);
					}
					
					$qrey = "INSERT INTO $table ($keys) VALUES($vals);";
					$results = executeConf($db, $qrey);
					
					if ($results){
						$ret['RESULT']=$results;
						$ret['AFFECTED']=$lastaffected;
						$ret['ID']=$lastid;
						$ret['SUCCESS']=true;
					}
					else{
						$ret['FAILURE']='INSERT FAILED ' . json_encode($results);
						$ret['SUCCESS']=false;
					}
					
					break;
				case 'delete':
					//table,idlabel,id
					if ($seclevel>=100){
						$table = escapeIdentifierConf($db, $params['table']);
						$idlabel = escapeIdentifierConf($db, $params['idlabel']);
						$id = escapeConf($db, $params['id']);
						
						$qrey = "DELETE * FROM $table WHERE $idlabel = $id LIMIT 1";
						$results = executeConf($db, $qrey);
						
						//if ($results){
							$ret['RESULT']=$results;
							$ret['AFFECTED']=$lastaffected;
						//}
						$ret['SUCCESS']=true;
					}
					else{
						$ret['SUCCESS']=false;
						$ret['FAILURE']='Security Level Too Low';
					}
					
					break;
				case 'describe':
					$table = escapeIdentifierConf($db, $params['table']);
					$results = describeTableConf($db, $table);
					$ret['RESULT']=$results;
					$ret['AFFECTED']=$lastaffected;
					$ret['SUCCESS']=true;
					break;
				case 'tables':
					$database = isset($params['database'])?escapeIdentifierConf($db, $params['database']):null;
					$results = listTablesConf($db, $database);
					$ret['RESULT']=$results;
					$ret['SUCCESS']=true;
					break;
				case 'indexes':
					$table = escapeIdentifierConf($db, $params['table']);
					$results = listIndexedConf($db, $table);
					$ret['RESULT']=$results;
					$ret['SUCCESS']=true;
					break;
				case 'arbitrary':
					//query
					if ($username==$masterUsername){
						$qrey = $params['query'];
						$results = executeConf($db, $qrey);
						
						//if (is_array($results)){
							$ret['RESULT']=$results;
							$ret['AFFECTED']=$lastaffected;
						//}
						break;
					}
				default:
					$ret['SUCCESS']=false;
					$ret['FAILURE']='UNKNOWN VERB ' . $requestMethod;
					break;
			}
			closeConf($db);
		}
		if (count($dberrors)>0){
			$ret["ERRORS"] = $dberrors;
		}
		
	}
	
	echo json_encode($ret, JSON_PRETTY_PRINT);
?>