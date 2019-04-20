(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
'use strict';

var _world = require('../common/world.js');

var _world2 = _interopRequireDefault(_world);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

var web = getWeb();
Page({
	data: {},

	onReady: function onReady(get) {
		this.world = new _world2.default(get['name'], { param: 'ok', get: get });
		console.log('page onReady get=', get, ' web.title=', web.title);
		this.setData({ title: 'HELLO WORLD MPWORLD!' + new Date() });
	},

	hello: function hello(event) {
		this.world.pong();
		this.setData({ title: 'HELLO WORLD MPWORLD!' + new Date() });
	},

	world: null
});

},{"../common/world.js":2}],2:[function(require,module,exports){
"use strict";

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

var Hello = function () {
	function Hello(options) {
		_classCallCheck(this, Hello);

		this.options = options;
	}

	_createClass(Hello, [{
		key: "ping",
		value: function ping() {
			console.log(this.options);
		}
	}]);

	return Hello;
}();

var World = function (_Hello) {
	_inherits(World, _Hello);

	function World(name, options) {
		_classCallCheck(this, World);

		var _this = _possibleConstructorReturn(this, (World.__proto__ || Object.getPrototypeOf(World)).call(this, options));

		_this.name = name;
		return _this;
	}

	_createClass(World, [{
		key: "pong",
		value: function pong() {
			console.log(this.name, this.options);
		}
	}]);

	return World;
}(Hello);

module.exports = World;

},{}]},{},[1]);
