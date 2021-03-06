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
		<div id="wrapper"><div style="float:right;padding:3px;whitespace:nowrap;"><span id='extraHeaderButtons'></span> <button onclick="logout()" style="border:none;color:#cc1818;">logout</button></div><a href='/'><img src='resources/logo64.png' style="padding:5px;"></a><div style="position:absolute;right:3px;bottom:3px;white-space:nowrap" id="extraHeader"></div></div>
		
		<div id="content"></div>
	
		<script>
			var username = valOrOther(getCookie('username'),'');
			var password = valOrOther(getCookie('password'),'');
			var securityLevel = 0;
			var settings = null;
			var loggedin = false;
			
			var contentDiv = document.getElementById('content');
			var menuDiv = null;
			
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
						if (data.hasOwnProperty('SECURITY_LEVEL'))
							securityLevel=data['SECURITY_LEVEL'];
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
				if (securityLevel>=100){//admin
					document.getElementById('extraHeaderButtons').innerHTML="<button onclick='loadAndDisplayPage(\"config\")'>Config</button>";
				}
				else{
					document.getElementById('extraHeaderButtons').innerHTML="";
				}
			}
			
			function loadAndDisplayPage(pageName, parent){
				document.getElementById('extraHeader').innerHTML='';
				postJSON('php/loadpage.php','username='+username+'&password='+password+'&page='+pageName,function(data){
					if (data['SUCCESS']){
						displayPage(data['RESULT'],parent);
					}
					else{
						console.log('LOADPAGE FAILURE');
						easyNotify('Failed To Load ' + pageName + ' RETURNED ' + JSON.stringify(data));
					}
				},function(data){
					console.log('FAILED TO LOAD PAGE ' + JSON.stringify(data));
					failureCallback&&failureCallback(data);
				});
			}
			
			var loadingAnimation = null;
			function loadChange(loadCount, resource, verb, starting){				
				var word = 'Loaded';
				if (starting)
					word = 'Loading';
				
				console.log(word + " " + verb + " : " + resource  + "(" + loadCount + ")");
				
				if (loadCount>0){
					//show loading animation
					if (loadingAnimation==null){
						loadingAnimation = document.createElement('div');
						loadingAnimation.style.left='50%';
						loadingAnimation.style.marginLeft='-300px';
						loadingAnimation.style.width='600px';
						loadingAnimation.style.backgroundColor='#fff';
						//loadingAnimation.style.opacity='.7';
						loadingAnimation.style.top='10%';
						loadingAnimation.style.padding='40px';
						loadingAnimation.style.position='fixed';
						loadingAnimation.style.boxShadow='0px 0px 5px #000';
						loadingAnimation.style.zIndex='200';
						document.body.appendChild(loadingAnimation);
					}
					loadingAnimation.innerHTML += '<div>'+verb+' '+resource+' '+(starting?'beginning':'finished')+'</div>';
				}
				else{
					loadingAnimation.parentNode.removeChild(loadingAnimation);
					loadingAnimation=null;
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
			function displayLoginOrSetup(){
				if (!systemIsConfigured){
					loadAndDisplayPage('config');
				}
			
				displayPage('<div id="loginbox"><div><input type="username" id="username" placeholder="username"></input><br /><input type="password" id="password" placeholder="password"></input></div><button onclick="executeLogin()">Login</button></div>');
				document.getElementById('password').onkeydown = function(e) {
					e = e || window.event;
					if (e.keyCode == 13) {
						executeLogin();
					}
				};
			}
			
			function smartLogin(automated){
				login(username,password,function(data){
					loadAndDisplayPage('home');
				},function(data){
					if (!automated){
						easyNotify('FAILED TO LOGIN '+JSON.stringify(data));
					}
					displayLoginOrSetup();
				});
			}
			function logout(){
				securityLevel=0;
				loggedin=false;
				setCredentials(null,null);
				displayLoginOrSetup();
			}
			
			//verbs include VERB(required param list)
	
			//get(table,idlabel,id)
			//list(table,filters,sortby,page,pagesize)
			//update(table,idlabel,id)
			//create(table,data:json)
			//delete(table,idlabel,id)
			//describe(table)
			//tables(database)
			//indexes(table)
			
			function requestFromController(verb, properties, successCallback, failureCallback){
				var props = [];
				var keys = getObjectKeys(properties);
				keys.forEach(function(key){
					props.push("&"+encodeURIComponent(key)+"="+encodeURIComponent(properties[key]));
				},this);
				
				var propstring = props.join("");
				
				postJSON('php/controller.php','verb='+verb+'&username='+username+'&password='+password+propstring,function(data){
					if (data['SUCCESS']){
						successCallback&&successCallback(data['RESULT']);
					}
					else{
						failureCallback&&failureCallback(data);
					}
				},function(data){failureCallback&&failureCallback(data);});
			}
			
			smartLogin(true);
			
			
			
		</script>
	</body>
</html>