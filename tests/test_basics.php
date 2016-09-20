<?php
	include("../php_no_public/basics.php");
	
	$startTime = time();
	$res = assert(makepgstring("127.0.0.1","username","password","database",777)=="host=127.0.0.1 user=username password=password dbname=database port=777 connect_timeout=5");
	$endTime = time();
	
	$duration = $endTime - $startTime;
	
	echo("Testing postgres connection string creation RESULT : " . ($res?'success':'failure'). " TIME : $duration seconds");
?>