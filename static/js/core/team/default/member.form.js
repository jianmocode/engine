/*
 *  Document   : member.form.js
 *  Author     : ChuanBoLian.cn
 *  Description: 查看/修改团队成员JS页面
 */

var CoreTeamDefaultMemberForm = function() {


    // Init Register Form Validation, for more examples you can check out https://github.com/jzaefferer/jquery-validation
    var initValidationRegister = function(){
        jQuery('.js-validation-CoreTeamDefaultMemberForm').validate({
            errorClass: 'help-block animated fadeInDown',
            errorElement: 'div',
            errorPlacement: function(error, e) {
            	// console.log( 'onError NAME=', $(e).attr('name'), 'ERROR=' , error.html() , e );
            	jQuery(e).parents('.form-group > div').append(error);
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

                $('#member_update_success').hide();
                $('button', '#member_form_footer').attr('disabled','disabled');

            	App.blocks('#member_form_block', 'submit', {
            		   
            		   loading: 'show',

                       success: function( data, status ) {

                          $row = $('#list-row-' + data.id, '#member-table-list' );

                          // 更新表单
                          $('.name', $row ).html(data.name);
                          $('.role', $row ).html(data.role);
                          $('.role', $row ).removeClass('label-danger')
                                           .removeClass('label-primary')
                                           .addClass('label-' + data.role_style );
                          $('#member_update_success').hide();
                          $('#member_update_success').removeClass('hide');
                          $('#member_update_success').fadeIn();

                       },

                       error: function( data, status ) {
                          //  console.log( 'error',  data, status );
                       },

                       complete: function( xhr, status ){
                          // console.log( 'complete',  xhr, status );
                          $('button', '#member_form_footer').removeAttr('disabled','disabled');
                       }
                });

            },

            ignore: ".ignore",

            rules: {

                'name': {
                    required:true,
                    minlength: 2,
                    maxlength: 10,
                },

                'title': {
                    minlength: 2,
                    maxlength: 40,
                },

                'email': {
                    email: true
                },

                'mobile': {
                    digits:true,
                    minlength: 11,
                    maxlength: 11,
                },

                'wechat': {
                    maxlength: 100,
                }
                
            },
            messages: {
                'name': {
                    required: '请填写显示名称',
                    minlength: '显示名称至少2个汉字',
                    maxlength: '显示名称不能超过10个汉字'
                },

                'title': {
                    minlength: '在团队职务至少2个汉字',
                    maxlength: '在团队职务不能超过40个汉字'
                },

                'email':'邮箱格式不正确',
                'mobile': '手机号码格式不正确',
                'wechat': '微信号码格式不正确',
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
    // 初始化表单验证程序
    CoreTeamDefaultMemberForm.init();
    $('#member_update_submit').click(function() {
        $('.js-validation-CoreTeamDefaultMemberForm').submit();
    });
})