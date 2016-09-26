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
	}
	SettingsSection.prototype.loadFromArray = function(ray){
		this.data = ray;
		this.result = {};
		ray.forEach(function(el){this.createResultEntry(el);},this);
	}
	SettingsSection.prototype.createResultEntry = function(setting){
		if (setting.hasOwnProperty('name'))
			this.result[setting['name']]=null;
	}
	SettingsSection.prototype.addSetting = function(setting){
		this.data.push(setting);
		this.createResultEntry(setting);
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
	
	var section = [
		{'alias':'Database Information','type':'title'},
		{'name':'dbtype','alias':'Database Type','type':'singleChoice','data':['mysql','mssql','pgsql']},
		{'name':'server','alias':'Server','type':'string'},
		{'name':'database','alias':'Database','type':'string'},
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
		{'name':'indexedOnly','alias':'Sort/Search By Indexed Columns Only','type':'bool'},
		{'name':'rootOfPage','alias':'Web Facing Page Root','type':'string'}
	];
	
	//var sections = [section1, section2 , section3, section4];
	
	var settingsDiv = document.getElementById('settingsDiv');
	
	//sections.forEach(function(section){
		var sec = new SettingsSection();
		sec.loadFromArray(section);
		sec.display(settingsDiv);
		//addQuickElement(settingsDiv,'hr');
	//});
	
	var acceptButton = addQuickElement(document.getElementById('controlsDiv'),'button','Accept Configuration',{'id':'acceptButton'});
	addQuickElement(controlsDiv,'span',' ');
	var testButton = addQuickElement(document.getElementById('controlsDiv'),'button','Test Configuration',{'id':'testButton'});
	acceptButton.onclick=function(){
		if(sec.result['masterUsername']==null||sec.result['masterPassword']==null){
			easyNotify('Must Set Master Credentials');
		}
		else if (sec.result['dbtype']==null||sec.result['server']==null||sec.result['database']==null||sec.result['user']==null||sec.result['server']==null||sec.result['pass']==null){
			easyNotify('Must Set Database Information');
		}
		else{
			postJSON('php/savesettings.php','page=settings&data='+encodeURIComponent(JSON.stringify(sec.result)),function(data){
				if (data['SUCCESS']){
					easyNotify('Saved Settings, Rerouting');
					location.reload();
				}
				else{
					console.log('LOADPAGE FAILURE');
					easyNotify('Failed To Save Settings RETURNED ' + JSON.stringify(data));
				}
			},function(data){
				easyNotify('FAILED TO Save Settings ' + data);
			});
		}
		console.log(sec.result);
	};
	testButton.onclick=function(){
		console.log(JSON.stringify(sec.result));
	};
	
	
	
</script>