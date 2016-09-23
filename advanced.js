var loggedin= false;
var username = "";
var techID;
var password;
var level = 1;
var permissionLevel = 0;

http://stackoverflow.com/questions/14795099/pure-javascript-to-check-if-something-has-hover-without-setting-on-mouseover-ou
(function() {
	var matchfunc = null, prefixes = ["","ms","moz","webkit","o"], i, m;
	for(i=0; i<prefixes.length; i++) {
		m = prefixes[i]+(prefixes[i] ? "Matches" : "matches");
		if( document.documentElement[m]) {matchfunc = m; break;}
		m += "Selector";
		if( document.documentElement[m]) {matchfunc = m; break;}
	}
	if( matchfunc) window.isHover = function(elem) {return elem[matchfunc](":hover");};
	else {
		window.onmouseover = function(e) {
			e = e || window.event;
			var t = e.srcElement || e.target;
			while(t) {
				t.hovering = true;
				t = t.parentNode;
			}
		};
		window.onmouseout = function(e) {
			e = e || window.event;
			var t = e.srcElement || e.target;
			while(t) {
				t.hovering = false;
				t = t.parentNode;
			}
		};
		window.isHover = function(elem) {return elem.hovering;};
   }
})();
function quickRequestString(title,successFunc,failureFunc){
	
	var html = '<h2>'+title+'</h2>'+'<input style="padding:10px;width:100%;display:block;box-sizing:border-box;margin-bottom:10px;" id="requestInput"></input>'+'<div><button class="flatButton" id="modalCancel">Cancel</button><button style="float:right;" class="flatButton" id="modalAccept">Accept</button></div>';
	
	showModal(html);
	
	modalCloseFunction=undefined;
	
	document.getElementById('modalAccept').onclick=function(){var val = document.getElementById('requestInput').value;closeModal();successFunc && successFunc(val);};
	document.getElementById('modalCancel').onclick=function(){closeModal();failureFunc && failureFunc();};
}
function quickRequestLargeString(title,successFunc,failureFunc){
	
	var html = '<h2>'+title+'</h2>'+'<textarea style="min-height:300px;padding:10px;width:100%;display:block;box-sizing:border-box;margin-bottom:10px;resize:none;" id="requestInput"></textarea>'+'<div><button class="flatButton" id="modalCancel">Cancel</button><button style="float:right;" class="flatButton" id="modalAccept">Accept</button></div>';
	
	showModal(html);
	
	modalCloseFunction=undefined;
	
	document.getElementById('modalAccept').onclick=function(){var val = document.getElementById('requestInput').value;closeModal();successFunc && successFunc(val);};
	document.getElementById('modalCancel').onclick=function(){closeModal();failureFunc && failureFunc();};
}
//Hovering function from above MUST be included for hovering check
var notificationsShown = 0;
var notificationFooter = null;
function notify(ihtml,duration,clickback){
	if (duration==undefined){
		duration=6000;
	}
	if (notificationFooter==null){
		notificationFooter = document.createElement('div');
		notificationFooter.id='notificationFooter';
		notificationFooter.style.position = 'fixed';
		notificationFooter.style.left='0px';
		notificationFooter.style.right='0px';
		notificationFooter.style.bottom='0px';
		document.body.appendChild(notificationFooter);
	}
	var newid = 'n'+Date.now();
	var toadd = document.createElement('div');
	toadd.id=newid;
	toadd.setAttribute("style","position:relative;opacity:.8;");
	toadd.innerHTML=ihtml;
	var remfunc = function(){
		var element = document.getElementById(newid);
		if (element){element.parentNode.removeChild(element);}
		notificationsShown--;
		if (notificationsShown==0){
			document.body.removeChild(notificationFooter);
			notificationFooter=null;
		}
	}
	
	if (duration>0){
		var tm = setTimeout(remfunc,duration);
		toadd.onmouseover=function(){document.getElementById(newid).style.opacity="1";clearTimeout(tm);};
		toadd.onmouseout=function(){document.getElementById(newid).style.opacity=".5";tm = setTimeout(remfunc,duration);}
		toadd.onclick=function(){clickback && clickback();clearTimeout(tm);remfunc();}
	}
	else{
		toadd.onmouseover=function(){document.getElementById(newid).style.opacity="1";};
		toadd.onmouseout=function(){document.getElementById(newid).style.opacity=".5";}
		toadd.onclick=function(){clickback && clickback();remfunc();}
	}
	
	prependElement(notificationFooter,toadd);
	notificationsShown++;
}
function easyNotify(html,clickback){
	notify('<div style="padding:10px;background-color:#ffa;border:2px solid #333;">'+html+'</div>',6000,clickback);
	console.log(html);
}

