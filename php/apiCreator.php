<?php
	//To create a basic php api, we unfortunately have to avoid a pure RESTful ideology
	//the reason being the lack of access to sub urls (so endpoint/id is not really possible, we need to use POST: endpoint?id=id instead, not REST)
	
	//An obvious concern with API creation is security- this page needs to be secure enough to prevent unauthorized arbitrary creation of apis
	//but accessible enough that it isn't a problem to create new apis when necessary
?>