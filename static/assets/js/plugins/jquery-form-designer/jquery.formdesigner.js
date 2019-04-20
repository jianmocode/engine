/**
 * IsArray 工具函数
 * @param  {[type]}  param [description]
 * @return {Boolean}       [description]
 */
jQuery.is_array = function ( param ) {
	if ( typeof param == 'object' && !isNaN(param.length) ) {
		return true;
	}
	return false;
}


/**
 * Formdesigner
 * @param  {[type]} option [description]
 * @return {[type]}        [description]
 */
jQuery.fn.formdesigner = function( option ) {
	
	var self = this;
		option['toolbar'] = option['toolbar']  || [];

	/**
	 * 管理面板
	 * @type {Object}
	 */
	var toolbar  = {
		'create': option['toolbar']['create'] || null,
		'update': option['toolbar']['update'] || null,
		'setting': option['toolbar']['setting'] || null,
	}


	/**
	 * 事件定义
	 * @type {Object}
	 */
	var events = {
		'receive' : option['receive'] || function( event, ui, id ) { },
		'create' : option['create'] || function(  item, id, srcid ) { },
		'out' : option['out'] || function( event, ui, id ) { }
	}


	/**
	 * 元件对象
	 * @type {[type]}
	 */
	var component = null;


	/**
	 * 已载入脚本
	 * @type {Array}
	 */
	var fileLoaded = [];


	/**
	 * 模板数值替换
	 * @param  {[type]} keys   [description]
	 * @param  {[type]} string [description]
	 * @return {[type]}        [description]
	 */
	var replace = function( keys, string ) {
		if ( typeof string != 'string' ) {
			return string;
		}

		keys = keys || [];
		for( var name in keys ) {
			var value = keys[name];
			var reg = new RegExp("\\{_" + name + "\\}", "gi");
			// console.log(reg, name, value);
			string = string.replace(reg, value);
			// console.log(string);
		}

		return string;
	}


	/**
	 * 动态载入脚本
	 * @param  {[type]} filelist [description]
	 * @param  {[type]} events   [description]
	 * @param  {[type]} type     [description]
	 * @return {[type]}          [description]
	 */
	var require = function ( filelist, events, type ) {
		type = type || 'script';
		events = events || [];
		if ( typeof events.complete != 'function' ) {
			events.complete =  function(type, filelist){ }
		}

		if ( typeof events.done != 'function' ) {
			events.done =  function(type, file){ }
		}

		if ( typeof filelist == 'string') {
			filelist = [filelist];
		}

		var total = filelist.length, 
			loaded = 0;

		for( var i=0; i<total; i++) {
			var file = filelist[i].toString();
			var hasload = false;

			if ( type == 'script') {
				if ( $('script[src="'+file+'"]').length > 0 ) {
					hasload = true;
				}
			} else if ( type == 'css' ) {
				if ( $('link[href="'+file+'"]').length > 0 ) {
					hasload = true;
				}
			}
			
			if ( fileLoaded[file] === true  || hasload === true) { // 忽略已载入JS 脚本
				loaded  = loaded + 1;
				events.done(type, file, status);
				if ( loaded == total ) {
					events.complete( type,  filelist );
				}
				continue;
			}

			// 加载JavaScript
			if ( type == 'script') {
				/*
				$.ajax({
					url: file,
					dataType: 'text',
					cache: true,
					complete: function( resp, status) {
						try {
							ret = eval( resp.responseText );
						} catch( e ){
							console.log('code:', resp.responseText, '\n\te:', e);
						}
						fileLoaded[this.url] = true;
						loaded  = loaded + 1;
						events.done(type, this.url, status);
						if ( loaded == total ) {
							events.complete( type,  filelist );
						}
					}
				});*/

				var id = 'AUTO_LOAD_SCRIPT_' + new Date().getTime() + Math.floor(Math.random()* 10000);
				function done( evt) {
					var url = $(evt.target).attr('src');
					fileLoaded[url] = true;
					loaded  = loaded + 1;
					events.done(type, url, status);
					if ( loaded == total ) {
						events.complete( type,  filelist );
					}
				}
				

				var head = document.getElementsByTagName('head')[0]; 
				var script = document.createElement('script'); 
					script.type= 'text/javascript'; 
					script.id = id;
					script.onreadystatechange= function( evt ) { 
					if (this.readyState == 'complete') 
						done( evt ); 
					} 
					script.onload= function( evt ){ 
						done( evt ); 
					} 
					script.src= file;
					try  {
						head.appendChild(script); 
					} catch( e ) {
						console.log('require error');
					}


			// 加载CSS	
			} else if  ( type == 'css') {

				var id = 'AUTO_LOAD_CSS_' + new Date().getTime() + Math.floor(Math.random()* 10000);;
				var css = $('<link id="'+id+'" rel="stylesheet" href="'+file+'">');
				$('head').append(css);
				$('#'+ id ).on('load', function(){
					var url = $(this).attr('href');
					// console.log(url);
					fileLoaded[url] = true;
					loaded  = loaded + 1;
					events.done(type,url, status);
					if ( loaded == total ) {
						events.complete( type,  filelist );
					}
				});

			} else {
				loaded  = loaded + 1;
				events.done(type, this.url, status);
				if ( loaded == total ) {
					events.complete( type,  filelist );
				}
			}
		}
	}

	/**
	 * 运行JavaScript脚本
	 * @param  {[type]} script [description]
	 * @return {[type]}        [description]
	 */
	var evalScript = function( script ) {
		script = script || null;
		if ( script != null  ) {
			try{
				eval(script);
			} catch( e ) {
				console.log('eval script error', '\n\t script:', script ,  '\n\t e:', e);
			}
		}
	}


	/**
	 * 根据 slug 读取 srcid
	 * @param  {[type]} slug [description]
	 * @return {[type]}      [description]
	 */
	var getSrcid = function ( slug ) {
		var src = toolbar['create'].component(slug);
			if ( src != null ){
				return $(src).attr('id');
			}
	}



	/**
	 * 读取当前激活面板
	 * @return {[type]} [description]
	 */
	var toolbarActive = function( name, option ) {
		name = name || null;
		option = option || [];	
		var tablist = ['create','update','setting'];

		if ( name == null ) {
			for ( tab in tablist ) {
				if ( toolbar[tab] != null ) {
					if ( toolbar[tab].parent().hasClass('active') ) {
						return tab;
					}
				}
			}
			return null;
		} else if ( tablist.indexOf(name) >= 0 ) {
			if ( toolbar[name] != null ) {
				$(toolbar[name]).data('option', option);
				$(toolbar[name]).trigger('click');
				// $(toolbar[name]).trigger('active');
			}
		}
		return null;
	}

	/**
	 * 初始化元素
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	var initItem = function( item, option ) {

		if( typeof item == 'string') {
			item = $(item);
		}

		var group = item.parent().attr('data-group');
			if ( group == null ) {
				group = "GROUP_" + new Date().getTime() + Math.floor(Math.random()* 10000);
				item.parent().attr('data-group', group );
			}

		
		var id = "ID_" + new Date().getTime() + Math.floor(Math.random()* 10000);
		var name = "NAME_" + new Date().getTime() + Math.floor(Math.random()* 10000);
		var jsFiles=null, cssFiles=null;
			source = option['source'] || 'unknown';
			srcid = option['srcid'] || 'unknown';
			script = option['script'] || null;
			loadfiles = option['load'] || null;

			source = replace({NAME:name, ID:name}, source);
			script = replace({NAME:name, ID:name}, script);

		// 赋值
		item.attr('data-id', id)
			.attr('data-group', group)
			.attr('data-srcid', srcid)
			.attr('data-status', 'new')
			.html($(source).html());


		// 载入JS文件 
		if ( loadfiles != null){
			filelist = $.parseJSON(loadfiles);
			if ( filelist != null )  {
				if ( typeof filelist['js'] != "undefined" && filelist['js'].length > 0 ) {
					jsFiles = filelist['js'];
				}

				if (  typeof filelist['css'] != "undefined" && filelist['css'].length > 0 ) {
					cssFiles = filelist['css'];
				}
			}
		}

		// 加载ÇSS文件
		if ( cssFiles != null ) {
			require(cssFiles,{},'css');
		}

		// 加载JS文件后，运行脚本
		if ( jsFiles != null ) {
			require(jsFiles,{
				complete:function(type,filelist) {
					evalScript( script );
				}
			});

		// 运行脚本
		} else {
			evalScript( script );
		}
		


		// 双击事件
		$('.fd-dbclick', item).dblclick(function() {
			switchStatus(id);
		});

		// Trigger Create Event
		events.create( item, id, srcid );
		return id;
	}

	/**
	 * 根据srcid, 创建节点
	 * @param  {[type]} srcid [description]
	 * @return {[type]}       [description]
	 */
	var createItem = function( srcid, node ) {
		var source = $('#'+srcid).attr('data-source'),
			script =  $('#'+srcid).attr('data-script'),
			loadfiles = $('#'+srcid).attr('data-load'),
			group = $('<div class="form-sort form-group" >'+ source +'</div>'),
			item = group.children('div');
			node = node || null;

		if ( node == null ) {
			$(self).append(group);
		} else {
			$(node).after(group);
		}

		return initItem(item, {
				source:source,
				parent:group,
				srcid:srcid, 
				script:script, 
				load:loadfiles
			});
	}


	/**
	 * 前一个组件
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	var prevItem = function (item ){
		if( typeof item == 'string') {
			item = $('div[data-id="'+item+'"]');
		}

		var prev = item.prev();
		if (prev.length > 0  ) {
			return prev;
		}

		var group = item.parent();
		var prevGroup = group.prev() ;

			if (prevGroup.length <= 0 ) {
				return null;
			}


			prev = prevGroup.children(":last");
			return prev;
	}


	/**
	 * 下一个组件
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	var nextItem = function (item ){
		if( typeof item == 'string') {
			item = $('div[data-id="'+item+'"]');
		}


		var next = item.next();
		if (next.length > 0  ) {
			return next;
		}

		var group = item.parent();
		var nextGroup = group.next() ;
			if (nextGroup.length <= 0 ) {
				return null;
			}

			next = nextGroup.children(':last')
			return next;
	}


	/**
	 * 删除元素
	 * @param  {[type]} name [description]
	 * @return {[type]}      [description]
	 */
	var removeItem = function ( item ) {
		if( typeof item == 'string') {
			item = $('div[data-id="'+item+'"]');
		}
		var group = item.parent();

		if ( item.attr('data-status') == 'active' ) {
			toolbarActive('create');
		}

		item.remove();
		if ( group.children('div').length ==  0 ) {
			group.remove();	
		}
	}





	/**
	 * 根据slug, 替换节点
	 */
	var replaceItem = function( item,  slug ) {
		var srcid = getSrcid(slug);
		var group = item.parent();
		var id = createItem(srcid, group);
			removeItem(item);
		
		return id;
	}


	/**
	 * 复制 item
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	var duplicateItem = function( item ) {
		if( typeof item == 'string') {
			item = $('div[data-id="'+item+'"]');
		}
		var group = item.parent();
		var srcid = item.attr('data-srcid');
		var newid = createItem(srcid, group );
			activeItem(newid);

			return newid;
	}


	var getItems = function( self ) {
		var data = [];
		$('.component', $(self) ).each(function(idx, item){
			var rule = $(item).data('rule');
				if ( typeof rule != 'object' ) {
					rule = {};
				}
			var attrs = $(item).attrs('data-*') || {};
			var params = {};
			for( var name in attrs ) {
                var value = attrs[name];
                    name = name.replace('data-', '');
                    params[name] = value;
            }

            var exts = $('[ext-field=true]', item);
            exts.each(function(idx, ext){
            	var name = $(ext).attr('ext-name');
            	var type = $(ext).attr('ext-type');

            	if ( type == 'array' && typeof params[name] != 'object') {
					params[name] = [];
            	}

            	var extattrs = $(ext).attrs('data-*') || {};
            	var extparams = {};
				for( var extname in extattrs ) {
	                var value = extattrs[extname];
	                    extname = extname.replace('data-', '');
	                    extparams[extname] = value;
	            }
	            
	            if ( type == 'array' ) {
	            	params[name].push(extparams);
	            } else {
	            	params[name] = extparams;
	            }

            })


            params['rule'] = rule;
			data.push(params);
		});

		return data;
	}

	


	/**
	 * 将元素设定为选中状态
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	var activeItem = function ( item ) {
		if( typeof item == 'string') {
			item = $('div[data-id="'+item+'"]');
		}

		// 取消选中其他元素
		$(self).children('.form-group').children('div').each(function(idx,elm){
			self.unactiveItem( $(elm).attr('data-id') );
		});

		// 设定为选中样式
		item.addClass('fd-active');
		var tools = $('<div class="fd-tools" style="height:40px;position:absolute; min-height:40px; bottom:-20px;right:40px">'+
							'<div class="block block-rounded"><div class="block-content  bg-gray-lighter remove-margin	remove-padding">'+
								'<a class="btn text-success" href="#"><span class="h4"><i class="fa fa-plus-circle"></i></span></a>'+
								'<a class="btn text-danger" href="#"><span class="h4"><i class="fa fa-minus-circle"></i></span></a>'+
							'</div></div>' +
			           '</div>');
			

		// 绑定事件
		var id = item.attr('data-id');
		var srcid = item.attr('data-srcid');
		$('.text-success', tools).click(function(event) {
			// console.log( 'copyparse' , id );
			// var newid = createItem(srcid, item.parent() );
			// activeItem(newid);
			duplicateItem(item);
		});

		$('.text-danger', tools).click(function(event) {
			
		    var prev = prevItem(id);
		    var next = nextItem(id);
		    	removeItem(id);
		    	if ( prev != null ) {
		    		activeItem(prev);
		    	} else if ( next != null ) {
		    		activeItem(next);
		    	}

		});

		item.append(tools);
		item.attr('data-status', 'active');
		toolbarActive('update', {id: id});
	}


	/**
	 * 取消选中
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	var unactiveItem = function ( item ){
		if( typeof item == 'string') {
			item = $('div[data-id="'+item+'"]');
		}

		if ( item == null ) {
			return;
		}

		$('.fd-tools', item).remove();
		item.removeClass('fd-active');
		item.attr('data-status', 'normal');
		$(toolbar['update']).data('option', {});
	}


	/**
	 * 切换选中状态
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	var switchStatus =  function( item ) {
		if( typeof item == 'string') {
			item = $('div[data-id="'+item+'"]');
		}

		if ( item.attr('data-status') == 'active' ) {
			unactiveItem(item);
			toolbarActive('create');

		} else {
			activeItem(item);
		}
	}


	/**
	 * 将数据保存到LocalStorage
	 * @return {[type]} [description]
	 */
	var save = function( self ) {

		var attrs = $(self).attrs('data-*') || {};
		var data = {
			'allowkey': $(self).attr('data-allowkey') || '_id:id',
			'createsuccess': $(self).attr('data-createsuccess') || '创建成功',
			'createfailure': $(self).attr('data-createfailure') || '创建失败',
			'updatesuccess': $(self).attr('data-updatesuccess') || '更新成功',
			'updatefailure': $(self).attr('data-updatefailure') || '更新失败',
		};
		
		for( var name in attrs ) {
            var value = attrs[name];
            if ( value != null && value != undefined ) {
                name = name.replace('data-', '');
                data[name] = value;
            }
        }

		data['components'] = self.getItems();
		return data;
	}


	/**
	 * 将数据同步到指定接口
	 * @return {[type]} [description]
	 */
	var sync = function( api ) {

	}



	/**
	 * 初始化表单
	 * @return {[type]} [description]
	 */
	var init = function() {

		if( $(self).data('init') ===  true ) { // 只初始化一次
			return;
		}

		$(self).data('init', true);

		
		/**
		 * 接收组件拖入，创建并初始化元素
		 * @param  {[type]} event [description]
		 * @param  {[type]} ui    )             {  				var source [description]
		 * @return {[type]}       [description]
		 */
		$(self).sortable({
			items:'.form-sort',
			helper: "clone",
			placeholder:'draggable-placeholder',
			tolerance:'pointer',
			appendTo:$(self),
			receive: function( event, ui ) {  // 拖拽进来的组件
				// console.log('receive---time');
				var source =  ui.item.attr('data-source');
				var script = ui.item.attr('data-script');
				var loadfiles = ui.item.attr('data-load');
				var srcid =  ui.item.attr('id');

				ui.sender.append(ui.item.clone());
				ui.item.attr('class','form-sort form-group')
					   .removeAttr('data-source')
					   .removeAttr('data-script')
					   .removeAttr('data-setting')
					   .removeAttr('data-load')
					   .removeAttr('id')
					   .html(source);

				var id = initItem( ui.item.children('div'),  {
						source:source,
						parent:ui.item,
						srcid:srcid, 
						script:script, 
						load:loadfiles
					});

				events.receive(event, ui, id);
			},
			start: function( event, ui ) {
				ui.placeholder.css({
	                'height': ui.item.outerHeight(),
	                'margin-bottom': ui.item.css('margin-bottom')
	            });
			}
		});
	}



	// ==== API Method =================================================================================
	
	/**
	 * 切换工具面板
	 * @param  {[type]} name [description]
	 * @return {[type]}      [description]
	 */
	jQuery.fn.toolbarSwitch = function( name ) {
		name = name || null;
		return toolbarActive( name );
	}


	/**
	 * 删除元素
	 * @param  {[type]} obj [description]
	 * @return {[type]}     [description]
	 */
	jQuery.fn.removeItem = function ( item ) {
		return  removeItem( item );
	}

	/**
	 * 复制元素
	 * @param  {[type]} obj [description]
	 * @return {[type]}     [description]
	 */
	jQuery.fn.duplicateItem = function ( item ) {
		return  duplicateItem( item );
	}
	
	/**
	 * 替换元素
	 */
	jQuery.fn.replaceItem = function ( item ,  slug ) {

		return  replaceItem( item, slug );
	}


	/**
	 * 前一个元素
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	jQuery.fn.prevItem = function ( item ) {

		return prevItem( item );
	}


	/**
	 * 后一个元素
	 * @param  {[type]} item [description]
	 * @return {[type]}      [description]
	 */
	jQuery.fn.nextItem = function ( item ) {

		return  nextItem( item );
	}
	


	/**
	 * 选中元素
	 * @param  {[type]} obj [description]
	 * @return {[type]}     [description]
	 */
	jQuery.fn.activeItem = function ( item ) {
		activeItem( item );
	}


	/**
	 * 取消选中元素
	 * @param  {[type]} obj [description]
	 * @return {[type]}     [description]
	 */
	jQuery.fn.unactiveItem = function ( item ) {
		unactiveItem( item );
	}



	/**
	 * 取消/选中元素
	 * @param  {[type]} obj [description]
	 * @return {[type]}     [description]
	 */
	jQuery.fn.switchItem = function ( item ) {
		switchStatus( item );
	}

	jQuery.fn.getItems = function() {
		return getItems( $(this) );
	}

	/**
	 * 将数据同步到指定地址
	 * @param  {[type]} location [description]
	 * @return {[type]}          [description]
	 */
	jQuery.fn.sync = function ( api ) {
		return sync(api);
	}


	/**
	 * 将数据保存到 LocalStorage 中
	 * @return {[type]} [description]
	 */
	jQuery.fn.save = function () {
		return save( $(this) );
	}


	/**
	 * 动态载入脚本
	 * @param  {[type]} filelist [description]
	 * @param  {[type]} events   [description]
	 * @param  {[type]} type     [description]
	 * @return {[type]}          [description]
	 */
	jQuery.fn.require = function ( filelist, events, type ) {
		require( filelist, events, type );
	}


	/**
	 * 修正窗体高度
	 * @param  {[type]}   offset   [description]
	 * @param  {Function} callback [description]
	 * @return {[type]}            [description]
	 */
	jQuery.fn.fixheight = function( offset) {
		offset = parseInt(offset) || 0;
		if ( typeof callback != 'function') {
			callback = function( height, form ) {}
		}
		var wh = $(window).height();
		var h = parseInt(wh - offset);
		$(self).css('height', h + 'px');
		$(self).css('min-height', h + 'px');
		return self;
	}

	init();
	return this;
}