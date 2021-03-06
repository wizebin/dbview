<style>
	table.mainTable td,table.mainTable th{background-color:#f4f4f4;}
	table.mainTable td.tempSelected{background-color:#faf;}
	table.mainTable td.tempSelected:hover{background-color:#ebe;cursor:pointer;}
	table.mainTable td.indicateRow{background-color:#ffa;}
	table.mainTable td.selectable:hover{background-color:#aaa;}
	table.mainTable th{background-color:#eee;font-weight:normal;color:#888;white-space:nowrap;}
	div.selectableDiv{padding:10px;border-bottom:1px solid #000;}
	div.selectableDiv:hover{background-color:#aaa;}
	table.fullWidthTable,button.fullWidthButton{width:100%;}
	table.mainTable th.sortingASC{}
	table.mainTable th.sortingDESC{}
	table.mainTable th.sortable{background-color:#e5e5e5;}
	table.mainTable th.sortingASC:hover, table.mainTable th.sortingDESC:hover, table.mainTable th.sortable:hover{cursor:pointer;background-color:#ddd;}
	div.minorControls{padding:10px;border-bottom:1px solid #aaa;}
	span.sortIndicator{display:inline-block;vertical-align:center;padding:5px;background-color:#ddd;color:#fff;}
	span.sortOrdinal{display:inline-block;vertical-align:center;padding:5px;background-color:#fff;color:#aaa;}
</style>

<div id="tableView"></div>

<script type="text/javascript">
	
	//applies a class to all children of an element, and returns their current class, use that return as the classname to restore the previous state
	function applyClassToChildren(parent, classname){
		var ret = [];
		if (typeof(classname)=='array' || typeof(classname)=='object'){
			if (parent.childNodes.length == classname.length){
				for(var a = 0; a < classname.length; a++){
					ret.push(parent.childNodes[a].className);
					parent.childNodes[a].className=classname[a];
				}
			}
		}
		else{
			parent.childNodes.forEach(function(el){ret.push(el.className);el.className=classname;},this);
		}
		return ret;
	}
	function getSelectedText(element){
		return element.options[element.selectedIndex].text
	}
	function getSelectedIndex(element){
		return element.selectedIndex;
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
	
	var acceptableVerbList=[{'name':'eq','alias':'is'},{'name':'not_eq','alias':'is not'},{'name':'lt','alias':'is less than'},{'name':'gt','alias':'is greater than'},{'name':'gteq','alias':'is greater than or equal to'},{'name':'lteq','alias':'is less than or equal to'},{'name':'cont','alias':'contains'},{'name':'start','alias':'starts with'},{'name':'end','alias':'ends with'},{'name':'i_cont','alias':'case insensitive contains'},{'name':'present','alias':'is present'},{'name':'blank','alias':'is blank'},{'name':'null','alias':'is null'},{'name':'not_null','alias':'is not null'}];
	var acceptableVerbs = ['eq','not_eq','lt','gt','gteq','lteq','cont','start','end','i_cont','present','blank','null','not_null'];

	var TableView = function(){
		this.data=   [];
		this.filters=[];
		this.sorts=  [];
		this.cols =  {};
		this.table='';
		this.page=0;
		this.pageSize=50;
		this.canWrite=false;
		this.onlyIndexes=indexedOnly;//true;
		this.clickFirstToDisplayObject=true;
		this.shouldReturnToFirstPage=false;
	}
	
	//3 steps to successfully displaying data: loadModelFromServer, loadDataFromServer, display
	
	TableView.prototype.loadModelFromServer = function(table, canWrite, successCallback, failureCallback){
		this.filters=[];
		this.data=   [];
		this.sorts=  [];
		this.table = table;
		this.canWrite=canWrite;
		this.page=0;
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
					var name = el['column_name'];
					tthis.cols[name]['indexed']=true
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
		var found = false;
		this.filters.forEach(function(el){
			if (el['sub']==subject&&el['verb']==verb&&el['obj']==object)
				found=true;
		},this);
		if (!found){
			var obj = {};
			obj['sub']=subject;
			obj['verb']=verb;
			obj['obj']=object;
			this.filters.push(obj);
			return true;
		}
		return false;//already added
	}
	TableView.prototype.display = function(parent){
		var tthis = this;
		this.parent = parent;
		//hard way for now, the react way later
		
		this.subjectSelect = makeOrClear(this.subjectSelect,'select');
		
		var colkeys = getObjectKeys(this.cols);
		colkeys.forEach(function(key){
			var el = this.cols[key];
			if (el['indexed']||(!this.onlyIndexes)){
				var colbuf = document.createElement('option');
				colbuf.text = el['column_name'];//only valid for pgsql
				this.subjectSelect.appendChild(colbuf);
			}
		},this);
		
		this.verbSelect = makeOrClear(this.verbSelect,'select');
		
		acceptableVerbList.forEach(function(el){
		
			var colbuf = document.createElement('option');
			colbuf.text = el['alias'];//only valid for pgsql
			this.verbSelect.appendChild(colbuf);
			
		},this);
		
		if (this.objectInput==undefined)
			this.objectInput = document.createElement('input');
		this.objectInput.placeholder='filter (press enter)';
		this.objectInput.className='regInput';
		this.objectInput.onkeydown = function(e) {
			e = e || window.event;
			if (e.keyCode == 13) {
				tthis.addFilter(getSelectedText(tthis.subjectSelect),acceptableVerbList[getSelectedIndex(tthis.verbSelect)]['name'],tthis.objectInput.value);
				tthis.shouldReturnToFirstPage=true;
				tthis.initiateLoadAndDisplay();
				tthis.objectInput.value='';
			}
		};
		
		if (this.submitButton==undefined)
			this.submitButton = document.createElement('button');
		this.submitButton.innerHTML = 'Load';
		this.submitButton.onclick=function(){tthis.initiateLoadAndDisplay();};
		
		if (this.filtersButton==undefined)
			this.filtersButton = document.createElement('button');
		this.filtersButton.innerHTML = '' + this.filters.length + ' Filters';
		this.filtersButton.onclick=function(){tthis.editFilters();};
		
		//if (this.sortsButton==undefined)
		//	this.sortsButton = document.createElement('button');
		//this.sortsButton.innerHTML = 'Sort By';
		//this.sortsButton.onclick=function(){tthis.editSorts();};
		
		if (this.fieldsButton==undefined)
			this.fieldsButton = document.createElement('button');
		this.fieldsButton.innerHTML = 'Columns';
		this.fieldsButton.onclick=function(){tthis.editFields();};
		
		this.nextPageButton = makeOrClear(this.nextPageButton,'button');
		this.nextPageButton.innerHTML = 'Next';
		this.nextPageButton.onclick=function(){tthis.nextPage();};
		
		this.prevPageButton = makeOrClear(this.prevPageButton,'button');
		this.prevPageButton.innerHTML = 'Prev';
		this.prevPageButton.style.float='right';
		this.prevPageButton.onclick=function(){tthis.prevPage();};
		
		this.nextPageButton = makeOrClear(this.nextPageButton,'button');
		this.nextPageButton.innerHTML = 'Next';
		this.nextPageButton.style.float='right';
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
		this.controlDiv.appendChild(this.fieldsButton);
		addQuickSpacer(this.controlDiv,10);
		
		this.controlDiv.appendChild(this.nextPageButton);
		this.controlDiv.appendChild(this.prevPageButton);
		
		
		var extraHeader = document.getElementById('extraHeader');
		if (extraHeader!=null){
			extraHeader.innerHTML='';
			
			var quantity = this.data.length;
			addQuickElement(extraHeader,'span','Page ' + (this.page+1),{'class':'infoLabel'});
			var pageStart = (this.pageSize*this.page);
			var pageEnd = (this.pageSize*(this.page)+quantity);
			if (quantity==0){
				pageStart='-';
				pageEnd='-';
			}
			addQuickElement(extraHeader,'span',''+ pageStart +' to '+ pageEnd,{'class':'detailLabel'});
		
		}
		
		parent.appendChild(this.controlDiv);
		
		
		if (this.canWrite){
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
		this.tableView.className='mainTable';
		
		this.headRow = makeOrClear(this.headRow,'tr');
		
		var shown = this.getShownCols();
		
		shown.forEach(function(el){			
			var th = document.createElement('th');
			th.innerHTML = el;
			if (this.isColIndexed(el)||(!this.onlyIndexes)){
				//allow clicking to sort
				th.onclick=function(){
					tthis.sortBy(el);
				}
				th.className='sortable';
			}
			var cursort = this.getSortDirectionForColumn(el);
			if (cursort!=null){
				var cardinal = this.getSortCardinalForColumn(el);
				if (cardinal!=null)
					cardinal += 1;
				if (cursort=='DESC'){
					//th.className='sortingDESC';
					th.innerHTML+=" <span class='sortIndicator'>v</span><span class='sortOrdinal'>"+cardinal+"</span>";
				}
				else{
					//th.className='sortingASC'
					th.innerHTML+=" <span class='sortIndicator'>^</span><span class='sortOrdinal'>"+cardinal+"</span>";
				}
			}
			this.headRow.appendChild(th);
			
		},this);
		
		this.tableView.appendChild(this.headRow);
		
		
		
		this.data.forEach(function(el){
			var irow = document.createElement('tr');
			var clickToDisplayObject = true;
			shown.forEach(function(col){			
				var td = document.createElement('td');
				td.innerHTML = el[col];
				prevClasses=[];
				if (clickToDisplayObject){
					td.className='selectable';
					td.onmouseover=function(){
						prevClasses=applyClassToChildren(irow,'tempSelected');
					}
					td.onmouseout=function(){
						applyClassToChildren(irow,prevClasses);
					}
					if (this.canWrite){
						td.onclick=function(){editSingleObject(el, function(updated){el=updated;tthis.initiateLoadAndDisplay();});};
					}
					else{
						td.onclick=function(){displaySingleObject(el);};
					}
				}
				else{
					td.className='selectable';
					td.onmouseover=function(){
						prevClasses=applyClassToChildren(irow,'indicateRow');
					}
					td.onmouseout=function(){
						applyClassToChildren(irow,prevClasses);
					}
					
				}
				if (this.clickFirstToDisplayObject){
					clickToDisplayObject=false;
				}
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
			tthis.shouldReturnToFirstPage=true;
			tthis.initiateLoadAndDisplay();
		});
	}
	TableView.prototype.sortBy = function(choice, soloSort){
		var foundIndex = null;
		
		var newsort = {'col':choice,'direction':'ASC'};
		
		if (soloSort===true){
			this.sorts = [newsort];
		}
		else{
			//find the choice in the current sort list, if it is found reverse the direction and make it the first sort object
			
			if (this.sorts.length>0){
				this.sorts.forEach(function(curSort, curIndex){
					if (curSort['col']==choice){
						foundIndex = curIndex
						if (curIndex==0){
							if (curSort['direction']=='ASC')
								curSort['direction']='DESC';
							else
								curSort['direction']='ASC';
						}
						else{
							var tmp = this.sorts[foundIndex];
							this.sorts[foundIndex]=this.sorts[0];
							this.sorts[0]=tmp;
						}
					}
				},this);
			}
			
			//if it is not found, add it to the front, and limit the size of the sort array to two
			
			if (foundIndex==null){
				if (this.sorts.length>0){
					this.sorts.unshift(newsort);
					if (this.sorts.length>2){
						this.sorts=this.sorts.slice(0,2);
					}
				}
				else{
					this.sorts = [newsort];
				}
			}
		}
		this.shouldReturnToFirstPage=true;
		this.initiateLoadAndDisplay();
	}
	TableView.prototype.editSorts = function(){
		var tthis = this;
		chooseFromList(this.getIndexedCols(),function(choice){
			tthis.sortBy(choice);
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
	TableView.prototype.getSortDirectionForColumn = function(column){
		var ret = null;
		this.sorts.forEach(function(el){
			if (el['col']==column)
				ret=el['direction'];
		},this);
		return ret;
	}
	TableView.prototype.getSortCardinalForColumn = function(column){
		var ret = null;
		this.sorts.forEach(function(el, dex){
			if (el['col']==column)
				ret=dex;
		},this);
		return ret;
	}
	TableView.prototype.isColIndexed = function(col){
		var indexed = this.getIndexedCols();
		return (indexed.includes(col));
	}
	
	TableView.prototype.initiateLoadAndDisplay = function(){
		var tthis = this;
		this.loadDataFromServer(function(){tthis.display(tthis.parent);},function(data){easyNotify('Failed To Load Table' + JSON.stringify(data));});
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
		tableView.loadModelFromServer(mainTable,false,function(){
			tableView.display(document.getElementById('tableView'));
		},function(data){
			tableView.display(document.getElementById('tableView'));
			easyNotify('failed to load table ' + JSON.stringify(data));
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
				easyNotify('Failed to load table view ' + JSON.stringify(data));
			}
		},function(data){easyNotify('Failed to load table view ' + JSON.stringify(data));});
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
		idiv.className='minorControls';
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
			table.className='mainTable';
			
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
					list.splice(todelete[a],1);
				}
				closeModal();
				acceptCallback(list);
			}
			
			var div = document.createElement('div');
			div.appendChild(table);
			var cdiv = document.createElement('div');
			cdiv.className='minorControls';
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
			var choose = document.createElement('div');
			choose.onclick=function(){
				chooseCallback&&chooseCallback(data);
				closeModal();
			};
			choose.innerHTML=data;
			choose.className='selectableDiv';
			objectList.appendChild(choose);
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
	
	function editSingleObject(obj, callback){
		//nothing;
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