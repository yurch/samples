/**
 * ! Copyright (c) 2013 Yuriy Yurchenko (http://github.com/yurch)
 * 
 * @fileOverview Tool for safe logging. (soon on github!)
 * @author <a href="mailto:work@yuriy@gmail.com">Yuriy Yurchenko</a>
 * @version 0.1.1
 */

;(function(){
	/**
	 * default options
	 */
	var defOptions = {
			enable: false,
			keep: false //TODO #future
	};
	
	/**
	 * check out what's available
	 */
	var _checks = {
			console : !!window.console,
			log : !!window.console && !!window.console.log,
			error : !!window.console && !!window.console.error,
			info : !!window.console && !!window.console.info,
			debug : !!window.console && !!window.console.debug
	};
	
	/**
	 * setting of logger
	 */
	var _settings = {
			_names: ["mc", "mconsole"]
	};
	
	/**
	 * Use method of console if it is available to print the arguments
	 * @param method {string} method name (log, info, debug, error)
	 * @param args {arguments} arguments of method
	 * @this {_Mconsole}
	 * @returns this
	 */
	
	function _do (method, args){
		if(_settings.enable){
			if(_checks[method]){
				/*
				 * console in not an Object in IE
				 * it methods are not Functions
				 */
				if(console[method].apply){
					console[method].apply(console, args);
				} else {
					//little help
					helpie(method, args);
				}
			}
		}
		return this;
	}
	
	/**
	 * Help IE to print the parameters
	 * @param {string} name of console methods
	 * @returns void  
	 */
	function helpie(method, args){
		var sb = [];
		for(var l = args.length, i = 0; l--; i++){
			sb.push(getSource(args[i]));
		}
		console[method](sb.join(", "));
	}
	
	
	/**
	 * Convert parameter to string 
	 * TODO improve objects, functions
	 * @param anything
	 * @returns {string}
	 */
	function getSource(o){
		if(typeof o === "string"){
			return '"' + o + '"';
		} else if(typeof o === "object"){
			var r = [];
			for(var p in o){
				try{
					r.push(p + ":" + o[p]); //flat
				} catch (e) {
					r.push(p + ': Error - "' + e + '"');
				}
			}
			return '{' + r.join(', \n') + '}';
		} else if(typeof o === "function"){
			return o.toString().replace(/\n/g, '');
		} else {
			return o + "";
		}
	}
	
	/**
	 * Insert inst object as property of win object if they are not taken.
	 * Names of property names are taken from nameArr.
	 * @param win {Object} window object(could be any )
	 * @param nameArr {Array} names of properties
	 * @param inst {Object} object to set
	 * @returns void
	 */
	function _setPublic(win, nameArr, inst){
		var _nameArr = nameArr instanceof Array ? nameArr : typeof nameArr === "object" ? [nameArr] : [];
		
		for(var i = _nameArr.length; i--;){
			var name = _nameArr[i];
			if(!win[name]){
				win[name] = inst;
			}
		}
	}
	
	/**
	 * Set to settings all fields of all objects in the arguments.
	 * Each next object override  the same field of previous.
	 * Use to combine and update settings of logger
	 * @param def {Object} default options
	 * @param opts {Object} new options
	 * @this {_Mconsole}
	 * @returns this
	 */
	function _set(def, opts){
		for(var i = arguments.length; i--;){
			var obj = arguments[i];
			if(typeof obj === "object"){
				for(var optName in obj){
					_settings[optName] = obj[optName];
				}
			}
		}
		
		return this;
	}
	
	/**
	 * gather stuff
	 * @constructor
	 */
	function _Mconsole(options){
		if(this instanceof _Mconsole){
			this.l = this.log = function(){return _do.call(this, "log", arguments);};
			this.i = this.info = function(){return _do.call(this, "info", arguments);};
			this.d = this.debug = function(){return _do.call(this, "debug", arguments);};
			this.e = this.error = function(){return _do.call(this, "error", arguments);};
			this.set = _set;
			this.on = function(){return this.set({enable: true});};
			this.off = function(){return this.set({enable: false});};
			this.set(defOptions, options);
		} else {
			if(arguments.length > 0){
				l.apply(this, arguments);
			}
			return mcInst;
		}
	}
	
	//add logger to window
	_setPublic(window, _settings._names, new _Mconsole());
	
})();