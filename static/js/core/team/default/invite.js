/*
 *  Document   : invite.js
 *  Author     : ChuanBoLian.cn
 *  Description: 邀请团队成员页面JS
 */


$(function () {

	// 复制地址功能
	var copy_url = new ZeroClipboard( document.getElementById("invite_copy") );
	copy_url.on( "ready", function( readyEvent ) {
  		copy_url.on( "aftercopy", function( event ) {
  			$('#invite_info').removeClass('text-muted');
			$('#invite_info').addClass('text-success');
			$('#invite_info').html('复制成功，请发送给新成员。');

			setTimeout( function(){
				$('#invite_info').removeClass('text-success');
				$('#invite_info').addClass('text-muted');
				$('#invite_info').html( $('#invite_info').attr('placeholder'));
			}, 3000);
  		});
	});

});
