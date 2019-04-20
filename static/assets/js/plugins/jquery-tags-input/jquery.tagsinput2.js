


jQuery.fn.tagsinput2 = function( properties ) {
	
	var self = this;
	var defaults = {
        height: '36px',
        width: '100%',
        defaultText: '添加',
        removeWithBackspace: true,
        delimiter: [','],
        delimiter2:['|'],
        style: null,
        onClick: function(){},
        onChange: function(){},
        onRemoveTag: function() {},
        onAddTag: function() {},
        onLoad: function(){},
    }

    properties = properties || {};
    properties = jQuery.extend({}, defaults, properties );

    $(this).each(function(idx,elm){
    	$(elm).data('tagsMap', {}); // Init 数据存储
    	$(elm).data('valuesMap', {});
    	$(elm).data('defaults', $(elm).val());
    	$(elm).val('');
   	});

    var tagsInputOption  = jQuery.extend({}, properties, {
    	onChange: function( elm, tag, arg3 ) {
    		var tagsMap = jQuery.extend({}, $(this).data('tagsMap') );
    		if (typeof tagsMap[tag] == 'undefined') {
    			this.onChange = properties['onChange'];
    			this.onChange( tag );
    			return true;
    		}

    		var value = tagsMap[tag]['value'];
    		$(this).setStyle( value, tagsMap[tag]['style'] );
    		this.onChange = properties['onChange'];
    		this.onChange( tagsMap[tag] );
    	},

    	onRemoveTag: function( tag ) {
    		var tagsMap = jQuery.extend({}, $(this).data('tagsMap') );
    		if (typeof tagsMap[tag] == 'undefined') {
    			this.onRemoveTag = properties['onRemoveTag'];
    			this.onRemoveTag( tag );
    			return true;
    		}

    		var value = tagsMap[tag]['value'];
    		$(this).deleteTag( value );
    		this.onRemoveTag = properties['onRemoveTag'];
    		this.onRemoveTag( tagsMap[tag] );
    	},
    	onAddTag: function( tag ) {
    		var tagsMap = jQuery.extend({}, $(this).data('tagsMap') );
    		if (typeof tagsMap[tag] == 'undefined') {
    			this.onAddTag = properties['onAddTag'];
    			this.onAddTag( tag );
    			return true;
    		}
    		
    		this.onAddTag = properties['onAddTag'];
    		this.onAddTag( tagsMap[tag] );
    	}
    });


    // 初始化 TagsInput
    $(this).tagsInput( tagsInputOption );
    
    /**
     * 添加一个带样式的TAG
     * @param {[type]} tag   Tag名称
     * @param {[type]} value 数值
     * @param {[type]} style 样式 ( css class or function(elm, obj){} )
     */
    jQuery.fn.addTag2 = function( tag, value, style ) {
    	if ( typeof tag == 'undefined' || tag == "" ){
    		return false;
    	}

    	var tagsMap = jQuery.extend({}, $(this).data('tagsMap') );
    	var valuesMap = jQuery.extend({}, $(this).data('valuesMap') );
    	style = style || null;
    	value = value || tag;
    	tagsMap[tag] = valuesMap[value] = {
    		'tag': tag,
    		'value': value,
    		'style': style,
    	}
    	$(this).data('tagsMap', tagsMap);
    	$(this).data('valuesMap', valuesMap);
    	$(this).addTag( tag );
    	return $(this);
    }

    /**
     * 设置Tag样式
     * @param {[type]} value [description]
     * @param {[type]} style [description]
     */
    jQuery.fn.setStyle = function( value, style ) {
    	style = style || null;
    	var valuesMap = jQuery.extend({}, $(this).data('valuesMap') );
    	

    	if ( typeof valuesMap[value] == 'undefined') {
    		return false;
    	}

    	var tag = valuesMap[value]['tag'];
    	var stl = properties['style'];
    	var id =  $(this).attr('id');
    	

    	if ( style != null || typeof stl == 'function' ) {
	    	$('.tag', $(this).parent() ).each(function(){

	            if( $(this).text().search(tag) >= 0) {
	            	if ( style == null ) {
	            		stl( this, valuesMap[value] );
	            	} else if (typeof style == 'function' ) {
	            		style(this, valuesMap[value] );
	            	} else {
	            		$(this).addClass(style);
	            	}
	            }
	        });
        }
        return $(this);
    }

    /**
     * 移除一个 Tag
     * @param  {[type]} value [description]
     * @return {[type]}       [description]
     */
    jQuery.fn.removeTag2 =function( value ) {
    	var valuesMap = jQuery.extend({}, $(this).data('valuesMap') );
    	if ( typeof valuesMap[value] == 'undefined') {
    		return false;
    	}
    	var tag = valuesMap[value]['tag'];
    	$(this).removeTag( tag );
    	return $(this);
    }


    jQuery.fn.deleteTag = function( value ) {
    	var tagsMap = jQuery.extend({}, $(this).data('tagsMap') );
    	var valuesMap = jQuery.extend({}, $(this).data('valuesMap') );
    	if ( typeof valuesMap[value] == 'undefined') {
    		return false;
    	}
    	var tag = valuesMap[value]['tag'];
    	delete tagsMap[tag];
    	delete valuesMap[value];
    	$(this).data('tagsMap', tagsMap);
    	$(this).data('valuesMap', valuesMap);
    }


    jQuery.fn.tagExist2 = function( value ) {
    	var valuesMap = jQuery.extend({}, $(this).data('valuesMap') );
    	if ( typeof valuesMap[value] == 'undefined') {
    		return false;
    	}
    	var tag = valuesMap[value]['tag'];
    	return $(this).tagExist(tag);
    }


    jQuery.fn.importTags2 = function ( value_string ) {
    	var input_obj = $(this);
    	value_string = value_string || null;
    	if ( value_string == null || value_string == "" ) {
    		return true;
    	}

    	var delimiter = properties['delimiter'];
    	var delimiter2 = properties['delimiter2'];
    	valr = value_string.split(delimiter);
    	$(valr).each(function(idx,val){
    		var vr = val.split(delimiter2);
    		var tag = vr[0],
    			value = vr[1] || vr[0],
    			style = vr[2] || null;

    		$(input_obj).addTag2(tag, value, style);
    	});
    }


    jQuery.fn.setLink = function( name, click_callback ) {
    	var tagsMap = jQuery.extend({}, $(this).data('tagsMap') );
    	var self = $(this);
    		self.callback = click_callback;
    	  	$obj = $(this).parent();
            $('.tagsinput > div', $obj).html('<a href="javascript:void(0)">'+name+'</a>');
            $('.tagsinput > .tags_clear', $obj).html('');
            $('.tagsinput > div > a', $obj).unbind('click');
            $('.tagsinput > div > a', $obj).click( function(){self.callback(tagsMap)} );
    }

    jQuery.fn.value = function( value_only ) {
    	if ( typeof value_only == 'undefined'  ) {
    		value_only = false;
    	}
    	var valuesMap = jQuery.extend({}, $(this).data('valuesMap') );

    	if ( !value_only ) {
    		return valuesMap;
    	}
    	var val = [];
    	for(key in valuesMap){
    		val.push(valuesMap[key]['value']);
    	}
    	return val;

    }

    jQuery.fn.valueString = function( value_only ) {
    	if ( typeof value_only == 'undefined'  ) {
    		value_only = true;
    	}

    	var tagsMap = jQuery.extend({}, $(this).data('tagsMap') );
    	if (value_only ) {
    		var val = [];
    		var delimiter = properties['delimiter'][0];
    		for(key in tagsMap){
    			val.push(tagsMap[key]['value']);
    		};
    		return val.join(delimiter);
    	}

    	var delimiter = properties['delimiter'][0];
    	var delimiter2 = properties['delimiter2'][0];

    	var val = [];
    	for(key in tagsMap){
    		var tagobj = [];
    		if ( tagsMap[key]['style'] != null ) {
    			 tagobj = [tagsMap[key]['tag'],tagsMap[key]['value'],tagsMap[key]['style']];
    		} else {
    			 tagobj = [tagsMap[key]['tag'],tagsMap[key]['value']];
    		}
    		val.push(tagobj.join(delimiter2));
    	};

    	return val.join(delimiter);
    }


    // INIT 添加默认值
    $(this).each(function(idx,elm){
    	var default_value = $(this).data('defaults');
    	$(this).importTags2(default_value);

    	// 添加链接
    	if ( typeof properties['link'] == 'string') {
    		$(this).setLink( properties['link'], properties['onClick'] );
    	}

    }); 

}