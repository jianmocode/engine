/**
 * MINA WEB JS SDK 
 *  
 * Require quill v1.2.4, highlight.js v9.11.0, katex v0.7.1
 * Copyright (c) 2014-2017 JianMoApp.com
 * Licensed MIT
 * https://JianMoApp.com/
 * https://help.JianMoApp.com
 */

import * as vdom  from  '../lib/vdom/snabbdom';
import vdom_class from  '../lib/vdom/snabbdom-class';
import vdom_props from  '../lib/vdom/snabbdom-props';
import vdom_style from  '../lib/vdom/snabbdom-style';
import vdom_events from  '../lib/vdom/snabbdom-eventlisteners';
import utils from '../lib/utils';

let h = vdom.h;
let patch  = vdom.init([vdom_class,vdom_props,vdom_style,vdom_events]);


class Page {
	
	constructor( options )  {
		options = options || {};
		this.options = options;
		this.options['setData'] = this.setData;
		this.map = {};
	}

	$load( params,  data ) {

		data = data || {};
		this.options['data'] = this.options['data']  || {};
		this.options['data'] = utils.extend(this.options['data'], data);
		this.options['onReady']( params ); 

        
        this.bindEvent( params["selector"], params["__component"] );
        
		// this.playground();
    }
    
    bindEvent( selector, isComponet ) {
        if ( document == undefined ) {
            return;
        }

        if ( document.querySelectorAll == undefined ) {
            return;
        }
        // 处理组件
        let bindtaps = []; 
        if ( isComponet ) {
            bindtaps = document.querySelectorAll(`${selector} > [bindtap]`);
        } else {  // 页面
            bindtaps = document.querySelectorAll('[bindtap]');
        }

        // 效率较低, 下一版应该优化
        function findParentTag(el, tag) {

            if (el.tagName === tag) {
                return el;
            }

            while (el.parentNode) {
                el = el.parentNode;
                if (el.tagName === tag)
                    return el;
            }
            return null;
        }


        for( let i in bindtaps) {
            let elm = bindtaps[i];
            
            if ( !elm.getAttribute ) {
                continue;
            }
            
            var init = elm.getAttribute("__event-inited");
            var method = elm.getAttribute("bindtap");

            if ( elm.addEventListener && init !== "__event-inited" ) {

                let com = findParentTag(elm, "COMPONENT");
                if( com !== null ) {
                    continue;
                }

                let evt = this.options[method];
                if ( typeof evt == "function" ) {
                    elm.addEventListener('click', (event)=>{
                        try { 

                            // 运行事件
                            let response = evt( event ); 
                            
                            // 阻止冒泡
                            if ( response === false ) {
                                if (!event) var event = window.event;
                                event.cancelBubble = true;
                                if (event.stopPropagation) event.stopPropagation();
                            }

                        } catch( e ){
                            console.log("Event Error:", evt );
                        }
                    });
                    elm.setAttribute("__event-inited", "__event-inited");
                }
            }
        }
        
    }

	playground() {

		let that = this;
		let node = null;
		if ( typeof that.map['vnode-01'] == 'undefined') {
			node = document.getElementById('vnode-01');
			that.map['vnode-01'] = h('div#vnode-01',{style:{color:'#565656'}} ,[
				h('div#vnode-0101', '这样好吗？' ),
				h('div', 'OK')
			]);
		} else {
			node = that.map['vnode-01'];
		}

		let vnode = h('div#vnode-01',{style:{color:'#565656'}} ,[
			h('div#vnode-0101', '这样好吗？' ),
			h('div', 'DOING')
		]);

		that.map['vnode-01'] = vnode;
		patch( node, vnode);

		let vnode2 = h('div#vnode-01',{style:{color:'#565656'}},  [
			h('div#vnode-0101', {style:{color:'#ff0000'}}, '没啥不好的！'),
			h('div', 'DONE')
		]);

		let nodeChild = document.getElementById('vnode-0101');
		let vnode3 = h('div#vnode-0101', {style:{color:'#ff00ff'}}, '我也觉得不太好！');  // 局部更新
		
		setTimeout(function(){
			patch( nodeChild, vnode3);

			setTimeout(function(){
				that.map['vnode-01'] = vnode2;
				patch( vnode, vnode2);

				setTimeout(function(){
					that.playground();
				}, 1000);

			}, 1000);

		}, 1000);
	}

	init() {
		this.options['onReady'] = this.options['onReady'] || function( params ){ }
	}

	ready( cb, ...args  ) {
		///兼容FF,Google
		if (document.addEventListener) {
			document.addEventListener('DOMContentLoaded', function () {
				document.removeEventListener('DOMContentLoaded', args, false);
				cb();
			}, false)
		}
		 //兼容IE
		else if (document.attachEvent) {
			document.attachEvent('onreadystatechange', function () {
				  if (document.readyState == "complete") {
						document.detachEvent("onreadystatechange", args);
						cb();
				   }
			})
		}
		else if (document.lastChild == document.body) {
			cb();
		}
	}

	setData( data ) {
		console.log( 'setData 方法暂未实现', data );
	}
}


function PageInst( options ) {

	let page = new Page( options );
		page.init();

    window.mina.page = page;
    window.page = window.mina.page.options;
	return page;
}


module.exports = PageInst