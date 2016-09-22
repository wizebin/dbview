<!DOCTYPE html>

<html>
	<head>
		<script type="text/javascript" src="basics.js"></script>
		<script type="text/javascript" src="advanced.js"></script>
		<?php include('php/fullconf.php'); ?>
		<meta charset="utf-8" />
		<link rel="stylesheet" type="text/css" href="style/basic.css" />
		<title>DBView</title>
	</head>

	<body>
		<div id="wrapper"><div style="float:right;"><button onclick="logout()">logout</button></div><a href="/" style=""><img src='resources/logo64.png' style="padding:5px;background-color:#544;"></img></a></div><div style="overflow:hidden;"></div>
		<div id="content"></div>
	
		<script>
			var username = valOrOther(getCookie('username'),'');
			var password = valOrOther(getCookie('password'),'');
			var settings = null;
			var loggedin = false;
			
			var Settings = function(settingsFile){
				this.loadSettings(settingsFile);
				this.settings = {};
			}
			Settings.prototype.loadSettings = function(settingsFile){
				this.settingsFile = settingsFile;
				postJSON('php/loadsettings.php','page='+settingsFile+'&username='+username+'&password='+password,function(data){
					if (data['SUCCESS']){
						easyNotify('Loaded ' + JSON.stringify(data));
						$result = data['RESULT'];
						if ($result.length > 0){
							try{
								var tmp = JSON.parse($result);
							}
							catch(err){
								easyNotify('Could not parse json ' + err);
							}
						}
						else{
							//no settings
						}
					}
				},function(data){
					console.log(data);
				});
			}
			Settings.prototype.saveSettings = function(settingsFile){
				if (settingsFile==undefined)
					settingsFile = this.settingsFile;
				postJSON('php/savesettings.php','page='+settingsFile+'&username='+username+'&password='+password+'&data='+encodeURIComponent(JSON.stringify(this.settings)),function(data){
					console.log(data);
				},function(data){
					console.log(data);
				});
			}
			Settings.prototype.get = function(key){
				return this.settings[key];
			}
			Settings.prototype.set = function(key, val){
				this.settings[key]=val;
			}
			Settings.prototype.list = function(){
				return this.settings;
			}
			
			function login(user, pass, successCallback, failureCallback){
				username = user, password = pass;
				postJSON('php/authenticate.php','username='+username+'&password='+password,function(data){
					if (data['SUCCESS']){
						loggedin = true;
						settings = new Settings('companySettings');
						successCallback&&successCallback(data['RESULT']);
					}
					else{
						loggedin = false;
						failureCallback&&failureCallback(data);
					}
				},function(data){
					console.log('FAILED TO LOGIN ' + data);
					loggedin = false;
					failureCallback&&failureCallback(data);
				});
			}
			function displayPage(content, parent){
				if (parent==undefined)parent='content';
				setElementContentWithScripts(parent,content);
			}
			
			function loadAndDisplayPage(pageName, parent){
				postJSON('php/loadpage.php','username='+username+'&password='+password+'&page='+pageName,function(data){
					if (data['SUCCESS']){
						displayPage(data['RESULT'],parent);
					}
					else{
						console.log('LOADPAGE FAILURE');
						easyNotify('Failed To Load ' + pageName + ' RETURNED ' + JSON.stringify(data));
					}
				},function(data){
					console.log('FAILED TO LOGIN ' + data);
					loggedin = false;
					failureCallback&&failureCallback(data);
				});
			}
			
			function loadChange(loadCount, resource, verb, starting){				
				var word = 'Loaded';
				if (starting)
					word = 'Loading';
				
				console.log(word + " " + verb + " : " + resource  + "(" + loadCount + ")");
				
				if (loadCount>0){
					//show loading animation
				}
				else{
					//hide loading animation
				}
			}
			setLoadingFunction(loadChange);
			
			
			function setCredentials(user,pass){
				username= user;
				password= pass;
				setCookie('username',username);
				setCookie('password',password);
			}
			function executeLogin(){
				setCredentials(document.getElementById('username').value, document.getElementById('password').value);
				smartLogin();
			}
			function displayLogin(){
				displayPage('<div id="loginbox"><div><input type="username" id="username" placeholder="username"></input><br /><input type="password" id="password" placeholder="password"></input></div><button onclick="executeLogin()">Login</button></div>');
			}
			
			function smartLogin(automated){
				login(username,password,function(data){
					loadAndDisplayPage('home');
				},function(data){
					if (!automated){
						easyNotify('FAILED TO LOGIN '+JSON.stringify(data));
					}
					displayLogin();
				});
			}
			function logout(){
				setCredentials(null,null);
				displayLogin();
			}
			
			smartLogin(true);
			
		</script>
	</body>
</html>