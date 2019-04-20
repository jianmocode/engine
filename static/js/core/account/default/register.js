/*
 *  Document   : register.js
 *  Author     : ChuanBoLian.cn
 *  Description: 用户注册页面JS
 */

var CoreAccountDefaultRegister = function() {
    // Init Register Form Validation, for more examples you can check out https://github.com/jzaefferer/jquery-validation
    var initValidationRegister = function(){
        jQuery('.js-validation-CoreAccountDefaultRegister').validate({
            errorClass: 'help-block text-right animated fadeInDown',
            errorElement: 'div',
            errorPlacement: function(error, e) {
                jQuery(e).parents('.form-group .form-material').append(error);
            },
            highlight: function(e) {
                jQuery(e).closest('.form-group').removeClass('has-error').addClass('has-error');
                jQuery(e).closest('.help-block').remove();
            },
            success: function(e) {
                jQuery(e).closest('.form-group').removeClass('has-error');
                jQuery(e).closest('.help-block').remove();
            },


            rules: {
                'name': {
                    required: true,
                    minlength: 2,
                    maxlength: 10,
                },
                'mobile': {
                    required: true,
                    digits:true,
                    minlength: 11,
                    maxlength: 11,
                },
                'email': {
                    required: true,
                    email: true
                },
                'password': {
                    required: true,
                    minlength: 6,
                    maxlength: 10,
                },
                'password2': {
                    required: true,
                    equalTo: '#register-password'
                },

               /* 'vcode': {
                    required: true,
                    minlength: 4,
                    maxlength: 4,
                }, */

                'register-terms': {
                    required: true,
                },

            },
            messages: {
                'name': {
                    required: '请填写真实姓名',
                    minlength: '姓名至少2个汉字',
                    maxlength: '姓名不能超过10个汉字'
                },
                'mobile': {
                    required: '请填写手机号码',
                    digits: '手机号码格式不正确',
                    maxlength: '手机号码格式不正确',
                    minlength: '手机号码格式不正确',
                },
                'email': {
                    required: '请填写常用邮箱',
                    email:'邮箱格式不正确',
                },
                'password': {
                    required: '请填写密码',
                    minlength: '密码长度应该在6-10位',
                    maxlength: '密码长度应该在6-10位',
                },
                'password2': {
                    required: '请再输入一遍密码',
                    equalTo: '两次输入的密码不一致'
                },
               /* 'vcode' : {
                    required: '请输入图片验证码',
                    minlength: '图片验证码不正确',
                    maxlength: '图片验证码不正确',
                }, */

                'register-terms':'请阅读并接受用户使用协议'
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

// 刷新验证码
function changeVcode() {
    $('#register-vcode-img').attr('src', _vcodeUrl + '&rand=' + Math.random() );
}

// Initialize when page loads
jQuery(function(){ CoreAccountDefaultRegister.init(); });