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
//Hovering function from above MUST be included for hovering check
var notificationsShown = 0;
var notificationFooter = null;
function notify(ihtml,duration,clickback){
	if (duration==undefined){
		duration=4000;
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

function funcValid(obfunc){
	return(typeof obfunc === 'function');
}

var cachedStructures = {};

