<?php
	include("conf.php");
?>

<script type="text/javascript">
	<?php 
		
		$setup = false;
		if (isset($GLOBALS['systemConfigured'])){
			$setup = $GLOBALS['systemConfigured'];
		}
		else{
			$setup = file_exists('php/settings/settings.json');
		}
		echo("var fileExists='".file_exists($SETTINGS_FILE)."';\n");
		
		echo("var systemIsConfigured=".($setup?'true':'false').";\n");
		echo("var systemConfiguredVarIsSet=".(isset($GLOBALS['systemConfigured'])?'true':'false').";\n");
		$settable = array('rootOfPage','indexedOnly');
		foreach($settable as $setting){
			if (isset($GLOBALS[$setting])){
				if (is_string($GLOBALS[$setting])){
					echo("var $setting = '" . $GLOBALS[$setting] . "';\n");
				}
				else if ($GLOBALS[$setting]==null){
					echo("var $setting = null;");
				}
				else{
					echo("var $setting = " . $GLOBALS[$setting] . ";\n");
				}
			}
		}
	?>
</script>