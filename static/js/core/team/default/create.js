/*
 *  Document   : create.js
 *  Author     : ChuanBoLian.cn
 *  Description: 创建团队JS页面
 */




var CoreTeamDefaultCreate = function() {
    // Init Register Form Validation, for more examples you can check out https://github.com/jzaefferer/jquery-validation
    var initValidationRegister = function(){
        jQuery('.js-validation-CoreTeamDefaultCreate').validate({
            errorClass: 'help-block text-right animated fadeInDown',
            errorElement: 'div',
            errorPlacement: function(error, e) {
            	// console.log( 'onError NAME=', $(e).attr('name'), 'ERROR=' , error );
            	if ( $(e).attr('name') == 'headimgurl_path' ) {
            		App.fileuploader( 'error', {'errno':'100100','errmsg':$(error).html(), 'timeout':0}, $("[name='headimgurl']"));
            	} else {
            		jQuery(e).parents('.form-group .form-material').append(error);
            	}
            },
            highlight: function(e) {
                jQuery(e).closest('.form-group').removeClass('has-error').addClass('has-error');
                jQuery(e).closest('.help-block').remove();
            },
            success: function(e) {
                jQuery(e).closest('.form-group').removeClass('has-error');
                jQuery(e).closest('.help-block').remove();
            },

            submitHandler: function(form) {

            	
            	App.blocks('#team_create_form', 'submit', {
            		   
            		   loading: 'hide',

                       success: function( data, status ) {

                       	  console.log( 'success',  data, status );
                       	  
                       	  $(form).addClass('hide');
                       	  $('#team-create-success').removeClass('hide');

                       	  var id = data.id;
                       	  var url = $('a', '#team-create-success').attr('href');
                       	  url = url.replace('_ID_', id);
                       	  $('a', '#team-create-success').attr('href', url );

                       	  var time_cnt = 5;
                       	  var goto_next  = function(){
                       	  	$("#next_action").html('邀请成员 <small>( ' + time_cnt +' 秒后自动跳转 ) </small>');
                       	  	time_cnt--;
                       	  	if (time_cnt == 0) {
                       	  		window.location = url;
                       	  	} else {
                       	  		setTimeout(function(){goto_next()}, 1000);
                       	  	}
                       	  }
                       	  goto_next();
                       },

                       error: function( data, status ) {
                           // console.log( 'error',  data, status );
                       },

                       complete: function( xhr, status ){
                           // console.log( 'complete',  xhr, status );
                       }

                });

            },

            ignore: ".ignore",

            rules: {

            	'headimgurl_path' : {
            		required:true,
            	},

                'name': {
                    required: true,
                    minlength: 2,
                    maxlength: 50,
                },


                'intro': {
                    required: true,
                    minlength: 6,
                    maxlength: 100,
                },
                
                'company': {
                    required: true,
                    minlength: 4,
                    maxlength: 50,
                },

                'gm_name': {
                    required: true,
                    minlength: 2,
                    maxlength: 10,
                },

                'gm_title': {
                    required: true,
                    minlength: 2,
                    maxlength: 40,
                },

                'gm_mobile': {
                    required: true,
                    digits:true,
                    minlength: 11,
                    maxlength: 11,
                },

                'gm_email': {
                    required: true,
                    email: true
                },

                'gm_address': {
                    required: true,
                    minlength: 4,
                    maxlength: 100,
                },

            },
            messages: {
            	
            	'headimgurl_path': '请上传团队标识',

                'name': {
                    required: '请填团队名称',
                    minlength: '团队名称至少2个汉字',
                    maxlength: '团队名称不能超过50个汉字'
                },

                'intro': {
                    required: '请填团队简介',
                    minlength: '团队简介至少6个汉字',
                    maxlength: '团队简介不能超过100个汉字'
                },

                'company': {
                    required: '请填所属公司或机构',
                    minlength: '公司或机构至少4个汉字',
                    maxlength: '公司或机构不能超过50个汉字'
                },

                'gm_name': {
                    required: '请填负责人真实姓名',
                    minlength: '真实姓名至少2个汉字',
                    maxlength: '真实姓名不能超过10个汉字'
                },

                'gm_title': {
                    required: '请填负责人职务',
                    minlength: '职务至少2个汉字',
                    maxlength: '职务不能超过40个汉字'
                },

                'gm_mobile': {
                    required: '请填写负责人手机号码',
                    digits: '手机号码格式不正确',
                    maxlength: '手机号码格式不正确',
                    minlength: '手机号码格式不正确',
                },

                'gm_email': {
                    required: '请填写负责人邮箱',
                    email:'邮箱格式不正确',
                },

                'gm_address': {
                    required: '请填负责人通信地址',
                    minlength: '通信地址至少4个汉字',
                    maxlength: '通信地址不能超过100个汉字'
                },

            }
        });
    };

    return {
        init: function () {
            // Init Register Form Validation
            initValidationRegister();
        }
    };
}();


$(function () {
  
    // File Uploader 
    App.initHelpers('file-uploader', {'home':$req.upload_home} );
    
    // Image Crop 
    App.initHelpers('image-crop');

    // 初始化表单验证程序
    CoreTeamDefaultCreate.init();

})