fetchCount = 0;

function formatSeconds(secs,doweeks){
	var ret = [];
	if (doweeks==true){
		if (secs>604800){
			var weeks = Math.floor(secs / 604800);
			secs = secs % 604800;
			
			ret.push(weeks + ' Week' + (weeks>1?'s':''));
		}
	}
	if (secs>86400){
		var days = Math.floor(secs / 86400);
		secs = secs % 86400;
		
		ret.push(days + ' Day' + (days>1?'s':''));
	}
	if (secs>3600){
		var hours = Math.floor(secs / 3600);
		secs = secs % 3600;
		
		ret.push(hours + ' Hour' + (hours>1?'s':''));
	}
	if (secs>60){
		var minutes = Math.floor(secs / 60);
		secs = secs % 60;
		
		ret.push(minutes + ' Minute' + (minutes>1?'s':''));
	}
	
	if (secs > 0){
		ret.push(secs + ' Second' + (secs>1?'s':''));
	}
	return ret.join(' ');
}

function escapeForParam(param){
	return param.replace(/&/g, "&amp;").replace(/"/g, '&quot;').replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

var modalCloseFunction;
var inModal = false;

function unhandledClose(){
	console.log("UNHANDLED MODAL CLOSE");
}

var modalElement = null;
var modalCancelElement = null;
function closeModal(){
	if (modalElement==null)
		return false;
	if (inModal){
		modalCloseFunction&&modalCloseFunction();
	}
	document.body.removeChild(modalElement);
	document.body.removeChild(modalCancelElement);
	modalElement=null;
	modalCancelElement=null;
	if (inModal){
		document.body.scrollTop = lastScroll;
	}
	inModal=false;
	return true;
}
function showModal(body,width,manualScrollPos){
	if (!inModal){
		if (manualScrollPos!=undefined){
			lastScroll = manualScrollPos;
		}
		else{
			lastScroll = document.body.scrollTop;
		}
		
	}
	inModal=true;
	modalCloseFunction=unhandledClose;
	if (modalElement==null){
		document.body.style.position='relative';
		modalElement = document.createElement('div');
		modalElement.style.zIndex='101';
		modalElement.style.position='fixed';
		modalElement.style.top='0px';
		modalElement.style.left='50%';
		modalElement.style.overflowY='auto';
		modalElement.style.backgroundColor='#fff';
		modalElement.style.maxHeight='100%';
		if (typeof(body)=='object'){
			modalElement.appendChild(body);
		}
		else{
			modalElement.innerHTML = body;
		}
		modalCancelElement = document.createElement('div');
		modalCancelElement.style.opacity='.5';
		modalCancelElement.style.backgroundColor='#000';
		modalCancelElement.onclick=closeModal;
		modalCancelElement.style.zIndex='100';
		modalCancelElement.style.position='fixed';
		modalCancelElement.style.left='0px';
		modalCancelElement.style.right='0px';
		modalCancelElement.style.top='0px';
		modalCancelElement.style.bottom='0px';
		document.body.appendChild(modalCancelElement);
		document.body.appendChild(modalElement);
		//create modal element, prepend body
	}
	
	if (width==null) width=800;
	var wid = (width/2)*-1;
	var lmargin = ''+wid+"px";
	modalElement.style.width=width + "px";
	modalElement.style.marginLeft=lmargin;
}


var complexObject = function(table,id,idlabel,tables,data,components){
	this.table = table;
	this.id=id;
	this.idlabel=idlabel;
	this.tables=tables;
	this.data=data;
	if (data==null){
		this.data={};
		var tableKeys = getObjectKeys(tables);
		tableKeys.forEach(function(element){this.data[element] = [];},this);
		var blanker = {};
		tables[table].forEach(function(element){blanker[element['Field']]=null;},this);
		this.data[table]=[blanker];
	}
	this.removable=[];
	this.links={};
	components.forEach(function(element,index,ray){this.links[element["table"]]={'linkid':element["linkid"],'idlabel':element['idlabel']}},this);
}
complexObject.prototype.display = function(){
	displayComplex(this.table,this.id,this.idlabel,this.tables,this.data,this.links);
}
complexObject.prototype.addChild = function(tablename){
	var achild = {};
	this.tables[tablename].forEach(function(element){
		if (element['Field']==this.links[tablename]['linkid']){
			achild[element['Field']]=this.id;
		}
		else{
			achild[element['Field']]=null;
		}
	},this);
	this.data[tablename].push(achild);
	this.display();
}

complexObject.prototype.remChild = function(tablename, index){
	if (this.data[tablename][index][this.links[tablename]["idlabel"]]!=null){
		this.removable.push({'table':tablename,'idlabel':this.links[tablename]['idlabel'],'data':this.data[tablename][index]});
	}
	this.data[tablename].splice(index,1);
	this.display();
}
complexObject.prototype.editChild = function(tablename, index, fieldname, content){
	this.data[tablename][index][fieldname]=content;
}
complexObject.prototype.getID = function(){
	return this.data[this.table][0][this.idlabel];
}
complexObject.prototype.syncID = function(){
	var tables = getObjectKeys(this.data);
	var primaryID = this.getID();
	tables.forEach(function(table){
		if (table!=this.table){
			this.data[table].forEach(function(el){
				el[this.links[table]['linkid']]=primaryID;
			},this);
		}
	},this);
}
complexObject.prototype.setID = function(newID){
	this.id=newID;
	this.data[this.table][0][this.idlabel]=newID;
	this.syncID();
}
complexObject.prototype.getPrimaryObject = function(){
	return this.data[this.table][0];
}


var cachedComplex = null;

function funcValid(obfunc){
	return(typeof obfunc === 'function');
}

function getTableDisplayCode(tableName, prefixID, describeStructure, fillCallback, hidefields){
	var ret = '<table>';
	describeStructure.forEach(function(element,index,ray){
		var fieldName = element['Field'];
		
		var curval = '';
		if (fillCallback){
			curval = fillCallback(fieldName);
			if (curval==null) curval = '';
			if(funcValid(curval.replace))
				curval = curval.replace(/&/g, "&amp;").replace(/"/g, '&quot;').replace(/</g, "&lt;").replace(/>/g, "&gt;");
		}
		if (fieldName=='UpdateTime')
			return;
		var intype = '';
		var innerValue = '';
		if (element["Type"]=="text"){
			intype="textarea";
			innerValue = curval;
		}
		else{
			intype = 'input';
		}
		var inputID = prefixID+tableName+fieldName;
		var inputField = "<"+intype+" class=\"fieldEdit\" id=\""+inputID+"\" placeholder=\""+fieldName+"\" value=\""+curval+"\" oninput=\"cachedComplex.editChild('"+tableName+"',"+prefixID+",'"+fieldName+"',getInputContent('"+inputID+"'))\">"+innerValue+"</"+intype+">";
		var trstyle="";
		if(hidefields!=undefined){
			if (hidefields.includes(fieldName)){
				trstyle="display:none;";
			}
		}
		ret += "<tr style=\""+trstyle+"\"><td style=\"vertical-align:top;\">"+fieldName+"</td><td style=\"width:100%;\">"+inputField+"</td></tr>";
	});
	ret += "</table>";
	return ret;
}
function saveFullComplex(){
	var turl = apiroot+'record/multiupdate/';
	var tparams = 'username='+username+'&password='+password+"&data="+encodeURIComponent(JSON.stringify(cachedComplex.data))+"&removable="+encodeURIComponent(JSON.stringify(cachedComplex.removable))+'&primary='+cachedComplex.table+'&idlabel='+cachedComplex.idlabel;
	postJSON(turl,tparams,function(data){
		cachedComplex=null;
		closeModal();
	},function(data){
		easyNotify('FAILED TO SAVE' + data);
	});
}
function savePrimaryComplex(callback){
	var turl = apiroot+'record/create/';
	
	var tparams = 'username='+username+'&password='+password+"&values="+encodeURIComponent(JSON.stringify(cachedComplex.getPrimaryObject()))+'&recordset='+cachedComplex.table+'&idlabel='+cachedComplex.idlabel;
	postJSON(turl,tparams,function(data){
		if (data['SUCCESS']){
			var newid = data['ID'];
			cachedComplex.setID(newid);
			callback && callback();
		}
	},function(data){
		easyNotify('FAILED TO SAVE' + data);
	});
	
}
function saveComplex(){
	if (cachedComplex.getID()==null){
		savePrimaryComplex(function(){saveFullComplex();});
	}
	else{
		saveFullComplex();
	}
	
}
function displayComplex(table, id, idlabel, tables, data, connectors, optionalCallback){
	var ht = "<div style=\"float:right;\"><button onclick=\"saveComplex();\">Save</button> <button onclick=\"closeModal();\">Cancel</button></div><h1>"+table+" : "+id+"</h1>";
	ht += getTableDisplayCode(table,0,tables[table],function(field){
		if (field=="UpdateTime")
			data[table][0][field]=null;
		return data[table][0][field];
	},[idlabel]);
	
	var tableList = getObjectKeys(tables);
	tableList.forEach(function(element, index, ray){
		if (element!=table){
			ht += '<hr /><div><button style="float:right;" onClick="cachedComplex.addChild('+"'"+element+"'"+')">Add New</button><h3>'+element+'</h3>';
			data[element].forEach(function(el,idex,iray){
				ht += "<div style='margin-top:10px;'><button onclick='cachedComplex.remChild(\""+element+"\","+idex+")'>Delete</button>"+
				getTableDisplayCode(element,idex,tables[element],function(fieldname){
					if (fieldname=="UpdateTime")
						return el[fieldname]=null;
					return el[fieldname];
				},[connectors[element]["idlabel"],connectors[element]["linkid"]])+"</div>";
				
			});
			ht += '</div>';
		}
	});
	
	showModal(ht);
	modalCloseFunction = null;
	if (optionalCallback){
		modalCloseFunction = optionalCallback;
	}
	
}
var cachedStructures = {};

function complexGet(table, id, idlabel, connecters){
	//steps
	//1 get table architectures
	var tables = [];
	tables.push(table);
	connecters.forEach(function (val,dex,ray){tables.push(val.table)});
	var turl = apiroot+'table/multiget/';
	var tparams = "tables=" + encodeURIComponent(JSON.stringify(tables)) + '&username='+username+'&password='+password;
	
	var getData = function(structure){
			if (id==null||id==undefined){
				cachedComplex = new complexObject(table,id,idlabel,structure,null,connecters);
				cachedComplex.display();
			}
			else{
				var turl = apiroot+'record/multiget/';
				var tparams = 'username='+username+'&password='+password+'&table='+table+'&id='+id+'&idlabel='+idlabel+'&connections='+encodeURIComponent(JSON.stringify(connecters));
				postJSON(turl,tparams,function(data){
					cachedComplex = new complexObject(table,id,idlabel,structure,data["RESULT"],connecters);
					cachedComplex.display();
					//displayComplex(table, id, idlabel, structure, data["RESULT"]);
				},function(data){});
			}
	}
	if (cachedStructures.hasOwnProperty(table)){
		getData(cachedStructures[table]);
	}
	else{
		postJSON(turl,tparams, function(data){
			if (data["SUCCESS"]==true){
				arch = data["RESULT"];cachedStructures[table]=arch;
				getData(arch);
			}
			else{
				
			}
			
		}, function(data){});
	}
}
