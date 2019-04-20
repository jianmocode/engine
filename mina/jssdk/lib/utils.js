/**
 * MINA WEB JS SDK 常用工具函数
 *	
 * Require quill v1.2.4, highlight.js v9.11.0, katex v0.7.1
 * Copyright (c) 2014-2017 JianMoApp.com
 * Licensed MIT
 * https://JianMoApp.com/
 * https://help.JianMoApp.com
 */

class Utils {
	
	constructor( options )	{
	}

    /**
     * 检查对象是否为对象
     * @param  {[type]}  item [description]
     * @return {Boolean}      [description]
     */
	isObject(item) {
		return (item && typeof item === 'object' && !Array.isArray(item));
	}


    /**
     * 深度合并对象
     * @param  {[type]}    target  [description]
     * @param  {...[type]} sources [description]
     * @return {[type]}            [description]
     */
	extend(target, ...sources) {

		if (!sources.length) return target;
		const source = sources.shift();

		if (this.isObject(target) && this.isObject(source)) {
			for (const key in source) {
				if (this.isObject(source[key])) {
					if (!target[key]) Object.assign(target, { [key]: {} });
					this.extend(target[key], source[key]);
				} else {
					Object.assign(target, { [key]: source[key] });
				}
			}
		}

		return this.extend(target, ...sources);
	}


}

module.exports = new Utils();