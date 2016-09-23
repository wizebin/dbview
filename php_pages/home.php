<style>
	td,th{padding:3px; background-color:#aaa;}
	div.selectableDiv:hover{background-color:#aaa;}
	table.fullWidthTable,button.fullWidthButton{width:100%;}
</style>

<div id="tableView"></div>

<script type="text/javascript">

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
	function getSelectedText(element){
		return element.options[element.selectedIndex].text
	}
	function makeOrClear(current, elementType){
		if (current==undefined){
			return document.createElement(elementType);
		}
		else{
			removeAllChildren(current);
			return current;
		}
	}
	function addQuickSpacer(parent, width){
		var splitter = document.createElement('span');
		splitter.style.display='inline-block';
		splitter.style.width=width+'px';
		parent.appendChild(splitter);
	}
	function addQuickElement(parent, element, content){
		var el = document.createElement(element);
		if (content!=undefined)
			el.innerHTML=content;
		parent.appendChild(el);
	}
	
	var acceptableVerbs = ['eq','not_eq','lt','gt','gteq','lteq','cont','start','end','i_cont','present','blank','null','not_null'];

	var TableView = function(){
		this.data=[];
		this.table='';
		this.canwrite=false;
		this.onlyIndexes=true;
		this.cols = [];
		this.page = 0;
		this.pageSize = 50;
		this.filters=[];
		this.sorts=[];
		this.shouldReturnToFirstPage=false;
	}
	TableView.prototype.loadModelFromServer = function(table, canwrite, successCallback, failureCallback){
		this.table = table;
		this.canwrite=canwrite;
		this.page=0;
		this.filters=[];
		this.data=[];
		this.sorts=[];
		var tthis = this;
		requestFromController('describe',{"table":table},function(data){
			tthis.cols = {};
			data.forEach(function(el){
				var name = el['column_name'];
				tthis.cols[name]={};
				tthis.cols[name]['column_name']=name;
				tthis.cols[name]['shown']=true;
				tthis.cols[name]['indexed']=false;
			},tthis);
			requestFromController('indexes',{"table":table},function(data){
				var indexed = data;
				indexed.forEach(function(el){
					tthis.cols[el['column_name']]['indexed']=true
				},tthis);//only valid for pgsql
				successCallback&&successCallback();
			},function(data){
				failureCallback&&failureCallback(data);
			});
		},function(data){
			failureCallback&&failureCallback(data);
		});
		
	}
	TableView.prototype.addFilter = function(subject, verb, object){
		var obj = {};
		obj['sub']=subject;
		obj['verb']=verb;
		obj['obj']=object;
		this.filters.push(obj);
	}
	TableView.prototype.display = function(parent){
		var tthis = this;
		this.parent = parent;
		//hard way for now
		
		this.subjectSelect = makeOrClear(this.subjectSelect,'select');
		
		var colkeys = getObjectKeys(this.cols);
		colkeys.forEach(function(key){
			var el = this.cols[key];
			if (el['indexed']){
				var colbuf = document.createElement('option');
				colbuf.text = el['column_name'];//only valid for pgsql
				this.subjectSelect.appendChild(colbuf);
			}
		},this);
		
		this.verbSelect = makeOrClear(this.verbSelect,'select');
		
		acceptableVerbs.forEach(function(el){
		
			var colbuf = document.createElement('option');
			colbuf.text = el;//only valid for pgsql
			this.verbSelect.appendChild(colbuf);
			
		},this);
		
		if (this.objectInput==undefined)
			this.objectInput = document.createElement('input');
		this.objectInput.placeholder='filter (press enter)';
		this.objectInput.className='regInput';
		this.objectInput.onkeydown = function(e) {
			e = e || window.event;
			if (e.keyCode == 13) {
				tthis.addFilter(getSelectedText(tthis.subjectSelect),getSelectedText(tthis.verbSelect),tthis.objectInput.value);
				this.shouldReturnToFirstPage=true;
				tthis.initiateLoadAndDisplay();
				tthis.objectInput.value='';
			}
		};
		
		if (this.submitButton==undefined)
			this.submitButton = document.createElement('button');
		this.submitButton.innerHTML = 'Display';
		this.submitButton.onclick=function(){tthis.initiateLoadAndDisplay();};
		
		if (this.filtersButton==undefined)
			this.filtersButton = document.createElement('button');
		this.filtersButton.innerHTML = 'Filters[' + this.filters.length +']';
		this.filtersButton.onclick=function(){tthis.editFilters();};
		
		if (this.sortsButton==undefined)
			this.sortsButton = document.createElement('button');
		this.sortsButton.innerHTML = 'Sort By';
		this.sortsButton.onclick=function(){tthis.editSorts();};
		
		if (this.fieldsButton==undefined)
			this.fieldsButton = document.createElement('button');
		this.fieldsButton.innerHTML = 'Columns';
		this.fieldsButton.onclick=function(){tthis.editFields();};
		
		this.nextPageButton = makeOrClear(this.nextPageButton,'button');
		this.nextPageButton.innerHTML = 'Next';
		this.nextPageButton.onclick=function(){tthis.nextPage();};
		
		this.prevPageButton = makeOrClear(this.prevPageButton,'button');
		this.prevPageButton.innerHTML = 'Prev';
		this.prevPageButton.onclick=function(){tthis.prevPage();};
		
		
		
		this.controlDiv = makeOrClear(this.controlDiv,'div');
		this.controlDiv.className='controls';
		
		this.controlDiv.appendChild(this.subjectSelect);
		addQuickSpacer(this.controlDiv,10);
		this.controlDiv.appendChild(this.verbSelect);
		addQuickSpacer(this.controlDiv,10);
		this.controlDiv.appendChild(this.objectInput);
		addQuickSpacer(this.controlDiv,10);
		this.controlDiv.appendChild(this.submitButton);
		addQuickSpacer(this.controlDiv,10);
		this.controlDiv.appendChild(this.filtersButton);
		addQuickSpacer(this.controlDiv,10);
		this.controlDiv.appendChild(this.sortsButton);
		addQuickSpacer(this.controlDiv,10);
		this.controlDiv.appendChild(this.fieldsButton);
		
		addQuickSpacer(this.controlDiv,20);
		this.controlDiv.appendChild(this.prevPageButton);
		addQuickSpacer(this.controlDiv,10);
		this.controlDiv.appendChild(this.nextPageButton);
		addQuickSpacer(this.controlDiv,10);
		
		var quantity = this.data.length;
		addQuickElement(this.controlDiv,'span',''+(this.pageSize*this.page) +'-'+ (this.pageSize*(this.page)+quantity) + ' (page ' + (this.page+1) + ')');
		
		
		parent.appendChild(this.controlDiv);
		
		
		if (this.canwrite){
			if (this.addButton==undefined)
				this.addButton = document.createElement('button');
			this.addButton.innerHTML = 'Add';
			this.addButton.onclick=function(){this.addRow();};
			
			parent.appendChild(this.addButton);
		}
		else{
			if (this.addButton!=null){
				this.addButton.parentNode.removeChild(this.addButton);
			}
		}
		
		this.tableDiv = makeOrClear(this.tableDiv,'div');
		this.tableDiv.style.overflow='auto';
		
		this.tableView = makeOrClear(this.tableView,'table');
		
		this.headRow = makeOrClear(this.headRow,'tr');
		
		var shown = this.getShownCols();
		
		shown.forEach(function(el){			
			var th = document.createElement('th');
			th.innerHTML = el;
			this.headRow.appendChild(th);
		},this);
		
		this.tableView.appendChild(this.headRow);
		
		this.data.forEach(function(el){
			var irow = document.createElement('tr');
			shown.forEach(function(col){			
				var td = document.createElement('td');
				td.innerHTML = el[col];
				irow.appendChild(td);
			},this);
			this.tableView.appendChild(irow);
		},this);
		
		this.tableDiv.appendChild(this.tableView);
		parent.appendChild(this.tableDiv);
	}
	TableView.prototype.loadDataFromServer = function(successCallback, failureCallback){
		if (this.shouldReturnToFirstPage){
			this.page=0;
			this.shouldReturnToFirstPage=false;
		}
		var jsonEncodedFilters = JSON.stringify(this.getFilters());
		var jsonEncodedSorts = JSON.stringify(this.getSorts());
		var tthis = this;
		requestFromController('list',{'table':this.table,'filters':jsonEncodedFilters,'sortby':jsonEncodedSorts,'page':this.page,'pagesize':this.pageSize},function(data){
			tthis.data = data;
			successCallback&&successCallback();
		},function(data){
			failureCallback&&failureCallback(data);
		});
	}
	TableView.prototype.getFilters = function(){
		return this.filters;
	}
	TableView.prototype.getSorts = function(){
		return this.sorts;
	}
	TableView.prototype.editFilters = function(){
		var tthis = this;
		displayForDeletion(this.filters, function(newfilters){
			tthis.filters=newfilters;
			tthis.initiateLoadAndDisplay();
		});
	}
	TableView.prototype.editSorts = function(){
		var tthis = this;
		chooseFromList(this.getIndexedCols(),function(choice){
			tthis.sorts = [{'col':choice}];
			tthis.initiateLoadAndDisplay();
		});
	}
	TableView.prototype.editFields = function(){
		var tthis = this;
		var tlist = {};
		var keys = getObjectKeys(this.cols);
		keys.forEach(function(key){
			tlist[key]=this.cols[key]['shown'];
		},this);
		editBoolList(tlist,function(nlist){tthis.setShownCols(nlist);tthis.reloadDisplay();});
	}
	TableView.prototype.getShownCols = function(){
		var ret = [];
		var colkeys = getObjectKeys(this.cols);
		colkeys.forEach(function(key){
			var el = this.cols[key];
			if (el['shown']){
				ret.push(el['column_name']);
			}
		},this);
		return ret;
	}
	TableView.prototype.setShownCols = function(collist){
		var colkeys = getObjectKeys(collist);
		colkeys.forEach(function(key){
			var el = this.cols[key];
			el['shown']=collist[key];
		},this);
	}
	TableView.prototype.getIndexedCols = function(){
		var ret = [];
		var colkeys = getObjectKeys(this.cols);
		colkeys.forEach(function(key){
			var el = this.cols[key];
			if (el['indexed']){
				ret.push(el['column_name']);
			}
		},this);
		return ret;
	}
	
	TableView.prototype.initiateLoadAndDisplay = function(){
		var tthis = this;
		this.loadDataFromServer(function(){tthis.display(tthis.parent);},function(data){easyNotify('Failed To Load Table' + data);});
	}
	TableView.prototype.nextPage = function(){
		this.page++;
		this.initiateLoadAndDisplay();
	}
	TableView.prototype.prevPage = function(){
		this.page--;
		if (this.page<0){
			this.page=0;
		}
		else{
			this.initiateLoadAndDisplay();
		}
	}
	TableView.prototype.reloadDisplay = function(){
		var tthis = this;
		tthis.display(tthis.parent);
	}
	
	var tableView = new TableView();
	
	function reloadPage(){
		tableView.loadModelFromServer('eventbus_events',false,function(){
			tableView.display(document.getElementById('tableView'));
		},function(data){
			easyNotify('failed to load table ' + data);
		});
	}
	
	reloadPage();
	
	function loadTableView(table, filters, sorts, fields){
		postJSON('php/controller.php','verb=list&username='+username+'&password='+password+'&table='+table,function(data){
			if (data['SUCCESS']){
				tableView.clear();
				tableView.setData(data['RESULT']);
				tableView.displayIn(document.getElementById('tableView'));
			}
			else{
				easyNotify('Failed to load table view ' + data);
			}
		},function(data){easyNotify('Failed to load table view ' + data);});
	}
	
	//edit checklist
	function editBoolList(list, acceptCallback){
		var boolList = document.createElement('div');
		var keys = getObjectKeys(list);
		var checks = [];
		keys.forEach(function(key){
			var el = document.createElement('input');
			el.type='checkbox';
			el.checked=list[key];
			el.onclick=function(){event.cancelBubble = true;if(event.stopPropagation) event.stopPropagation();};
			checks.push(el);
			var name = document.createElement('span');
			name.innerHTML=key;
			var div = document.createElement('div');
			div.style.padding='10px';
			div.style.borderBottom='1px solid #000';
			div.className='selectableDiv';
			div.onclick=function(){el.checked=!el.checked;}
			
			div.appendChild(el);
			div.appendChild(name);
			boolList.appendChild(div);
			
		},this);
		var acceptButton = document.createElement('button');
		acceptButton.onclick=function(){
			var ret = {};
			var next=0;
			checks.forEach(function(node,i){
				if (node.type=='checkbox'){
					ret[keys[next++]]=node.checked;
				}
			},this);
			closeModal();
			acceptCallback&&acceptCallback(ret);
		};
		acceptButton.innerHTML='Accept';
		
		var allButton = document.createElement('button');
		allButton.onclick=function(){
			checks.forEach(function(node,i){
				node.checked=true;
			},this);
		};
		allButton.innerHTML='All';
		
		var noneButton = document.createElement('button');
		noneButton.onclick=function(){
			checks.forEach(function(node,i){
				node.checked=false;
			},this);
		};
		noneButton.innerHTML='None';
		
		var idiv = document.createElement('div');
		idiv.className='controls';
		idiv.appendChild(acceptButton);
		addQuickSpacer(idiv,30);
		idiv.appendChild(allButton);
		addQuickSpacer(idiv,10);
		idiv.appendChild(noneButton);
		
		boolList.appendChild(idiv);
		showModal(boolList);
	}
	
	
	function sortNumber(a,b) {
		return a - b;
	}
	function displayForDeletion(list, acceptCallback){
		
		if (list.length>0){
			var todelete = [];
			var keys = getObjectKeys(list[0]);	
			var table = document.createElement('table');
			table.className='fullWidthTable';
			
			var thr = document.createElement('tr');
			keys.forEach(function(key){
				var th = document.createElement('th');
				th.innerHTML=key;
				thr.appendChild(th);
			},this);
			addQuickElement(thr,'th');
			
			table.appendChild(thr);
			
			list.forEach(function(el, dex){
				var tr = document.createElement('tr');
				keys.forEach(function(key){
					var td = document.createElement('td');
					td.innerHTML=el[key];
					tr.appendChild(td);
				},this);
				var del = document.createElement('button');
				del.innerHTML='Delete';
				del.onclick=function(){
					table.removeChild(tr);
					todelete.push(dex);
				};
				tr.appendChild(del);
				table.appendChild(tr);
				
			},this);
			
			var acceptButton = document.createElement('button');
			acceptButton.innerHTML='accept';
			acceptButton.onclick=function(){
				todelete.sort(sortNumber);
				for(var a = todelete.length-1; a>=0; a--){
					list.splice(todelete[a]);
				}
				closeModal();
				acceptCallback(list);
			}
			
			var div = document.createElement('div');
			div.appendChild(table);
			var cdiv = document.createElement('div');
			cdiv.className='controls';
			cdiv.appendChild(acceptButton);
			div.appendChild(cdiv);
			showModal(div);
		}
	}
	
	function editFilterList(list, acceptCallback){
		var boolList = document.createElement('div');
		var keys = getObjectKeys(list);
		var checks = [];
		keys.forEach(function(key){
			var el = document.createElement('input');
			el.type='checkbox';
			el.checked=list[key];
			checks.push(el);
			var name = document.createElement('span');
			name.innerHTML=key;
			var del = document.createElement('button');
			del.onclick='';
			var hr = document.createElement('hr');
			
			boolList.appendChild(el);
			boolList.appendChild(name);
			boolList.appendChild(hr);
		},this);
		var button = document.createElement('button');
		button.onclick=function(){
			var ret = {};
			var next=0;
			checks.forEach(function(node,i){
				if (node.type=='checkbox'){
					ret[keys[next++]]=node.checked;
				}
			},this);
			acceptCallback&&acceptCallback(ret);
			closeModal();
		};
		button.innerHTML='Accept';
		boolList.appendChild(button);
		showModal(boolList);
	}
	
	function chooseFromList(list, chooseCallback){
		var objectList = document.createElement('div');
		list.forEach(function(data){
			var choose = document.createElement('button');
			choose.onclick=function(){
				chooseCallback&&chooseCallback(data);
				closeModal();
			};
			choose.innerHTML=data;
			choose.className='fullWidthButton';
			var hr = document.createElement('hr');
			objectList.appendChild(choose);
			objectList.appendChild(hr);
		},this);
		showModal(objectList);
	}
	
	function displaySingleObject(obj){
		var displayAble = document.createElement('div');
		displayAble.className='objecdiv';
		displayAble.style.padding='10px';
		var keys = getObjectKeys(obj);
		keys.forEach(function(key){
			var hh = document.createElement('h3');
			hh.innerHTML=key;
			var el = document.createElement('div');
			el.innerHTML=obj[key];
			var hr = document.createElement('hr');
			displayAble.appendChild(hh);
			displayAble.appendChild(el);
			displayAble.appendChild(hr);
		},this);
		showModal(displayAble);
	}
	
	function modalEditFields(){
		editBoolList(tableView.showCols, function(newcols){tableView.showCols=newcols;tableView.displayIn(document.getElementById('tableView'));});
	}
	function modalEditFilters(){
		
	}
	
	
	
	function logVerbAndProperties(verb, properties){
		requestFromController(verb,properties,function(data){console.log(data);},function(data){console.log(data);});
	}
	
	
	//loadTableView('eventbus_events');
</script>