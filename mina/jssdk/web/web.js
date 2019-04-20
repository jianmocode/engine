/**
 * MINA WEB JS SDK 
 *  
 * Require quill v1.2.4, highlight.js v9.11.0, katex v0.7.1
 * Copyright (c) 2014-2017 JianMoApp.com
 * Licensed MIT
 * https://JianMoApp.com/
 * https://help.JianMoApp.com
 */


class Web {
	
	constructor( options )  {
		options = options || {};
		this.options = options;
	}

	init() {
		// console.log( 'Web Inited', this.options );
	}
}


function WebInst( options ) {

	let web = new Web( options );
		web.init();
		window.mina = window.mina || {}
		window.mina.web = web;
		try {
			web.options.load( web );
		}catch(e) {}
}


module.exports = WebInst
