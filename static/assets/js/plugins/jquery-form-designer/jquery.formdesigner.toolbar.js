/**
 * FormDesigner 工具箱
 * @param  {[type]} option [description]
 * @return {[type]}        [description]
 */
jQuery.fn.toolbar = function( option ) {

	var self = this;
	var panel = $($(self).attr('href'));
	var block =  $(self).parent().parent().parent();
	var type = option['type'] || 'comcreate'; 
	var form = option['form'] || 'body';
		if ( typeof form == 'string' ) {
			form = $('#'+form);
		}

		$(this).data('vars', {panel:panel, block:block,type:type,form:form});


	/**
	 * 组件面板滚动条
	 * @param  {[type]} obj    [description]
	 * @param  {[type]} option [description]
	 * @return {[type]}        [description]
	 */
	var slimscroll = function( obj, option ) {
		if ( block.data('fixheight') === true )  {
			return ;
		}

		option = option || [];
		var $this       = $(obj);
        var $height     = option['height'] || '200px';
    	var $size       = option['size'] || '5px';
    	var $position   = option['position'] || 'right';
    	var $color      = option['color'] || '#000';
    	var $avisible   = option['always-visible'] ||  false;
    	var $rvisible   = option['rail-visible'] ||  false;
    	var $rcolor     = option['rail-color']  ||  '#999';
    	var $ropacity   = option['rail-opacity']||  0.3;

    	$this.slimScroll({
    	    height: $height,
    	    size: $size,
    	    position: $position,
    	    color: $color,
    	    alwaysVisible: $avisible,
    	    railVisible: $rvisible,
    	    railColor: $rcolor,
    	    railOpacity: $ropacity
    	});
	}


	/**
	 * 载入组件配置面板
	 * @param  {[type]} inst 以创建实例
	 * @return {[type]}      [description]
	 */
	var loadInst = function( inst ) {
		
		var parent = inst.parent(),
	    	id = inst.attr('data-id'),
	    	srcid = inst.attr('data-srcid');

	    // 根据模板 读取通用配置项
	    var stdsetting = $('.std-setting-template', block).attr('data-setting');
	    if ( stdsetting != null ){
	    	$('.std-setting', panel).html( stdsetting );
	    }

	    // 根据srcid, 读取扩展配置项
	    var extsetting = $('#'+srcid).attr('data-setting');
	    if ( extsetting != null ) {
	    	$('.ext-setting', panel).html( $(extsetting) );
	    }

	    // 根据配置菜单，设定函数
	    $('.option', panel).each(function() {
	    	
	    	var $this = $(this);
	    	var $target = inst;
	    	var tgetFunStr = $(this).attr('data-tget');
	    	var tsetFunStr = $(this).attr('data-tset');
	    	var getFunStr = $(this).attr('data-get');
	    	var setFunStr = $(this).attr('data-set');
	    	var eventStr = $(this).attr('data-event');
	    	var targetStr = $(this).attr('data-target');

	    	if ( tgetFunStr == null ) {
	    		tgetFunStr = "function(target, form){ return $(target).html();}";
	    	}
	    	if ( tsetFunStr == null ) {
	    		tsetFunStr = "function(target, value, form){ $(target).html(value);}";
	    	} 

	    	if ( getFunStr == null ) {
	    		getFunStr = "function( source ){ return $(source).val(); }";
	    	} 

	    	if ( setFunStr == null ) {
	    		setFunStr = "function( source, value ){ $(source).val(value); }";
	    	}

	    	if ( eventStr == null ) {
	    		eventStr = "keyup";
	    	}

	    	if ( targetStr == null ) {
	    		targetStr = ".SLabel";
	    	}
	    	

	    	if ( targetStr == null || eventStr == null || tgetFunStr == null || tsetFunStr == null || getFunStr == null || setFunStr == null    ) {
	    		console.log('loadInst Error: ', {
	    			'targetStr':targetStr,
	    			'eventStr':eventStr,
	    			'tgetFunStr':tgetFunStr,
	    			'tsetFunStr':tsetFunStr,
	    			'getFunStr':getFunStr,
	    			'setFunStr':setFunStr
	    		}, 'one of params not exist!' );
	    		return;
	    	}

	    	// set & get 方法
	    	try{ 
	    		eval('var tget=' + tgetFunStr);
	    		eval('var tset=' + tsetFunStr);
	    		eval('var get=' + getFunStr);
	    		eval('var set=' + setFunStr);
	    	} catch( e ) {
	    		console.log('loadInst Error: ', e , '\n\tdefined:', {
	    			'targetStr':targetStr,
	    			'tgetFunStr':tgetFunStr,
	    			'tsetFunStr':tsetFunStr,
	    			'getFunStr':getFunStr,
	    			'setFunStr':setFunStr
	    		} );
	    		return;
	    	}
	    	$this.data('tset', tset);
	    	$this.data('get', get);


	    	// target object 目标元素
	    	
	    	if ( targetStr !=  '$target' && targetStr != null ) {
	    		$target = $(targetStr, inst);
	    	}
	    	if ( $target.length == 0 ) {
	    		console.log('loadInst Error: ', targetStr, 'not exist!' );
	    		return ;
	    	}
	    	$this.data('target', $target);


	    	// 设定初始数据
	    	var currValue = tget($target, form);
	    		set($this, currValue);

	    	// bind events 
	    	var evts = eventStr.split(',');
	    	if ( evts.length <= 0 ) {
	    		console.log('loadInst Error: ', eventStr, 'Error' );
	    		return ;
	    	}

	    	for( var idx in evts ) {
	    		evt = evts[idx];
	    		$this.on(evt, function(event) {
	    			var $target = $(this).data('target');
	    			var $this = $(this);
	    			var tset = $(this).data('tset');
	    			var get = $(this).data('get');

	    			if ( typeof tset != 'function') {
	    				console.log('components setting error: ', 'tset not function', '\n\ttset:', tset );
	    				return;
	    			}

	    			if ( typeof get != 'function') {
	    				console.log('components setting error: ', 'get not function', '\n\tget:', get );
	    				return;
	    			}

	    			if ( $target == null ) {
	    				console.log('components setting error: ', 'target not exist' );
	    				return;
	    			}
	    			var value = get($this);
	    			tset( $target, value, form, $this );
	    		});
	    	}
	    });
	}



	/**
	 * 添加组件面板初始化
	 * @return {[type]} [description]
	 */
	var comcreateInit = function() {
		
		var components = $('.component', panel);
		components.parent().sortable({  // 每个组件均可拖拽
			appendTo: $(form),
			connectWith: $(form),
			placeholder:'draggable-placeholder',
			helper: function(event, item ){
				return $(item.attr('data-source'));
			},
			start: function( event, ui ) {
			 	ui.placeholder.css({
	                'height': ui.item.outerHeight(),
	                'margin-bottom': ui.item.css('margin-bottom')
	            });
			},
		});
	}


	/**
	 * 表单配置初始化
	 * @return {[type]} [description]
	 */
	var formsettingInit = function() {
		var vars = self.data('vars');
			panel = vars['panel'];
			formid = vars['form'].attr('id');
			$('.formfield', panel).data('form', formid);
		 

		// 主键赋值
		var allowkey = $('#'+formid).attr('data-allowkey');
		if ( allowkey == "" || allowkey == undefined) { 
			allowkey = '_ID:id'; 
		}
		$('[name="allowkey"]').importTags(allowkey);
		
		// 各种文案赋值
		var words = ['createsuccess','createfailure','updatesuccess','updatefailure','model'];
		for( var i=0; i<words.length; i++ ){
			var word = words[i];
			var val =  $('#'+formid).attr('data-' + word);
			if ( val == undefined) { 
				val = "" ; 
			}
			$('[name="'+word+'"]').val(val);
		}


		// 以下代码只运行一次 
		if ( $(panel).data('init') ===  true ) {
			return;
		}
		$(panel).data('init', true);
		App.initHelpers('tags-inputs', { id:'[name="allowkey"]',
			onChange: function() {
				var formid = $(this).data('form');
				$('#'+formid).attr('data-allowkey', $(this).val() );
			}
		});

		for( var i=0; i<words.length; i++ ){
			var word = words[i];
			$('[name="'+word+'"]', panel).on('keyup',function(event) {
				var formid = $(this).data('form');
				$('#'+formid).attr('data-' + $(this).attr('name'), $(this).val() );
			});
		}
	}



	/**
	 * 组件配置面板初始化
	 * @return {[type]} [description]
	 */
	var comsettingInit = function() {
		$(self).click(function(event) {
			// 组件实例ID, 组件ID 父组件, 实例
	        var id = null, srcid=null, parent=null, inst=null;
	        var length = $('.fd-active', form).length;

	        if ( length <= 0 ) { // 为空的情况
	            // console.log( 'empty');
	            $('.empty', panel).removeClass('hidden');
	            $('.form', panel).addClass('hidden');

	        } else { // 激活的情况
	        	$('.empty', panel).addClass('hidden');
	            $('.form', panel).removeClass('hidden');

	            inst = $($('.fd-active', form)[0]);
	           	loadInst(inst);

	           	// 功能按钮方法 （ 复制，删除，新增 ）
	           	var option =  $(self).data('option');
	           	if ( option['id'] != undefined ) {

	           		var id = option['id'];
	           			$('.duplicate').data('id', id);
	           			$('.duplicate').data('form', form);

	           			$('.delete').data('id', id);
	           			$('.delete').data('form', form);

	           			$('.create').data('id', id);
	           			$('.create').data('form', form);

	           		// 复制
	           		if ( $('.duplicate').data('init')  !== true ) {

	           			$('.duplicate').data('init', true);
		           		$('.duplicate', panel).click(function(event) {
		           			var id = $(this).data('id');
		           			var form = $(this).data('form');
		           				$(form).duplicateItem(id);
		           		});
	           		}


	           		// 删除
	           		if ( $('.delete').data('init')  !== true ) {

	           			$('.delete').data('init', true);
		           		$('.delete', panel).click(function(event) {
		           			var id = $(this).data('id');
		           			var form = $(this).data('form');
		           			var prev = $(form).prevItem(id);
		           			var next = $(form).nextItem(id);
		           				$(form).removeItem(id);
		           				if ( prev != null ) {
		           					$(form).activeItem(prev);
		           				} else if ( next != null ) {
		           					$(form).activeItem(next);
		           				}
		           		});
	           		}

	           		// 添加
	           		if ( $('.create').data('init')  !== true ) {

	           			$('.create').data('init', true);
		           		$('.create', panel).click(function(event) {
		           			var id = $(this).data('id');
		           			var form = $(this).data('form');
		           				$(form).switchItem(id);
		           		});
	           		}
	        	}
	        }
		});

	}




	//==== API ========================================
	
	/**
	 * 修正窗体高度
	 * @param  {[type]}   offset   [description]
	 * @param  {Function} callback [description]
	 * @return {[type]}            [description]
	 */
	jQuery.fn.fixheight = function( offset ) {
		var vars = $(this).data('vars');
			panel = vars['panel'];
			block = vars['block'];

		offset = parseInt(offset) || 0;
		if ( typeof callback != 'function') {
			callback = function( height, form ) {}
		}
		var wh = $(window).height();
		var h = parseInt(wh - offset);
			slimscroll(block, {height:h});
			block.data('fixheight', true);
		return self;
	}

	/**
	 * 读取组件面板
	 * @return {[type]} [description]
	 */
	jQuery.fn.panel = function () {
		var vars = $(this).data('vars');
			panel = vars['panel'];
			return panel;
	}

	/**
	 * 根据Slug读取Srcid
	 */
	jQuery.fn.component = function(slug) {
		var vars = $(this).data('vars');
			panel = vars['panel'];
		return $('[data-slug="'+slug+'"]', $(panel));
	}



	// === INIT ====

	// 初始化添加组件面板
	if (type == 'comcreate') {  
		comcreateInit();
	} else if ( type == 'comsetting') {
		comsettingInit();
	} else if ( type == 'formsetting') {
		formsettingInit();
	}


	return this;
}