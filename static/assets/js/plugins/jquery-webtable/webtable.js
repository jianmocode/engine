/**
 * WebTable v1.0
 *  
 * Excel like table editor base on Handsontable. Webtable easier to use, has better ui and can push data to remote api.
 * Require handsontable v0.31.2, moment  2.18.1, pikaday 1.5.1, numbro 1.11.0, ZeroClipboard 2.3.0
 * Copyright (c) 2014-2017 Xpmse.com
 * Licensed MIT
 * https://xpmse.com/
 * https://help.xpmse.com
 * 
 */

jQuery.fn.webtable = function( option ) {


	/**
	 * 返回 Handsontable 对象(或对象数组)
	 * @param  string option = 'handsontable'
	 * @return handsontable/handsontable array
	 */
	if ( option === 'handsontable' ) {  // 返回 handsontable 对象/数组

		if ( this.length > 1 ) {
			var hots = [];
			this.each(function(){
				var webtable = $(this).data('webtable');
					hots.push( webtable.handsontable() );
			});
			return hots;

		}

		return $(this).data('webtable').handsontable();
	}

	/**
	 * 返回 webtable 对象(或对象数组)
	 * @param  string option = 'get'
	 * @return webtable/webtable array
	 */
	else if ( option === 'get' ) {
		if ( this.length > 1 ) {
			var webtables = [];
			this.each(function(){
				webtables.push($(this).data('webtable') );
			});
			return webtables;

		}
		return $(this).data('webtable');
	}


	// 初始化所有元素
	this.each(function(){
		$(this).data('webtable', init($(this), option) );
	});



	/**
	 * 初始化 Handsontable
	 * @param  {[type]} em     [description]
	 * @param  {[type]} option [description]
	 * @return {[type]}        [description]
	 */
	function init( em, option ) {
		return new webtable( em, option );
	}



	/**
	 * Webtable 对象
	 * @param  object em containter
	 * @param  array  option 配置信息
	 * @return webtable
	 */
	function webtable( em, option ) {
		
		var that = this;
		this.option = option || [];
		this.em = em;
		this.colHeaders = {},
		this.columns =[];
		this.columnsMap ={};
		this.data = [];
		this.needsave = false;
		this.submitLock = false;
		this.getdataLock = false;
		this.changelogs = {create:{}, update:{}, remove:{} };  // 更新的数据
		this.removeTemp = {};
		this.handsontableSetting = {};

		this.renderer = {
			unikey:function(instance, td, row, col, prop, value, cellProperties) {  // 渲染 Unikey
				return td;
			}
		}


		var defaults = {
			readOnly: true,
			stretchH: 'all',  // 平铺方式, 填满屏幕宽度
			minRows:  40,
			minCols:  15,
			currentRowClassName:  'currentRow',
			currentColClassName:  'currentCol',
			search:{
				searchResultClass: 'htSearchResult',
			},
			colWidths:  140,
			rowHeights: 23,
			rowHeaders: true,
			offsetHeight:0,
			contextOption: {
				'submit': true  // 右键菜单显示保存按钮
			},
			submitOption: {},  // 数据保存选项

			needSubmit: function( need ) {}, // 是否需要提交数据
			beforeSubmit: function( changelogs, ajax_option ){ },  // 提交数据之前
			afterSubmit: function( resp, status, xhr ){},  // 提交数据之后
			beforeGetdata: function( ajax_option ){},  // 读取数据之前
			afterGetdata: function( resp, status, xhr ){},  // 读取数据之后
			afterUnlock: function() {},  // 解锁之后
			afterLock: function() {},  // 锁定之后


			contextMenu: {
        		callback: function (key, options) {
				},
				items: {
					"row_above": {
						name:'插入行',
						disabled: function () {
							if ( that.isLocked() ) return true;
						}
					},
					"remove_row":{
						name:'删除行',
						disabled: function () {
							if ( that.isLocked() ) return true;
						}
					}
				}
			},
			afterChange: function( changes, source){ return changes; },   // 表格变更之后
			beforeRemoveRow: function( index, amount ){ return true },
			afterRemoveRow:function( index, amount ){ return true }
		};



		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		// API 
		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		


		/**
		 * 返回 handsontable 对象
		 * @return
		 */
		this.handsontable = function(){
			return this.hot;
		}



		/**
		 * 读取唯一主键数值
		 * @param  {[type]} row [description]
		 * @return {[type]}     [description]
		 */
		this.getUnikeyCell = function( row ) {
			var unikey_col = parseInt(that.columnsMap['{{unikey}}']);
			var rs = that.hot.getDataAtRow(row);
			var unikey_val = rs[unikey_col] || null;

			return unikey_val;
		}


		/**
		 * 更新唯一主键数值
		 * @param  {[type]} row [description]
		 * @param  {[type]} val [description]
		 * @return {[type]}     [description]
		 */
		this.setUnikeyCell = function( row, val ) {
			val  = val || null;
			var unikey_col = parseInt(that.columnsMap['{{unikey}}']);
			that.hot.setDataAtCell(row, unikey_col, val, 'silent' );
		}



		/**
		 * 读取唯一主键名称
		 * @return {[type]} [description]
		 */
		this.getUnikeyColumn = function() {
			var unikey_col = parseInt(that.columnsMap['{{unikey}}']);
			return that.columns[unikey_col];
		}



		/**
		 * 从远程读取数据并渲染表格
		 * @param  string/object ajax_option 服务器地址/Jquery Ajax 选项
		 * @return void()
		 */
		this.getData = function( ajax_option, success, error ) {

			if (that.getdataLock ) {
				return;
			}

			var defaults = {
				type:'GET',
				dataType:'json',
				contentType: "application/json; charset=utf-8",
				success:success || function success(resp,status,xhr){ return resp; },
				error:error||function error(xhr,status,error){}
			};

			if( typeof ajax_option == 'string' ) {
				ajax_option = {
					url: ajax_option
				};
			}

			ajax_option = $.extend({}, defaults, ajax_option ); 

			var errorFn = ajax_option['error'];
			ajax_option['error'] = function( xhr,status,error ) {

				that.getdataLock = false; // 关闭读取锁
				if ( typeof that.handsontableSetting['afterGetdata'] == 'function' ) {
					that.handsontableSetting['afterGetdata']( error, status, xhr );
				}
				errorFn( xhr,status,error );
			}


			var success = ajax_option['success'];
			ajax_option['success'] = function( resp,status,xhr ) {

				

				var resp = success(resp,status,xhr);
				if ( resp === false  ||  typeof resp === 'undefined' ) return;



				if (typeof resp['columns'] == 'object')  {
					that.setColumns(resp['columns']);
				}

				if (typeof resp['colHeaders'] == 'object')  {
					that.hot.updateSettings({'colHeaders':resp['colHeaders']});
				}

				if (typeof resp['data'] == 'object')  {
					that.setData(resp['data']);
				}

				that.getdataLock = false; // 关闭读取锁
				if ( typeof that.handsontableSetting['afterGetdata'] == 'function' ) {
					that.handsontableSetting['afterGetdata']( resp,status,xhr );
				}

			}


			that.getdataLock = true;
			if ( typeof that.handsontableSetting['beforeGetdata'] == 'function' ) {
				that.handsontableSetting['beforeGetdata']( ajax_option );
			}

			$.ajax(ajax_option);


		}


		this.arrayToObject = function( data) {
			var rs = {}; 
			for ( var j in data ) {
				var field_name = that.columns[j]['data'] || '';
				if ( field_name == '' ) {
					field_name = that.columns[j]['name'] || '';
				}
				rs[field_name] = data[j];
			}

			return rs;
		}



		/**
		 * 将数据表信息提交到服务器
		 * @param  string api 服务器地址
		 * @param  function success 提交成功后回调
		 * @param  function fail    提交失败后回调
		 * @return void()
		 */
		this.submitData = function( ajax_option, success, error ) {
			
			if ( !that.needsave ||  that.submitLock ) {
				debug.log('WebTable submitData that.needsave=',that.needsave, ' that.submitLock=', that.submitLock );
				return false;
			}

			var data = {create:[], update:[], remove:[]}
			var logs = that.getChanges();

			for( var i in logs['update'] ) {
				var log = logs['update'][i];
				data['update'].push({data:that.arrayToObject(log['data']) , unikey:log['unikey'], row:log['row']});
			}

			for( var i in logs['create'] ) {
				var log = logs['create'][i];
				data['create'].push({data:that.arrayToObject(log['data']), row:log['row']});
			}

			for( var val in logs['remove'] ) {
				var dt = logs['remove'][val];
				data['remove'].push({unikey:dt['unikey'], value:dt['value'], row:dt['row']});
			}

			// 增加扩展信息
			if ( typeof ajax_option == 'object' && typeof ajax_option != null ) {
				if ( typeof ajax_option['data'] == 'object') {
					data = $.extend({}, data, ajax_option['data']);
					delete ajax_option['data'];
				}
			}

			var json_string = JSON.stringify(data);
			if ( typeof ajax_option == 'object'  && typeof ajax_option != null ) {
				if (  ajax_option['escape']  === true ) {
					json_string = escape(json_string);
				}
			}

			var defaults = {
				type:'POST',
				dataType:'json',
				data:'data=' + json_string,
				contentType: "application/x-www-form-urlencoded; charset=utf-8",
				success:success || function success(resp,status,xhr){ return resp; },
				error:error||function error(xhr,status,error){}
			};

			if( typeof ajax_option == 'string' ) {
				ajax_option = {
					url: ajax_option
				};
			}

			ajax_option = $.extend({}, defaults, that.handsontableSetting['submitOption'], ajax_option ); 

			var errorFn = ajax_option['error'];
			ajax_option['error'] = function( xhr,status,error ) {

				that.submitLock = false; // 关闭提交锁
				that.unlock();
				if ( typeof that.handsontableSetting['afterSubmit'] == 'function' ) {
					that.handsontableSetting['afterSubmit']( error, status, xhr );
				}

				errorFn( xhr,status,error );
			}


			var success = ajax_option['success'];
			ajax_option['success'] = function( resp,status,xhr ) {
				var resp = success(resp, status, xhr);
				if ( resp === false  ||  typeof resp === 'undefined'  ) return;

				if ( resp == null ) {
					ajax_option['error']( xhr, 500, resp );
					return ;
				}

				if (  typeof resp['code'] != 'undefined' && resp['message'] != 'undefined' && resp['code'] != 0  ){
					ajax_option['error']( xhr, 500, resp );
					return ;
				}

				if (  typeof resp['result'] != 'undefined' && resp['content'] != 'undefined' && resp['result'] === false  ){
					ajax_option['error']( xhr, 500, resp );
					return ;
				}


				// 更新ID数据 
				for( var row in resp ) {

					// 更新ID
					if ( typeof resp[row]['method'] == 'string' && typeof resp[row]['unikey'] != 'undefined' && resp[row]['method'] == 'create') {
						that.setUnikeyCell(row, resp[row]['unikey']);
					}

					// 通报数据异常字段 (下一版支持 )
					if ( typeof resp[row]['method'] == 'string' &&  resp[row]['method'] == 'error') {

						var err = resp[row]['data'] || null;
						if ( typeof err == 'object' ) {

							for( var field in err ) {
								console.log( 'field =', field, 'error=', err[field], ' row=', row );
							}

						} else  { // 未知字段
							console.log( 'error all line error=', err, ' row=', row,  typeof err );
						}
					}
					
				}


				that.submitLock = false; // 关闭提交锁
				that.unlock();
				that.changelogs = {create:{}, update:{}, remove:{} }; // 充值更新记录
				that.needsave  = false;  // 重置保存标记

				if ( typeof that.handsontableSetting['needSubmit'] == 'function' ) {
					that.handsontableSetting['needSubmit']( that.needsave );
				}

				if ( typeof that.handsontableSetting['afterSubmit'] == 'function' ) {
					that.handsontableSetting['afterSubmit']( resp,status,xhr );
				}
			}


			that.submitLock = true; // 提交锁
			that.lock();
			if ( typeof that.handsontableSetting['beforeSubmit'] == 'function' ) {
				that.handsontableSetting['beforeSubmit']( ajax_option, that.changelogs );
			}

			debug.log( 'WebTable submitData: ajax_option=', ajax_option );
			$.ajax(ajax_option);

		}


		this.needSubmit = function( need  ) {

			if ( typeof need == 'undefined' || need == null ) {
				need = false;
			}

			that.needsave = need;
			if ( typeof that.handsontableSetting['needSubmit'] == 'function' ) {
				that.handsontableSetting['needSubmit']( that.needsave );
			}
		}

		/**
		 * 设定数据
		 * @param {[type]} data [description]
		 */
		this.setData = function( data ) {
			that.hot.updateSettings({'data':data});
			that.data = that.data;
			return that.data;
		}


		/**
		 * 设定表头
		 * @param {[type]} columns [description]
		 */
		this.setColHeaders = function( colHeaders ) {
			that.colHeaders = colHeaders;
			that.hot.updateSettings({'colHeaders':colHeaders});
			return that.colHeaders;
		}




		/**
		 * 设定栏位
		 * @param {[type]} columns [description]
		 */
		this.setColumns = function( columns ) {
			
			that.columnsMap = {};

			// 处理 renderer
			for ( var k in columns ) {
				if ( typeof columns[k]["renderer"] == 'string' ) {
					var match = columns[k]["renderer"].match(/\{\{(.+)\}\}/) ;
					if ( match != null ) {
						columns[k]["renderer"] = that.renderer[match[1]];
						that.columnsMap['{{unikey}}'] = k;
					}
				}

				var name = columns['name'];
				that.columnsMap[name] = k;
			}

			that.columns = columns;
			that.hot.updateSettings({'columns':columns});
			return that.columns;
		}


	


		/**
		 * 更新高度
		 * @return {[type]} [description]
		 */
		this.fixHeight = function( offset ) {
			var winHeight = $(document.body).outerHeight(true);
			that.handsontableSetting['offsetHeight'] = offset = offset || 100;
			that.hot.updateSettings({
	            height: winHeight - offset,
	        });
		}


		/**
		 * 重新渲染表格
		 * @return {[type]} [description]
		 */
		this.refresh = function(){
			that.hot.updateSettings({});
		}



		/**
		 * 解锁数据表 
		 * @return {[type]} [description]
		 */
		this.unlock = function() {
			that.handsontableSetting['readOnly'] = false;
			that.hot.updateSettings({readOnly: false});
			if ( typeof that.handsontableSetting['afterUnlock'] == 'function' ) {
				that.handsontableSetting['afterUnlock']();
			}
		}

		/**
		 * 锁定数据表
		 * @return {[type]} [description]
		 */
		this.lock = function(){
			that.handsontableSetting['readOnly'] = true;
			that.hot.updateSettings({readOnly: true});

			if ( typeof that.handsontableSetting['afterLock'] == 'function' ) {
				that.handsontableSetting['afterLock']();
			}
		}

		/**
		 * 检查数据表是否为锁定状态
		 * @return {Boolean} [description]
		 */
		this.isLocked = function(){
			return that.handsontableSetting['readOnly'];
		}


		/**
		 * 向前操作
		 * @return {[type]} [description]
		 */
		this.redo = function(){
			that.hot.redo();
		}

		/**
		 * 回退操作
		 * @return {[type]} [description]
		 */
		this.undo = function(){

			that.hot.undo();
		}


		/**
		 * 重新渲染表格
		 */
		this.render = function(){
			that.hot.render();
		}


		
		/**
		 * 按关键词检索所有栏位
		 * @param  string keyword 关键词
		 * @param  function matchFn 匹配方式 ( function(queryStr, value){} )
		 * @param  function callbackFn 匹配成功回调 （function(instance, row, col, value, result){})
		 * @return array 搜索结果
		 */
		this.search = function( keyword, matchFn, callbackFn  ) {

			matchFn = matchFn || null;
			callbackFn = callbackFn || null;
			var searchSetting = that.handsontableSetting['search'];

			if ( matchFn != null ) {
				searchSetting['queryMethod'] = matchFn;
			}

			if ( callbackFn != null ) {
				searchSetting['callback'] = callbackFn;
			}

			that.hot.updateSettings({search:searchSetting});
			var resp = that.hot.search.query(keyword);
			that.hot.render();
			return resp;

		}




		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		// 工具 
		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		
		/**
		 * 记录更新日志
		 * @param  {[type]} changes [description]
		 * @param  {[type]} source  [description]
		 * @return {[type]}         [description]
		 */
		this.updateChanges = function( changes, source ) {


			var col = that.getUnikeyColumn();

			for( var i in changes ) {
				var row = changes[i][0];
				var unikey_col = parseInt(that.columnsMap['{{unikey}}']);
				var rs = that.hot.getDataAtRow(row);
				var unikey_val = rs[unikey_col] || null;

				// console.log( "row=", row , that.hot.isEmptyRow(row));

				// if ( that.hot.isEmptyRow(row)) {
				// 	continue;
				// }

				// console.log( row );
				
				if ( unikey_val == null ) {

					unikey_val = 'tmp_' +  Date.parse(new Date()) + Math.random()*1000;
					that.hot.setDataAtCell(row, unikey_col, unikey_val, 'silent');
				}

				if ( unikey_val.toString().substring(0,4) == 'tmp_' ) { // 新建

					that.changelogs['create'][unikey_val] = {data:null, row:row};
					
				} else { // 更新
					
					var unikey_name = col['data'] || '';
						if ( unikey_name == '') {
							unikey_name = col['name'] || '';
						}

					that.changelogs['update'][unikey_val] = {data:null, row:row, unikey:unikey_name};
				}

				that.needsave = true;
				if ( typeof that.handsontableSetting['needSubmit'] == 'function' ) {
					that.handsontableSetting['needSubmit']( that.needsave );
				}
			}

		}


		/**
		 * 读取更新记录
		 * @return {[type]} [description]
		 */
		this.getChanges = function() {
			for ( var method in that.changelogs ) {
				if ( method == 'remove' ) {
					continue;
				}
				for ( var id in  that.changelogs[method] ) {
					var row = parseInt(that.changelogs[method][id]['row']);
					if ( that.hot.isEmptyRow(row)) {
						delete that.changelogs[method][id];
						continue;
					}

					var rs = that.hot.getDataAtRow(row);
					that.changelogs[method][id]['data'] = rs;
				}
			}

			return that.changelogs;
		}



		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		// 创建 Handsontable 实例
		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		
		this.handsontableSetting = $.extend({}, defaults, option ); 

		var afterChange = this.handsontableSetting['afterChange'] || function(){};

		// 当数据表发生变更时, 更新修改记录/用于存盘
		this.handsontableSetting['afterChange'] = function( changes, source) {
			changes =afterChange(changes, source);
			if ( changes == false ) {
				return ;
			}

			// console.log(  typeof changes );
			if ( changes !== null && typeof changes == 'object' ) {
				// console.log(  typeof changes['source']  );
				// console.log(  typeof changes['changes']  );
				if ( typeof changes['source'] == 'string' && 
					 typeof changes['changes'] == 'object' ) {
					source = changes['source'];
					changes = changes['changes'];
				}
			}
			if ( source == 'edit' ) {
				that.updateChanges( changes, source );
			}
		}

		// 当数据表发生变更时( drop paste ), 更新修改记录/用于存盘
		this.handsontableSetting['beforePaste'] = function( data, coords) {

			if ( that.isLocked() ) {
				return;
			}

			if ( typeof coords[0] != 'object' || coords[0] == null ) {
				return;
			}

			var startRow = parseInt(coords[0]['startRow']);
			var endRow = parseInt(coords[0]['endRow']);
			var startCol = parseInt(coords[0]['startCol']);
			var endCol = parseInt(coords[0]['endCol']);

			var changes = [];
			for ( var i in data ) {
				var idxI = parseInt(i);
				var row = startRow + idxI;
				var dt = data[i];
				for ( var j in dt ) {
					var idxJ = parseInt(j);
					var col = startCol + idxJ;
					var oldData = that.hot.getDataAtCell(row, col) || "";
					changes.push([row, col, oldData, dt[j]]);
				}
			}

			source = 'paste';
			changes =afterChange(changes, source);
			if ( changes == false ) {
				return ;
			}

			if ( changes !== null && typeof changes == 'object' ) {
				if ( typeof changes['source'] == 'string' && 
					 typeof changes['changes'] == 'object' ) {
					source = changes['source'];
					changes = changes['changes'];
				}
			}

			that.updateChanges( changes, source );

		}

		// 当数据表发生变更时( drop down ), 更新修改记录/用于存盘
		this.handsontableSetting['beforeAutofill'] = function( start, end, data ) {

			if ( that.isLocked() ) {
				return;
			}

			var startRow = parseInt(start.row);
			var endRow = parseInt(end.row);
			var startCol = parseInt(start.col);
			var endCol = parseInt(end.col);
			var selected = {
				rows: data.length,
				cols: data[0].length
			};

			var changes = []; 
			var currRow = 0;

			for ( var i=startRow; i<=endRow; i++ ) {
				var row = parseInt(i);
				var dt = data[currRow];
				currRow ++; 
				if( currRow >= selected.rows ) {
					currRow =0;
				}

				for ( var j in dt ) {
					var idxJ = parseInt(j);
					var col = startCol + idxJ;
					var oldData = that.hot.getDataAtCell(row, col) || "";
					changes.push([row, col, oldData, dt[j]]);
				}
			}

			source = 'autofill';
			changes =afterChange(changes, source);
			if ( changes == false ) {
				return ;
			}

			if ( changes !== null && typeof changes == 'object' ) {
				if ( typeof changes['source'] == 'string' && 
					 typeof changes['changes'] == 'object' ) {
					source = changes['source'];
					changes = changes['changes'];
				}
			}

			that.updateChanges( changes, source );
		}


		// 当删除行时，更新修改记录/用于存盘
		var beforeRemoveRow = this.handsontableSetting['beforeRemoveRow'];
		this.handsontableSetting['beforeRemoveRow'] = function( index, amount ) {
			if ( beforeRemoveRow(index, amount) === false ) {
				return;
			}

			var last = index + amount;
			var col = that.getUnikeyColumn();
			for ( var i=index; i<last; i++ ) {

				if ( that.hot.isEmptyRow(i) ) {
					continue;
				}

				var unikey_val = that.getUnikeyCell( i );

				var unikey_name = col['data'] || '';
						if ( unikey_name == '') {
							unikey_name = col['name'] || '';
						}

				that.removeTemp[unikey_val] = {unikey:unikey_name, row:i, value:unikey_val};
			}

		}

		// 当删除行后，更新修改记录/用于存盘
		var afterRemoveRow = this.handsontableSetting['afterRemoveRow'];
		this.handsontableSetting['afterRemoveRow'] = function( index, amount ) {
			if ( afterRemoveRow(index, amount) === false ) {
				return;
			}

			for ( var unikey_val in that.removeTemp ) {
				if ( unikey_val.substring(0,4) == 'tmp_' ) { 
					try {delete that.changelogs['create'][unikey_val];} catch( e) {}
				} else {
					try {delete that.changelogs['update'][unikey_val];} catch( e) {} // 删除更新
					that.changelogs['remove'][unikey_val] = that.removeTemp[unikey_val];
					that.needsave = true;  // 标记为需要同步
					if ( typeof that.handsontableSetting['needSubmit'] == 'function' ) {
						that.handsontableSetting['needSubmit']( that.needsave );
					}
				}
			}

			// 清空 Temp
			that.removeTemp = {};
		}


		// afterRedo & afterUndo 增加 Redo/Undo 检查
		var afterRedo = this.handsontableSetting['afterRedo'] || function(){};
		var afterUndo = this.handsontableSetting['afterUndo'] || function(){};
		this.handsontableSetting['afterRedo'] = function( changes ) {
			afterRedo(changes, that.hot.isRedoAvailable() );
		}
		this.handsontableSetting['afterUndo'] = function( changes ) {
			afterUndo(changes, that.hot.isUndoAvailable() );
		}



		// 保存右键菜单
		if ( this.handsontableSetting['contextOption']['submit'] == true  ) {

			var contextMenu = this.handsontableSetting['contextMenu'];
			var callback = this.handsontableSetting['contextMenu']['callback'] || function (key, options) {}
			this.handsontableSetting['contextMenu']['items'] = this.handsontableSetting['contextMenu']['items'] || {}
			if ( typeof this.handsontableSetting['contextMenu']['items']['submit'] == 'undefined' ) {
				this.handsontableSetting['contextMenu']['items']['submit'] = {
					name:'保存',
					disabled: function () {
						return !that.needsave;
					}
				}
			}

			this.handsontableSetting['contextMenu']['callback'] = function (key, options) {
				callback( key, options );
				if ( key == 'submit' ) {
					that.submitData();
				}
			}

		}

		var initData = { 'columns':null, 'colHeaders':null, 'data':null }
		if (typeof this.handsontableSetting['columns'] == 'object') {
			initData['columns'] = this.handsontableSetting['columns'];
			delete this.handsontableSetting['columns'];
		}
		if (typeof this.handsontableSetting['colHeaders'] == 'object') {
			initData['colHeaders'] = this.handsontableSetting['colHeaders'];
			delete this.handsontableSetting['colHeaders'];
		}
		if (typeof this.handsontableSetting['data'] == 'object') {
			initData['data'] = this.handsontableSetting['data'];
			delete this.handsontableSetting['data'];
		}


		$(em).handsontable(this.handsontableSetting);
		this.hot = $(em).handsontable('getInstance');

		// 修复高度
		if ( this.handsontableSetting['offsetHeight'] != 0 ) {
			this.fixHeight(  this.handsontableSetting['offsetHeight'] );	
		}

		// 初始化数据
		if (typeof initData['columns'] == 'object') {
			this.setColumns(  initData['columns'] );
		}
		if (typeof initData['colHeaders'] == 'object') {
			this.setColHeaders(  initData['colHeaders'] );
		}
		if (typeof initData['data'] == 'object') {
			this.setData(  initData['data'] );
		}


		delete initData['data'];
	}


}
