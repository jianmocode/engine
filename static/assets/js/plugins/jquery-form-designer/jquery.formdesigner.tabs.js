/**
 * FormDesigner 标签页操作
 * @param  {[type]} option [description]
 * @return {[type]}        [description]
 */
jQuery.fn.tabs = function( option ) {

	var self = this;
	var navlist =  $('[data-toggle="tabs"]', $(this) );
	var content =  $('.tab-content', $(this) );
	var SettingForm = option['SettingForm'] || null;
	var events = {
			create: option['create'] || function( resps ){},
			update: option['update'] || function( resps ){},
			active: option['active'] || function( id ){},
			remove: option['remove'] || function( id ){}
		}


	/**
	 * 取消激活面板
	 * @return {[type]} [description]
	 */
	var unactive = function() {

		// 导航
		$(navlist).children('li').each(function(idx, nav){
			$(nav).removeClass('active');
		});

		// 内容
		$(content).children('.tab-pane').each(function(idx, con) {
			$(con).removeClass('active');
		});
	}


	/**
	 * 激活某个面板
	 * @param  {[type]} id [description]
	 * @return {[type]}    [description]
	 */
	var active = function( id ) {
		unactive();
		$('#tabnav-'+id, navlist ).parent().addClass('active');
		$('#tabs-'+id, content ).addClass('active');
			events.active( id );

		return self;
	}

	var getActiveid = function( id ) {
		var link = $(navlist).children('.active').children('a');
			if ( link.length > 0 ) {
				return link.attr('data-id');
			}

			return null;
	}


	/**
	 * 创建一组标签
	 * @param  {[type]} namelist [description]
	 * @return {[type]}          [description]
	 */
	var createArr = function( tablist, trigger ) {
		if ( typeof trigger == 'undefined') {
			trigger = true;
		}

		var tabs = [];
		for( var i=0; i<tablist.length; i++ ) {
			var tab = create(tablist[i], false);
				tabs.push(tab);
		}

		if ( trigger === true ){
			events.create( tabs );
			return self;
		}

		return tabs;
	}

	/**
	 * 根据标签ID 读取标签
	 * @param  {[type]} id [description]
	 * @return {[type]}    [description]
	 */
	var get = function( id ) {
		var obj = $('#tabnav-'+id, navlist );
			if ( !$.is_array(obj) ) {
				return null;
			}

		return {
				id: obj.attr('data-id'),
				name:obj.attr('data-name'),
				ename:obj.attr('data-ename'),
				position:obj.attr('data-position'),
				icon:obj.attr('data-icon'),
				order:index(id)
			};
	}

	/**
	 * 计算标签数量
	 * @return {Boolean} [description]
	 */
	var count = function() {
		return $('li',navlist).length;
	}

	/**
	 * 重新计算排序
	 * @return {[type]} [description]
	 */
	var resetOrder = function() {
		$('a',navlist).each(function(idx, a){
			$(this).attr('data-order', idx);
		})
	}


	/**
	 * 读取id 的 index 数值
	 * @param  {[type]} id [description]
	 * @return {[type]}    [description]
	 */
	var index = function( id ) {
		var obj = $('#tabnav-'+id, navlist );
			if ( !$.is_array(obj) ) {
				return null;
			}

		return $('a', navlist).index(obj);
	}


	/**
	 * 根据索引数据读取
	 * @param  {[type]} idx [description]
	 * @return {[type]}     [description]
	 */
	var getByIndex = function( idx  ){
		var len = count();
			idx = parseInt(idx);

		if (idx >= len ) {
			idx =  len-1;
		} 

		if ( idx < 0 || isNaN(idx) ) {
			idx = 0;
		}

		var id = $($('a',navlist)[idx]).attr('data-id');
		return get(id);
	}




	/**
	 * 创建一个/一组新标签
	 * @param  {[type]} obj [description]
	 * @return {[type]}      [description]
	 */
	var create = function( tab, trigger ) {
		if ( typeof trigger == 'undefined') {
			trigger = true;
		}

		if ( $.is_array(tab) ) {
			return createArr( tab, trigger );
		}

		if ( typeof tab == 'string') {
			tab = {name:tab};
		}

		var id = 'TAB_' + new Date().getTime() + Math.floor(Math.random()* 10000);
		var name = tab['name'], position='', icon='';
			tab['position'] = tab['position'] || 'left';
			tab['icon']  = tab['icon'] || '';
			tab['ename']  = tab['ename'] || id;

			if ( tab['position'] == 'right' ) {
				position = 'class="pull-right" ';
			} 

			if ( tab['icon'] != '' ) {
				icon = '<i class="'+tab['icon']+'"></i> '
			}

        
        var nav = $('<li ' + position + ' > <a href="#tabs-'+id+'" class="font-w300" id="tabnav-'+id
        			+'" data-id="'+id
        			+'" data-name="'+name
        			+'" data-position="'+tab['position']
        			+'" data-icon="'+tab['icon']
        			+'" data-ename="'+tab['ename']
        			+' ">' 
        		  		+ '<span class="icon">' + icon + '</span>' 
        		  		+ '<span class="name">' + name + '</span>'
        		  		+ ' </a> '
        		  	+ '</li>');

        var con = $('<div id="tabs-'+id+'" class="tab-pane" ></div>');
			navlist.append(nav);
			content.append(con);

		var order = $('a',navlist).index($('a', nav));
			$('a', nav).attr('data-order', order);

			// 绑定active 事件
			$('a', nav).click(function(event) {
				var id = $(this).attr('data-id');
					events.active(id);
			});

			if ( trigger === true ) {
				events.create( [ $.extend(tab, {id:id, nav:nav, content:con}) ] );
				return self;
			}

			return $.extend(tab, {id:id, nav:nav, content:con});
	}


	/**
	 * 更新一个标签
	 * @param  {[type]} id      [description]
	 * @param  {[type]} name    [description]
	 * @param  {[type]} trigger [description]
	 * @return {[type]}         [description]
	 */
	var update  = function (id, tab, trigger ) {

		if ( typeof trigger == 'undefined') {
			trigger = true;
		}

		if ( typeof tab == 'string') {
			tab = {name:tab};
		}

		var a = $('#tabnav-'+id, navlist ),
			li = a.parent();
		
		if (!$.is_array(li) || li.length == 0 ) {
			return ;
		}

		if ( tab['name'] != undefined ) {
			var nameElm = $('.name', $('#tabnav-'+id, navlist ));
				nameElm.html(tab['name']);
				a.attr({'data-name':tab['name']});
		}

		if ( tab['ename'] != undefined ) {
			a.attr({'data-ename':tab['ename']});
		}


		if ( tab['icon'] != undefined ) {
			var iconElm = $('.icon', $('#tabnav-'+id, navlist ));
				if ( iconElm.children('i').length > 0 ) {
					iconElm.children('i').attr('class',tab['icon']);
				} else {
					iconElm.html(' <i class="'+tab['icon']+'"></i> ');
				}
				a.attr({'data-icon':tab['icon']});
		}

		if( tab['order'] != undefined ) {
			var idx = index( id );
			if ( idx !=  tab['order'] ) {
				var dst = getByIndex(tab['order']);
				var dstid = dst['id'];
				moveto(dstid, id);
			}
		}

		if ( tab['position'] != undefined ) {
			var position = '';
			if ( tab['position'] == 'right') {
				position = 'pull-right'
				li.addClass('pull-right');
				a.attr({'data-position':'right'});
			} else {
				li.removeClass('pull-right');
				a.attr({'data-position':'left'});
			}

		}

		var tab = get(id);
		if ( trigger === true ) {
			events.update([tab]);
			return self;
		}

		return tab;

	}


	var remove = function( id, trigger ) {

		if ( typeof trigger == 'undefined') {
			trigger = true;
		}

		if ( typeof tab == 'string') {
			tab = {name:tab};
		}

		var a = $('#tabnav-'+id, navlist ),
			li = a.parent();
			con = $('#tabs-'+id, content );
		
		if (!$.is_array(li) || li.length == 0 ) {
			return ;
		}

		if (!$.is_array(con) || con.length == 0 ) {
			return ;
		}

		li.remove();
		con.remove();

		if ( trigger === true ) {
			events.remove(id );
			return self;
		}
		return id;

	}

	/**
	 * 移动到after
	 * @param  {[type]} dst    [description]
	 * @param  {[type]} source [description]
	 * @return {[type]}        [description]
	 */
	var moveto = function( dstid, srcid ) {
		var dsta = $('#tabnav-'+dstid, navlist ),
			dstli = dsta.parent();
		var srca = $('#tabnav-'+srcid, navlist ),
			srcli = srca.parent();

			srcli.insertAfter(dstli);
			resetOrder();
	}




	/**
	 * 获取第一个标签
	 * @return {[type]} [description]
	 */
	var first = function() {
		var li = $('li',navlist).first();
		if (!$.is_array(li) || li.length == 0 ) return  null;

		var a = li.children('a');
		if (!$.is_array(a) || a.length == 0 ) return  null;

		var id = a.attr('data-id');
		if ( id == null ) return null;
		return get(id);
	}



	/**
	 * 绑定配置表单
	 * @param  {[type]} id [description]
	 * @return {[type]}    [description]
	 */
	var setPanel = function( formid, tabid ) {
		var form = $('#'+formid),
			
			updateinput = $('.updateinput', form),
			updateinputename = $('.updateinputename', form),
			updateinputicon =$('.updateinputicon', form),
			updateinputorder = $('.updateinputorder', form),
			updatecheckboxposition = $('.updatecheckboxposition', form),
			
			createinput = $('.createinput', form),
			createinputename = $('.createinputename', form),
			createinputicon =$('.createinputicon', form),
			createinputorder = $('.createinputorder', form),
			createcheckboxposition = $('.createcheckboxposition', form),
			
			del  = $('.deletebutton', form),
			crt  = $('.createbutton', form),
			tab = get(tabid);



		// 创建
		if( crt.length > 0  ) {

			// 标签位置（排序）
			createinputorder.unbind('keyup');
			createinputorder.on('keyup',  function(event) {
				var order = parseInt($(this).val());
				var len = count();
					if ( order <= 0 || isNaN(order) ) {order = 0; $(this).val('0');}
					if ( order > len ) { order = len; $(this).val(order); }
			});


			// 创建标签
			crt.unbind('click');
			crt.on('click',function(){
				var newpos = 'left';
				if (createcheckboxposition.prop('checked')){
					newpos = 'right';
				}

				var newtab = {
					name:createinput.val(),
					ename:createinputename.val(),
					icon:createinputicon.val(),
					order:createinputorder.val(),
					position:newpos,
				}

				create(newtab);
			})
		}


		// 删除
		if ( del.length > 0 && tab != null ){
			del.data('tab-id', tabid );
			del.unbind('submit');

			if ( count() > 1 ) {
				del.attr('confirm-title','请确认删除标签【' + tab['name']+ '】');
				del.attr('confirm-content',  "请确认删除标签【" + tab['name']+ "】。<strong class='text-danger'>该标签下已设计的表单将被删除</strong>， 此操作不可恢复!"); 
				del.attr('confirm-submit', '确认删除');

			} else {
				del.attr('confirm-title','无法删除');
				del.attr('confirm-content',  "请保留至少一个标签。"); 
				del.attr('confirm-submit', '确定');
			}


			del.bind('submit',function() {
				var len = count();
				if ( len > 1 ) {
					remove($(this).data('tab-id'));
					var ntab = first();
					if ( ntab != null ){
						active(ntab['id']);
					}
				}
			})
		}




		// 更新
		if ( updateinput.length > 0 && tab != null ) {

			updateinput.data('tab-id', tabid );
			updateinputicon.data('tab-id', tabid );
			updateinputename.data('tab-id', tabid );
			updatecheckboxposition.data('tab-id', tabid );
			updateinputorder.data('tab-id', tabid);


				updateinput.val(tab['name']);
				updateinputename.val(tab['ename']);
				updateinputorder.val(tab['order']);
				updateinputicon.val(tab['icon']);

				if ( tab['position'] == 'right') {
					updatecheckboxposition.prop('checked', true);
				} else{
					updatecheckboxposition.prop('checked', false);
				}


			// 标签名称
			updateinput.unbind('keyup');updateinput.unbind('blur');
			updateinput.on('keyup',  function(event) {
				var id= $(this).data('tab-id');
				    update(id, {name:$(this).val()});
			});
			updateinput.on('blur', function(){
				var id= $(this).data('tab-id');
					if ( $(this).val()  == "" && updateinputicon.val() == "" ){
						update(id, {name:'未命名'} );
					}
			})


			// 标签Ename
			updateinputename.unbind('keyup');
			updateinputename.on('keyup',  function(event) {
				var id= $(this).data('tab-id');
				    update(id, {ename:$(this).val()});
			});


			// 标签图标
			updateinputicon.unbind('keyup');
			updateinputicon.on('keyup',  function(event) {
				var id= $(this).data('tab-id');
				    update(id, {icon:$(this).val()});
			});

			// 标签位置（排序）
			updateinputorder.unbind('keyup');
			updateinputorder.on('keyup',  function(event) {
				var id= $(this).data('tab-id');
				var order = parseInt($(this).val());
				var len = count();
					if ( order <= 0 || isNaN(order) ) {order = 0; $(this).val('0');}
					if ( order >= len ) { order = len -1;$(this).val(order); }

				    update(id, {order:order});
			});


			// 标签位置（左右）
			updatecheckboxposition.unbind('click');
			updatecheckboxposition.on('click',  function(event) {
				var id= $(this).data('tab-id');
				var position = 'left';
					if ($(this).prop('checked')) {
						position = 'right';
					}
				    update(id, {position:position});
			});
		}
	}


	/**
	 * 将数据保存
	 * @return {[type]} [description]
	 */
	var save = function() {
		var data = [];
		$('li',navlist).each(function(idx, li){
			var id = $(li).children('a').attr('data-id');
			var tab = get(id);
			data.push(tab);
		});
		return data;
	}


	
	// ======  API ==================================

	/**
	 * 创建一个新标签
	 * @return {[type]} [description]
	 */
	jQuery.fn.create = function ( tab ) {
		return create( tab );
	}

	/**
	 * 更新一个标签
	 * @return {[type]} [description]
	 */
	jQuery.fn.update = function ( id, tab ) {
		return update( id, tab);
	}

	/**
	 * 删除一个标签
	 * @return {[type]} [description]
	 */
	jQuery.fn.delete = function ( id ) {
		return remove( id );
	}


	/**
	 * 激活一个标签
	 * @param  {[type]} id [description]
	 * @return {[type]}    [description]
	 */
	jQuery.fn.active = function ( id ) {
		return active(id);
	}


	/**
	 * 保存标签信息
	 * @return {[type]} [description]
	 */
	jQuery.fn.savetabs = function () {
		return save();
	}


	/**
	 * 绑定管理面板
	 * @param {[type]} id [description]
	 */
	jQuery.fn.setPanel = function ( formid, tabid, callback ) {
		if (typeof callback != 'function') {
			callback = function() {} ;
		}
		setPanel(formid, tabid);
		callback();
	}

	return this;

}