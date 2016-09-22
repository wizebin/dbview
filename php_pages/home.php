<style>
	td,th{padding:3px; background-color:#aaa;}
	div#tableView{overflow:auto;}
</style>

<div class="controls">
	<input type="search" class="regInput" placeholder="Search"></input> 
	<button>Search</button> 
	<button>Filters</button>
	|
	<button>Sort</button>
	<button id='fields'>Fields</button>
</div>
<div id="tableView"></div>

<script type="text/javascript">
	var TableView = function(){
		this.table=null;
		this.rows=null;
		this.data=null;
		
		this.cols=[];
		this.showCols={};
	}
	
	TableView.prototype.clearTable = function(){
		this.rows=[];
		removeAllChildren(this.table);
	}
	TableView.prototype.clear = function(){
		this.data=null;
		if (this.table!=null && this.table.parentNode!=null){
			this.table.parentNode.removeChild(this.table);
		}
		this.rows=null;
		this.table=null;
		this.cols=[];
		this.showCols={};
	}
	TableView.prototype.setData = function(data){
		this.data=data;
		
		this.cols=[];
		if (this.data.length>0){
			var keys = getObjectKeys(this.data[0]);
			this.cols=keys;
			keys.forEach(function(el){this.showCols[el]=true;},this);
		}
		
	}
	TableView.prototype.hideCol = function(col){
		this.showCols[col]=false;
	}
	TableView.prototype.showCol = function(col){
		this.showCols[col]=true;
	}
	
	TableView.prototype.assureTableCreated = function(){
		if (this.table==null){
			this.table = document.createElement('table');
		}
	}
	TableView.prototype.createRows = function(){
		this.rows = [];
		if (this.data==null)
			return null;
		if (this.data.length>0){
			var keys = getObjectKeys(this.data[0]);
			var head = document.createElement('tr');
			keys.forEach(function(key){
				if (this.showCols[key]){
					var th = document.createElement('th');
					th.innerHTML = key;
					head.appendChild(th);
				}
			},this);
			this.rows.push(head);
			this.data.forEach(function(el){
				var tr = document.createElement('tr');
				keys.forEach(function(key){
					if (this.showCols[key]){
						var td = document.createElement('td');
						td.innerHTML = valOrOther(el[key],'');
						td.onclick=function(){displaySingleObject(el);}
						tr.appendChild(td);
					}
				},this);
				this.rows.push(tr);
			},this);
		}
		return true;
	}
	TableView.prototype.attachRows = function(){
		this.assureTableCreated();
		this.rows.forEach(function(el){this.table.appendChild(el);},this);
	}
	
	TableView.prototype.displayIn = function(el){
		this.assureTableCreated();
		
		removeAllChildren(this.table);
		
		this.createRows();
		this.attachRows();
		
		el.appendChild(this.table);
	}
	
	var tableView = new TableView();
	
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
		keys.forEach(function(key){
			var el = document.createElement('input');
			el.type='checkbox';
			el.checked=list[key];
			var name = document.createElement('span');
			name.innerHTML=key;
			var hr = document.createElement('hr');
			
			boolList.appendChild(el);
			boolList.appendChild(name);
			boolList.appendChild(hr);
		},this);
		var button = document.createElement('button');
		button.onclick=function(){
			var ret = {};
			var next=0;
			boolList.childNodes.forEach(function(node,i){
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
			displayAble.appendChild(hh);
			displayAble.appendChild(el);
		},this);
		showModal(displayAble);
	}
	
	document.getElementById('fields').onclick=function(){
		editBoolList(tableView.showCols, function(newcols){tableView.showCols=newcols;tableView.displayIn(document.getElementById('tableView'));});
	};
	
	loadTableView('eventbus_events');
</script>