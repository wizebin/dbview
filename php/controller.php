<?php 
	$home = $_SERVER['DOCUMENT_ROOT'];
	$securityRequired=10;
	$required=array('verb');
	include "secbase.php";
?><?php
	global $lastid, $lastaffected;
	
	if ($success){
		//$seclevel = current security level
		$requestMethod = $params['verb'];	//FAKING REQUEST METHOD, use $_SERVER['REQUEST_METHOD'];
		
		$requestScheme = $_SERVER['REQUEST_SCHEME'];//http or https
		$remoteAddress = $_SERVER['REMOTE_ADDR'];//requester IP
		
		if ($requestScheme == 'https'){
			$ret['HTTPS']=true;
		}
		else{
			$ret['HTTPS']=false;
		}
		
		
		$db = openConf();
		
		$qrey = "";
		
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
				
				break;
			case 'list':
				//table,sortby,filters,pagesize,page
				
				$table = escapeIdentifierConf($db, $params['table']);
				$page = isset($params['page'])?escapeConf($db, $params['page']):'';
				$pagesize = isset($params['pagesize'])?escapeConf($db, $params['pagesize']):'';
				
				$filters = isset($params['filters'])?json_decode($params['filters'],true):array(); // ['id'=>123]
				$sortby = isset($params['sortby'])?json_decode($params['sortby'],true):array(); //['id'=>'DESC']
				
				$filtered = "";
				$sortedby = "";
				
				if (count($filters)>0){
					$filtered = " WHERE ";
					$filterlist = array();
					foreach($filters as $key => $val){
						array_push($filterlist,escapeIdentifierConf($db,$key) . " LIKE " . escapeConf($db,$val));
					}
					$filtered .= implode( " AND ", $filterlist);
				}
				
				if (count($sortby)>0){
					$sortedby = " ORDER BY ";
					$sortedlist = array();
					foreach($sortby as $key => $val){
						array_push($sortedlist,escapeIdentifierConf($db,$key) . " " . escapeConf($db,$val));
					}
					$sortedby .= implode(", ",$sortedlist);
				}
				
				$qrey = "SELECT * FROM $table $filtered $sortedby";///TODO: paginate
				$results = executeConf($db, $qrey);
				
				//if ($results){
					$ret['RESULT']=$results;
				//}
			
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
				}
				else{
					$ret['FAILURE']='INSERT FAILED ' . json_encode($results);
				}
				
				break;
			case 'delete':
				//table,idlabel,id
				
				$table = escapeIdentifierConf($db, $params['table']);
				$idlabel = escapeIdentifierConf($db, $params['idlabel']);
				$id = escapeConf($db, $params['id']);
				
				$qrey = "DELETE * FROM $table WHERE $idlabel = $id LIMIT 1";
				$results = executeConf($db, $qrey);
				
				//if ($results){
					$ret['RESULT']=$results;
					$ret['AFFECTED']=$lastaffected;
				//}
				
				break;
			case 'describe':
				$table = escapeIdentifierConf($db, $params['table']);
				$results = describeTableConf($db, $table);
				$ret['RESULT']=$results;
				$ret['AFFECTED']=$lastaffected;
				break;
			case 'tables':
				$database = escapeIdentifierConf($db, $params['database']);
				$results = listTablesConf($db, $database);
				ret['RESULT']=$results;
				break;
			case 'arbitrary':
				//query
				if ($username=='admin'){
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
		
		if (count($dberrors)>0){
			$ret["ERRORS"] = $dberrors;
		}
		
		closeConf($db);
		
		//$ret['SERVER']=$_SERVER;
		$ret['SUCCESS']=true;
	}
	
	echo json_encode($ret, JSON_PRETTY_PRINT);
?>