(function webpackUniversalModuleDefinition(root, factory) {
	if(typeof exports === 'object' && typeof module === 'object')
		module.exports = factory();
	else if(typeof define === 'function' && define.amd)
		define([], factory);
	else if(typeof exports === 'object')
		exports["Page"] = factory();
	else
		root["Page"] = factory();
})(this, function() {
return /******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 10);
/******/ })
/************************************************************************/
/******/ ([
/* 0 */,
/* 1 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }(); /**
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      * MINA WEB JS SDK 
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      *  
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      * Require quill v1.2.4, highlight.js v9.11.0, katex v0.7.1
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      * Copyright (c) 2014-2017 JianMoApp.com
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      * Licensed MIT
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      * https://JianMoApp.com/
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      * https://help.JianMoApp.com
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                      */

var _snabbdom = __webpack_require__(8);

var vdom = _interopRequireWildcard(_snabbdom);

var _snabbdomClass = __webpack_require__(4);

var _snabbdomClass2 = _interopRequireDefault(_snabbdomClass);

var _snabbdomProps = __webpack_require__(6);

var _snabbdomProps2 = _interopRequireDefault(_snabbdomProps);

var _snabbdomStyle = __webpack_require__(7);

var _snabbdomStyle2 = _interopRequireDefault(_snabbdomStyle);

var _snabbdomEventlisteners = __webpack_require__(5);

var _snabbdomEventlisteners2 = _interopRequireDefault(_snabbdomEventlisteners);

var _utils = __webpack_require__(3);

var _utils2 = _interopRequireDefault(_utils);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _interopRequireWildcard(obj) { if (obj && obj.__esModule) { return obj; } else { var newObj = {}; if (obj != null) { for (var key in obj) { if (Object.prototype.hasOwnProperty.call(obj, key)) newObj[key] = obj[key]; } } newObj.default = obj; return newObj; } }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var h = vdom.h;
var patch = vdom.init([_snabbdomClass2.default, _snabbdomProps2.default, _snabbdomStyle2.default, _snabbdomEventlisteners2.default]);

var Page = function () {
	function Page(options) {
		_classCallCheck(this, Page);

		options = options || {};
		this.options = options;
		this.options['setData'] = this.setData;
		this.map = {};
	}

	_createClass(Page, [{
		key: '$load',
		value: function $load(params, data) {

			data = data || {};
			this.options['data'] = this.options['data'] || {};
			this.options['data'] = _utils2.default.extend(this.options['data'], data);
			this.options['onReady'](params);

			this.bindEvent(params["selector"], params["__component"]);

			// this.playground();
		}
	}, {
		key: 'bindEvent',
		value: function bindEvent(selector, isComponet) {
			var _this = this;

			if (document == undefined) {
				return;
			}

			if (document.querySelectorAll == undefined) {
				return;
			}
			// 处理组件
			var bindtaps = [];
			if (isComponet) {
				bindtaps = document.querySelectorAll(selector + ' > [bindtap]');
			} else {
				// 页面
				bindtaps = document.querySelectorAll('[bindtap]');
			}

			// 效率较低, 下一版应该优化
			function findParentTag(el, tag) {

				if (el.tagName === tag) {
					return el;
				}

				while (el.parentNode) {
					el = el.parentNode;
					if (el.tagName === tag) return el;
				}
				return null;
			}

			for (var i in bindtaps) {
				var elm = bindtaps[i];

				if (!elm.getAttribute) {
					continue;
				}

				var init = elm.getAttribute("__event-inited");
				var method = elm.getAttribute("bindtap");

				if (elm.addEventListener && init !== "__event-inited") {
					var _ret = function () {

						var com = findParentTag(elm, "COMPONENT");
						if (com !== null) {
							return 'continue';
						}

						var evt = _this.options[method];
						if (typeof evt == "function") {
							elm.addEventListener('click', function (event) {
								try {

									// 运行事件
									var response = evt(event);

									// 阻止冒泡
									if (response === false) {
										if (!event) var event = window.event;
										event.cancelBubble = true;
										if (event.stopPropagation) event.stopPropagation();
									}
								} catch (e) {
									console.log("Event Error:", evt);
								}
							});
							elm.setAttribute("__event-inited", "__event-inited");
						}
					}();

					if (_ret === 'continue') continue;
				}
			}
		}
	}, {
		key: 'playground',
		value: function playground() {

			var that = this;
			var node = null;
			if (typeof that.map['vnode-01'] == 'undefined') {
				node = document.getElementById('vnode-01');
				that.map['vnode-01'] = h('div#vnode-01', { style: { color: '#565656' } }, [h('div#vnode-0101', '这样好吗？'), h('div', 'OK')]);
			} else {
				node = that.map['vnode-01'];
			}

			var vnode = h('div#vnode-01', { style: { color: '#565656' } }, [h('div#vnode-0101', '这样好吗？'), h('div', 'DOING')]);

			that.map['vnode-01'] = vnode;
			patch(node, vnode);

			var vnode2 = h('div#vnode-01', { style: { color: '#565656' } }, [h('div#vnode-0101', { style: { color: '#ff0000' } }, '没啥不好的！'), h('div', 'DONE')]);

			var nodeChild = document.getElementById('vnode-0101');
			var vnode3 = h('div#vnode-0101', { style: { color: '#ff00ff' } }, '我也觉得不太好！'); // 局部更新

			setTimeout(function () {
				patch(nodeChild, vnode3);

				setTimeout(function () {
					that.map['vnode-01'] = vnode2;
					patch(vnode, vnode2);

					setTimeout(function () {
						that.playground();
					}, 1000);
				}, 1000);
			}, 1000);
		}
	}, {
		key: 'init',
		value: function init() {
			this.options['onReady'] = this.options['onReady'] || function (params) {};
		}
	}, {
		key: 'ready',
		value: function ready(cb) {
			for (var _len = arguments.length, args = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
				args[_key - 1] = arguments[_key];
			}

			///兼容FF,Google
			if (document.addEventListener) {
				document.addEventListener('DOMContentLoaded', function () {
					document.removeEventListener('DOMContentLoaded', args, false);
					cb();
				}, false);
			}
			//兼容IE
			else if (document.attachEvent) {
					document.attachEvent('onreadystatechange', function () {
						if (document.readyState == "complete") {
							document.detachEvent("onreadystatechange", args);
							cb();
						}
					});
				} else if (document.lastChild == document.body) {
					cb();
				}
		}
	}, {
		key: 'setData',
		value: function setData(data) {
			console.log('setData 方法暂未实现', data);
		}
	}]);

	return Page;
}();

function PageInst(options) {

	var page = new Page(options);
	page.init();

	window.mina.page = page;
	window.page = window.mina.page.options;
	return page;
}

module.exports = PageInst;

/***/ }),
/* 2 */,
/* 3 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

/**
 * MINA WEB JS SDK 常用工具函数
 *	
 * Require quill v1.2.4, highlight.js v9.11.0, katex v0.7.1
 * Copyright (c) 2014-2017 JianMoApp.com
 * Licensed MIT
 * https://JianMoApp.com/
 * https://help.JianMoApp.com
 */

