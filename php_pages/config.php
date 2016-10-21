<style>
	span.titleSpan{width:300px;display:inline-block;padding:5px;}
	div.setting{background-color:#eee;margin-bottom:5px;}
	span.choiceNameSpan{margin-right:10px;padding:5px;display:inline-block;background-color:#e7e7e7;}
	div#controlsDiv{padding:20px;}
</style>

<div id='settingsDiv'></div>
<div id='controlsDiv'></div>

<script>
	SettingsSection = function(){
		this.data = [];
		this.result = {};
		this.associative = {};
	}
	SettingsSection.prototype.loadFromArray = function(ray){
		//this.data = ray;
		this.result = {};
		ray.forEach(function(el){this.addSetting(el);},this);
	}
	SettingsSection.prototype.createResultEntry = function(setting){
		if (setting.hasOwnProperty('name')){
			if (setting.hasOwnProperty('initial')){
				this.result[setting['name']]=setting['initial'];
			}
			else{
				this.result[setting['name']]=null;
			}
		}
	}
	SettingsSection.prototype.addSetting = function(setting){
		this.data.push(setting);
		this.createResultEntry(setting);
		if (setting.hasOwnProperty('name'))
			this.associative[setting['name']]=this.data[this.data.length-1];
	}
	SettingsSection.prototype.addSettingByType = function(name, alias, type, data){
		this.addSetting({'name':name,'alias':alias,'type':type,'data':data});
	}
	SettingsSection.prototype.display = function(element){
		var tthis = this;
		if (this.div!=null){
			this.div.innerHTML='';
		}
		else{
			this.div = document.createElement('div');
		}
		
		this.data.forEach(function(datum){
			if (datum['type']=='divider'){
				addQuickElement(this.div,'div','',{'style':'height:50px;'});
			}
			else if (datum['type']=='title'){
				addQuickElement(this.div,'h2',datum['alias']);
			}
			else{
				var idiv = document.createElement('div');
				idiv.className='setting';
				idiv.id=datum['name'];
				var span = document.createElement('span');
				span.className='titleSpan';
				span.innerHTML=datum['alias'];
				idiv.appendChild(span);
				var handled = false;
				if (datum['type']=='string'){
					var entry = document.createElement('input');
					if (datum.hasOwnProperty('initial')){
						entry.value=datum['initial'];
					}
					entry.oninput=function(){
						tthis.result[datum['name']]=entry.value;
						datum['callback']&&datum['callback'](datum['name'],entry.value);
					}
					idiv.appendChild(entry);
					handled = true;
				}
				else if (datum['type']=='singleChoice'){
					var ispan = document.createElement('span');
					datum['data'].forEach(function(choice){
						var choiceNameSpan = document.createElement('span');
						choiceNameSpan.innerHTML=choice;
						choiceNameSpan.className='choiceNameSpan';
						var ichoice = document.createElement('input');
						ichoice.type='radio';
						ichoice.name=datum['name'];
						ichoice.value=choice;
						if (datum.hasOwnProperty('initial')){
							if (datum['initial']==choice){
								ichoice.checked=true;
							}
						}
						
						ichoice.onclick=function(){
							tthis.result[datum['name']]=choice;
							datum['callback']&&datum['callback'](datum['name'],choice);
						}
						choiceNameSpan.appendChild(ichoice);
						ispan.appendChild(choiceNameSpan);
						
						
					},this);
					idiv.appendChild(ispan);
					handled = true;
				}
				else if (datum['type']=='bool'){
					var checker = document.createElement('input');
					checker.type='checkbox';
					if (datum.hasOwnProperty('initial')){
						checker.checked=datum['initial'];
					}
					checker.onclick=function(){
						tthis.result[datum['name']]=checker.checked;
						datum['callback']&&datum['callback'](datum['name'],checker.checked);
					}
					idiv.appendChild(checker);
					handled = true;
				}
				
				if (handled){
					tthis.div.appendChild(idiv);
				}
			}
			
		},this);
		
		element.appendChild(this.div);
	}
	
	var sections = [
		{'alias':'Database Information','type':'title'},
		{'name':'dbtype','alias':'Database Type','type':'singleChoice','data':['mysql','mssql','pgsql']},
		{'name':'server','alias':'Server','type':'string'},
		{'name':'database','alias':'Database','type':'string'},
		{'name':'mainTable','alias':'Table','type':'string'},
		{'name':'user','alias':'Username','type':'string'},
		{'name':'pass','alias':'Password','type':'string'},
		{'type':'divider'},
		{'alias':'Master Credentials','type':'title'},
		{'name':'masterUsername','alias':'Master Username','type':'string'},
		{'name':'masterPassword','alias':'Master Password','type':'string'},
		{'type':'divider'},
		{'alias':'Credential Database Information','type':'title'},
		{'name':'credentialServerType','alias':'Credentials Database Type','type':'singleChoice','data':['file','mysql','mssql','pgsql'],'callback':function(col,choice){
			var hide = ['credentialServer','credentialDatabase','credentialTable','credentialUsername','credentialPassword','credentialUserColumn','credentialPassColumn','credentialAdminColumn'];
			if (choice=='file'){
				hide.forEach(function(name){
					document.getElementById(name).style.display='none';
				},this);
			}
			else{
				hide.forEach(function(name){
					document.getElementById(name).style.display='block';
				},this);
			}
		}},
		{'name':'credentialServer','alias':'Credentials Server','type':'string'},
		{'name':'credentialDatabase','alias':'Credentials Database','type':'string'},
		{'name':'credentialTable','alias':'Credentials Table','type':'string'},
		{'name':'credentialUsername','alias':'Credentials Username','type':'string'},
		{'name':'credentialPassword','alias':'Credentials Password','type':'string'},
		{'name':'credentialUserColumn','alias':'Credentials Username Column','type':'string'},
		{'name':'credentialPassColumn','alias':'Credentials Password Column','type':'string'},
		{'name':'credentialAdminColumn','alias':'Credentials Security Level Column','type':'string'},
		{'type':'divider'},
		{'alias':'Extra Configuration','type':'title'},
		{'name':'indexedOnly','alias':'Sort/Search By Indexed Columns Only','type':'bool','initial':false},
		{'name':'rootOfPage','alias':'Web Facing Page Root','type':'string'}
	];
	
	function saveSettings(successCallback,failureCallback){
		postJSON('php/savesettings.php','username='+username+'&password='+password+'&page=settings&data='+encodeURIComponent(JSON.stringify(sec.result)),function(data){
			if (data['SUCCESS']){
				successCallback&&successCallback(data);
			}
			else{
				failureCallback&&failureCallback(data);
			}
		},function(data){
			failureCallback&&failureCallback(data);
		});
	}
	
	var sec = new SettingsSection();
	
	function loadPage(){
		var settingsDiv = document.getElementById('settingsDiv');

		sec.loadFromArray(sections);
		sec.display(settingsDiv);
		
		var acceptButton = addQuickElement(document.getElementById('controlsDiv'),'button','Accept Configuration',{'id':'acceptButton'});
		
		addQuickElement(controlsDiv,'span',' ');
		
		var testButton = addQuickElement(document.getElementById('controlsDiv'),'button','Test Configuration',{'id':'testButton'});
		
		addQuickElement(controlsDiv,'span',' ');
		
		acceptButton.onclick=function(){
			if(sec.result['masterUsername']==null||sec.result['masterPassword']==null){
				easyNotify('Must Set Master Credentials');
			}
			else if (sec.result['dbtype']==null||sec.result['server']==null||sec.result['database']==null||sec.result['user']==null||sec.result['server']==null||sec.result['pass']==null){
				easyNotify('Must Set Database Information');
			}
			else{
				saveSettings(function(){
					location.reload();
				},function(data){
					easyNotify('Failed to save settings ' + JSON.stringify(data));
				});
			}
			console.log(sec.result);
		};
		testButton.onclick=function(){
			saveSettings(function(){
				username=sec.result['masterUsername'];
				password=sec.result['masterPassword'];
				//setCredentials(username,password);
				login(username,password,function(){
					easyNotify('SUCCESSFULLY LOGGED IN AS MASTER USER');
					requestFromController('tables',null,function(data){
							easyNotify('SUCCESSFULLY LISTED ' +data.length+ ' TABLES');
					},function(data){
						easyNotify('FAILED TO LIST TABLES (SQL SETUP PROBLEM)' + JSON.stringify(data));
					});
				},function(data){
					easyNotify('FAILED TO LOG IN AS MASTER USER' + JSON.stringify(data));
				});//function defined in index.php
			},function(data){
				easyNotify('Failed to save settings ' + JSON.stringify(data));
			});
		};
	}
	
	function loadInitialConfigOrDisplay(){
		postJSON('php/loadsettings.php','username='+username+'&password='+password+'&page=settings',function(data){
			if (data['SUCCESS']){
				var initial = JSON.parse(data['RESULT']);
				console.log(initial);
				sections.forEach(function(section){
					if (section.hasOwnProperty('name')){
						if (initial.hasOwnProperty(section['name'])){
							section['initial']=initial[section['name']];
						}
					}
				});
				loadPage();
			}
			else{
				loadPage();
			}
		},function(data){
			loadPage();
		});
	}
	
	loadInitialConfigOrDisplay();
	
	
	
</script>