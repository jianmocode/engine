/**
 * ImgEditor v1.0
 *  
 * ImgEditor is image editor, can add watermark to image
 * Require interact.js 1.2.9
 * Copyright (c) 2014-2017 Xpmse.com
 * Licensed MIT
 * https://xpmse.com/
 * http://book.xpmse.com/
 */

jQuery.fn.imgeditor = function( option, helper_option ) {

	/**
	 * 返回 WebEditor 对象(或对象数组)
	 * @param  string option = 'get'
	 * @return WebEditor/WebEditor Array
	 */
	
	// 返回 imgeditor 实例
	if ( option === 'get' ) {
		if ( this.length > 1 ) {
			var imgeditors = [];
			this.each(function(){
				imgeditors.push($(this).data('imgeditor') );
			});
			return imgeditors;
		}
		return $(this).data('imgeditor');

	}

	// 初始化所有元素
	this.each(function(){
		$(this).data('imgeditor', init( $(this), option ) );
	});


	/**
	 * 初始化
	 * @param  {[type]} em     [description]
	 * @param  {[type]} option [description]
	 * @return {[type]}        [description]
	 */
	function init( em, option ) {
		var inst = new imgeditor( em, option );
		inst.canvasInit() // 初始化画布
			.panelInit()  // 初始化属性面板
			.helperInit()  // 初始化助手界面
			;

		var ready = inst.get('ready');
		if ( typeof ready == 'function' ) {
			ready( inst );
		}

		inst.updatePage({
			id:inst.get('id'),
			index:inst.get('index'),
			title:inst.get('title'),
			bgimage:inst.get('bgimage'),
			bgcolor:inst.get('bgcolor')
		}, function(){

			inst.setId(inst.get('id'));
			inst.setIndex(inst.get('index'));

			// 载入 Items 
			var items = inst.get('items');
			for(var idx in items ){
				var it = items[idx];
				debug.info('load item :', it[0], it[1], it[2] );
				try {$imgeditor.add(it[0], it[1], it[2]);}catch(e){
					debug.info('load items error =', e);
				}
			}

			// disabled 
			if ( inst.get('disabled')  === true ) {
				inst.disabled();
			}

			var load = inst.get('load');
			if ( typeof load == 'function' ) {
				load( inst );
			}

		}, 'init');

		return inst;
	}



	/**
	 * 管理面板
	 * @param  {[type]} em [description]
	 * @return {[type]}    [description]
	 */
	function panel( em ) {
		
		var that = this;
		this.$em = $(em);
		this.events = {
			load: function(data){},
			show: function(){},
			hide: function(){},
			update: function(event, data){},
			remove: function(event, id ){},
			'upload.click': function( event, id) {}
		};


		$('.fn-update', this.$em).click(function(event) {
			that.events['update']( event, $('input[data-name=id]', that.$em).val(), that.serializeArray() );
		});

		$('.fn-remove', this.$em).click(function(event) {
			that.events['remove']( event, $('input[data-name=id]', that.$em).val() );
		});

		$('.fn-upload', this.$em ).click(function(event) {
			that.events['upload.click']( event, $('input[data-name=id]', that.$em).val() );
		});

		$('.fn-save', this.$em ).click(function(event) {
			that.events['save.click']( event, $('input[data-name=id]', that.$em).val(), that.serializeArray()  );
		});


		this.load = function( id, data, pos ) {
			data = data || {};
			pos = pos || {};

			if( typeof id == 'object' && id != null ) {  // 没有ID清空
				pos = data || {};
				data = id;
				id = null;
			}

			// $form  = that.$em.children('form');
			$form = $('form', that.$em );

			if ( typeof id != 'undefined' && id != null ) {
				if ( $('input[data-name=id]', that.$em).length == 0 ) {
					$form.append('<input type="hidden" data-name="id" value="'+ id +'" />');
				} else {
					$('input[data-name=id]', that.$em).val(id);
				}
			}

			// 设置属性
			for( var name in data  ) {
				$input = $('[name='+name+']', $form);
				if  ( $input.length == 0 ) {
					continue;
				}
				if ( $input.prop('tagName') == 'INPUT' && $input.attr('type') == 'text' ) {

					if ( $input.parent().hasClass('js-colorpicker') ) {
					// 颜色选择器
						$input.parent().colorpicker( 'setValue',data[name]);
					} else {

					// 普通文本
						$input.val( data[name] );
					}
				} else if ( $input.prop('tagName') == 'TEXTAREA'  ) {
						$input.val( data[name] );
				
				} else if ( $input.prop('tagName') == 'SELECT' ) {  // 选择器

					// SELECT 2
					$input.val(data[name]).trigger('change');
				}
			}


			// 设置位置
			for ( var name in pos  ){
				$input = $('[name='+name+']', $form);
				if  ( $input.length == 0 ) {
					continue;
				}
				if ( $input.prop('tagName') == 'INPUT' && $input.attr('type') == 'text' ) {
					$input.val( pos[name] );
				}
			}


			that.events['load']( id, data, pos );
			return that;
		}

		this.show = function() {
			that.$em.removeClass('hidden').show();
			that.events['show']();
			return that;
		}

		this.hide = function(){
			that.$em.addClass('hidden').hide();
			that.events['hide']();
			return that;
		}

		this.on = function( name, fn ){
			if ( typeof fn  == 'function') {
				that.events[name] = fn;
			}
		}

		/**
		 * 处理表单
		 * @return {[type]} [description]
		 */
		this.serializeArray = function() {

			$form  = that.$em.children('form');

			var data = {};
			function setVal( n, data, v ) {

				if ( n.slice(-2) == "[]") {
					n = n.slice(0,-2);
					if ( typeof data[n] == 'undefined' ) {
						data[n] = [];
					}
					data[n].push(v);
				} else {
					data[n] = v;
				}
			}

			$.each( $form.serializeArray(), function(index, val){
				var n = val['name'];
				if ( $('select[name="'+n+'"]', $form).hasClass('js-select2') ) {
					setVal(n, data, $('select[name="'+n+'"]', $form).val() );
				} else {
					setVal(n, data, val['value'] );
				}
			});

			return data;
		}
	}



	/**
	 * ImgEditor 对象
	 * @param  {[type]} em     [description]
	 * @param  {[type]} option [description]
	 * @return {[type]}        [description]
	 */
	function imgeditor(em, option ) {
		var that = this;
		var defaults = {
			disabled:false,
			action: null,
			toolbar: null,
			api: {
				text: null,
				qrcode: null
			},

			helper: {
				em: $('.imgeditor-helper')
			},

			panel: {
				image: $('.imgeditor-panels  .image'),
				text:  $('.imgeditor-panels .text'),
				qrcode: $('.imgeditor-panels .qrcode'),
				page: $('.imgeditor-panels .qrcode')
			},
			canvas: {
				width: 'auto',
				height: 'auto'
			},
			id:null,
			index:null,
			bgimage:'',
			bgcolor:'rgba(255,255,255,0)',
			title:'未命名',
			zoom:1,
			action:null,
			items:[],
			ready:function(){},
			load:function(){}
		};

		/**
		 * 配置项
		 * @type {[type]}
		 */
		this.option = $.extend({}, defaults, option );


		/**
		 * 图集ID
		 * @type {[type]}
		 */
		this.id = null;


		/**
		 * 当前图片索引 ( 绑定后同步更新 )
		 * @type {Number}
		 */
		this.index = null;


		/**
		 * 编辑器元素
		 * @type {[type]}
		 */
		this.$em = em;


		/**
		 * 画布元素
		 * @type {[type]}
		 */
		this.$canvas = null;


		/**
		 * 助手界面元素
		 * @type {[type]}
		 */
		this.$helper = null;


		/**
		 * 参数面板元素
		 * @type {[type]}
		 */
		this.$panels = {
			image: null,
			text: null,
			qrcode: null,
			page:null
		};


		/**
		 * 工具栏
		 * @type {[type]}
		 */
		this.$toolbar = this.option['toolbar'];


		/**
		 * 所有元素
		 * @type {Array}
		 */
		this.items = {};

		/**
		 * 选中的元素
		 * @type {[type]}
		 */
		this.selected = null;


		/**
		 * 当前页面数据结构
		 * @type {Object}
		 */
		this.page = {
			id:null,
			index:null,
			title:null,
			bgimage:null,
			origin:null,
			bgcolor:'rgba(255,255,255,0)',
		};

		/**
		 * 事件列表
		 */
		this.events = {
			zoom: function( zoom ) {},
			import: function( event ) {},
			load: function(layer) {}
		};

		

		/**
		 * 各种标记
		 */
		this.flag = {
			draggable: true,  // 可拖拽
			resizeable: true,  // 可调整大小
			locked: false  // 不可以添加元素
		}



		var genid = function() {
			id = Date.parse(new Date()) + parseInt(Math.random()*10000);

			return id;
		}


		/**
		 * 移动背景
		 * @param  {[type]} target [description]
		 * @param  {[type]} dx     [description]
		 * @param  {[type]} dy     [description]
		 * @return {[type]}        [description]
		 */
		var moveto = function( target,  dx, dy , source) {
			
			if ( that.flag['draggable'] == false ) {
				return;
			}

			var $target = $(target);
			x = (parseFloat($target.attr('data-x')) || 0) + dx,
			y = (parseFloat($target.attr('data-y')) || 0) + dy;

			// translate the element
			// target.style.webkitTransform =
			// target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';
			$target.css('transform','translate(' + x + 'px, ' + y + 'px)');
			$target.css('webkitTransform','translate(' + x + 'px, ' + y + 'px)');
			// update the posiion attributes
			$target.attr('data-x', x);
			$target.attr('data-y', y);



		}




		/**
		 * 移动 Item 
		 * @param  {[type]} target [description]
		 * @param  {[type]} dx     [description]
		 * @param  {[type]} dy     [description]
		 * @return {[type]}        [description]
		 */
		var imoveto = function( target, dx, dy , source) {

			source = 'move';

			if ( that.flag['draggable'] == false ) {
				return;
			}

			x = (parseFloat(target.getAttribute('data-x')) || 0) + dx,
			y = (parseFloat(target.getAttribute('data-y')) || 0) + dy;

			// translate the element
			target.style.webkitTransform =
			target.style.transform = 'translate(' + x + 'px, ' + y + 'px)';

			// update the posiion attributes
			target.setAttribute('data-x', x);
			target.setAttribute('data-y', y);

			var id = target.getAttribute('data-id') || null;
			if (id != null && typeof that.items[id] != 'undefined' ) {
				that.update(id, {}, {x: parseInt(x/that.zoom()), y: parseInt(y/that.zoom()) }, source );
			}
		}



		/**
		 * 调整 Item 大小
		 * @param  {[type]} target [description]
		 * @param  {[type]} width  [description]
		 * @param  {[type]} height [description]
		 * @param  {[type]} zoom   [description]
		 * @return {[type]}        [description]
		 */
		var imgresizeto = function ( target, width, height, zoom, source ) {

			source = 'resize';
			
			if ( that.flag['resizeable'] == false ) {
				return;
			}

			
			zoom = zoom || 1;
			x = (parseFloat(target.getAttribute('data-x')) || 0),
			y = (parseFloat(target.getAttribute('data-y')) || 0);

			// update the element's style
			target.style.width  = width + 'px';
			target.style.height = height + 'px';

			target.style.webkitTransform = target.style.transform =
				    'translate(' + x + 'px,' + y + 'px)';

			target.setAttribute('data-x', x);
			target.setAttribute('data-y', y);

			target.setAttribute('data-width', width / zoom );
			target.setAttribute('data-height', height / zoom );

			var id = target.getAttribute('data-id') || null;
			if (id != null && typeof that.items[id] != 'undefined' ) {
				that.update(id, 
					{width: parseInt(width / zoom), height: parseInt(height / zoom) }, 
					{x: parseInt(x/zoom), y: parseInt(y/zoom) 
				}, source);
			}
		}


		/**
		 * 字体参数
		 * @type {Object}
		 */
		var textparams = {
			'width':true, 'height':true, 'origin':true,
			'text':true, 
			'font':true, 'size':true, 'color':true, 'background':true, 
			'align':true, 'valign':true, 'type':true, 'dir':true,
			'line':true, 'space':true
		};


		/**
		 * 二维码参数
		 * @type {Object}
		 */
		var qrcodeparams = {
			'width':true, 'height':true, 'origin':true,
			'text':true, 'params':true,
			'logo':true, 'logowidth':true,
			'color':true, 'background':true,
			'type':true,
			'config':true, 'appid': true,'secret':true
		};




		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		// API 
		// ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ==== ====
		

		/**
		 * 根据参数初始化画布
		 * @return {[type]} [description]
		 */
		this.canvasInit = function() {
			that.$em.css('margin', 0)
					.css('padding', 0)
					.css('overflow', 'hidden');

			// 计算宽高
			var w = that.option.canvas.width;
			var h = that.option.canvas.height;
			if ( w == 'auto' ) {
				w = that.$em.width();
			} else if ( w == 'screen' ) {
				var offsetX = that.option.canvas.offsetX || 0;
				var ww = $(window).width();
				w = ww - offsetX;
			}

			if ( h == 'auto' ) {
				h = that.$em.height();
			} else if ( h == 'screen' ) {
				var offsetY = that.option.canvas.offsetY || 0;
				var wh = $(window).height();
				h = wh - offsetY;
			}

			// 设定背景栅格
			that.$em.css('width', w)
				.css('height', h)
				.css('background', '#ccc') // 5c90d2
				.css('background-image', 'linear-gradient(white 2px,transparent 0),linear-gradient(90deg, white 2px,transparent 0),linear-gradient(hsla(0,0%,100%,.3) 1px,transparent 0),linear-gradient(90deg,hsla(0,0%,100%,.3) 1px,transparent 0)')
				.css('background-size', '75px 75px,75px 75px,15px 15px,15px 15px' );


			// 单击背景时，显示背景面板
			if ( that.$em.data('init') != 1 ) {
				
				that.$em.data('init', 1 );

				that.$em.click(function(event) {
					that.deselect(that.selected, 'user');
					that.panelShow('page', {},{}, 'user');
				});
				

				// 鼠标按下时隐藏面板
				that.$em.mousedown(function(event) {
					that.panelHide('user');
				});

				// 鼠标松开时显示面板
				that.$em.mouseup(function(event) {
					that.panelToggle('user');
				});
			}

			return that;
		}


		/**
		 * 初始化助手面板
		 * @return {[type]} [description]
		 */
		this.helperInit = function(){
			
			if ( this.option.helper['em'].length == 0 ) {
				return that;
			}

			that.$helper = that.option.helper['em'];
			that.$helper
				.css('margin', 0)
				.css('padding', 0)
				.css('overflow', 'hidden')
				.css('width', that.$em.width() )
				.css('height', that.$em.height() )
				.attr('data-height', that.$em.height() )
				.attr('data-width', that.$em.width() )
				;

			return that;
		}


		/**
		 * 打开 Helper 页面
		 * @param  {[type]}   page     [description]
		 * @param  {[type]}   query    [description]
		 * @param  {Function} callback [description]
		 * @return {[type]}            [description]
		 */
		this.open = function( page, query, callback ) {

			if ( that.$helper == null ) return;

			query = query || {};
			callback = callback || function(){}

			var pages = that.option.helper['pages'] || {};
			if ( typeof pages[page] == 'string' ) {
				var data = $.extend({id:that.getId(), index:that.getIndex() }, query || {});
				that.$helper.load(pages[page], data, callback);
			} else if  ( typeof  pages[page] == 'object' ) {
				var url = pages[page]['url'] || null;
				var data = $.extend({id:that.getId(), index:that.getIndex()}, pages[page]['query'] || {}, query);
				that.$helper.load(url, data, callback);
			}
		}


		/**
		 * 锁定 编辑区
		 */
		this.disabled = function() {

			if ( $('.mask', that.$em).length >= 1  ){
				return ;
			}

			var offsetH = 0;
			if ( that.$toolbar != null ) {
				offsetH = that.$toolbar.outerHeight();
			}

			var node = $('<div class="mask" style="'  +
							'position:absolute;' +
							'background:rgba(0,0,0,0.3);' +
							'z-index:1030;' +
							'top:0px;' +
							'width:' + that.$em.outerWidth() + 'px;' +
							'height:' + (that.$em.outerHeight() + offsetH) + 'px;' +
					   '"></div>');
			
			node.click(function(event){
				event.stopPropagation();
			});

			node.mouseup(function(event){
				event.stopPropagation();
			})

			node.mousedown(function(event){
				event.stopPropagation();
			})
			that.$em.append(node);
		}


		/**
		 * 打开 编辑区
		 */
		this.enabled = function() {
			$('.mask', that.$em).remove();
		}


		/**
		 * 隐藏 编辑区
		 * @return {[type]} [description]
		 */
		this.min = function() {
			
			var parent = that.$em.parent();
			if ( parent.is($("[class*='col-']")) ) {

				parent.removeClass(function (index, className) {
					var cols = className.match (/(^|\s)col-\S+/g) || [];
					for( var i in cols ) {
						if ( parent.attr('data-col')  == null ) {
							parent.attr('data-col', cols[i]);
						}

						parent.attr('data-border-left', parent.css('border-left') );
						parent.attr('data-border-right', parent.css('border-right') );
					}
					return cols.join(' ');
				}).addClass('col-zero').addClass('hidden');

			} else {
				that.$em.addClass('hidden');
			}


			if ( that.$helper != null ) {
				var h = that.$helper.parent().parent();
				h.removeClass(function (index, className) {
					var cols = className.match (/(^|\s)col-\S+/g) || [];
					for( var i in cols ) {
						if ( h.attr('data-col')  == null ) {
							h.attr('data-col', cols[i]);
						}
					}
					return cols.join(' ');
				}).addClass('col-xs-12').removeClass('hidden');
			}

		}


		/**
		 * 显示 编辑区
		 * @return {[type]} [description]
		 */
		this.normal = function(){
			var parent = that.$em.parent();

			if ( parent.is($("[class*='col-']")) ) {



				parent.removeClass(function (index, className) {
					var cols = className.match (/(^|\s)col-\S+/g) || [];
					if ( parent.attr('data-col') == null ) {
						parent.attr('data-col', cols[0] );
					}
					return cols.join(' ');
				}).addClass(parent.attr('data-col')).removeClass('hidden');

			} else {
				that.$em.removeClass('hidden');
			}

			if ( that.$helper != null ) {
				var h = that.$helper.parent().parent();
				h.removeClass(function (index, className) {
					var cols = className.match (/(^|\s)col-\S+/g) || [];
					if ( h.attr('data-col') == null ) {
						h.attr('data-col', cols[0] );
					}
					return cols.join(' ');
				}).addClass(h.attr('data-col'));
				h.removeClass('hidden');
			}
		}

		/**
		 * 铺满整个屏幕
		 * @return {[type]} [description]
		 */
		this.max = function(){
			
			var parent = that.$em.parent();
			if ( parent.is($("[class*='col-']")) ) {
				parent.removeClass(function (index, className) {
					var cols = className.match (/(^|\s)col-\S+/g) || [];
					for( var i in cols ) {
						if ( parent.attr('data-col')  == null ) {
							parent.attr('data-col', cols[i]);
						}

						parent.attr('data-border-left', parent.css('border-left') );
						parent.attr('data-border-right', parent.css('border-right') );
					}
					return cols.join(' ');
				}).addClass('col-xs-12').removeClass('hidden')	

				parent.css('border-right', 'none');
				parent.css('border-left', 'none');
			} else {
				that.$em.removeClass('hidden');
			}


			if ( that.$helper != null ) {
				var h = that.$helper.parent().parent();
				h.removeClass(function (index, className) {
					var cols = className.match (/(^|\s)col-\S+/g) || [];
					for( var i in cols ) {
						if ( h.attr('data-col')  == null ) {
							h.attr('data-col', cols[i]);
						}
					}
					return cols.join(' ');
				}).addClass('col-zero').addClass('hidden');
			}
		}



		/**
		 * 属性面板初始化
		 * @return {[type]} [description]
		 */
		this.panelInit = function() {

			var panels = this.option.panel;
			for( var name in panels ) {

				if ( typeof panels[name] == 'object' && panels[name].length > 0 ) {

					var node = panels[name].clone();
					node.attr('data-panel', name )
						.css('position', 'absolute')
						.css('z-index', 1029)  // Mask & 导航条之下
						.css('border-top', '1px solid #e9e9e9')
						.css('background', '#f5f5f5')
						.css('box-shadow', '0 0 12px rgba(0, 0, 0, 0.1)' )
						.css('-webkit-box-shadow', '0 0 12px rgba(0, 0, 0, 0.1)' )
						.css('width', '100%')
						.css('bottom', '0px')
						.addClass('option-panel')
						.addClass('hidden')
						.hide()
						;
					
					// 阻止表单冒泡
					node.click(function(event) {

						// 关闭 color picker
						if ( $('.colorpicker').hasClass('colorpicker-visible') === true ) {
							try {$('.js-colorpicker').colorpicker('hide')}catch(e){};
						}

						event.stopPropagation();
					});


					$('.js-colorpicker', node).click(function(event) {
						event.stopPropagation();
					});

					node.mousedown(function(event) {
						event.stopPropagation();
					});
					node.mouseup(function(event) {
						event.stopPropagation();
					});

					that.$em.append(node);
					that.$panels[name] = new panel(node);

					// 绑定事件
					// 更新 Update ( fn-update click)
					that.$panels[name].on('update', function(event, id, data ){ 


						var item = that.items[id] || {};
						var name = item['name'] || 'page';

						var pos = {};
						if ( typeof data['x'] != 'undefined' ) {
							pos['x'] = data['x'];
							delete data['x'];
						}

						if ( typeof data['y'] != 'undefined' ) {
							pos['y'] = data['y'];
							delete data['y'];
						}

						if ( name == 'page' ) {
							that.updatePage( data, null, 'panel' );
							if ( typeof that.events['page.update'] == 'function' ) {
								that.events['page.update']( event, data );
							}
							return;
						}

						that.update( id, data, pos, 'panel');
						if ( typeof that.events[name +'.update'] == 'function' ) {
							that.events[name + '.update']( event, id, data, pos );
						}

					});

					// 删除 Remove ( fn-remove click)
					that.$panels[name].on('remove', function(event, id ){

						that.remove(id, 'user');
						if ( typeof that.events[name +'.remove'] == 'function' ) {
							that.events[name + '.remove']( event, id );
						}
					});

					// 上传  Upload ( fn-upload click)
					that.$panels[name].on('upload.click', function( event, id ) {
						var item = that.items[id] || {};
						var name = item['name'] || 'page';

						if ( typeof that.events[name +'.upload.click'] == 'function' ) {
							that.events[name +'.upload.click']( event, id );
						}
					});


					// 保存 Save ( fn-save click )
					that.$panels[name].on('save.click', function( event, id, data ) {

						var item = that.items[id] || {};
						var name = item['name'] || 'page';
						var callback = '';
						

							var pos = {};
						if ( typeof data['x'] != 'undefined' ) {
							pos['x'] = data['x'];
							delete data['x'];
						}

						if ( typeof data['y'] != 'undefined' ) {
							pos['y'] = data['y'];
							delete data['y'];
						}

						
						// Update 
						if ( name == 'page' ) {
							that.updatePage( data,null, 'panel' );
							if ( typeof that.events['page.update'] == 'function' ) {
								that.events['page.update']( event, data );
							}
						} else {
							that.update( id, data, pos, 'panel');
							if ( typeof that.events[name +'.update'] == 'function' ) {
								that.events[name + '.update']( event, id, data, pos );
							}
						}


						// save
						if ( typeof that.events['save'] == 'function' ) {
							callback = that.events['save']( event, id );
						}
						
						if ( typeof callback != 'function' ) {
							callback = function( status, response ){};
						}

						that.save( callback );

					});
				}
			}

			return that;
		}



		/**
		 * 切换属性面板
		 * @return {[type]} [description]
		 */
		this.panelToggle = function( source ){

			if ( source == 'init' || source == 'silent' ) {
				return that;
			}

			var show = false;
			for ( var name in that.$panels) {
				if ( that.$panels[name] != null ) {
					if ( that.$panels[name].$em.is(":hidden") == false ) {
						show = name;
					}
				}
			}

			if ( show !== false ) {
				that.$panels[show].hide();
				return that;
			}

			var selected = {id:null, name:'page' };
			if ( this.selected != null ) {
				selected = this.items[this.selected];
			}

			if ( that.$panels[selected.name] != null ) {
				that.$panels[selected.name].show();
			}

			return that;
		}


		/**
		 * 显示属性面板
		 */
		this.panelShow = function( id, data, pos, source ) {

			data = data || {};
			pos = pos || {};

			if ( typeof that.items[id] == undefined &&  id !== 'page' ) {
				return that;
			}

			var name = '';
			if ( id == 'page' ) {
				name  = id;
			} else {
				name = that.items[id]['name'];
			}

			if ( typeof that.$panels[name] == 'undefined' || that.$panels[name] == null ) {
				return that;
			}

			if ( id != 'page' )  {
				var option = that.items[id]['option'] || {};
				var params = that.items[id]['params'] || {};
				var position = that.items[id]['pos'] || {};
				data = $.extend(option, params, data );
				pos = $.extend(position, pos);
				that.update( id, data, pos, 'silent' );
			}

		
			if ( source == 'init' || source == 'silent' ) {
				return that;
			}
			

			that.panelHide( source );
			that.$panels[name].show();
			return that;
		}


		/**
		 * 隐藏属性面板
		 * @return {[type]} [description]
		 */
		this.panelHide = function( source ) {

			if ( source == 'init' || source == 'silent' ) {
				return that;
			}

			for ( var name in that.$panels) {
				if ( that.$panels[name] != null ) {
					that.$panels[name].hide();
				}
			}

			return that;
		}	




		/**
		 * 设置背景图片/颜色/alpha
		 * @param  string bg "#FFFF00", "RGB(0,21,2,0.5)"
		 * @return this
		 */
		this.background = function( bg, color, callback ) {


			bg = bg || null; 
			color = color || null;
			callback = callback || function(){}

			if ( bg == null && color == null ) {
				return that.option.bgimage;
			}

			if ( bg == 'color' && color == null ){
				return that.option.bgcolor;
			}

			that.set('bgimage', bg );
			if ( color != null ) {
				that.set('bgcolor', color );
			}
			

			// 图片背景处理方式
			var $layer = that.$em.children('.imgeditor-background');
				$layer.children('.imgeditor-background-image').remove();

			if ( $layer.length == 0 ) {
				$layer = $('<div class="imgeditor-background" style="position:relative;visibility:hidden"></div>');
				$layer.css('width', 'auto').css('height', 'auto');
				that.$em.append($layer);
			}

			if ( color != null ) {
				$layer.css('background', color);
				that.page['bgcolor'] = color;
			}

			$layer.css('visibility', 'hidden');
			var addbg = function ( $realW, $realH ) {

				$layer.width($realW);
				$layer.height($realH);
				var ox = 100/ $realW;
				var oy = 100/ $realH;

				$layer.attr('data-width', $realW);
				$layer.attr('data-height', $realH);
				$layer.attr('data-offsetx', ox);
				$layer.attr('data-offsety', oy);
				$layer.attr('data-x', 0);
				$layer.attr('data-y', 0);

				that.$canvas = $layer;
				var pos = that.getBGPerfectPos($layer.attr('data-width'), $layer.attr('data-height'));
				// 移动到最佳位置
				moveto( $layer, pos.x, pos.y );

				that.zoom(pos.zoom);

				interact('.imgeditor-background').draggable({
					autoScroll: true,
					inertia: true,
					restrict: {
						restriction: "parent",
						endOnly: true,
						elementRect: { top:1-oy, left:1-ox, bottom: oy, right:ox }
					},
					onmove: function(event){
						moveto( event.target, event.dx, event.dy );
					}
				});

				that.move();
				that.page['bgimage'] = bg;
				callback();
				$layer.css('visibility', 'visible');
			}


			if ( bg == null ) { // 仅有背景色
				addbg( that.$em.width() - 80,   that.$em.height() - 80  );
				return that;
			}

			// 加载图片
			$layer.append('<img class="imgeditor-background-image ">');
			$layer.children('.imgeditor-background-image').attr('src', bg);
			$layer.children('.imgeditor-background-image').on('error',function(event) {
				addbg( that.$em.width() - 80,   that.$em.height() - 80  );
				$(this).addClass('hidden');
			});

			$layer.children('.imgeditor-background-image').on('load',function(event) {
				addbg( $(this).width(), $(this).height());
				$(this).removeClass('hidden');
			});
			return that;
		}


		/**
		 *  读取最佳 Zoom 
		 */
		this.getBGPerfectPos = function( width, height, padding  ) {
			padding = padding || 40;
			var CW= that.$em.width()  -  padding*2;
			var CH = that.$em.height() -  padding*2;
			width = parseInt(width);
			height = parseInt(height);

			if ( width > height ) {  // 以高为准
				var times = CH / height;
					times = times - times % 0.05  + 0.05;

					// console.log( width, height, padding,  times );

				var TW = width * times;
				var x = parseInt(parseFloat((that.$em.width()-TW) / 2).toFixed(0));
				if ( x < 40  ) {
					x = 40;
				}

				return {zoom:times, x:parseInt(x), y:40};
			}


			// 以高为准
			var times = CW / width;
				times = times - times % 0.05  + 0.05;


			var TW = width * times;
			var x = parseFloat((that.$em.width()-TW) / 2).toFixed(0);
			return {zoom:times, x:parseInt(x), y:40};
		}



		/**
		 * 读取最佳位置
		 * @return {[type]} [description]
		 */
		this.getItemPerfectPos = function( width, height  ) {
			
			width = parseInt(width);
			height = parseInt(height);
			
			var CW= that.$em.width();
			var CH = that.$em.height();
			var zoom = this.zoom();

			var BW = parseFloat(that.$canvas.attr('data-width'));
			var BH = parseFloat(that.$canvas.attr('data-height'));
			var BX = parseFloat(that.$canvas.attr('data-x')) || 0;
			var BY = parseFloat(that.$canvas.attr('data-y'))|| 0;

			// 坐标原点
			var X = BX / zoom * -1;
			var Y = BY / zoom * -1;

			var offsetX = 100 / zoom;
			var offsetY = 100 / zoom;

			// console.log( zoom, CW, CH , BW, BH, BX, BY, X, Y , offsetX, offsetY );
			// console.log( {x: X , y:Y }, BX, BY, offsetX, offsetY );
			return {x: X + offsetX , y: Y + offsetY };
			
		}


		/**
		 * 缩放背景图片
		 * @param  {[type]} zoom [description]
		 * @return {[type]}      [description]
		 */
		this.zoom = function( zoom ) {

			if ( this.$canvas == null ) {
				return;
			}

			zoom = zoom || null;
			if ( zoom == null ) {
				return that.option.zoom;
			}

			var lastzoom = that.option.zoom;

			that.set('zoom', zoom );

			// 背景  Zoom 
			var ncw = that.$canvas.attr('data-width') * zoom;
			var nch = that.$canvas.attr('data-height') * zoom;
			that.$canvas.width(ncw);
			that.$canvas.height(nch);
			that.$canvas.children('.imgeditor-background-image').width(ncw);
			that.$canvas.children('.imgeditor-background-image').height(nch);

			// Item Zoom
			var step = zoom - lastzoom;
			
			$('.imgeditor-item').each( function(idx, item ){

				var w = $(item).width();
				var h = $(item).height(); 

				// 计算新大小
				var nw = $(item).attr('data-width') * zoom;
				var nh = $(item).attr('data-height') * zoom;
				$(item).width(nw);
				$(item).height(nh);

				// 计算 新位置
				var x = parseFloat($(item).attr('data-x'));
				var y = parseFloat($(item).attr('data-y'));

				var nx = x/lastzoom  * zoom;
				var ny = y/lastzoom  * zoom;
				$(item).attr('data-x', nx);
				$(item).attr('data-y', ny);
				$(item).css('transform',  'translate(' + nx + 'px,' + ny + 'px)');
				$(item).css('-webkit-transform',  'translate(' + nx + 'px,' + ny + 'px)');

			});

			if ( typeof that.events['zoom'] == 'function') {
				that.events['zoom']( zoom );
			}

			return that;
		}


		// 选中 Item
		this.select = function(id, source) {


			if ( that.selected == id && (source == 'init' || source == 'silent') ) return;

			var lastitem = that.selected;
				that.deselect( lastitem, source );
			if ( source != 'init' && source != 'silent') {
				$('[data-id='+id+']').children('.title').removeClass('hidden').show();
			}

			that.selected = id;
			that.panelShow(id, {},{},  source);

			return that;
		}

		// 取消选中 item
		this.deselect = function(id, source) {

			if ( typeof id == 'undefined' || id == null ) {
				return that;
			}

			if ( source != 'init' && source != 'silent') {
				$('[data-id='+id+']').children('.title').addClass('hidden').hide();	
			}
			
			that.selected = null;
			that.panelHide( source);
			return that;
		}






		/**
		 * 清空所有元素
		 * @return {[type]} [description]
		 */
		this.clean = function( source ) {
			
			source = source || 'user';
			if ( that.islocked() ) {
				return;
			}

			that.lock();
			for ( var id in that.items  ) {
				var item_id = '#item-' + id;
				$item = $(item_id, that.$canvas);
				$item.remove();
				delete that.items[id];
			}

			that.panelShow('page', {},{}, source);
			that.unlock();
			return that;
		}



		/**
		 * 更新页面信息
		 * @param  {[type]} data [description]
		 * @return {[type]}      [description]
		 */
		this.updatePage = function( data, callback, source ) {

			source = source || 'silent';
			callback = callback || function(){}
			that.page = $.extend(that.page, data);

			debug.log( ' updatePage', 'source=', source,  'data=', data , ' that.page=', that.page );

			bgcolor =  data['bgcolor'] || null;
			bgimage = data['bgimage'] || null;
			if ( bgcolor != null || bgimage != null ) {
				that.background(bgimage, bgcolor, callback );
			}

			if ( that.$panels['page'] != null ) {
				that.$panels['page'].load(null, that.page );

				if ( source != 'init' && source != 'silent' ) {
					that.$panels['page'].show();
				}
			}
		}



		this.save = function( cb ) {

			if ( that.option.action == null ) {
				console.log('未设定数据服务器');
				return ;
			}

			var api = that.option.action;
			var data = that.getData();

			$.ajax({
				url: api,
				type: 'POST',
				dataType: 'json',
				contentType:'application/json',
				processData: false,
				data:JSON.stringify(data)
			})

			.done(function(data, status, xhr ) {

				if ( typeof data['code'] != 'undefined' && typeof data['message'] != 'undefined' && data['code'] != 0 ) {
					that.$em.trigger('status-change', ['error'] );
					cb(data, 'error', xhr );
					return;
				}

				debug.info('mark needsave =', false);
				that.needsave = false;
				that.$em.trigger('status-change', ['saved'] );
				
				that.setId( data['page']['id'] );

				// that.items = data['items'];
				cb(data, 'success', xhr );
			})

			.fail(function( xhr, status, body ) {
				that.$em.trigger('status-change', ['error'] );
				cb({code:500, message:body}, 'error', xhr );
			});
		}


		this.unlock = function() {
			that.flag.locked = false;
		}

		this.lock = function(){
			that.flag.locked = true;
		}

		this.islocked = function(){
			return that.flag.locked;
		}


		/**
		 * 返回页面 ID
		 * @return {[type]} [description]
		 */
		this.getId = function(){
			if ( that.id == null ) {
				that.setId( genid() );
			}
			return that.id;
		}

		/**
		 * 返回页面 索引
		 * @return {[type]} [description]
		 */
		this.getIndex = function() {
			if ( that.index == null  ) {
				that.setIndex(0);
			}
			return that.index;
		}



		this.setIndex = function( index ) {

			if ( index == null || index == undefined ) {
				that.index = null;
				that.option.index = null;
				that.page['index'] = null;
				that.$em.attr('data-param-index', "" );
			} else {
				that.index =  that.option.index  =  that.page['index']  = index;
				that.$em.attr('data-param-index', index );
			}
		}


		this.setId = function( id ) {

			if ( id == null  || id == undefined ) {
				that.id = null;
				that.option.id = null;
				that.page['id'] = null;
				that.$em.attr('data-param-id', "" );
			} else {
				that.id =  that.option.id  =  that.page['id']  = id;
				debug.log('setId', 
					'       that.id=', that.id,
					' that.option.id=', that.option.id, 
					" that.page['id']=", that.page['id'] , 
					" id=", id );
				that.$em.attr('data-param-id', id );
			}
		}


		this.getData  = function() {

			var data = {
				page: that.page,
				items: that.items
			};

			return data;
		}


		/**
		 * 添加元素
		 * @param {[type]} type   [description]
		 * @param {[type]} option [description]
		 * @param {[type]} pos    [description]
		 */
		this.add = function( type, option, pos, source, callback ) {
			
			source = source || 'silent';
			callback = callback || function(){}

			var id = genid();

			if ( type == 'image' ) {

				that.items[id] = {name:type, option:option, pos:pos };
				that.addImage( id, option, pos, null, source, callback );

			} else if ( type == 'text' ) {  // 插入文字

				if ( that.option.api.text == null ) {
					console.log('未设定字体服务器');
					return ;
				}

				// 允许自由调整大小
				if ( typeof option['preserveAspectRatio'] == 'undefined' )  {
					option['preserveAspectRatio'] = false;
				}

				var params = {
					width:option['width'] || 260,
					height:option['height'] || 36,
					text: option['text'] || "",
					font: option['font'] || 1,  // 黑体
					size: option['size'] || 24,
					color: option['color'] || "rgba(0,0,0,1)",   // 颜色代码 (RGBA)
					background: option['background'] || "rgba(255,255,255,0)",    // 颜色代码 (RGBA)
					align: option['align'] || 'center',  //  水平对齐 center 居中 left 居左  right 居右  top 居上 bottom 居下  
					valign: option['valign'] || 'top' ,  //  垂直对齐 top 居上 bottom 居下  middle 居中
					type: option['type'] || 'horizontal', // 排版方式 vertical 竖排  horizontal 横排
					dir: option['dir'] || 'ltr',  // 文字方向 ltr 左向右 rtl 右向左
					line: option['line'] || 1.2,  // 行高/宽
					space: option['space'] || 0.2  // 文字间距
				};

				option['src'] =  that.option.api.text + '?' + $.param(params);


				// 调整大小后重新设定样式
				option['resizeend'] = function( event,  option, zoom ) {

					option = option || {};
					var target = event.target;
					var params = $.extend({},that.items[id]['params']);
					for ( var attr  in option ) {
						if ( textparams[attr] == true ){
							params[attr] = option[attr];
						}
					}

					params['width'] =target.getAttribute('data-width');
					params['height'] =target.getAttribute('data-height');

					var item_id = target.getAttribute('id');
					var nsrc = that.option.api.text + '?' + $.param(params);
					$('#' + item_id).children('img').attr('src', nsrc);

					that.items[id]['params'] =params;
				}


				that.items[id] = {name:type, option:option, pos:pos, params:params };
				this.addImage(id, option, pos, '文本', source, callback);

			} else if ( type == 'qrcode' ) {  // 插入二维码

				if ( that.option.api.qrcode == null ) {
					console.log('未设定二维码生成服务器');
					return id;
				}

				option['height'] = option['width'];

				// 不允许调整大小
				option['preserveAspectRatio'] = true;

				var params = {
					text: option['text'] || "",
					width:option['width'] || 300,
					height:option['width'] || 300,
					logo:option['logo'] || "",
					logowidth: option['logowidth'] || 50,
					color: option['color'] || "rgba(0,0,0,1)",   // 颜色代码 (RGBA)
					background: option['background'] || "rgba(255,255,255,0)",    // 颜色代码 (RGBA)
					type: option['type'] || 'url',  // url/wxapp/wechat/text,
					config: option['config'] || null,  // 配置信息
					appid: option['appid'] || null,  // 应用 ID
					secret: option['secret'] || null,  // secret ID
				};

				option['src'] =  that.option.api.qrcode + '?' + $.param(params);

				// 调整大小后重新设定样式
				option['resizeend'] = function( event,  option, zoom ) {

					option = option || {};
					var target = event.target;
					var params = $.extend({},that.items[id]['params']);
					for ( var attr  in option ) {
						if ( qrcodeparams[attr] == true ){
							params[attr] = option[attr];
						}
					}

					params['width'] =target.getAttribute('data-width');
					params['height'] =target.getAttribute('data-width');

					var item_id = target.getAttribute('id');
					var nsrc = that.option.api.qrcode + '?' + $.param(params);
					$('#' + item_id).children('img').attr('src', nsrc);

					that.items[id]['params'] =params;
				}

				that.items[id] = {name:type, option:option, pos:pos, params:params };
				this.addImage(id, option, pos, '二维码', source, callback);
			}


			if ( typeof that.events['add'] == 'function') {
				that.events['add']( id, type, option, pos, source );
			}

			return id;
		}



		/**
		 * 更新
		 * @param  {[type]} id     [description]
		 * @param  {[type]} option [description]
		 * @param  {[type]} pos    [description]
		 * @return {[type]}        [description]
		 */
		this.update = function( id, option, pos, source, callback ) {

			if ( typeof that.items[id] == 'undefined') return that;
			callback = callback || function(){}
			source = source || 'silent';
			option = option || {};
			pos = pos || {};

			var type = that.items[id]['name'];
			var old = $.extend({},that.items[id]);
			var $item = $('#item-' + id, that.$canvas );

			that.items[id]['pos'] = that.items[id]['pos'] || {};
			
			// 插入图片
			if ( type == 'image' ) {
				that.updateImage( id, option, pos, old, source, callback );
				that.items[id] = {
					name:type, 
					option:$.extend(that.items[id]['option'], option), 
					pos:$.extend(that.items[id]['pos'], pos)
				};

				if ( that.$panels['image'] != null ) {
					that.$panels['image'].load( id, option, pos );
				}

			// 插入文字
			} else if ( type == 'text' ) { 

				var params = $.extend({},that.items[id]['params']);
				for ( var attr  in option ) {
					if ( textparams[attr] == true ){
						params[attr] = option[attr];
					}
				}
				// params['t'] = Date.parse(new Date());
				
				if ( $item.data('resizeing') != 1 ) { 
					option['src'] =  that.option.api.text + '?' + $.param(params);
				}

				that.updateImage( id, option, pos, old, source, callback );
				that.items[id] = {
					name:type, 
					params: params,
					option:$.extend(that.items[id]['option'], option), 
					pos:$.extend(that.items[id]['pos'], pos)
				};

				if ( that.$panels['text'] != null ) {
					that.$panels['text'].load( id, option, pos );
				}

			// 插入二维码
			} else if ( type == 'qrcode' ) {

				var params = $.extend({},that.items[id]['params']);

				if ( typeof option['width'] != 'undefined' ) {
					option['height'] = option['width']
				}

				for ( var attr  in option ) {
					if ( qrcodeparams[attr] == true ){
						params[attr] = option[attr];
					}
				}
				// params['t'] = Date.parse(new Date());

				if ( $item.data('resizeing') != 1 ) { 
					option['src'] =  that.option.api.qrcode + '?' + $.param(params);
				}


				that.updateImage( id, option, pos, old, source, callback);
				that.items[id] = {
					name:type, 
					params: params,
					option:$.extend(that.items[id]['option'], option), 
					pos:$.extend(that.items[id]['pos'], pos)
				};

				if ( that.$panels['qrcode'] != null ) {
					that.$panels['qrcode'].load( id, option, pos );
				}
			}


			if ( typeof that.events['update'] == 'function') {
				that.events['update']( id, type,  option, pos, source );
			}

			return that;

		}

		/**
		 * 删除
		 * @param  {[type]} id [description]
		 * @return {[type]}    [description]
		 */
		this.remove = function( id, source ) {
			if ( typeof that.items[id] == 'undefined') return that;
			source = source || 'silent';

			var item_id = '#item-' + id;
			that.deselect(id, source);
			$item = $(item_id, that.$canvas);
			$item.remove();
			delete that.items[id];

			var flag = 0;

			// 选中下一个元素
			for( var idx in that.items ) {
				// 添加完毕后选中 item
				that.select(idx, source);
				flag = 1;
				break;
			}

			// 如没有任何元素则显示主题面板
			if ( flag === 0 ) {
				that.panelShow('page', {}, {}, source);
			}

			if ( typeof that.events['remove'] == 'function') {
				that.events['remove']( id, source );
			}
			return that;
		}


		/** 
		 * 更新图片
		 */
		this.updateImage = function( id, option, pos, old, source, callback ) {

			if ( that.islocked() ) {
				return;
			}

			if ( typeof that.items[id] =='undefined') {
				return;
			}

			callback = callback || function(){}
			source = source || 'silent';
			option = option || {};
			pos = pos || {};

			var item_id = '#item-' + id;
			$item = $(item_id, that.$canvas);

			for ( var attr in option ) {
				if ( attr == 'resizeend' ) {
					continue;
				}
				$item.attr( 'data-' + attr, option[attr]);
			}


			// 替换图片
			// if ( typeof option['src'] != 'undefined' 
			// 	 && old['option']['src'] != option['src']  ) {
			// 	$('img', $item).attr('src', option['src']);
			// 	var src = $('img', $item).attr('src');
			// }

			if ( typeof option['src'] != 'undefined' && option['src'] != null && option['src'] != false) {
				var src = $('img', $item).attr('src');
				if ( src != option['src'] ) {
					$('img', $item).attr('src', option['src']);
				}
			}

			var zoom = this.zoom();
			if ( typeof  option['width']  != 'undefined' ) {
				$item.width( option['width'] * zoom );
			}

			if ( typeof  option['height']  != 'undefined' ) {
				$item.height( option['height'] * zoom );
			}

			if ( typeof option['alpha']  != 'undefined' ) {
				$item.children('.image').css('opacity', option['alpha'] );
			}

			var x = that.items[id]['pos']['x'];
			var y = that.items[id]['pos']['y'];
			if ( typeof  pos['x']  != 'undefined' ) {
				x = pos['x'] * zoom;
				$item.attr('data-x',  x);
				$item.css('transform',  'translate(' + x + 'px,' + y + 'px)');
				$item.css('-webkit-transform',  'translate(' + x + 'px,' + y + 'px)');
			}

			if ( typeof  pos['y']  != 'undefined' ) {
				y = pos['y'] * zoom;
				$item.attr('data-y', y );
				$item.css('transform',  'translate(' + x + 'px,' + y + 'px)');
				$item.css('-webkit-transform',  'translate(' + x + 'px,' + y + 'px)');
			}

		}


		/**
		 * 插入图片
		 * @param {[type]} option [description]
		 * @param {[type]} pos    [description]
		 */
		this.addImage = function( id, option, pos, type, source, callback ) {

			if ( that.islocked() ) {
				return;
			}
    
	
			if ( option['width'] == 'undefined') {

				return;
			}

			callback = callback || function(){}
			source = source || 'silent';
			type  = type  || '图片';

			option['width'] = option['width'] || null;
			option['height'] = option['height'] || null;
			option['alpha'] = option['alpha'] || 1;

			if ( typeof option['preserveAspectRatio'] == 'undefined' )  {
				option['preserveAspectRatio'] = true;
			}

			var item_id = 'item-' + id;
			
			// 添加元素
			var $item = $('<div id="'+item_id+'" class="imgeditor-item"  style="top:0px; left:0px;border:1px dashed #e9e9e9;position:absolute;" >' +
					    	'<div class="title" style="top:-20px;position:absolute;" >'+
					    		'<span class="label label-primary"> '+type+ ' #'+id+'</span>' +
					    	'</div>'+
					    	'<img class="image" src="'+ option['src'] + '"  style="width:100%;height:100%;" >' +
					      '</div>');

			for ( var attr in option ) {
				if ( attr == 'resizeend' ) {
					continue;
				}
				$item.attr( 'data-' + attr, option[attr]);
			}


			var add = function( $img, realW, realH ) {

				$img.addClass('hidden').hide().removeAttr('style');
				$img.css('width', '100%').css('height', '100%').removeClass('hidden').show();

				option['width'] = option['width'] || realW;
				option['height'] = option['height'] || realW;

				// 计算宽高和位置
				pos = pos || {};
				if (typeof pos['x'] == 'undefined' || typeof pos['y'] == 'undefined' ) {
					pos = that.getItemPerfectPos( option['width'], option['height'] );
				}

				var zoom = that.zoom();
				var w = option['width'] * zoom;
				var h = option['height'] * zoom;

				var x = parseFloat(pos.x * zoom) ;
				var y = parseFloat(pos.y * zoom) ;


				if ( $item.data('image-loaded') != 1 ) {  // 首次载入/创建时

					// 应用默认值
					$item.width(w);
					$item.height(h);
					$item.children('.image').css('opacity', option['alpha'] );
					$item.css('transform',  'translate(' + x + 'px,' + y + 'px)');
					$item.css('-webkit-transform',  'translate(' + x + 'px,' + y + 'px)');
					$item.attr('data-x', x);
					$item.attr('data-y',y);
					$item.attr('data-id',id);
					$item.children('.title').hide();
					that.$canvas.append($item);

					// 当点击时选中 item;
					$('#' + item_id).click(function( event ) {
						that.select( $(this).attr('data-id'), 'user' );
						event.stopPropagation();
					});

					// item 可移动
					interact('#' + item_id ).draggable({
						autoScroll: true,
						inertia: true,
						restrict: {
							restriction: "parent",
							endOnly: true,
							elementRect: { top:0, left:0, bottom: 1, right:1 }
						},
						onmove: function(event){
							imoveto( event.target, event.dx, event.dy, source );
						}
					}).resizable({
						preserveAspectRatio: option['preserveAspectRatio'],
						edges: { left: false, right: true, bottom: true, top: false },

					}).on('resizemove',function(event) {
						$item.data('resizeing', 1);
						imgresizeto( event.target, event.rect.width, event.rect.height, that.zoom(), source);

					}).on('resizeend', function(event){
						$item.data('resizeing', 0);

						if ( typeof option['resizeend'] == 'function') {
							option['resizeend']( event, option, that.zoom() );
						}
					});

					that.update(id,{}, {
						x: parseInt(pos.x),
						y: parseInt(pos.y)
					}, source);

					$item.data('image-loaded', 1);

					// 添加完毕后选中 item
					that.select(id, source);
					callback( id, source );
					

				} else if ( type == '图片') {  // 更换图片 (按比例缩放)
					
					var theIdx = $item.attr('data-id') || null;
					if ( theIdx == null ) return;
					var theItem = that.items[theIdx] || null;
					if ( theItem == null ) return;

					var currW = theItem['option']['width'] || realW;
					var currH = theItem['option']['height'] || realH;
					var alpha = theItem['option']['alpha'] || 1;

					var fixW = realW, fixH = realH;
					if ( realW > realH ) {  // 以宽为准
						fixW = currW;
						fixH = parseInt(realH/realW * fixW);
					} else {  // 以高为准
						fixH = currH;
						fixW = parseInt(realW/realH * fixH);
					}

					that.update(theIdx, {
						width:fixW,
						height:fixH,
						alpha:alpha
					}, source);
					callback( id, source );
				}
			}


			$('img', $item).on('error', function( error ) {
				
				var realW = option['width'] || 100;
				var realH = option['height'] || 100;
				add( $(this), realW, realH );
				$item.css('background', '#d26a5c');
				$(this).addClass('hidden');
			});


			$('img', $item).on('load',  function( xhr ) {
				$item.css('background', '');
				var realW =  this.width;
				var realH =  this.height;
				add( $(this), realW, realH );
			});

		}


		this.move  = function() {
			that.flag['draggable'] = true;
		}

		this.on = function ( name, fn ) {
			if ( typeof name != 'string') {
				return that;
			}

			if ( typeof fn != 'function') {
				return that;
			}

			that.events[name]  = fn;
			return that;

			var namer = name.split('.');
			if ( namer.length == 1  ) { // Editor
				that.events[name]  = fn;	

			} else if ( namer.length == 2 ){  // Panel
				var p =  namer[0];
				var evt = namer[1];
				if ( typeof that.$panels[p] != 'undefined') {
					that.$panels[p].on( evt, fn );	
				}
			}

			return that;
		}



		this.set = function( name, value) {

			if ( typeof value != 'undefined' && typeof name != 'undefined') {
				that.option[name] = value;
			}

			return this;
		}

		this.get = function ( name ) {
			if ( typeof name != 'undefined') {
				return that.option[name];
			}
		}

	}

}









