/*
 *  Document   : member.js
 *  Author     : ChuanBoLian.cn
 *  Description: 加入申请审批页面JS
 */


$(function () {
	
    $('a','.pagination').click(function() {
        
        if ( !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active') ) {
            var remote = $(this).attr('data-remote');
            var $tab = $("a[href='#team-member']");
                $tab.attr('data-remote', remote );
            $tab.trigger('click');
        }
    });

    App.initHelpers('btn-ajax-post', {  
         id:'#member-table-list',
         error: function( error, status, ids ) {
             App.notify( error.errmsg, 'fa fa-times','danger');
         },
         success: function( data, status, ids ) {
             App.notify('操作成功');
         },
     });
});
