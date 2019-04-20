/**
 * RSPicker v1.0
 * Recordset batch selector 
 * required  store.js Marcus Westin
 * @see https://github.com/marcuswestin/store.js
 * 
 * Copyright (c) 2014-2017 Xpmse.com
 * Licensed MIT
 * https://xpmse.com/
 * https://help.xpmse.com
 */

jQuery.fn.rspicker = function( option ) {

	var that = this;
	option = option || [];

	this.isSelectable = option['selectable'] || false;
	this.t = Date.parse(new Date());
	this.selected = {};
	this.name = option['name'] || 'rspicker_' + this.t;

	// 从store中读取数据
	get();


	// 初始化
	this.each( function() {
		initRow( $(this), option );
	});


	/**
	 * 清空
	 * @return 
	 */
	jQuery.fn.clear = function() {
		var selected = that.selected;
		this.each( function() {
			$(this).removeClass('rspicker-selected');
			$(this).data("selected", "0");
		});

		that.selected = {};
		store.remove(that.name);
		$(this).trigger('clear', selected);
		return ;
	}


	/**
	 * 选中
	 * @return {[type]} [description]
	 */
	jQuery.fn.select = function() {

		this.each( function() {
			var params = getParams( $(this) );
			select( $(this), params );
		});
	}

	/**
	 * 取消选择
	 * @return {[type]} [description]
	 */
	jQuery.fn.deselect = function() {
		this.each( function() {
			var params = getParams( $(this) );
			deselect( $(this), params['id'] );
		});
	}

	/**
	 * 读取选中数值 id:Object
	 * @return object {}
	 */
	jQuery.fn.val = function(){
		return that.selected;
	}


	/**
	 * 读取选中项ID
	 * @return {[type]} [description]
	 */
	jQuery.fn.ids = function() {
		return Object.keys(that.selected);
	}


	/**
	 * 读取选中项数量
	 * @return {[type]} [description]
	 */
	jQuery.fn.cnt = function(){
		return  Object.keys(that.selected).length;
	}



	/**
	 * 设定/读取选中状态
	 * @param  {[type]} flag [description]
	 * @return {[type]}      [description]
	 */
	jQuery.fn.selectable = function ( flag ) {
		if (typeof flag == 'undefined') {
			return that.isSelectable;
		}
		that.isSelectable = flag;
	}

	return this;


	/**
	 * ===== 以下为工具函数
	 */

	function initRow( $em, option ) {

		var params = getParams( $em );
		var id = params['id'] || null;

		if ( id === null ) {
			$em.trigger('error', '未设定id参数',  params );
			return ;
		}

		if ( typeof that.selected[id] != 'undefined' ) {
			select($em, params, true);
		}

		$em.on('click', function( ) {
			if ( $(this).data('selected') == "1" ) {
				deselect( $(this), id );
			} else {
				select($(this), params );
			}
		});


	}


	function select( $em, params, slient, selectonly ) {

		if ( !that.isSelectable && slient !== true ) {
			return;
		}

		var id = params['id'] || null;
		if ( id === null ) { 
			$em.trigger('error', '未设定id参数',  params );
			return;
		}

		$em.addClass('rspicker-selected');
		$em.data('selected', "1");
		that.selected[id] = params;

		if ( selectonly != true ) {
			save();
		}

		slient = slient || false;
		if ( slient !== true ) {
			$em.trigger('select',  params );
		}
	}


	function deselect( $em, id, slient ) {

		if ( !that.isSelectable  && slient !== true ) {
			return;
		}

		id = id || null;
		if ( id === null ) { 
			$em.trigger('error', '未设定id参数', params );
			return;
		}

		$em.removeClass('rspicker-selected');
		$em.data('selected', "0");
		var param = that.selected[id];
		delete that.selected[id];
		save();

		if ( slient !== true ) {
			$em.trigger('deselect', param );
		}
	}


	function getParams( item ) {

		var attrs = $(item).attrs('data-param-*') || {};
		var params = {};
		for( var name in attrs ) {
			var value = attrs[name];
				name = name.replace('data-param-', '');

				params[name] = value;
		}

		return params;
	}

	function get() {
		var dt = store.get(that.name) || {};
		// console.log(  'get==', dt);
		that.selected = dt['selected'] || {};
	}

	function save() {
		// console.log( 'save==', that.selected );
		store.set(that.name, {selected:that.selected});
	}

}