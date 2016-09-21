<!DOCTYPE html>

<html>
	<head>
		<script type="text/javascript" src="basics.js"></script>
		<script type="text/javascript" src="advanced.js"></script>
		<?php include('php/fullconf.php'); ?>
	</head>

	<body>
		<script>
			var Settings = function(settingsFile){
				this.loadSettings(settingsFile);
			}
			Settings.prototype.loadSettings = function(settingsFile){
				this.settingsFile = settingsFile;
				postJSON('php/loadpage.php','page='+settingsFile+'&root=settings',function(data){},function(data){});
			}
		</script>
	</body>
</html>