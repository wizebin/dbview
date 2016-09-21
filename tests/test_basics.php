<?php
	include("../php/basics.php");
	
	function getNS(){
		return round(microtime(true) * 100000);
	}
	function makeMessage($message, $depth=0){
		$ret = "";
		if (gettype($message)=='array'){
			$ret .= "<table>";
			foreach($message as $key => $val){
				$ret .= "<tr><th>".makeMessage($key,$depth+1)."</th><td>".makeMessage($val,$depth+1)."</td></tr>";
			}
			$ret .= "</table>";
			return $ret;
		}
		if (gettype($message)=='boolean'){
			return $message?'true':'false';
		}
		else{
			if ($depth==0){
				return("<p>$message</p>");
			}
			else{
				return($message);
			}
		}
		return $ret;
	}
	function notify($message){
		echo(makeMessage($message));
	}
	
	function simpleTest($phpcode, $assertion, $operator = "=="){
		$startTime = getNS();
		try{
			//notify("<hr /><p>INITIATING " . $phpcode . "</p>");
			eval("\$result = ". $phpcode);
		}
		catch(Exception $err){
			notify("TEST $phpcode == $assertion FAILED : " . $err);
		}
		eval("\$res = (\$result $operator \$assertion);");
		$endTime = getNS();
		
		$duration = $endTime - $startTime;
		
		notify("<hr /><h3>$phpcode <span style=\"background-color:#333;color:#fff;padding:0px 10px;display:inline-block;\">$operator</span> <span style=\"opacity:.5\">".json_encode($assertion)."</span></h3> <div style=\"font-weight:bold;\">" . ($res?'<span style="color:#0f0;">SUCCESS</span>':'<span style="color:#f00;">FAILURE</span>'). "</div><div>TIME : $duration ns</div>");
		if (!$res){
			notify("<div style=\"background-color:#aaa;padding:10px;\"><div style=\"font-weight:bold;\">RESULTS</div>". makeMessage($result) . "</div>");
			notify("<div style=\"background-color:#aaa;padding:10px;\"><div style=\"font-weight:bold;\">SHOULD BE</div>". makeMessage($assertion) . "</div>");
		}
	}
	
	
	
	simpleTest("makepgstring(\"127.0.0.1\",\"username\",\"password\",\"database\",777);", "host=127.0.0.1 user=username password=password dbname=database port=777 connect_timeout=5");
	
	simpleTest("getTypeLetter(\"Hello\");","s");
	simpleTest("getTypeLetter(1);","i");
	simpleTest("getTypeLetter(null);","s");
	
	simpleTest("makeTypeArray(array('hello',1,null));",array('s','i','s'));
	
	include('../php/security.php');
	
	simpleTest("checkAdminLevel(0);",true);
	simpleTest("checkAdminLevel(1);",false);
	simpleTest("checkAdminLevel(1000);",false);
	
	simpleTest("safeFilename(\".\");","");
	simpleTest("safeFilename(\"..\");","");
	simpleTest("safeFilename(\"../\");","");
	simpleTest("safeFilename(\"~/\");","");
	simpleTest("safeFilename(\"page.php\");","page.php");
	simpleTest("safeFilename(\"page name.backup\");","page name.backup");
	
	$username = 'stephen';
	$password = 'jackofhearts';
	
	include('../php/conf.php');
	
	simpleTest("checkAdminLevel(1);",true);
	
	
	
?>