var Utils = function () {
	function Utils(options) {
		_classCallCheck(this, Utils);
	}

	/**
  * 检查对象是否为对象
  * @param  {[type]}  item [description]
  * @return {Boolean}      [description]
  */


	_createClass(Utils, [{
		key: 'isObject',
		value: function isObject(item) {
			return item && (typeof item === 'undefined' ? 'undefined' : _typeof(item)) === 'object' && !Array.isArray(item);
		}

		/**
   * 深度合并对象
   * @param  {[type]}    target  [description]
   * @param  {...[type]} sources [description]
   * @return {[type]}            [description]
   */

	}, {
		key: 'extend',
		value: function extend(target) {
			for (var _len = arguments.length, sources = Array(_len > 1 ? _len - 1 : 0), _key = 1; _key < _len; _key++) {
				sources[_key - 1] = arguments[_key];
			}

			if (!sources.length) return target;
			var source = sources.shift();

			if (this.isObject(target) && this.isObject(source)) {
				for (var key in source) {
					if (this.isObject(source[key])) {
						if (!target[key]) Object.assign(target, _defineProperty({}, key, {}));
						this.extend(target[key], source[key]);
					} else {
						Object.assign(target, _defineProperty({}, key, source[key]));
					}
				}
			}

			return this.extend.apply(this, [target].concat(sources));
		}
	}]);

	return Utils;
}();

module.exports = new Utils();

