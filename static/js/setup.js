// Setup Common JS

/**
 * 校验服务配置，前端交互逻辑
 * @param  {string} selector   触发按钮 selector
 * @param  {function} onComplete 成功后回调 function( status, resp ) {}  // 成功 status = success  失败 status = error
 * @param  {object} option 配置选项 
 *     {
 *          'status':'.tryit-status',  // 状态显示区 Selector
 *          'action':'.tryit-action',  // 运行中需锁定的元素 Selector
 *          'doing':'<i class="fa fa-refresh fa-spin"></i> 正在连接 Redis 服务器 ',   // 进行中状态提醒
 *          'mute':true  // 是否为静默方式，如是静默方式，不显示进行中状态提醒
 *     }
 * @return null
 *
 *
 * 代码示例:
 *
 *    ....
 *    <button 
 *        class="btn btn-primary pull-left font-w300 push-15-r tryit tryit-action"
 *        data-action="/setup.php?a=tryit&se=redis"
 *        data-form="#redis-server-form"
 *        type="button" > 
 *        连接测试
 *    </button>
 *    
 *    <span class="pull-left tryit-status hidden" > <i class="fa fa-refresh fa-spin"></i> 正在连接 Redis 服务器 </span>
 *    
 *    <button class="btn btn-primary pull-right font-w300 tryit-action" type="button">  
 *       下一步  <i class="fa fa-angle-double-right"></i> 
 *    </button>
 *    ....
 *
 *    ....
 *    seActionInit('.tryit', function( status, resp ) {
 *         console.log( status, resp );
 *     }, {
 *         'status':'.tryit-status',
 *         'action':'.tryit-action',
 *         'doing':'<i class="fa fa-refresh fa-spin"></i> 正在连接 Redis 服务器 ',
 *         'mute':true
 *     });
 *    ....
 * 
 */

function seActionInit( selector, onComplete, option ) {

	selector = selector || null;


	onComplete = onComplete || function( status, resp ) {}
	option = option || { mute:false }


	if ( selector == null ) return;
	if ( $(selector).length == 0 ) return;

	$(selector).click(function(event) {	

		// 校验配置
		var tryBtn = option['hidden'] || this;

		var url =  $(this).attr('data-action');


		// 抓取form值
		var formSelector = $(this).attr('data-form');

		
		if ( url == null || formSelector == null )  return;
		var form = $(formSelector);
		if ( form.length ==0 ) return;

		// 读取From数据 ( 这段代码需要封装到组件库中 )
		var data = {};
    	var formData =  form.serializeArray();
        for( var i=0; i<formData.length; i++ ) {
            var name = formData[i]['name'];
            var value = formData[i]['value'];
            if ( value !== null ) {
            	var arrflag = name.indexOf('[]');
            	if ( arrflag > 0 ) {
            		name = name.substr(0, arrflag);
            		if ( typeof data[name] == 'undefined' ) {
            			data[name] = [];
            		}
            	}
                if ( typeof data[name] == 'undefined' ) {
                	data[name] = value;
                } else if ( typeof data[name] == 'string' ) {
                	data[name] = [data[name], value];
                } else if ( $.isArray(data[name]) ) {
                	data[name].push(value);
                }
            }
        }


		// 状态呈现: 开始
        var tryStart = function(){

			$(tryBtn).addClass("disabled").attr('disabled','disabled');

			if( $(option['action']).length > 0 ) {
	        	$(option['action']).addClass('disabled').attr('disabled','disabled');

	        }

	        
	        var statusSelector = null;

			// 判断option是否为空
	        if ( typeof option['status'] != 'undefined' ) {
	        	statusSelector = option['status'];
	        }	



	        // 把option的值放到变量里面
			statusBar = $(statusSelector);

			if ( statusBar.length>0 && option['mute']!== true ){
				// 正在校验打开
				statusBar.removeClass('hidden').removeAttr('hidden').html( option['doing'] || '<i class="fa fa-refresh fa-spin"></i>  正在连接...' );
				// 校验配置隐藏
	        	$(tryBtn).addClass('hidden');

	        }
    	}


		// 状态呈现: 完成
    	var tryComplete = function() {

 			// 校验配置显示
    		$(tryBtn).removeClass('disabled').removeAttr('disabled');

			if ( $(option['action']).length > 0 ) {
	        	$(option['action']).removeClass('disabled').removeAttr('disabled');
	        }

	        var statusSelector = null;
	        if ( typeof option['status'] != 'undefined' ) {
	        	statusSelector = option['status'];
	        }
	        statusBar = $(statusSelector);
	        if ( statusBar.length > 0  && option['mute'] !== true ){

	        	// 正在校验关闭
	        	statusBar.addClass('hidden');

	        	// 校验配置显示
	        	$(tryBtn).removeClass('hidden');
	        }

    	}

		// 请求远端程序, 校验数据
        tryStart();
		data = jQuery.extend(data, {});
		
		$.post( url, data, function( resp, textStatus, xhr) {

			if ( typeof resp != 'object' || resp  == null ) {

	    		resp = {code:-1, message:'校验失败', extra:{}};
	    	}

			if  ( resp['code'] == 201 ) {
	    		onComplete('warning', { 
	    			code:resp.code || -1, 
	    			message:resp.message || '校验成功',
	    			extra: resp.extra || {}
	    		});
				
				tryComplete();
	    		return;
	    	}	

			if( resp['code'] != 1 ) {
			
				onComplete('error', { 
	    			code:resp.code || -1, 
	    			message:resp.message || '校验失败',
	    			extra: resp.extra || {}
	    		});
	    		tryComplete();
	    		return;
	    	}

	    	onComplete('success', resp );
			tryComplete();

	    }, 'json')
		.error( function(xhr, status, statusText ){
	    	onComplete('error', {code:-1, message:statusText, extra:{ error:status,responseText:xhr.responseText, responseStatus:xhr.status}});
	    	tryComplete();
	    })

	});

}