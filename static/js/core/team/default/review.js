/*
 *  Document   : review.js
 *  Author     : ChuanBoLian.cn
 *  Description: 加入申请审批页面JS
 */


$(function () {
	
    function membersList() {
    	var $cnt = $('#tab-review-cnt');
    	if ( $cnt.html() == '' ) {
                $parent = $('#review-table-list').parent();
    			$('#review-table-list').remove();
    			$('.alert', $parent).removeClass('hide');
    			$('.alert', $parent).show();
    	}
    }

    membersList();
    App.initHelpers('btn-ajax-post', {  
         id:'#review-table-list',
         error: function( error, status, ids ) {
             App.notify( error.errmsg, 'fa fa-times','danger');
         },
         success: function( data, status, ids ) {
             var $total = $('#tab-review-cnt');
             if ( data.total == 0 ) data.total = "";

             $total.html( data.total );
             
             membersList();
             App.notify('操作成功');
         },
     });

});