/***/ }),
/* 4 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;var require;var require;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

(function (f) {
    if (( false ? "undefined" : _typeof(exports)) === "object" && typeof module !== "undefined") {
        module.exports = f();
    } else if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_FACTORY__ = (f),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    } else {
        var g;if (typeof window !== "undefined") {
            g = window;
        } else if (typeof global !== "undefined") {
            g = global;
        } else if (typeof self !== "undefined") {
            g = self;
        } else {
            g = this;
        }g.snabbdom_class = f();
    }
})(function () {
    var define, module, exports;return function e(t, n, r) {
        function s(o, u) {
            if (!n[o]) {
                if (!t[o]) {
                    var a = typeof require == "function" && require;if (!u && a) return require(o, !0);if (i) return i(o, !0);var f = new Error("Cannot find module '" + o + "'");throw f.code = "MODULE_NOT_FOUND", f;
                }var l = n[o] = { exports: {} };t[o][0].call(l.exports, function (e) {
                    var n = t[o][1][e];return s(n ? n : e);
                }, l, l.exports, e, t, n, r);
            }return n[o].exports;
        }var i = typeof require == "function" && require;for (var o = 0; o < r.length; o++) {
            s(r[o]);
        }return s;
    }({ 1: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            function updateClass(oldVnode, vnode) {
                var cur,
                    name,
                    elm = vnode.elm,
                    oldClass = oldVnode.data.class,
                    klass = vnode.data.class;
                if (!oldClass && !klass) return;
                if (oldClass === klass) return;
                oldClass = oldClass || {};
                klass = klass || {};
                for (name in oldClass) {
                    if (!klass[name]) {
                        elm.classList.remove(name);
                    }
                }
                for (name in klass) {
                    cur = klass[name];
                    if (cur !== oldClass[name]) {
                        elm.classList[cur ? 'add' : 'remove'](name);
                    }
                }
            }
            exports.classModule = { create: updateClass, update: updateClass };
            exports.default = exports.classModule;
        }, {}] }, {}, [1])(1);
});

/***/ }),
/* 5 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;var require;var require;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

(function (f) {
    if (( false ? "undefined" : _typeof(exports)) === "object" && typeof module !== "undefined") {
        module.exports = f();
    } else if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_FACTORY__ = (f),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    } else {
        var g;if (typeof window !== "undefined") {
            g = window;
        } else if (typeof global !== "undefined") {
            g = global;
        } else if (typeof self !== "undefined") {
            g = self;
        } else {
            g = this;
        }g.snabbdom_eventlisteners = f();
    }
})(function () {
    var define, module, exports;return function e(t, n, r) {
        function s(o, u) {
            if (!n[o]) {
                if (!t[o]) {
                    var a = typeof require == "function" && require;if (!u && a) return require(o, !0);if (i) return i(o, !0);var f = new Error("Cannot find module '" + o + "'");throw f.code = "MODULE_NOT_FOUND", f;
                }var l = n[o] = { exports: {} };t[o][0].call(l.exports, function (e) {
                    var n = t[o][1][e];return s(n ? n : e);
                }, l, l.exports, e, t, n, r);
            }return n[o].exports;
        }var i = typeof require == "function" && require;for (var o = 0; o < r.length; o++) {
            s(r[o]);
        }return s;
    }({ 1: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            function invokeHandler(handler, vnode, event) {
                if (typeof handler === "function") {
                    // call function handler
                    handler.call(vnode, event, vnode);
                } else if ((typeof handler === "undefined" ? "undefined" : _typeof(handler)) === "object") {
                    // call handler with arguments
                    if (typeof handler[0] === "function") {
                        // special case for single argument for performance
                        if (handler.length === 2) {
                            handler[0].call(vnode, handler[1], event, vnode);
                        } else {
                            var args = handler.slice(1);
                            args.push(event);
                            args.push(vnode);
                            handler[0].apply(vnode, args);
                        }
                    } else {
                        // call multiple handlers
                        for (var i = 0; i < handler.length; i++) {
                            invokeHandler(handler[i]);
                        }
                    }
                }
            }
            function handleEvent(event, vnode) {
                var name = event.type,
                    on = vnode.data.on;
                // call event handler(s) if exists
                if (on && on[name]) {
                    invokeHandler(on[name], vnode, event);
                }
            }
            function createListener() {
                return function handler(event) {
                    handleEvent(event, handler.vnode);
                };
            }
            function updateEventListeners(oldVnode, vnode) {
                var oldOn = oldVnode.data.on,
                    oldListener = oldVnode.listener,
                    oldElm = oldVnode.elm,
                    on = vnode && vnode.data.on,
                    elm = vnode && vnode.elm,
                    name;
                // optimization for reused immutable handlers
                if (oldOn === on) {
                    return;
                }
                // remove existing listeners which no longer used
                if (oldOn && oldListener) {
                    // if element changed or deleted we remove all existing listeners unconditionally
                    if (!on) {
                        for (name in oldOn) {
                            // remove listener if element was changed or existing listeners removed
                            oldElm.removeEventListener(name, oldListener, false);
                        }
                    } else {
                        for (name in oldOn) {
                            // remove listener if existing listener removed
                            if (!on[name]) {
                                oldElm.removeEventListener(name, oldListener, false);
                            }
                        }
                    }
                }
                // add new listeners which has not already attached
                if (on) {
                    // reuse existing listener or create new
                    var listener = vnode.listener = oldVnode.listener || createListener();
                    // update vnode for listener
                    listener.vnode = vnode;
                    // if element changed or added we add all needed listeners unconditionally
                    if (!oldOn) {
                        for (name in on) {
                            // add listener if element was changed or new listeners added
                            elm.addEventListener(name, listener, false);
                        }
                    } else {
                        for (name in on) {
                            // add listener if new listener added
                            if (!oldOn[name]) {
                                elm.addEventListener(name, listener, false);
                            }
                        }
                    }
                }
            }
            exports.eventListenersModule = {
                create: updateEventListeners,
                update: updateEventListeners,
                destroy: updateEventListeners
            };
            exports.default = exports.eventListenersModule;
        }, {}] }, {}, [1])(1);
});

/***/ }),
/* 6 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;var require;var require;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

(function (f) {
    if (( false ? "undefined" : _typeof(exports)) === "object" && typeof module !== "undefined") {
        module.exports = f();
    } else if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_FACTORY__ = (f),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    } else {
        var g;if (typeof window !== "undefined") {
            g = window;
        } else if (typeof global !== "undefined") {
            g = global;
        } else if (typeof self !== "undefined") {
            g = self;
        } else {
            g = this;
        }g.snabbdom_props = f();
    }
})(function () {
    var define, module, exports;return function e(t, n, r) {
        function s(o, u) {
            if (!n[o]) {
                if (!t[o]) {
                    var a = typeof require == "function" && require;if (!u && a) return require(o, !0);if (i) return i(o, !0);var f = new Error("Cannot find module '" + o + "'");throw f.code = "MODULE_NOT_FOUND", f;
                }var l = n[o] = { exports: {} };t[o][0].call(l.exports, function (e) {
                    var n = t[o][1][e];return s(n ? n : e);
                }, l, l.exports, e, t, n, r);
            }return n[o].exports;
        }var i = typeof require == "function" && require;for (var o = 0; o < r.length; o++) {
            s(r[o]);
        }return s;
    }({ 1: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            function updateProps(oldVnode, vnode) {
                var key,
                    cur,
                    old,
                    elm = vnode.elm,
                    oldProps = oldVnode.data.props,
                    props = vnode.data.props;
                if (!oldProps && !props) return;
                if (oldProps === props) return;
                oldProps = oldProps || {};
                props = props || {};
                for (key in oldProps) {
                    if (!props[key]) {
                        delete elm[key];
                    }
                }
                for (key in props) {
                    cur = props[key];
                    old = oldProps[key];
                    if (old !== cur && (key !== 'value' || elm[key] !== cur)) {
                        elm[key] = cur;
                    }
                }
            }
            exports.propsModule = { create: updateProps, update: updateProps };
            exports.default = exports.propsModule;
        }, {}] }, {}, [1])(1);
});

/***/ }),
/* 7 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;var require;var require;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

(function (f) {
    if (( false ? "undefined" : _typeof(exports)) === "object" && typeof module !== "undefined") {
        module.exports = f();
    } else if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_FACTORY__ = (f),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    } else {
        var g;if (typeof window !== "undefined") {
            g = window;
        } else if (typeof global !== "undefined") {
            g = global;
        } else if (typeof self !== "undefined") {
            g = self;
        } else {
            g = this;
        }g.snabbdom_style = f();
    }
})(function () {
    var define, module, exports;return function e(t, n, r) {
        function s(o, u) {
            if (!n[o]) {
                if (!t[o]) {
                    var a = typeof require == "function" && require;if (!u && a) return require(o, !0);if (i) return i(o, !0);var f = new Error("Cannot find module '" + o + "'");throw f.code = "MODULE_NOT_FOUND", f;
                }var l = n[o] = { exports: {} };t[o][0].call(l.exports, function (e) {
                    var n = t[o][1][e];return s(n ? n : e);
                }, l, l.exports, e, t, n, r);
            }return n[o].exports;
        }var i = typeof require == "function" && require;for (var o = 0; o < r.length; o++) {
            s(r[o]);
        }return s;
    }({ 1: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            var raf = typeof window !== 'undefined' && window.requestAnimationFrame || setTimeout;
            var nextFrame = function nextFrame(fn) {
                raf(function () {
                    raf(fn);
                });
            };
            function setNextFrame(obj, prop, val) {
                nextFrame(function () {
                    obj[prop] = val;
                });
            }
            function updateStyle(oldVnode, vnode) {
                var cur,
                    name,
                    elm = vnode.elm,
                    oldStyle = oldVnode.data.style,
                    style = vnode.data.style;
                if (!oldStyle && !style) return;
                if (oldStyle === style) return;
                oldStyle = oldStyle || {};
                style = style || {};
                var oldHasDel = 'delayed' in oldStyle;
                for (name in oldStyle) {
                    if (!style[name]) {
                        if (name[0] === '-' && name[1] === '-') {
                            elm.style.removeProperty(name);
                        } else {
                            elm.style[name] = '';
                        }
                    }
                }
                for (name in style) {
                    cur = style[name];
                    if (name === 'delayed' && style.delayed) {
                        for (var name2 in style.delayed) {
                            cur = style.delayed[name2];
                            if (!oldHasDel || cur !== oldStyle.delayed[name2]) {
                                setNextFrame(elm.style, name2, cur);
                            }
                        }
                    } else if (name !== 'remove' && cur !== oldStyle[name]) {
                        if (name[0] === '-' && name[1] === '-') {
                            elm.style.setProperty(name, cur);
                        } else {
                            elm.style[name] = cur;
                        }
                    }
                }
            }
            function applyDestroyStyle(vnode) {
                var style,
                    name,
                    elm = vnode.elm,
                    s = vnode.data.style;
                if (!s || !(style = s.destroy)) return;
                for (name in style) {
                    elm.style[name] = style[name];
                }
            }
            function applyRemoveStyle(vnode, rm) {
                var s = vnode.data.style;
                if (!s || !s.remove) {
                    rm();
                    return;
                }
                var name,
                    elm = vnode.elm,
                    i = 0,
                    compStyle,
                    style = s.remove,
                    amount = 0,
                    applied = [];
                for (name in style) {
                    applied.push(name);
                    elm.style[name] = style[name];
                }
                compStyle = getComputedStyle(elm);
                var props = compStyle['transition-property'].split(', ');
                for (; i < props.length; ++i) {
                    if (applied.indexOf(props[i]) !== -1) amount++;
                }
                elm.addEventListener('transitionend', function (ev) {
                    if (ev.target === elm) --amount;
                    if (amount === 0) rm();
                });
            }
            exports.styleModule = {
                create: updateStyle,
                update: updateStyle,
                destroy: applyDestroyStyle,
                remove: applyRemoveStyle
            };
            exports.default = exports.styleModule;
        }, {}] }, {}, [1])(1);
});

/***/ }),
/* 8 */
/***/ (function(module, exports, __webpack_require__) {

"use strict";
var __WEBPACK_AMD_DEFINE_FACTORY__, __WEBPACK_AMD_DEFINE_ARRAY__, __WEBPACK_AMD_DEFINE_RESULT__;var require;var require;

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; };

(function (f) {
    if (( false ? "undefined" : _typeof(exports)) === "object" && typeof module !== "undefined") {
        module.exports = f();
    } else if (true) {
        !(__WEBPACK_AMD_DEFINE_ARRAY__ = [], __WEBPACK_AMD_DEFINE_FACTORY__ = (f),
				__WEBPACK_AMD_DEFINE_RESULT__ = (typeof __WEBPACK_AMD_DEFINE_FACTORY__ === 'function' ?
				(__WEBPACK_AMD_DEFINE_FACTORY__.apply(exports, __WEBPACK_AMD_DEFINE_ARRAY__)) : __WEBPACK_AMD_DEFINE_FACTORY__),
				__WEBPACK_AMD_DEFINE_RESULT__ !== undefined && (module.exports = __WEBPACK_AMD_DEFINE_RESULT__));
    } else {
        var g;if (typeof window !== "undefined") {
            g = window;
        } else if (typeof global !== "undefined") {
            g = global;
        } else if (typeof self !== "undefined") {
            g = self;
        } else {
            g = this;
        }g.snabbdom = f();
    }
})(function () {
    var define, module, exports;return function e(t, n, r) {
        function s(o, u) {
            if (!n[o]) {
                if (!t[o]) {
                    var a = typeof require == "function" && require;if (!u && a) return require(o, !0);if (i) return i(o, !0);var f = new Error("Cannot find module '" + o + "'");throw f.code = "MODULE_NOT_FOUND", f;
                }var l = n[o] = { exports: {} };t[o][0].call(l.exports, function (e) {
                    var n = t[o][1][e];return s(n ? n : e);
                }, l, l.exports, e, t, n, r);
            }return n[o].exports;
        }var i = typeof require == "function" && require;for (var o = 0; o < r.length; o++) {
            s(r[o]);
        }return s;
    }({ 1: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            var vnode_1 = require("./vnode");
            var is = require("./is");
            function addNS(data, children, sel) {
                data.ns = 'http://www.w3.org/2000/svg';
                if (sel !== 'foreignObject' && children !== undefined) {
                    for (var i = 0; i < children.length; ++i) {
                        var childData = children[i].data;
                        if (childData !== undefined) {
                            addNS(childData, children[i].children, children[i].sel);
                        }
                    }
                }
            }
            function h(sel, b, c) {
                var data = {},
                    children,
                    text,
                    i;
                if (c !== undefined) {
                    data = b;
                    if (is.array(c)) {
                        children = c;
                    } else if (is.primitive(c)) {
                        text = c;
                    } else if (c && c.sel) {
                        children = [c];
                    }
                } else if (b !== undefined) {
                    if (is.array(b)) {
                        children = b;
                    } else if (is.primitive(b)) {
                        text = b;
                    } else if (b && b.sel) {
                        children = [b];
                    } else {
                        data = b;
                    }
                }
                if (is.array(children)) {
                    for (i = 0; i < children.length; ++i) {
                        if (is.primitive(children[i])) children[i] = vnode_1.vnode(undefined, undefined, undefined, children[i]);
                    }
                }
                if (sel[0] === 's' && sel[1] === 'v' && sel[2] === 'g' && (sel.length === 3 || sel[3] === '.' || sel[3] === '#')) {
                    addNS(data, children, sel);
                }
                return vnode_1.vnode(sel, data, children, text, undefined);
            }
            exports.h = h;
            ;
            exports.default = h;
        }, { "./is": 3, "./vnode": 6 }], 2: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            function createElement(tagName) {
                return document.createElement(tagName);
            }
            function createElementNS(namespaceURI, qualifiedName) {
                return document.createElementNS(namespaceURI, qualifiedName);
            }
            function createTextNode(text) {
                return document.createTextNode(text);
            }
            function createComment(text) {
                return document.createComment(text);
            }
            function insertBefore(parentNode, newNode, referenceNode) {
                parentNode.insertBefore(newNode, referenceNode);
            }
            function removeChild(node, child) {
                node.removeChild(child);
            }
            function appendChild(node, child) {
                node.appendChild(child);
            }
            function parentNode(node) {
                return node.parentNode;
            }
            function nextSibling(node) {
                return node.nextSibling;
            }
            function tagName(elm) {
                return elm.tagName;
            }
            function setTextContent(node, text) {
                node.textContent = text;
            }
            function getTextContent(node) {
                return node.textContent;
            }
            function isElement(node) {
                return node.nodeType === 1;
            }
            function isText(node) {
                return node.nodeType === 3;
            }
            function isComment(node) {
                return node.nodeType === 8;
            }
            exports.htmlDomApi = {
                createElement: createElement,
                createElementNS: createElementNS,
                createTextNode: createTextNode,
                createComment: createComment,
                insertBefore: insertBefore,
                removeChild: removeChild,
                appendChild: appendChild,
                parentNode: parentNode,
                nextSibling: nextSibling,
                tagName: tagName,
                setTextContent: setTextContent,
                getTextContent: getTextContent,
                isElement: isElement,
                isText: isText,
                isComment: isComment
            };
            exports.default = exports.htmlDomApi;
        }, {}], 3: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            exports.array = Array.isArray;
            function primitive(s) {
                return typeof s === 'string' || typeof s === 'number';
            }
            exports.primitive = primitive;
        }, {}], 4: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            var vnode_1 = require("./vnode");
            var is = require("./is");
            var htmldomapi_1 = require("./htmldomapi");
            function isUndef(s) {
                return s === undefined;
            }
            function isDef(s) {
                return s !== undefined;
            }
            var emptyNode = vnode_1.default('', {}, [], undefined, undefined);
            function sameVnode(vnode1, vnode2) {
                return vnode1.key === vnode2.key && vnode1.sel === vnode2.sel;
            }
            function isVnode(vnode) {
                return vnode.sel !== undefined;
            }
            function createKeyToOldIdx(children, beginIdx, endIdx) {
                var i,
                    map = {},
                    key,
                    ch;
                for (i = beginIdx; i <= endIdx; ++i) {
                    ch = children[i];
                    if (ch != null) {
                        key = ch.key;
                        if (key !== undefined) map[key] = i;
                    }
                }
                return map;
            }
            var hooks = ['create', 'update', 'remove', 'destroy', 'pre', 'post'];
            var h_1 = require("./h");
            exports.h = h_1.h;
            var thunk_1 = require("./thunk");
            exports.thunk = thunk_1.thunk;
            function init(modules, domApi) {
                var i,
                    j,
                    cbs = {};
                var api = domApi !== undefined ? domApi : htmldomapi_1.default;
                for (i = 0; i < hooks.length; ++i) {
                    cbs[hooks[i]] = [];
                    for (j = 0; j < modules.length; ++j) {
                        var hook = modules[j][hooks[i]];
                        if (hook !== undefined) {
                            cbs[hooks[i]].push(hook);
                        }
                    }
                }
                function emptyNodeAt(elm) {
                    var id = elm.id ? '#' + elm.id : '';
                    var c = elm.className ? '.' + elm.className.split(' ').join('.') : '';
                    return vnode_1.default(api.tagName(elm).toLowerCase() + id + c, {}, [], undefined, elm);
                }
                function createRmCb(childElm, listeners) {
                    return function rmCb() {
                        if (--listeners === 0) {
                            var parent_1 = api.parentNode(childElm);
                            api.removeChild(parent_1, childElm);
                        }
                    };
                }
                function createElm(vnode, insertedVnodeQueue) {
                    var i,
                        data = vnode.data;
                    if (data !== undefined) {
                        if (isDef(i = data.hook) && isDef(i = i.init)) {
                            i(vnode);
                            data = vnode.data;
                        }
                    }
                    var children = vnode.children,
                        sel = vnode.sel;
                    if (sel === '!') {
                        if (isUndef(vnode.text)) {
                            vnode.text = '';
                        }
                        vnode.elm = api.createComment(vnode.text);
                    } else if (sel !== undefined) {
                        // Parse selector
                        var hashIdx = sel.indexOf('#');
                        var dotIdx = sel.indexOf('.', hashIdx);
                        var hash = hashIdx > 0 ? hashIdx : sel.length;
                        var dot = dotIdx > 0 ? dotIdx : sel.length;
                        var tag = hashIdx !== -1 || dotIdx !== -1 ? sel.slice(0, Math.min(hash, dot)) : sel;
                        var elm = vnode.elm = isDef(data) && isDef(i = data.ns) ? api.createElementNS(i, tag) : api.createElement(tag);
                        if (hash < dot) elm.setAttribute('id', sel.slice(hash + 1, dot));
                        if (dotIdx > 0) elm.setAttribute('class', sel.slice(dot + 1).replace(/\./g, ' '));
                        for (i = 0; i < cbs.create.length; ++i) {
                            cbs.create[i](emptyNode, vnode);
                        }if (is.array(children)) {
                            for (i = 0; i < children.length; ++i) {
                                var ch = children[i];
                                if (ch != null) {
                                    api.appendChild(elm, createElm(ch, insertedVnodeQueue));
                                }
                            }
                        } else if (is.primitive(vnode.text)) {
                            api.appendChild(elm, api.createTextNode(vnode.text));
                        }
                        i = vnode.data.hook; // Reuse variable
                        if (isDef(i)) {
                            if (i.create) i.create(emptyNode, vnode);
                            if (i.insert) insertedVnodeQueue.push(vnode);
                        }
                    } else {
                        vnode.elm = api.createTextNode(vnode.text);
                    }
                    return vnode.elm;
                }
                function addVnodes(parentElm, before, vnodes, startIdx, endIdx, insertedVnodeQueue) {
                    for (; startIdx <= endIdx; ++startIdx) {
                        var ch = vnodes[startIdx];
                        if (ch != null) {
                            api.insertBefore(parentElm, createElm(ch, insertedVnodeQueue), before);
                        }
                    }
                }
                function invokeDestroyHook(vnode) {
                    var i,
                        j,
                        data = vnode.data;
                    if (data !== undefined) {
                        if (isDef(i = data.hook) && isDef(i = i.destroy)) i(vnode);
                        for (i = 0; i < cbs.destroy.length; ++i) {
                            cbs.destroy[i](vnode);
                        }if (vnode.children !== undefined) {
                            for (j = 0; j < vnode.children.length; ++j) {
                                i = vnode.children[j];
                                if (i != null && typeof i !== "string") {
                                    invokeDestroyHook(i);
                                }
                            }
                        }
                    }
                }
                function removeVnodes(parentElm, vnodes, startIdx, endIdx) {
                    for (; startIdx <= endIdx; ++startIdx) {
                        var i_1 = void 0,
                            listeners = void 0,
                            rm = void 0,
                            ch = vnodes[startIdx];
                        if (ch != null) {
                            if (isDef(ch.sel)) {
                                invokeDestroyHook(ch);
                                listeners = cbs.remove.length + 1;
                                rm = createRmCb(ch.elm, listeners);
                                for (i_1 = 0; i_1 < cbs.remove.length; ++i_1) {
                                    cbs.remove[i_1](ch, rm);
                                }if (isDef(i_1 = ch.data) && isDef(i_1 = i_1.hook) && isDef(i_1 = i_1.remove)) {
                                    i_1(ch, rm);
                                } else {
                                    rm();
                                }
                            } else {
                                api.removeChild(parentElm, ch.elm);
                            }
                        }
                    }
                }
                function updateChildren(parentElm, oldCh, newCh, insertedVnodeQueue) {
                    var oldStartIdx = 0,
                        newStartIdx = 0;
                    var oldEndIdx = oldCh.length - 1;
                    var oldStartVnode = oldCh[0];
                    var oldEndVnode = oldCh[oldEndIdx];
                    var newEndIdx = newCh.length - 1;
                    var newStartVnode = newCh[0];
                    var newEndVnode = newCh[newEndIdx];
                    var oldKeyToIdx;
                    var idxInOld;
                    var elmToMove;
                    var before;
                    while (oldStartIdx <= oldEndIdx && newStartIdx <= newEndIdx) {
                        if (oldStartVnode == null) {
                            oldStartVnode = oldCh[++oldStartIdx]; // Vnode might have been moved left
                        } else if (oldEndVnode == null) {
                            oldEndVnode = oldCh[--oldEndIdx];
                        } else if (newStartVnode == null) {
                            newStartVnode = newCh[++newStartIdx];
                        } else if (newEndVnode == null) {
                            newEndVnode = newCh[--newEndIdx];
                        } else if (sameVnode(oldStartVnode, newStartVnode)) {
                            patchVnode(oldStartVnode, newStartVnode, insertedVnodeQueue);
                            oldStartVnode = oldCh[++oldStartIdx];
                            newStartVnode = newCh[++newStartIdx];
                        } else if (sameVnode(oldEndVnode, newEndVnode)) {
                            patchVnode(oldEndVnode, newEndVnode, insertedVnodeQueue);
                            oldEndVnode = oldCh[--oldEndIdx];
                            newEndVnode = newCh[--newEndIdx];
                        } else if (sameVnode(oldStartVnode, newEndVnode)) {
                            patchVnode(oldStartVnode, newEndVnode, insertedVnodeQueue);
                            api.insertBefore(parentElm, oldStartVnode.elm, api.nextSibling(oldEndVnode.elm));
                            oldStartVnode = oldCh[++oldStartIdx];
                            newEndVnode = newCh[--newEndIdx];
                        } else if (sameVnode(oldEndVnode, newStartVnode)) {
                            patchVnode(oldEndVnode, newStartVnode, insertedVnodeQueue);
                            api.insertBefore(parentElm, oldEndVnode.elm, oldStartVnode.elm);
                            oldEndVnode = oldCh[--oldEndIdx];
                            newStartVnode = newCh[++newStartIdx];
                        } else {
                            if (oldKeyToIdx === undefined) {
                                oldKeyToIdx = createKeyToOldIdx(oldCh, oldStartIdx, oldEndIdx);
                            }
                            idxInOld = oldKeyToIdx[newStartVnode.key];
                            if (isUndef(idxInOld)) {
                                api.insertBefore(parentElm, createElm(newStartVnode, insertedVnodeQueue), oldStartVnode.elm);
                                newStartVnode = newCh[++newStartIdx];
                            } else {
                                elmToMove = oldCh[idxInOld];
                                if (elmToMove.sel !== newStartVnode.sel) {
                                    api.insertBefore(parentElm, createElm(newStartVnode, insertedVnodeQueue), oldStartVnode.elm);
                                } else {
                                    patchVnode(elmToMove, newStartVnode, insertedVnodeQueue);
                                    oldCh[idxInOld] = undefined;
                                    api.insertBefore(parentElm, elmToMove.elm, oldStartVnode.elm);
                                }
                                newStartVnode = newCh[++newStartIdx];
                            }
                        }
                    }
                    if (oldStartIdx > oldEndIdx) {
                        before = newCh[newEndIdx + 1] == null ? null : newCh[newEndIdx + 1].elm;
                        addVnodes(parentElm, before, newCh, newStartIdx, newEndIdx, insertedVnodeQueue);
                    } else if (newStartIdx > newEndIdx) {
                        removeVnodes(parentElm, oldCh, oldStartIdx, oldEndIdx);
                    }
                }
                function patchVnode(oldVnode, vnode, insertedVnodeQueue) {
                    var i, hook;
                    if (isDef(i = vnode.data) && isDef(hook = i.hook) && isDef(i = hook.prepatch)) {
                        i(oldVnode, vnode);
                    }
                    var elm = vnode.elm = oldVnode.elm;
                    var oldCh = oldVnode.children;
                    var ch = vnode.children;
                    if (oldVnode === vnode) return;
                    if (vnode.data !== undefined) {
                        for (i = 0; i < cbs.update.length; ++i) {
                            cbs.update[i](oldVnode, vnode);
                        }i = vnode.data.hook;
                        if (isDef(i) && isDef(i = i.update)) i(oldVnode, vnode);
                    }
                    if (isUndef(vnode.text)) {
                        if (isDef(oldCh) && isDef(ch)) {
                            if (oldCh !== ch) updateChildren(elm, oldCh, ch, insertedVnodeQueue);
                        } else if (isDef(ch)) {
                            if (isDef(oldVnode.text)) api.setTextContent(elm, '');
                            addVnodes(elm, null, ch, 0, ch.length - 1, insertedVnodeQueue);
                        } else if (isDef(oldCh)) {
                            removeVnodes(elm, oldCh, 0, oldCh.length - 1);
                        } else if (isDef(oldVnode.text)) {
                            api.setTextContent(elm, '');
                        }
                    } else if (oldVnode.text !== vnode.text) {
                        api.setTextContent(elm, vnode.text);
                    }
                    if (isDef(hook) && isDef(i = hook.postpatch)) {
                        i(oldVnode, vnode);
                    }
                }
                return function patch(oldVnode, vnode) {
                    var i, elm, parent;
                    var insertedVnodeQueue = [];
                    for (i = 0; i < cbs.pre.length; ++i) {
                        cbs.pre[i]();
                    }if (!isVnode(oldVnode)) {
                        oldVnode = emptyNodeAt(oldVnode);
                    }
                    if (sameVnode(oldVnode, vnode)) {
                        patchVnode(oldVnode, vnode, insertedVnodeQueue);
                    } else {
                        elm = oldVnode.elm;
                        parent = api.parentNode(elm);
                        createElm(vnode, insertedVnodeQueue);
                        if (parent !== null) {
                            api.insertBefore(parent, vnode.elm, api.nextSibling(elm));
                            removeVnodes(parent, [oldVnode], 0, 0);
                        }
                    }
                    for (i = 0; i < insertedVnodeQueue.length; ++i) {
                        insertedVnodeQueue[i].data.hook.insert(insertedVnodeQueue[i]);
                    }
                    for (i = 0; i < cbs.post.length; ++i) {
                        cbs.post[i]();
                    }return vnode;
                };
            }
            exports.init = init;
        }, { "./h": 1, "./htmldomapi": 2, "./is": 3, "./thunk": 5, "./vnode": 6 }], 5: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            var h_1 = require("./h");
            function copyToThunk(vnode, thunk) {
                thunk.elm = vnode.elm;
                vnode.data.fn = thunk.data.fn;
                vnode.data.args = thunk.data.args;
                thunk.data = vnode.data;
                thunk.children = vnode.children;
                thunk.text = vnode.text;
                thunk.elm = vnode.elm;
            }
            function init(thunk) {
                var cur = thunk.data;
                var vnode = cur.fn.apply(undefined, cur.args);
                copyToThunk(vnode, thunk);
            }
            function prepatch(oldVnode, thunk) {
                var i,
                    old = oldVnode.data,
                    cur = thunk.data;
                var oldArgs = old.args,
                    args = cur.args;
                if (old.fn !== cur.fn || oldArgs.length !== args.length) {
                    copyToThunk(cur.fn.apply(undefined, args), thunk);
                    return;
                }
                for (i = 0; i < args.length; ++i) {
                    if (oldArgs[i] !== args[i]) {
                        copyToThunk(cur.fn.apply(undefined, args), thunk);
                        return;
                    }
                }
                copyToThunk(oldVnode, thunk);
            }
            exports.thunk = function thunk(sel, key, fn, args) {
                if (args === undefined) {
                    args = fn;
                    fn = key;
                    key = undefined;
                }
                return h_1.h(sel, {
                    key: key,
                    hook: { init: init, prepatch: prepatch },
                    fn: fn,
                    args: args
                });
            };
            exports.default = exports.thunk;
        }, { "./h": 1 }], 6: [function (require, module, exports) {
            "use strict";

            Object.defineProperty(exports, "__esModule", { value: true });
            function vnode(sel, data, children, text, elm) {
                var key = data === undefined ? undefined : data.key;
                return { sel: sel, data: data, children: children,
                    text: text, elm: elm, key: key };
            }
            exports.vnode = vnode;
            exports.default = vnode;
        }, {}] }, {}, [4])(4);
});

/***/ }),
/* 9 */,
/* 10 */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(1);


/***/ })
/******/ ]);
});