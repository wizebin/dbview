<?php
	include("../php_no_public/basics.php");
	
	function getMS(){
		return round(microtime(true) * 1000);
	}
	
	function simpleTest($phpcode, $assertion){
		$startTime = getMS()
		try{
			eval("\$result = ". $phpcode);
		}
		catch($err){
			echo("TEST $phpcode == $assertion FAILED : " . $err);
		}
		$res = assert($result==$assertion);
		$endTime = getMS()
		
		$duration = $endTime - $startTime;
		
		echo("Testing $phpcode == $assertion RESULT : " . ($res?'success':'failure'). " TIME : $duration ms");
	}
	
	simpleTest("makepgstring(\"127.0.0.1\",\"username\",\"password\",\"database\",777)", "host=127.0.0.1 user=username password=password dbname=database port=777 connect_timeout=5");
	
	simpleTest("getTypeLetter(\"Hello\")","s");
	simpleTest("getTypeLetter(1)","i");
	simpleTest("getTypeLetter(null)","s");
	
	simpleTest("makeTypeArray(array('hello',1,null)))",array('s','i','s'));
	
	
	
	
?>