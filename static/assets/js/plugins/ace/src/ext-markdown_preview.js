define("ace/ext/markdown_preview",["require","exports","module","ace/lib/dom","ace/editor","ace/config"], function(require, exports, module) {

var markdownPreviewCss = "\
.ace_markdown_preview, .ace_markdown_show {\
position: absolute;\
right: 20px;\
border-left: 2px dotted rgba(128,128,128,0.5);\
padding: 2px;\
padding-left: 7px;\
overflow: hidden;\
cursor: text;\
}\
.ace_markdown_show { display:block; }\
.ace_markdown_preview > *, .ace_markdown_show > * {pointer-events: auto;}\
\
.ace_markdown_preview img, .ace_markdown_show img {height: 100%; width: auto; background-color: rgba(128,128,128,0.85);}\
.ace_markdown_preview img:hover, .ace_markdown_show img:hover {outline: 2px solid #808080;}\
\
.ace_markdown_preview iframe, .ace_markdown_show iframe {height: 100%; width: 100%; background: #808080;}\
.unseen {visibility:hidden;}\
";

var dom = require("../lib/dom");
dom.importCssString(markdownPreviewCss, "ace_markdown_previews");

var Editor = require("ace/editor").Editor;
var Range = require("ace/range").Range;
var ScrollBar = require('ace/scrollbar').ScrollBar;


function stringHashAbs(str){
	str = str||"";
  var hash = 0, i, chr, len;
  if (str.length == 0) return hash;
  for (i = 0, len = str.length; i < len; i++) {
    chr   = str.charCodeAt(i);
    hash  = ((hash << 5) - hash) + chr;
    hash |= 0; // Convert to 32bit integer
  }
  return hash<0?-hash:hash;
}

function arrayDelete( arr, value ) { 
	var a = arr.indexOf(value); 
	if (a >= 0) { 
		arr.splice(a, 1); 
		return true; 
	}
	return false; 
};


function arrayOrder( x, y ) { 
	if(x>y) { 
		return 1; 
	}else{ 
		return -1;
	} 
}


function arraySelect( arr, start ) {

	var newArr = arr;
	var half = 0, from = 0, to =0;
	var length = newArr.length;

	// console.log( '===OK 0 === length=',length, ' start:', start , 'newArr', newArr);

	if (length == 0) return false;
	if ( start > newArr[length-1]) return false;

	if ( length == 1) {

		// console.log( '===OK 1 === length=',length, ' start:', start, 'VAL:', newArr[0]);

		if ( start != newArr[0] ) {
			return false;
		}

		newArr[0];
	}

	if ( length == 2 ) {
		// console.log( '===OK 2 === length=',length, ' start:', start, 'VAL:', newArr[1]);

		if ( start > newArr[1] ) {
			return false;
		}

		if ( newArr[0] > start ) {
			return newArr[0];
		}


		return newArr[1];
	}


	if ( length % 2 != 0 )  {
		// length = newArr.length;
		half = (length+1)/2;
		from = half - 1;
		to  = length;

	} else {
		half = length/2;
		from = half;
		to  = length;
	}


	var row = newArr[from];

	// console.log('===: length:', length, 'start:', start, ',half:', half, ', row:', row, newArr );

	if ( start == row ) { 
		// console.log( '===OK2===', row);
		return row; 
	}

	if ( start > row ) {

		// console.log('\t from:',from, 'half:', half, 'slice[]=',  newArr.slice(from, half) );
		return arraySelect( newArr.slice(from, to), start );
	} else {
		return arraySelect( newArr.slice(0, half), start );
	}
}

var __poEventLock = false;  // 事件操作锁 

var __po = {};  // 预览对象存储 { id1=>object1, id2=>object2... }
var __poRows = [];  //预览对象开始行信息 [start_row1, start_row4 ...]
var __poRowsMap = {};  // 预览对象开始行反向查找Map {start_row1' => id1, start_row2 => id2....}

var __poRange = []; // 预览对象占用行 {id1 => [row1, row2 ...], id2 => [row8,row9...]...}
var __poRangeMap = {}; // 预览对象占用行反向查找Map {row1=>id1, row2=>id1, row3=>id1, row4=>id2 ....}

var __poSrc = {};  // 预览对象资源元素 {src_id1=>{ width:null, height:null, url:this.url }, src_id2 => { width:200, height:400, url:this.url }  ...}

var __editor = null; // 编辑器实例

var previewObject = function() {
	this._exp = null;
	this.line = null;
	this.range = {from:0, to:0};
	this.pos = {row:0,column:0};
	this.value = null;
	this.html = null;
	this.text = null;
	this.isCreate = false;
}


previewObject.prototype = {

	/**
	 * 下一个预览对象
	 * @return {Function} [description]
	 */
	next: function() {
		
		var index = __poRows.indexOf(this.start);
		if ( index < 0 ) return false;

		var next = index + 1;
		// console.log('\t NEXT INDEX:', next, ' index:', index );

		if ( next >= __poRows.length) return false;

		nextStart = __poRows[next];
		var id = __poRowsMap[nextStart];

		if (!id) return false;

		if ( __po[id] != undefined ) {
			return __po[id];
		}

		return false;
	},

	prev: function() {
		
		console.log('this', this );
		return null;
	},

	each: function() {

	},

	render: function() {

	},

	show: function() {

	}, 

	hide: function() {

	},

	/**
	 * 锁定: 锁定时不触发事件
	 * @return {[type]} [description]
	 */
	lock: function() {
		__poEventLock = true;
	},

	/**
	 * 解锁: 解锁后触发事件
	 * @return {[type]} [description]
	 */
	unlock: function() {
		__poEventLock = false;
	},


	hash: function(){
		console.log(  jQuery.toJSON( this) );
		return jQuery.toJSON( this);
	},

	/**
	 * 当前有多少空行
	 * @return {[type]} [description]
	 */
	blanklines: function() {
		var blanklines = 0;
		var lines = __editor.session.getDocument().$lines;

		for( var i= this.start+1; i<=this.end; i++ ) {

			if ( lines[i] == undefined ) break;
			if($.trim(lines[i]) != "") break;
			blanklines++;
		}
		return blanklines;
	},

	/**
	 * 补全空行
	 * @return {[type]} [description]
	 */
	insertBlankLines: function(){

		var hasBlankLines = this.blanklines();
		var needInsertLines = parseInt(this.h) - parseInt(hasBlankLines);
		var blanklines = '';
		for( var i=0; i<needInsertLines; i++ ) {
			blanklines = blanklines + '\n';
		}

		this.lock();
		__editor.session.insert({'row': this.start + 1, 'column':0 }, blanklines );

		// 调整后面预览元素的布局
		next = this.next();
		if ( next != false ) {
			next.moveDown( needInsertLines );
		}

		this.unlock();
	},


	/**
	 * 删除空行
	 */
	removeBlankLines: function() {

		var hasBlankLines = this.blanklines();
		var objBlankLines = parseInt(this.h);
		var needRemoveLines = objBlankLines;

		if ( needRemoveLines > hasBlankLines ) {
			needRemoveLines = hasBlankLines;
		}


		var r = new Range( this.start + 1,  0, this.start + 1 + needRemoveLines, 0 );

		// var r = new Range(0,0,2,0);
		// console.log( 'Range = : ', r.start , r.end, 'needRemoveLines=', needRemoveLines, 'hasBlankLines=', hasBlankLines);
		
		this.lock();

		__editor.session.replace(r, '');


		// 上移空行
		next = this.next();
		if ( next !== false ) {
			next.moveUp( objBlankLines );
		}
		this.unlock();

	},


	/**
	 * 预览元素下移 (同时移动所有下面的预览元素)
	 * @param  {[type]} rows [description]
	 * @return {[type]}      [description]
	 */
	moveDown: function( rows ) {
		console.log( 'start=', this.start + parseInt(rows)  );
		this.setStart( this.start + parseInt(rows) );
		this.layout();

		next = this.next();
		if ( next !== false ) {
			next.moveDown( rows );
		} 

	},

	/**
	 * 预览元素上移 (同时移动所有下面的预览元素)
	 * @param  {[type]} rows [description]
	 * @return {[type]}      [description]
	 */
	moveUp: function( rows ) {
		
		var moveTo =  this.start - parseInt(rows);
		if ( moveTo < 0 )  moveTo = this.start;

		console.log('===MOVE TO start:', moveTo );
		this.setStart( moveTo );
		this.layout();

		next = this.next();
		if ( next !== false ) {
			next.moveDown( rows );
		}
	},


	/**
	 * 删除预览对象
	 * @return {[type]} [description]
	 */
	destory: function() {

		// 释放空行和图片
		this.removeBlankLines();
		this.removeDraw();

		// 释放Range
		var id = this.id;
		var rows = __poRange[id];
		var from = this.start;

		//释放Range
		for( var i=0; i<rows.length; i++ ) {
			var row = rows[i];
			if ( __poRangeMap[row] == id ) {
				delete __poRangeMap[row];
			}
		}
		delete __poRange[id];

		// 释放 __poRows
		var oldRowIndex = __poRows.indexOf(from);
		delete __poRows[oldRowIndex];
		delete __poRowsMap[from];

		// 释放资源对象
		var srcId = this.src;
		if ( __poSrc[srcId] != undefined ) { // 资源
			delete __poSrc[srcId];
		}



		// 释放对象
		arrayDelete(__poRows, id);
		delete __po[id];

	},

	setStart: function( start ) {

		var oldStart = this.start;
		var oldRowIndex = __poRows.indexOf(oldStart);
		// console.log( '=== oldRowIndex:', oldRowIndex , ' oldStart:', oldStart,  '__poRows:', __poRows );

		// 释放对象
		delete __poRows[oldRowIndex];
		delete __poRowsMap[oldStart];


		// 重新赋值
		this.start = start;
		__poRows.push( this.start );
		__poRows.sort( arrayOrder );
		__poRowsMap[this.start] = this.id;

	},

	setRange: function( from, to, width, height ) {
		
		this.range = {start:from, end:to, width:width, height:height };
		this.start = from;
		this.end = to;
		this.width = width;
		this.height = height;


		// 删除旧Range
		if ( __poRange[this.id] ) {
			for ( var i in __poRange[this.id] ) {
				var row =  __poRange[this.id][i];
				delete __poRangeMap[row];
				// arrayDelete(__poList, row );
			}
		}

		// 更新Range
		__poRange[this.id] = [];
		for( var i=from; i<=to; i++) {
			__poRangeMap[i] = this.id;
			
			__poRange[this.id].push( i );
		}


		// 更新RangeList
		// __poList.push(from);
		// __poList.sort( arrayOrder );

	},

	getSrc: function() {
		return __poSrc[this.src];
	},


};



var previewImage = function( url, row ) {
	this.url = url;
	this.start = row;
}


previewImage.prototype = $.extend( new previewObject(), { 
	
	/**
	 * 创建对象
	 * @return {[type]} [description]
	 */
	create: function() {

		var randString = (new Date()).valueOf().toString() + parseInt(Math.random()*1000).toString(); 
		var url = this.url;
		var id = 'po_' + stringHashAbs(url + randString );
		var srcId = 'po_img_' + stringHashAbs(url);

		this.id = id;
		this.src = srcId;
		
		// var pos = this.position(200,60);  // Loading Images
		// 保存对象
		__po[id] = this;

		__poRows.push( this.start );
		__poRows.sort( arrayOrder );
		__poRowsMap[this.start] = this.id;

		if ( __poSrc[srcId] == undefined ) { // 资源
			__poSrc[srcId] = { width:null, height:null, url:this.url };
		}

		return null;
	},

	// 读取图片大小
	getImageSize: function( callback ) {
		var image = new Image();
		    image.src = this.url;
		    image.onload = function() {
		        callback({width:image.width, height:image.height});
		    }

		    image.onerror = function(err) {
		    	callback( err );
		    }

		return true;
	},


	// 显示位置信息
	position: function( realW, realH ) {

		var src = this.getSrc();
		var editorW = $(__editor.renderer.content).width();
		var editorH = $(__editor.renderer.content).height();
		var LineH = parseInt(__editor.renderer.lineHeight);
		var showW = realW || src.realW || 600;
		var showH = realH || src.realH || 200;

		if ( showW >= editorW ) {  // 图片过宽，优化显示
			showW = editorW * 0.8;
			showH = editorW * 0.8;
		}

		if ( showH >= editorH ) {  // 图片过高，优化显示
			showW = editorH * 0.6;
			showH = editorH * 0.6;
		}
		var showL = Math.ceil( showH/LineH ) + 1; 
		var showPos = __editor.renderer.textToScreenCoordinates( this.start, 0 );

		//console.log( this.id, '===showPos: ', showPos, '====this.start:', this.start );

			showPos.pageX = parseInt( ( editorW- showW ) / 2 );
		this.setRange( this.start, this.start + showL, showW, showH );

		showPos.pageY = showPos.pageY- $('.editor-core').offset().top + ( LineH * 1.5);



		this.pageX = showPos.pageX;
		this.pageY = showPos.pageY;
		this.h = showL;

		return {'pageX':showPos.pageX, 'pageY':showPos.pageY, 'h':showL, 'showW':showW, 'showH':showH };

	},


	// 绘制图片
	draw: function() {

		var self = this;
		this.getImageSize(function( width, height ) {

			self.width = width;
			self.height = height;
			self.updateDraw();
		});
	},

	updateDraw: function() {

		var self = this;
		var srcId = self.src;
		var pos = self.position( self.width, self.height );
			self.insertBlankLines();
			__poSrc[srcId] = { width:self.width, height:self.height, url:self.url };


		// Current Range
		content = "<img id='src_"+self.id+"' class='ace_markdown_src'  width='"+pos.showW+"' height='"+pos.showH+"' src='"+self.url+"' />";
		var objEmt = $('#obj_'+self.id);

		if ( objEmt.length === 0 ) {
			$('.ace_markdown_previews').prepend("<div class='ace_markdown_preview'"
				+ "id='obj_"+self.id+"'>"
				+content
				+"</div>");

			objEmt = $('#obj_'+self.id);
		}

		objEmt.css({
			'left':pos.pageX,
			'top':pos.pageY,
			'width':pos.showW,
			'height':pos.showH
		});

		// Show 逻辑
		objEmt.show();

	},

	removeDraw: function(){

		var objEmt = $('#obj_'+this.id);
		if ( objEmt.length !== 0 ) {
			objEmt.remove();
		}
	},


	layout: function() {
		this.position();
		this.insertBlankLines();

		/*var src = this.getSrc();
		if ( src != undefined ) {
			if (src.realW == null || src.realH == null ) {
				this.draw();
			} else {
				this.updateDraw();
			}
		} */
		
	},

	test: function() {
	},
});



function dumpPrevieObjects() {


	console.log( '==== 打印预览对象数据 =======');
	console.log( '\t预览对象存储 \t__po:', __po );
	console.log('');
	console.log( '\t预览对象开始行信息 \__poRows:', __poRows );
	console.log( '\t预览对象开始行反向查找Map \__poRowsMap:', __poRowsMap );
	console.log('');
	console.log( '\t预览对象占用行 \__poRange:', __poRange );
	console.log( '\t预览对象占用行反向查找Map \__poRangeMap:', __poRangeMap );
	console.log('');
	console.log( '\t预览对象资源文件 \__poSrc:', __poSrc );
	console.log('');
	console.log( '=========================');

}






function renderTest(e, editor){

	// GET Docmeumt 
	console.log( 'Document Changed  e=', e );
}

/**
 * 快速查找文档中需要显示预览的对象
 * @param  {[type]}   mdoc     [description]
 * @param  {Function} callback [description]
 * @return {[type]}            [description]
 */
function getPreviewObjects( lines, callback ) {

	handler = callback || function(){ console.log('callback')}
	var text = lines.toString();
	// "![enter image description here](http://7xleg1.com1.z0.glb.clouddn.com/2.1.1.png)
	var objReg = /\!\[([^\!\(\)]*)\]\(([0-9a-zA-Z\:\/\/\.^\!]+)\)/gi;
	var objects = [];


	// 查找匹配元素
	while ((match = objReg.exec(text)) != null)  {
		
		var value = match[0];
			value = value.replace(/(\[|\!|\(|\)|\.|\])/gi,"\\$1"); 

		var indexPos = new RegExp("((^|,)"+value+"(,|$))","gi"); 
		var posText = text.replace(indexPos,"$2@$3").replace(/[^,@]/g,"");
		var index = text.replace(indexPos,"$2@$3").replace(/[^,@]/g,"").indexOf("@"); 
		// console.log( 'match=',match, 'index=',index, 'line=', mdoc.$lines[index] );
		// console.log( index );
		objects.push({ 'row':index, 'match':match });
	}

	callback( objects );
}


// 创建图片对象
function initPreviewObjects( objects ) {

	var offset = 0;
	for( var i=0; i<objects.length; i++ ) {
		createPreviewObjects( objects[i] );
	}

	// 更新布局
	layoutPreviewObjects();

	// dumpPrevieObjects();

}



// 创建图片对象
function createPreviewObjects( object  ) {

	var exps = { 
		image:{ exp:/.*\.(jpg|gif|png|jpeg|ico|svg|bmp)$/, class:previewImage }
	}

	for( name in exps ) {
		var option = exps[name];
		if ( object.match[2].match(option.exp) ){
			var po = new option.class( object.match[2], object.row );
				po.create();
		}
	}

	return null;
}


// 获取某行后的第一个对象对象 （ 算法：折半查找 ）
function getPreviewObjectAfter( start ) {	
	var row = arraySelect( __poRows, start );
	return __poRowsMap[row];
}


// 获取一个区间的所有预览对象
function getPreviewObjectsRange( start, end ) {

	var objs = [];
	for(var i=start; i<=end; i++) {
		if( __poRangeMap[i] ) {
			objs.push(__poRangeMap[i]);
		}
	}
	return  $.unique(objs);
}



// 插入
function insertPreviewObjects( start, end ) {

	// 找到发生改变
	console.log( 'start', start, 'end', end );
	var next = getPreviewObjectAfter( start );

	if ( __po[next] !== undefined ) {
		var rows = end - start;
		__po[next].moveDown(rows);
	}

	console.log( 'next=',next , 'start=', start );
	dumpPrevieObjects();
}



// 删除
function removePreviewObjects(start,end) { 

	// 找到发生改变
	console.log( 'start', start, 'end', end );
	var objs = getPreviewObjectsRange( start, end );


	// 更新被修改的预览对象
	for( var i=0; i<objs.length; i++  ) {
		var name = objs[i];
		var obj = __po[name];

		if ( obj.start >= start  && obj.start <= end )  {  // 预览对象被删除

			var text = __editor.session.getLine(obj.start);
			var objReg = /\!\[([^\!\(\)]*)\]\(([0-9a-zA-Z\:\/\/\.^\!]+)\)/gi;
			console.log( 'Enter :: object:',name , 'DELETE');

			if ((match = objReg.exec(text)) == null) {
				console.log( 'DELETE :: object:',name , text );
				obj.destory();
			} else {
				obj.layout(); 
			}


			dumpPrevieObjects();

		} else { // ( 更新布局 )

			console.log( 'object:',name , 'REBUILD');
			obj.layout();  // 重新布局
		}
	}

	var next = getPreviewObjectAfter( end-1);
	
	if ( __po[next] !== undefined ) {
		var rows = end - start;
		__po[next].moveUp( rows );
	}

}


// 修改
function modifyObjects(start,end) { 

	console.log( '变更： start', start, 'end', end );
	var id =  __poRowsMap[start];
	var rangeId = __poRangeMap[start];

	if ( id == null && rangeId != null ) {

		__po[rangeId].lock();
		__editor.session.replace(new Range(start,0,start+1,0), '\n');
		__po[rangeId].unlock();

		return;
	}


	var text = __editor.session.getLine(start);
	var objReg = /\!\[([^\!\(\)]*)\]\(([0-9a-zA-Z\:\/\/\.^\!]+)\)/gi;
	

	if ((match = objReg.exec(text)) == null ) {

		if( id != undefined ) {
			console.log( 'DELETE :: object:' , id  );
			__po[id].destory();
		}
				
	} else {
		console.log(match);

		if( id != undefined ) {
			if ( __po[id].url != match[2]) {
				console.log('UPDATE:: object:', id );
			} 
		} else {

			console.log('CREATE:: object:');
			createPreviewObjects({row:start, match:match});
			var id = __poRowsMap[start];
			var obj = __po[id];
			obj.layout();
		}
	}


}



// 修改文章布局
function layoutPreviewObjects() {

	for(var start in __po ) {
		var obj = __po[start];
		obj.layout();
		break;
	}
}


/**
 * EVENTS
 */

function onEditorChange( e ) {

	var lines = __editor.session.getLines( e.start.row, e.end.row );  // 发生变化的行



	if ( e.lines.length == 1 ) { // 检查是否有变更

		console.log( e.action.length , '单行变更');
		modifyObjects(e.start.row, e.end.row);

		return;
	}


	console.log( e );

	// 检查是否有PreviewObjects 发生变更

	switch( e.action ) {

		case 'insert':

			console.log( e.lines.length -1 , '行插入。。。');
			insertPreviewObjects(e.start.row, e.end.row);

			break;
		case 'remove':
			console.log( e.lines.length -1 , '行删除。。。');
			removePreviewObjects(e.start.row, e.end.row);
			break;

		default:
			break;
	}

}








require("../config").defineOptions(Editor.prototype, "editor", {
  enableMarkdownPreview: {
    set: function(val) {
      __editor = this;


      if (val) {

				console.log("MarkdownPreview: Enabled");


				// TESTS 
				 // var a = [1,2,3,5,8,10,20,30,40,50];
				 // var v = arraySelect(a, 40);
				 // console.log(v);

				// 扫描整个文档、创建需要预览的对象
				getPreviewObjects(__editor.session.getDocument().$lines, initPreviewObjects );

				// this.renderer.on("afterRender",function( err,renderer ){ renderContent(err,renderer, self);});
				// this.renderer.on("afterRender",function( err,renderer ){ renderContent(err,renderer, self);});
				this.session.on("change", function( e ){ if (!__poEventLock) {onEditorChange(e, self);} });
				this.renderer.on("afterRender", function(err,renderer){
					for( var key in __po ) {
						__po[key].layout();
						break;
					}
				});

				// this.renderer.on("afterRender",function( err,renderer ){ onAfterRender(err,renderer, self);});
				$(this.container).find(".ace_content").append("<div class='ace_layer ace_markdown_previews'></div>");
				$(this.container).find(".ace_content").append("<div class='ace_layer ace_markdown_display'></div>");


      } else {
				console.log("MarkdownPreview: Disabled");
				this.renderer.off("afterRender",function( err,renderer ){ onAfterRender(err,renderer, self);});
				$(this.container).find(".ace_content .ace_layer.ace_markdown_previews").remove();
				$(this.container).find(".ace_content .ace_layer.ace_markdown_display").remove();
      }
    },
    value: false
  }
});
});
                (function() {
                    window.require(["ace/ext/markdown_preview"], function() {});
                })();
            