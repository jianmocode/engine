
jQuery.fn.cached = function( ns, opt ) {
	try { store.get } catch(e) { return false; }

	ns = ns || '';
	opt = opt || {}
	opt['autosave'] = opt['autosave'] || false;

	var self = this;
	var defaults = {
		'components': [
			{
				selector: 'input[type=text]',
				event:'blur',
				name: function( obj ){ return $(obj).attr('name'); },
				get: function(  obj  ) { return $(obj).val() },
				set: function(  obj , val ) { $(obj).val(val); }
			},{
				selector: 'input[type=checkbox]',
				event:'change',
				name: function( obj ){ return $(obj).attr('name'); },
				get: function(  obj  ) { return $(obj).prop('checked') },
				set: function(  obj , val ) { 
					if ( val == true) {
						$(obj).attr('checked', 'checked');
					} else {
						$(obj).removeAttr('checked');
					}
				}
			},{
				selector: 'input[type=radio]',
				event:'change',
				name: function( obj ){ return $(obj).attr('name'); },
				get: function(  obj  ) { 
					var name = $(obj).attr('name');
					$('[name='+name+']').each(function( idx , o ){
						if ( $(o).prop('checked')  ){
							return $(o).val();
						}
					})
					return null;
				},
				set: function(  obj , val ) {
					var name = $(obj).attr('name');
					$('[name='+name+']').each(function( idx , o ){
						if ( $(o).val() == val ){
							$(o).attr('checked', 'checked');
						} else {
							$(o).removeAttr('checked');
						}
					});
				}
			},{
				selector: 'input[type=hidden]',
				event:'change',
				name: function( obj ){ return $(obj).attr('name'); },
				get: function(  obj  ) { return $(obj).val() },
				set: function(  obj , val ) { $(obj).val(val); }
			},{
				selector: 'select[multiple!=multiple]',
				event:'change',
				name: function( obj ){ return $(obj).attr('name'); },
				get: function(  obj  ) { return $(obj).val() },
				set: function(  obj , val ) { $(obj).val(val); }
			},{
				selector: 'select[multiple=multiple]',
				event:'change',
				name: function( obj ){ return $(obj).attr('name'); },
				get: function(  obj  ) { return $(obj).val() },
				set: function(  obj , val ) { $(obj).val(val); }
			}
		]
	}

	components = defaults['components'];
	if ( typeof opt['components'] != 'undefined') {
		components =components.concat(opt['components']);
	}


	/**
	 * 保存一个元素的数据
	 * @param  {[type]} component [description]
	 * @return {[type]}           [description]
	 */
	var save = function( component ) {
		if ( typeof  component.name != 'function'  ) component.name = function(obj){ return $(obj).attr('name'); }
		if ( typeof  component.get != 'function'  ) component.get = function(  obj  ) { return $(obj).val() }

		$(component.selector, self ).each( function(idx , obj) {
			 var name = ns + '::' + component.name( obj );
			 var val = component.get( obj );
			 store.set(name, val);
		});
	}



	/**
	 * 读取一个元素的数据
	 * @param  {[type]} component [description]
	 * @return {[type]}           [description]
	 */
	var load = function( component ) {
		if ( typeof  component.name != 'function'  ) component.name = function(obj){ return $(obj).attr('name'); }
		if ( typeof  component.set != 'function'  ) component.get = function(  obj , val ) { $(obj).val(val); }
		$(component.selector, self ).each( function(idx , obj) {
			 var name = ns + '::' + component.name( obj );
			 var val = store.get( name );
			 component.set(obj, val);
		});
	}


	/**
	 * 绑定自动保存事件
	 * @param  {[type]} component [description]
	 * @return {[type]}           [description]
	 */
	var bind = function( component ) {
		if ( typeof  component.event != 'string'  ) return false;
		$(component.selector, self );
		if ( typeof  component.eventSelector == 'function'  ) {
			$(component.selector, self ).each(function( idx,obj) {
				var eventHander = component.eventSelector( obj );
				// console.log( ' eventHander component:', $(obj).attr('name'), ' event', component.event , ' eventHander', eventHander.attr('name'));
				
				eventHander.on( component.event, function(){
					// console.log('eventHander:: obj=', $(this).attr('name'), ' component:', component.selector );
					save(  component );
				});
			});

			return ;
		}  
		
		$(component.selector, self ).on(component.event, function(){
			// console.log('autosave:: obj=', $(this).attr('name'), ' component:', component.selector );
			save(  component );
		});
	}


	// Auto Save
	if ( opt['autosave'] === true ){
		for( var i=0; i<components.length; i++ ) {
			try { bind( components[i]) } catch(e) {
				console.log( 'bind error', components[i].selector , ' error', e );
			}
		}
	}


	/**
	 * 保存数据
	 * @return {[type]} [description]
	 */
	jQuery.fn.cached.save = function() {
		for( var i=0; i<components.length; i++ ) {
			try { save( components[i]) } catch(e) {
				console.log( 'save error', components[i].selector , ' error', e );
			}
		}
	}


	/**
	 * 载入表单数据
	 */
	jQuery.fn.cached.load = function() {
		for( var i=0; i<components.length; i++ ) {
			try { load( components[i]) } catch(e) {
				console.log( 'load error', components[i].selector , ' error', e );
			}
		}
	}


	/**
	 * 从远程读取数据
	 * @return {[type]} [description]
	 */
	jQuery.fn.cached.pull = function( api, done ) {
		try { store.pull(api, done, ns + '::'); } catch(e){ 
			console.log( 'pull error', e );
		}
	}

	/**
	 * 将数据提交到服务器
	 * @return {[type]} [description]
	 */
	jQuery.fn.cached.push = function( api, done ) {
		try { store.push(api, done, ns + '::'); } catch(e) { 
			console.log( 'push error',  e );
		}
	}


	/**
	 * 读取所有数据
	 */
	jQuery.fn.cached.getAll = function( namespace ) {
		namespace = namespace || '';
		if ( namespace !== '') namespace = namespace + '::';

		var data = {}
		store.forEach(function(nskey, val) {
			if ( nskey.indexOf(namespace) == 0 ) {
				var key = nskey.replace(namespace, '');
				data[key] = val;
			}
		});
		return data;
	}
	

}
