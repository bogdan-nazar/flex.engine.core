/*
 * Набор плагинов разметки страницы
 * FlexEngine Render Bundle
 *
 * 1) Render Core [render]
 * 2) PopUp Window [pu]
 *
 * Версия: 3.1.2 (07.08.2013 17:51 +0400)
 * Copyright (c) 2003-2013 Bogdan Nazar
 *
 * Примеры и документация: http://www.flexengin.ru/docs/client/plugins/render-js
 * Двойное лицензирование: MIT и FlexEngine License.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.flexengin.ru/docs/license
 *
 * Требования: FlexEngine Core Script 3.2.0+
 */
var render;//костыль совместимости

(function(){
var __name_render = "render";
//устраняем возможность двойного создания ядра
var entity = "", p = "flex_engine_" + __name_render + "_pointer";
for (var c in window) {
	if (c.indexOf(p) == 0) {
		entity = c;
		break;
	}
}
if (!entity) return;
var __name_lib = "lib";
var __name_msgr = "msgr";
var __name_popup = "popup";
var __name_script = "render.js";

(function(cfg){
var _render = function(cfg, ss) {
	//защищенные свойства ядра
	var self = this;
	this.$ = (function() {
		var secStack = ss;
		var $ = {
			appName:					cfg.appName || "FlexEngine",
			appRoot:					cfg.appRoot || "/",
			console:					!!window.console,
			debug:						true,
			elNames:					{
				Action:					cfg.elNameAction || "",
				Form:					cfg.elNameForm || ""
			},
			elems:						{
				action:					null,
				form:					null
			},
			entity:						cfg.entity,
			form:						{
				action:					"",
				target:					""
			},
			funcs:						{
				"onPopSate":			self.historyPop.bind(self),
				"pluginsInit":			self.pluginsInit.bind(self),
			},
			html5:						((!!(history.pushState && history.state !== "undefined")) && ("classList" in document.createElement("i"))),
			init:						{
				tm:						300,
				tmObj:					null,
				tryMax:					200
			},
			lang:						cfg.lang || "ru-Ru",
			name:						__name_render,
			page:						{
				alias:					cfg.pageAlias,
				loaded:					false,
				template:				cfg.pageTemplate,
				title:					cfg.pageTitle,
				url:					{},
				urlMode:				"first"//or "last"
			},
			plugins:					{".": 0},
			protos:						{}
		};
		return function(n, val) {
			var v = $;
			if (typeof n != "string") {
				if (!(n instanceof Array)) return null;
				var l = n.length;
				if (!l) return null;
				if (l > 1) {
					v = $;
					for (var c = 0; c < (l - 1); c++) {
						if (typeof v[n[c]] != "object") return null;
						v = v[n[c]];
					}
					n = n[c];
				}
			}
			if (typeof v[n] == "undefined") return null;
			var s = (arguments.callee.caller.id && secStack[arguments.callee.caller.id] && (secStack[arguments.callee.caller.id] === arguments.callee.caller));
			if (typeof val == "undefined") {
				if (typeof v[n] == "object") return (s ? v[n] : this.clone(v[n]));
				else return v[n];
			} else {
				if (!s) return null;
				v[n] = val;
			}
		}
	})();
	this.$dirs						=	{
		admin: "admin",
		app: "core",
		modules: "classes",
		require: "require",
		source: "",
		templates: "templates",
		userdata: "data"
	};
	this.$events					=	{
		"onPageLoaded":					{done: false, listeners: [], type: "single"},
		"onFormSubmit":					{done: false, listeners: [], type: "multiple"}
	};
	this.$historyCbs				=	{};
	this._onLoad					=	[];
	this._onSubmit					=	[];
	this._pu						=	-1;
	this._silentReqs				=	[];
	this._silentXReqs				=	[];
	this._waiter					=	false;
	if (this.$("html5")) this.evWinAdd(window, "popstate", this.$(["funcs", "onPopState"]));
};
_render.prototype.___bind = (function (slice){
	// based on [(C) WebReflection - Mit Style License]
	function bind(context) {
		var self = this;
		if (1 < arguments.length) {
			var $arguments = slice.call(arguments, 1);
			return function() {
				return self.apply(context, arguments.length ? $arguments.concat(slice.call(arguments)) : $arguments);
			};
		}
		return function () {
			return arguments.length ? self.apply(context, arguments) : self.call(context);
		};
	}
	return bind;
}(Array.prototype.slice));
_render.prototype._init = function() {
	this.$("page").url = this.urlParse();
	var ns = this.$("elNames"), name = this.$("name");
	//элемент Действия
	ns.Action = document.getElementById(name + "-" + ns.Action) || false;
	if(!ns.Action) this.console(__name_script + " > " + name + "._init(): Поле операций ядра не найдено [" + name + "-" + ns.Action + "]!");
	//элемент Форма
	ns.Form = document.getElementById(name + "-" + ns.Form) || false;
	if(!ns.Form) this.console(__name_script + " > " + name + "._init(): Форма отправки данных приложения не найдена [" + name + "-" + ns.Form + "]!");
	else {
		this.$("form").target = ns.Form.target;
		this.$("form").action = ns.Form.action;
	}
	//cfg.plugins ||
	this.pluginsInit();
	this.waiterInit();
};
_render.prototype.action = function(action, path, query, target, seed) {
	return this.z_sharedBoundAction(action, path, query, target, seed)
};
_render.prototype.console = function(msg, crit) {
	return this.z_sharedConsole(msg, crit);
};
_render.prototype.clone = function() {
	return this.z_sharedClone(o, deep, skip);
};
_render.prototype.evWinAdd = function(el, evnt, func) {
	if (el.addEventListener) {
		el.addEventListener(evnt, func, false);
	} else if (el.attachEvent) {
		el.attachEvent("on" + evnt, func);
	} else {
		el[evnt] = func;
	}
};
_render.prototype.evWinFix = function(e) {
	// получить объект событие для IE
	e = e || window.event
	// добавить pageX/pageY для IE
	if (e.pageX == null && e.clientX != null) {
		var html = document.documentElement;
		var body = document.body;
		e.pageX = e.clientX + (html && html.scrollLeft || body && body.scrollLeft || 0) - (html.clientLeft || 0);
		e.pageY = e.clientY + (html && html.scrollTop || body && body.scrollTop || 0) - (html.clientTop || 0);
	}
	// добавить which для IE
	if (!e.which && e.button) {
		e.which = (e.button & 1) ? 1 : ((e.button & 2) ? 3 : ((e.button & 4) ? 2 : 0));
	}
	if (!e.target && e.srcElement) {
		e.target = e.srcElement;
	}
	return e;
};
_render.prototype.evWinRem = function(el, evnt, func) {
	if (el.removeEventListener) {
		el.removeEventListener(evnt, func, false);
	} else if (el.attachEvent) {
		el.detachEvent("on" + evnt, func);
	} else {
		el[evnt] = null;
	}
};
_render.prototype.evWinStop = function(e) {
	if (typeof e == "undefined") return;
	if (e.preventDefault) {
		e.preventDefault();
		e.stopPropagation();
	} else {
		e.returnValue = false;
		e.cancelBubble = true;
	}
};
_render.prototype.footerUpdate = function() {
	var n = this.$("name");
	var fm = document.getElementById(n + "-footer-margin");
	var f = document.getElementById(n + "-footer");
	if(!fm || !f) return;
	var fh = $(f).outerHeight(true);
	f.style.marginTop = "-" + fh + "px";
	fm.style.height = "" + fh + "px";
};
_render.prototype.getDir = function(name) {
	if (typeof this.$dirs[name] != "undefined") return this.$dirs[name];
	else return "";
};
_render.prototype.getOwnElem = function(name, prop) {
	if (typeof name != "string") name = "Unknown";
	if (typeof prop != "string") prop = false;
	try {
		eval("var val = this.el" + name + (prop ? ("." + prop) : "") + ";");
	} catch (e) {
		var n = this.$("name");
		this.console(__name_script + " > " + n + ".getOwnElem(): Указанный свойство (DOM-указатель) [" + n + ".el" + name + (prop ? ("." + prop) : "") + "] не найдено.");
		return false;
	}
	return val;
};
_render.prototype.getOwnProp = function(prop, child) {
	if (typeof prop != "string") prop = "Unknown";
	if (typeof child != "string") child = false;
	try {
		eval("var p = this._" + prop + (child ? ("." + child) : "") + ";");
	} catch (e) {
		var n = this.$("name");
		this.console(__name_script + " > " + n + ".getOwnProp(): Указанное свойство [" + n + "._" + prop + (child ? ("." + child) : "") + "] не найдено.");
		return null;
	}
	return p;
};
_render.prototype.getRoot = function() {
	return this.$("appRoot");
};
_render.prototype.getTemplate = function() {
	return this.$(["page", "template"]);
};
_render.prototype.historyWrite = function(uri, title, params) {
	if (!this.$("html5")) return;
	if ((typeof uri != "string") || (!uri)) uri = "";
	if ((typeof title != "string") || (!title)) title = document.title;
	else document.title = title;
	if ((typeof params != "object") || !params) {
		pars = {key: this.seed(), name: "", replace: false, replaceAny: false, callback: false};
	} else {
		pars = {key: this.seed()};
		if ((typeof params.name != "string") || (!params.name)) pars.name = "";
		else pars.name = params.name;
		if ((typeof params.data != "object") || (!params.data)) pars.data = false;
		else pars.data = params.data;
		if (typeof params.replace != "boolean") pars.replace = false;
		else pars.replace = params.replace;
		if (typeof params.replaceAny != "boolean") pars.replaceAny = false;
		else pars.replaceAny = params.replaceAny;
		if (typeof params.callback != "function") pars.callback = false;
		else pars.callback = params.callback;
	}
	this.$historyCbs[pars.key] = pars;
	var state = {};
	state.flexapp = true;
	var u = this.$("page").url;
	state.url = "//"+ u.host + (u.port ? (":" + u.port) : "") + (uri.indexOf("/") == 0 ? "" : "/") + (uri ? uri : (u.segments.join("/")));
	state.title = title;
	state.key = pars.key;
	if (pars.replace) {
		if (pars.replaceAny || (!history.state) || (
			history.state &&
			(typeof history.state.key != "undefined") &&
			(typeof this.$historyCbs[history.state.key] != "undefined") &&
			(typeof this.$historyCbs[history.state.key].name == "string") &&
			(this.$historyCbs[history.state.key].name === pars.name)
		)) {
			history.replaceState(state, state.title, state.url);
			return;
		}
	}
	history.pushState(state, state.title, state.url);
};
_render.prototype.historyPop = function(e) {
	if (e && e.state && e.state.flexapp) {
		document.title = e.state.title;
		if (typeof this.$historyCbs[e.state.key] != "undefined") {
			var params = this.$historyCbs[e.state.key];
			if (typeof params.callback != "undefined") {
				var func = "";
				try {
					if (typeof params.callback == "function") params.callback(params);
					else if (typeof params.callback == "string") {
						func = params.callback + (params.callback.indexOf("()") == -1 ? "()" : "") + ";";
						eval(func);
					}
				} catch(e) {
					var n = this.$("name");
					this.console(__name_script + " > " + n + ".historyPop(): Ошибка выполнения callback-функции" + (func ? (" [" + func + "]") : "") + ". Сообщение интерпретатора: [" + e.name + "/" +  e.message + "]");
				}
			}
		}
	} else document.title = this.$("page").title;
};
_render.prototype.onLoad = function(func, instant) {
	var p = this.$("page");
	if (!p.loaded) p.loaded = true;
	var funcs;
	if (typeof func == "function") {
		if (typeof instant != "boolean") instant = false;
		if (instant) funcs = [func];
		else {
			var f = func;
			window.setTimeout((function(){
				try {f();} catch(e) {
					this.console(__name_script + " > " + this.$("name") + ".onLoad(): Ошибка выполнения фунции из стека. Сообщение интерпретатора: [" + e.name + "/" +  e.message + "]");
				}
			}), 100);
			return;
		}
	} else funcs = this._onLoad;
	for (var c in funcs) {
		if (!funcs.hasOwnProperty(c)) continue;
		try {funcs[c]();} catch(e) {
			this.console(__name_script + " > " + this.$("name") + ".onLoad(): Ошибка выполнения фунции из стека. Сообщение интерпретатора: [" + e.name + "/" +  e.message + "]");
		}
	}
};
_render.prototype.onLoadAdd = function(func) {
	if (typeof func == "undefined") return;
	if (typeof func == "string") func = (function(funcname) {eval(funcname + "();");}).bind(this, func);
	if (this.$("page").loaded) this.onLoad(func);
	this._onLoad.push(func);
};
_render.prototype.onSubmit = function() {
	var res;
	for (var c in this.onSubmit) {
		if (!this.onSubmit.hasOwnProperty(c)) continue;
		try {
			res = this._onSubmit[c]();
			if (res === false) return false;
			if (res !== true) {
				if (!confirm("Одна из функций вернула некорректный ответ о состоянии введенных данных на странице. Продолжить отправку данных?")) return false;
			}
		} catch(e) {
			if (!confirm("Одна из функций вернула некорректный ответ о состоянии введенных данных на странице. Продолжить отправку данных?")) return false;
		}
	}
	return true;
};
_render.prototype.onSubmitAdd = function(func) {
	if (typeof func == "undefined") return;
	if (typeof func == "string") func = (function(funcname) {eval(funcname + "();");}).bind(this, func);
	this._onSubmit.push(func);
};
_render.prototype.plugin = function(name, instance, struct) {
	return this.z_sharedBoundPlugin(name, instance, struct);
};
_render.prototype.pluginExt = function(plugin) {
	if (typeof plugin == "function") plugin = plugin.prototype;
	for (var c in plugin) {
		if (!plugin.hasOwnProperty(c)) continue;
		if (typeof plugin[c] != "function") continue;
		plugin[c].bind = this.___bind;
	}
	var n;
	for (var c in this) {
		if (!this.hasOwnProperty(c)) continue;
		if (typeof this[c] != "function") continue;
		if (c.indexOf() != 0) continue;
		n = c.replace("z_shared", "");
		if (n.indexOf("Bound") == 0) {
			n = n.replace("Bound", "");
			n = n.charAt(0).toLowerCase() + n.substring(1, n.length - 1)
			plugin[n] = this[c].bind(this);
		} else {
			if (typeof plugin[n] == "function") continue;
			n = n.charAt(0).toLowerCase() + n.substring(1, n.length - 1)
			plugin[n] = this[c];
		}
	}
};
_render.prototype.pluginNew = function(name, readyCb, struct) {
	return this.z_sharedBoundPluginNew(name, readyCb, struct);
};
_render.prototype.pluginsInit = function() {
	var init = this.$("init"),
		n = this.$("name"), notInitedYet = 0,
		p, pls = this.$("plugins");
	for (var c in pls) {
		if (!pls.hasOwnProperty(c)) continue;
		p = pls[c];
		if (p.inited) continue;
		if (p.init && (typeof p.obj.$inited == "boolean") && !p.obj.$inited) {
			if (typeof p.obj._init == "function") {
				p.initAttempt++;
				try {
					var res = p.obj._init((p.initAttempt == init.tryMax));
					if ((typeof res != "boolean") || res || (p.initAttempt >= init.tryMax)) p.inited = true;
					else notInitedYet++;
				} catch(e) {
					this.console(__name_script + " > " + n + ".pluginsInit(): Ошибка инициализации экземпляра [" + p.name + "]. Сообщение интерпретатора: [" + e.name + "/" + e.message + "].");
					p.inited = true;
					p.obj.$initErr = true;
				}
			} else {
				this.console(__name_script + " > " + n + ".pluginsInit(): Предупреждение - точка инициализации [._init] экземпляра [" + p.name + "] не определена или не является функцией. Экземпляр пропущен.");
				p.inited = true;
			}
		} else p.inited = true;
		if (p.inited) {
			if ((typeof p.obj.$inited == "undefined") || ((typeof p.obj.$inited == "boolean") && !p.obj.$inited)) p.obj.$inited = true;
			if (p.obj.$initErr) {
				if (typeof p.obj._initOnErr == "function")	{
					try {
						p.obj._initOnErr();
					} catch (e) {
						this.console(__name_script + " > " + n + ".pluginsInit(): Ошибка выполнения callback-функции для события [initOnErr] экземпляра плагина [" + p.name + "]. Сообщение интерпретатора: [" + e.name + "/" +  e.message + "]");
					}
				}
			}
			if (typeof p.readyCb == "function") {
				try {
					p.readyCb(!p.obj.$initErr);
				} catch(e) {
					this.console(__name_script + " > " + n + ".pluginsInit(): Ошибка выполнения callback-функции для события [oninit] экземпляра плагина [" + p.name + "]. Сообщение интерпретатора: [" + e.name + "/" +  e.message + "]");
				}
			}
		}
	}
	if (notInitedYet && !(init.tmObj)) init.tmObj = window.setTimeout(this.$("funcs").pluginsInit, init.tm);
};
_render.prototype.seed = function() {
	return this.z_sharedSeed();
};
_render.prototype.silent = function(reg) {
	return this.z_sharedBoundSilent(req);
}
_render.prototype.silentDataBuild = function(d, merge, encode, str) {
	if (typeof merge != "object") merge = false;
	if (typeof encode != "boolean") encode = true;
	if (typeof str != "boolean") str = true;
	if (!d)	{
		if (!merge) return "";
		d = {};
	} else {
		if (typeof d == "string") {
			var pars = d.split("&");
			for (var par in pars) {
				if (!pars.hasOwnProperty(par)) continue;
				if (pars[par]) {
					var pair = pars[par].split("=");
					if (pair[0]) d[pair[0]] = ((typeof pair[1] != "undefined" && pair[1]) ? pair[1] : null);
				}
			}
		} else {
			if (typeof d != "object") d = {};
		}
	}
	if ((typeof merge == "object") && merge) {
		for (var id in merge) {
			if (!merge.hasOwnProperty(id)) continue;
			d[id] = merge[id];
		}
	}
	if (str) {
		var p = [];
		for (var id in d) {
			if (!d.hasOwnProperty(id)) continue;
			if (typeof d[id] != "string") {
				if (typeof d[id] == "boolean") d[id] = (d[id] ? "1" : "0");
				else {
					if (typeof d[id] == "number") d[id] = ("").concat(d[id]);
					else d[id] = null;
				}
			}
			if (d[id] === null) p.push(("").concat(id));
			else {
				if (encode && d[id]) d[id] = encodeURIComponent(d[id]);
				p.push(("").concat(id, "=", d[id]));
			}
		}
		return (p.join("&") || "");
	} else return d;
};
_render.prototype.silentOnState = function(req) {
	if (req.done) return;//? что-то пошло не так :/
	if (req.r.readyState != 4) return;
	var n = this.$("name");
	req.done = true;
	if (req.r.status == 200) {
		if (req.action) {
			var r = this.silentReqPendingFind(req.action);
			if (r) {
				r.r.open(r.method, r.url, true);
				r.r.onreadystatechange = this.silentOnState.bind(this, r);
				if (r.method == "POST") r.r.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				r.r.send(r.method == "POST" ? r.dataPOST : null);
				r.sent = true;
			}
		}
		if (req.json) {
			if ((req.r.responseText == "ok") || (req.r.responseText == "true") || ((req.r.responseText == "") && !req.needResponse))
				req.response = {res: true, msg: ""};
			else {
				if ((req.r.responseText == "error") || (req.r.responseText == "false") || ((req.r.responseText == "") && (req.needResponse))) {
					req.response = {res: false, msg: ""};
					if (req.r.responseText == "") req.response.msg = "Сервер вернул пустой ответ.";
				} else {
					try {
						req.response = eval("(" + req.r.responseText + ")");
					} catch(e) {
						req.response = {res: false};
						req.response.msg = "Ошибка парсинга json-ответа [" + req.action + ": " + req.key + "]";;
						req.response.msgExt = "Сообщение интерпретатора: [" + e.name + "/" + e.message + "]";
						this.console(__name_script + " > " + n + ".silentOnState(): " + req.response.msg + ". " + req.response.msgExt);
						this.console("Необработанные данные: " + req.r.responseText);
					}
				}
			}
		} else req.response = {res: true, msg: "", data: req.r.responseText};
		if (req.debug) {
			if ((typeof req.response.debug == "object") && (req.response.debug)) {
				var cnt = 0;
				for (var c in req.response.debug) {
					if (!req.response.debug.hasOwnProperty(c)) continue;
					cnt++;
					if (cnt == 1) this.console("Доступны отладочные данные:");
					this.console("[" + c + "/" + typeof req.response.debug[c] + "]: " + req.response.debug[c]);
				}
			}
		}
	} else {
		req.response = {res: false, msg: "Ошибка выполнения XmlHttpRequest операции [status: " + req.r.status + "]", data: ""};
		this.console(__name_script + " > " + n + ".silentOnState(): " + req.response.msg + ", [" + req.action + ": " + req.key + "]");
	}
	if (typeof req.cbFunc == "function") {
		try {
			if (!req.cbBound) {
				if (req.owner)
					req.cbFunc.apply(req.owner, [req]);
				else
					this.console(__name_script + " > " + n + ".silentOnState(): Callback-функция не может быть выполнена при заданных параметрах [cbBound: false, owner: null].");
			} else req.cbFunc(req);
		} catch(e) {
			this.console(__name_script + " > " + n + ".silentOnState(): Ошибка выполнения callback-функции [" + req.action + ": " + req.key + "]. Сообщение интерпретатора: [" + e.name + " / " + e.message + "]");
		}
	}
	if ((req.response.msg != "") && req.msgDisplay) {
		var shown = false;
		if (req.msgDisplayWay == "popup") {
				if ((typeof this._plugins[__name_msgr] != "undefined") && (typeof this._plugins[__name_msgr].obj == "object") && this._plugins[__name_msgr].obj) {
					var type = ((typeof req.response.res == "undefined") || req.response.res) ? "wrn" : "err";
					this._plugins[__name_msgr].obj.dlgAlert(req.response.msg, type, "300px");
					shown = true;
				} else {
					if ((typeof this._plugins[__name_popup] != "undefined") && (typeof this._plugins[__name_popup].obj == "object") && this._plugins[__name_popup].obj) {
						var pu = this._plugins[__name_popup].obj.add({
							content: req.response.msg,
							showcloser: true,
							windowed: true
						});
						this._plugins[__name_popup].obj.show(pu);
						shown = true;
					}
				}
		}
		if (!shown) alert(req.response.msg);
	}
	var l = this._silentReqs.length;
	if (!l) return;
	for (var c = 0; c < l; c++)
		if (this._silentReqs[c] == req) {
			delete this._silentReqs[c]["r"];
			this._silentReqs.splice(c, 1);
			break;
		}
};
_render.prototype.silentReqBuild = function(o) {
	if (typeof o == "undefined") o = null;
	var req = {
		action:			"",
		cbBound:		true,
		cbFunc:			null,
		dataGET:		{},
		dataPOST:		{},
		debug:			false,
		done:			false,
		encode:			true,
		json:			true,
		key:			this.seed(),
		method:			"POST",
		msgDisplay:		true,
		msgDisplayWay:	"popup",//or alert
		needResponse:	true,
		owner: 			o,
		owner_store:	{},
		r:				this.xmlHttpGet(),
		response:		null,
		sent:			false,
		sequential:		false,
		time:			(new Date()).getTime(),
		url:			""
	};
	return req;
};
_render.prototype.silentReqPendingFind = function(action) {
	if (typeof action != "string") return false;
	for (var c in this._silentReqs) {
		if (!this._silentReqs.hasOwnProperty(c)) continue;
		if ((this._silentReqs[c].action == action) && (!this._silentReqs[c].sent)) return this._silentReqs[c];
	}
	return false;
};
_render.prototype.silentX = function(reg) {
	return this.z_sharedBoundSilentX(req);
}
_render.prototype.silentXFormFieldAdd = function(form, name, value) {
	if (typeof form == "undefined") return false;
	if (typeof name == "undefined") return false;
	if (typeof value == "undefined") value = "false";
	var type = "input";
	var val = "unknown value";
	if ((typeof value == "object") && (typeof value.nodeName != "undefined")) {
		if (typeof value.tagName != "undefined") {
			var tag = value.tagName.toLowerCase();
			switch (tag) {
				case "input":
					switch (value.type) {
						case "button":
							val = value.value;
							break;
						case "checkbox":
							val = value.checked ? "on" : "off";
							break;
						case "file":
							type = "file";
							break;
						case "hidden":
							val = value.value;
							break;
						case "password":
							val = value.value;
							break;
						case "submit":
							val = value.value;
							break;
						case "text":
							val = value.value;
							break;
						default:
							val = "not supported input type";
							break
					}
					break;
				case "textarea":
					type = "textarea";
					val = value.value;
					break;
				default:
					val = "not supported input";
					break;
			}
		} else {
			if (typeof value.textContent != "undefined") {
				type = "textarea";
				val = value.textContent;
			} else if (typeof value.innerText != "undefined") {
				type = "textarea";
				val = value.innerText;
			} else if (typeof value.innerHTML != "undefined") {
				type = "textarea";
				val = value.innerHTML;
			} else if (typeof value.nodeValue != "undefined") {
				type = "textarea";
				val = value.nodeValue;
			} else if (typeof value.toString != "undefined") {
				type = "textarea";
				val = value.toString();
			}
		}
	} else {
		if ((typeof value == "string") || (typeof value == "number")) val = "" + value;
		else {
			if (typeof value.toString != "undefined") val = value.toString();
			else val = "" + value;
		}
	}
	switch (type) {
		case "input":
			var el = document.createElement("INPUT");
			el.type = "hidden";
			el.name = name;
			el.value = val;
			form.appendChild(el);
			break;
		case "textarea":
			var el = document.createElement("TEXTAREA");
			el.name = name;
			el.value = val;
			form.appendChild(el);
			break;
		case "file":
			value.name = name;
			form.appendChild(value);
			break;
		default:
			return false;
	}
	return true;
};
_render.prototype.silentXOnReady = function(req) {
	if (req.done) return;
	var n = this.$("name");
	if (typeof req.statusUrl == "string") {
		var rstat = this.silentXReqBuild(this);
		rstat.method = "GET";
		rstat.dataGET[n + "-xcb"] = n + ".silentXOnStatus";
		rstat.dataGET[n + "-xkey"]= req.key;
		rstat.dataGET.status = "";
		rstat.owner_store.req = req;
		rstat.url = req.statusUrl;
		this.silentX(rstat);
	} else {
		req.done = true;
		if (typeof req.cbFunc == "function") {
			try {req.cbFunc(true);} catch(e) {this.console(__name_script + " > " + n + ".silentXOnReady(): Ошибка выполнения callback-функции. Сообщение интерпретатора: [" + e.name + " / " + e.message + "]");}
		}
	}
	this._plugins[__name_lib].obj.eventRemove(req.domWorker, "load", req.onready);
	req.domWorker.parentNode.parentNode.removeChild(req.domWorker.parentNode);
};
_render.prototype.silentXOnStatus = function(resp, key) {
	var n = this.$("name");
	for (var c in this._silentXReqs) {
		if (!this._silentXReqs.hasOwnProperty(c)) continue;
		if (this._silentXReqs[c].done) continue;
		if (typeof this._silentXReqs[c].owner_store.req == "undefined") continue;
		if (this._silentXReqs[c].owner_store.req.key != key) continue;
		this._silentXReqs[c].done = true;
		var req = this._silentXReqs[c].owner_store.req;
		req.done = true;
		if (req.action) {
			var r = this.silentXReqPendingFind(req.action);
			if (r) {
				if (r.method == "GET") {
					r.domWorker.src = r.url;
					document.getElementsByTagName("head")[0].appendChild(r.domWorker);
				}
				if(r.method == "POST") {
					r.onready = this.silentXOnReady.bind(this, r);
					this._plugins[__name_lib].obj.eventAdd(r.domWorker, "load", r.onready);
					r.domForm.submit();
				}
				r.sent = true;
			}
		}
		if (req.json) {
			if ((resp == "ok") || (resp == "true") || ((resp == "") && !req.needResponse))
				req.response = {res: true, msg: ""};
			else {
				if ((resp == "error") || (resp == "false") || ((resp == "") && (req.needResponse))) {
					req.response = {res: false, msg: ""};
					if (resp == "") req.response.msg = "Сервер вернул пустой ответ.";
				} else {
					try {
						req.response = eval("(" + resp + ");");
					} catch(e) {
						req.response = {res: false};
						req.response.msg = "Ошибка парсинга json-ответа.";
						req.response.msgExt = "Сообщение интерпретатора: [" + e.name + "/" + e.message + "]";
						req.response.data = resp;
						req.msgDisplay = true;
						req.msgDisplayWay = "popup"
						this.console(__name_script + " > " + n + ".silentXOnStatus(): " + req.response.msg + ". " + req.response.msgExt);
						this.console("Необработанные данные: " + resp);
					}
				}
			}
		} else req.response = {res: true, msg: "", data: resp};
		if (typeof req.cbFunc == "function") {
			try {
				if (!req.cbBound) {
					if (req.owner)
						req.cbFunc.apply(req.owner, [req]);
					else
						this.console(__name_script + " > " + n + ".silentXOnStatus(): Callback-функция не может быть выполнена при заданных параметрах [cbBound: false, owner: null].");
				} else req.cbFunc(req);
			} catch(e) {
				this.console(__name_script + " > " + n + ".silentXOnStatus(): Ошибка выполнения callback-функции. Сообщение интерпретатора: [" + e.name + " / " + e.message + "]");
			}
			if (req.debug) {
				if ((typeof req.response.debug == "object") && (req.response.debug)) {
					var cnt = 0;
					for (var d in req.response.debug) {
						if (!req.response.debug.hasOwnProperty(d)) continue;
						cnt++;
						if (cnt == 1) this.console("Доступны отладочные данные:");
						this.console("[" + d + "/" + (typeof req.response.debug[d]) + "]: " + req.response.debug[d]);
					}
				}
			}
		}
		if (req.response.msg) {
			if (req.msgDisplay) {
				var show = false;
				if (req.msgDisplayWay == "popup") {
					if ((typeof this._plugins[__name_msgr] != "undefined") && (typeof this._plugins[__name_msgr].obj == "object") && this._plugins[__name_msgr].obj) {
						var type = ((typeof req.response.res == "undefined") || req.response.res) ? "wrn" : "err";
						this._plugins[__name_msgr].obj.dlgAlert(req.response.msg, type, "300px");
						shown = true;
					} else {
						if ((typeof this._plugins[__name_popup] != "undefined") && (typeof this._plugins[__name_popup].obj == "object") && this._plugins[__name_popup].obj) {
							var pu = this._plugins[__name_popup].obj.add({
								content: req.response.msg,
								showcloser: true,
								windowed: true
							});
							this._plugins[__name_popup].obj.show(pu);
							shown = true;
						}
					}
				}
				if (!shown) alert(req.response.msg);
			}
		}
		this._silentXReqs[c].domWorker.parentNode.removeChild(this._silentXReqs[c].domWorker);
		return;
	}
	this.console(__name_script + " > " + n + ".silentXOnStatus(): Предупреждение, получен статус незарегистрированной операции [key: " + key + "]. Содержимое ответа: ");
	this.console(resp);
};
_render.prototype.silentXReqBuild = function(o) {
	if (typeof o == "undefined") o = null;
	var r = {
		action:			"",
		cbBound:		true,
		cbFunc:			false,
		dataGET:		{},
		dataPOST:		{},
		debug:			false,
		domForm:		null,
		domMain:		null,
		domWorker:		null,
		done:			false,
		encode:			true,
		json:			true,
		key:			"" + (Math.floor((Math.random()*1000000000) + 1)),
		method:			"POST",
		msgDisplay:		true,
		msgDisplayWay:	"popup",//or alert
		needResponse:	true,
		owner:			o,
		owner_store:	{},
		response:		null,
		sent:			false,
		sequential:		false,
		statusUrl:		false,
		time:			(new Date()).getTime(),
		url:			""
	};
	return r;
};
_render.prototype.silentXReqFetch = function(key) {
	for (var c in this._silentXReqs) {
		if (!this._silentXReqs.hasOwnProperty(c)) continue;
		if (this._silentXReqs[c].key == key) return this._silentXReqs[c];
	}
	return false;
};
_render.prototype.silentXReqPendingFind = function(action) {
	if (typeof action != "string") return false;
	for (var c in this._silentXReqs) {
		if (!this._silentXReqs.hasOwnProperty(c)) continue;
		if ((this._silentXReqs[c].action == action) && (!this._silentXReqs[c].sent)) return this._silentXReqs[c];
	}
	return false;
};
_render.prototype.urlParse = function(url) {
	return this.z_sharedUrlParse(url);
};
_render.prototype.waiterHide = function() {
	if (this._pu == -1) {
		this.console(__name_script + " > " + this.$name + ".waiterHide(): Плагин всплывающего (модального) окна [" + __name_popup + "] отсутствует или не инициализирован.");
		return;
	}
	this._plugins[__name_popup].obj.hide(this._pu);
	this._waiter = false;
};
_render.prototype.waiterInit = function() {
	if (this.$("lang") == "ru-Ru") l = "Загрузка...";
	else l = "Loading...";
	var d = document.createElement("DIV");
	d.className = this.$name + "-loader " + this.$name + "-loading";
	d.innerHTML = "Loading...";
	this._pu = this._plugins[__name_popup].obj.add({
		content: d,
		showcloser:false,
		windowed: false
	});
}
_render.prototype.waiterShow = function() {
	if (this._pu == -1) {
		this.console(__name_script + " > " + this.$name + ".waiterShow(): Плагин всплывающего (модального) окна [" + __name_popup + "] отсутствует или не инициализирован.");
		return;
	}
	this._plugins[__name_popup].obj.show(this._pu);
	this._waiter = true;
};
_render.prototype.xmlHttpGet = function() {
	var r;
	try {
		r = new ActiveXObject("Msxml2.XMLHTTP");
	} catch (e) {
		try {
			r = new ActiveXObject("Microsoft.XMLHTTP");
		} catch (e) {
			r = false;
		}
	}
	if (!r && typeof XMLHttpRequest != "undefined")
		r = new XMLHttpRequest();
	else {
		this.console(__name_script + " > " + this.$name + ".xmlHttpGet(): Невозможно создать объект [XmlHttpRequest]");
		r = null;
	}
	return r;
};
_render.prototype.z_sharedBoundAction = function(action, path, query, target, seed) {
	var n = this.$("elNames");
	n.Action.value = "" + action;
	n.Form.target = ((typeof target == "string") && target) ? target : this.$("form").target;
	n.Form.action = this._plugins[__name_lib].obj.urlBuild(path, query, seed);
	n.Form.submit();
};
_render.prototype.z_sharedBoundAlert = function(id, type) {
	if (typeof this._alerts == "undefined") {
		this._alerts = {};
		this.console(__name_script + " > " + this.$name + ".alert() > Неизвестный идентификатор сообщения [" + id + "].");
		return;
	};
	if (typeof this._alerts[id] == "undefined") {
		this.console(__name_script + " > " + this.$name + ".alert() > Неизвестный идентификатор сообщения [" + id + "].");
		return;
	} else {
		if (this._alerts[id].id != -1) msg = this._alerts[id].id;
		else msg = "";
	}
	var sh = -1;
	if (this.plMsgr) {
		if (typeof msg == "number") {
			sh = this.plMsgr.dlgAlert(msg);
		} else {
			if (typeof type != "string") type = "inf";
			sh = this.plMsgr.dlgAlert(this._alerts[id].msg, type);
			if (sh != -1) this._alerts[id].id = sh;
		}
	}
	if (sh == -1) {
		window.alert(this._alerts[id].msg);
	}
};
_render.prototype.z_sharedBoundPlugin = function(name, instance, struct) {
	if ((typeof name != "string") || !name) return false;
	instance = "" + instance;
	if (typeof struct != "boolean") struct = false;
	var p, pls = this.$("plugins");
	for (var c in pls) {
		if (!pls.hasOwnProperty(c)) continue;
		p = pls[c];
		if ((typeof p[instance] == "object") && p[instance]) {
			if (struct) return p[instance];
			else p[instance].obj;
		}
	}
	return null;
};
_render.prototype.z_sharedBoundPluginNew = function(name, readyCb, struct) {
	if ((typeof name != "string") || !name) return false;
	if (typeof init != "boolean") init = true;
	if (typeof readyCb != "function") readyCb = false;
	if (typeof struct != "boolean") struct = false;
	var n = this.$("name"), p, plugin,
		pls = this.$("plugins"),
		prs = this.$("protos");
	if (typeof prs[name] != "object") {
		this.console(__name_script + " > " + n + ".pluginNew(): Ошибка создания экземпляра плагина [" + name + "]: экземпляр не зарегистрирован в системе.");
		return null;
	}
	if (typeof prs[name] != "function") return false;
	try {
		plugin = new prs[name];
		pls["."]++;
	} catch (e) {
		this.console(__name_script + " > " + n + ".pluginNew(): Ошибка создания экземпляра плагина [" + name + "]. Сообщение интерпретатора: [" + e.name + "/" +  e.message + "]");
		return null;
	}
	if (typeof pls[name] != "object") pls[name] = {};
	p = {
		init: init,
		inited: !init,
		initAttempt: 0,
		initCb: readyCb,
		instance: "" + pls["."],
		name: plugin.$name,
		obj: plugin
	};
	pls[name]["" + pls["."]] = p;
	p.obj.$instance = p.instance;
	if ((p.init || readyCb) && !this.$("init").tmObj) {
		this.$("init").tmObj = window.setTimeout(this.$("funcs").pluginsInit, 10);
	}
	if (struct) return p;
	else return p.obj;
};
_render.prototype.z_sharedBoundPluginReg = function(plugin, name, init) {
	if (typeof name == "undefined") name = true;
	if (typeof init != "boolean") init = true;
	this.pluginExt(plugin);
	var alloc = false, proto = false, p,
		pls = this.$("plugins"),
		prs = this.$("protos");
	if (typeof plugin == "function") {
		proto = plugin;
		if (typeof name == "boolean") {
			plugin = new proto();
			name = plugin.$name || false;
		} else plugin = false;
	} else {
		if (plugin.prototype.constructor) proto = plugin.prototype.constructor;
		name = plugin.$name || false;
	}
	if ((typeof name != "string") || !name) return false;
	if (typeof proto != "function") return false;
	if (typeof prs[name] != "object") prs[name] = {proto: proto, init: init};
	if (plugin || alloc) {
		try {
			p = {
				init: init,
				inited: !init,
				initAttempt: 0,
				initCb: false,
				instance: "" + (pls["."] + 1),
				name: name,
				obj: plugin || new proto()
			};
			pls["."]++;
			pls[name]["" + pls["."]] = p;
			p.obj.$instance = p.instance;
		} catch (e){
			this.console(__name_script + " > " + n + ".pluginRegister(): Ошибка создания экземпляра плагина [" + name + "]. Сообщение интерпретатора: [" + e.name + "/" + e.message + "].");
			return false;
		}
		if (p.init && !this.$("init").tmObj) {
			this.$("init").tmObj = window.setTimeout(this.$("funcs").pluginsInit, 10);
		}
	}
	return true;
};
_render.prototype.z_sharedBoundSilent = function(req) {
	req.method = req.method.toUpperCase();
	if ((req.method != "POST") && (req.method == "GET")) {
		this.console(__name_script + " > " + this.$("name") + ".silent(): Неизвестный метод [" + req.method + "], операция прервана.");
		return false;
	}
	//проверка параметров uri
	var url;
	if ((typeof req.url != "string") || !req.url) url = document.location.href.replace("http://" + document.domain, "");
	else url = ((req.url.indexOf("http") != -1) ? req.url : (this.$("appRoot") + req.url).replace(/\/\//g, "/"));
	var query = "";
	url = url.split("?");
	if (typeof url[1] != "undefined") query = url[1];
	url = url[0];
	//полный http путь запроса
	req.url = url + (query ? ("?" + query) : "");
	//GET, POST параметры
	var merge = {silent: null}, ns = $("elNames");
	if (req.method == "GET") merge[ns.Action.name] = req.action;
	req.dataGET = this.silentDataBuild(req.dataGET, merge, req.encode);
	if (req.method == "POST") {
		delete merge.silent;
		merge[ns.Action.name] = req.action;
		req.dataPOST = this.silentDataBuild(req.dataPOST, merge, req.encode, true);
	}
	//отправка
	req.url = req.url + (req.dataGET ? ((query ? "&" : "?") + req.dataGET) : "");
	if (req.action && req.sequential) {
		var r = this.silentReqPendingFind(req.action);
		if (r) return true;
	}
	this._silentReqs.push(req);
	req.r.open(req.method, req.url, true);
	req.r.onreadystatechange = this.silentOnState.bind(this, req);
	if (req.method == "POST") req.r.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	req.r.send(req.method == "POST" ? req.dataPOST : null);
	req.sent = true;
	return true;
};
_render.prototype.z_sharedBoundSilentX = function(req) {
	var n = this.$("name");
	req.method = req.method.toUpperCase();
	if ((req.method != "POST") && (req.method != "GET")) {
		this.console(__name_script + " > " + n + ".silentX(): Неизвестный метод [" + req.method + "], операция прервана.");
		return false;
	}
	//проверка параметров uri
	var url;
	if ((typeof req.url != "string") || !req.url) url = document.location.href.replace("http://" + document.domain, "");
	else url = ((req.url.indexOf("http") != -1) ? req.url : (this.$("appRoot") + req.url).replace(/\/\//g, "/"));
	var query = "";
	url = url.split("?");
	if (typeof url[1] != "undefined") query = url[1];
	url = url[0];
	//полный http путь запроса
	req.url = url + (query ? ("?" + query) : "");
	//GET, POST данные
	var merge = {silent: ""}, ns = $("elNames");
	if (req.method == "GET") {
		merge[n + "-action-key"] = req.key;
		merge[ns.Action.name] = req.action;
	}
	req.dataGET = this.silentDataBuild(req.dataGET, merge, req.encode);
	if (req.method == "POST") {
		delete merge.silent;
		merge[n + "-action-key"] = req.key;
		merge[ns.Action.name] = req.action;
		req.dataPOST = this.silentDataBuild(req.dataPOST, merge, req.encode, false);
	}
	//отправка
	req.url = req.url + (req.dataGET ? ((query ? "&" : "?") + req.dataGET) : "");
	if (req.method == "GET") {
		req.domWorker = document.createElement("SCRIPT");
		req.domWorker.type = "text/javascript";
	}
	if(req.method == "POST") {
		var name = n + "-postform-" + this.seed();
		req.domWorker = document.createElement("IFRAME");
		req.domWorker.id = name;
		req.domWorker.name = name;
		req.domWorker.src = "javascript:false;";
		req.domForm = document.createElement("FORM");
		req.domForm.action = req.url;
		req.domForm.enctype = "multipart/form-data";
		req.domForm.method = "POST";
		req.domForm.target = name;
		if (typeof req.dataPOST == "object") {
			for (var c in req.dataPOST) {
				if (!req.dataPOST.hasOwnProperty(c)) continue;
				this.silentXFormFieldAdd(req.domForm, c, req.dataPOST[c]);
			}
		}
		req.domMain = document.createElement("DIV");
		req.domMain.style.display = "none";
		req.domMain.style.overflow = "hidden";
		req.domMain.style.height = "0";
		req.domMain.appendChild(req.domWorker);
		req.domMain.appendChild(req.domForm);
		if (document.body.childNodes.length)
			document.body.insertBefore(req.domMain, document.body.firstChild);
		else
			document.body.appendChild(req.domMain);
	}
	if (req.action && req.sequential) {
		var r = this.silentXReqPendingFind(req.action);
		if (r) return true;
	}
	this._silentXReqs.push(req);
	if (req.method == "GET") {
		req.domWorker.src = req.url;
		document.getElementsByTagName("head")[0].appendChild(req.domWorker);
	}
	if(req.method == "POST") {
		req.onready = this.silentXOnReady.bind(this, req);
		this._plugins[__name_lib].obj.eventAdd(req.domWorker, "load", req.onready);
		req.domForm.submit();
	}
	req.sent = true;
	return true;
};
_render.prototype.z_sharedClone = function(o, deep, skip) {
	if ((typeof o != "object") || !o) return o;
	var maxRec = 100;//safe exit for deep mode
	var curRec = -1;
	if (typeof deep != "boolean") {
		if (typeof deep != "number") deep = maxRec;
		else {
			if (deep < 0) deep = 0;
		}
	} else {
		if (deep) deep = maxRec;
		else deep = 0;
	}
	if ((typeof skip != "object") || !(skip instanceof Array)) skip = false;
	var pts = [];
	var findPt = function(o) {
		var l = pts.length;
		for (var c = 0; c < l; c++) {
			if (pts[c][0] === o) return pts[c][1];
		}
		if (skip) {
			l = skip.length;
			for (var c = 0; c < l; c++) {
				if ((typeof skip != "object") || !skip) continue;
				if (skip[c] === o) return o;
			}
		}
		return false;
	};
	var worker, no = {};
	worker = function(o, no) {
		curRec++;
		pts.push([o, no]);
		var rec = true;
		if ((curRec >= deep) || (curRec >= maxRec)) rec = false;
		for (var c in o) {
			if (!o.hasOwnProperty(c)) continue;
			if (rec && (typeof o[c] == "object") && o[c]) {
				no[c] = findPt(o[c]);
				if (no[c] === false) {
					no[c] = (o[c] instanceof Array ? [] : {});
					worker(o[c], no[c]);
				}
			} else no[c] = o[c];
		}
	};
	worker(o, no);
	return no;
};
_render.prototype.z_sharedConfigWait = function(config, last, func) {
	if (typeof this.$config != "object" || (!this.$config)) {
		this.$config = {};
		this.$config.$loaded = true;
		return false;
	}
	if (typeof this.$config.$loaded != "boolean") this.$config.$loaded = false;
	if (this.$config.$loaded) return false;
	else {
		if (last) {
			this.console(__name_script + " > " + this.$name + ".configWait() > Плагин не инициализирован - таймаут ожидания конфигурационных данных.");
			this.$initErr = true;
			this.$inited = true;
			return false;
		}
	}
	if (typeof func == "undefined") func = "_configImport";
	else {
		if (typeof func == "string") {
			if (typeof this[func] != "function") func = "_configImport";
		} else {
			if (typeof func != "function") func = "_configImport";
		}
	}
	if (typeof func == "string") {
		if (typeof this[func] != "function") {
			this.$config = {};
			this.$config.$loaded = true;
			return false;
		} else func = this[func];
	}
	var res;
	try {
		res = func.apply(this, [config]);
		if (typeof res != "boolean") res = true;
	} catch(e) {
		res = false;
		this.$initErr = true;
		this.$inited = true;
		this.console(__name_script + " > " + this.$name + ".configWait(): Ошибка инициализации плагина, функция импорта выполнена с ошибкой. Сообщение интерпретатора: [" + e.name + "/" + e.message + "].");
	}
	return !res;
};
_render.prototype.z_sharedConsole = function(msg, crit) {
	if (crit) this.dlgAlert(msg);
	else {
		if (this.$("console")) window.console.log(msg);
	}
};
_render.prototype.z_sharedDEl = function(elname, prop, last, store_as_object) {
	var name = "";
	if (typeof store_as_object != "string") {
		store_as_object = false;
	} else {
		name = store_as_object;
		store_as_object = true;
	}
	if ((typeof this[prop] != "object") || (!this[prop])) {
		if (store_as_object) {
			this[prop] = {};
			this[prop][name] = null;
		} else this[prop] = null;
	}
	var get = false;
	if (store_as_object) {
		if (!this[prop][name]) get = true;
	} else {
		if (!this[prop]) get = true;
	}
	if (get) {
		var el = document.getElementById(elname);
		if (!el) {
			if (last) {
				this.console(__name_script + " > " + this.$name + ".dEl() > Плагин не инициализирован - HTML-элемент [id: " + elname + "] не найден.");
				this._initErr = true;
				this._inited = true;
				return true;
			}
			return true;
		}
		if (store_as_object) this[prop][name] = el;
		else this[prop] = el;
	}
	return false;
};
_render.prototype.z_sharedDElName = function(name) {
	return this.$name + this.$instance + "-" + name;
};
_render.prototype.z_sharedPluginWait = function(name, prop, last, inited, config, parent, cb) {
	if (typeof this[prop] == "undefined") this[prop] = null;
	if (typeof this[prop] == "boolean" && (!this[prop])) {
		this.$initErr = true;
		this.$inited = true;
		return false;
	}
	if (typeof this[prop] != "object") this[prop] = null;
	if (typeof inited == "undefined") inited = true;
	if (typeof config == "undefined") config = true;
	if (typeof parent == "undefined") parent = this;
	if (!this[prop]) {
		var o = this.pluginNew(name, cb, true);
		if (!o) {
			this[prop] = false;
			this.$initErr = true;
			this.$inited = true;
			return false;
		}
		this[prop] = o.obj;
		if (o.init) {
			if (typeof this[prop]["_init"] == "function") {
				try {
					if (config || parent) {
						this[prop]._init(false, config, parent);
					} else {
						this[prop]._init(false);
					}
				} catch (e) {
					this.console(__name_script + " > " + this.$name + ".pluginWait(): Ошибка инициализации экземпляра [" + name + "].");
				}
			}
		}
	}
	if (inited) {
		if (!this[prop]._inited) {
			if (last) {
				this.console(__name_script + " > " + this.$name + ".pluginWait(): Ошибка инициализации - таймаут ожидания требуемого плагина [" + name + "].");
				this.$initErr = true;
				this.$inited = true;
				return false;
			}
			return true;
		}
	}
	return false;
};
_render.prototype.z_sharedSeed = function() {
	if (typeof Math != "undefined")
		return "" + (Math.floor((Math.random()*1000000000) + 1));
	else
		return (new Date()).getTime();
};
_render.prototype.z_sharedUrlParse = function(url) {
	if (typeof url != "string") url = document.location.href;
	var a = document.createElement('A');
	a.href = url;
	return {
		source: a.href,
		protocol: a.protocol.replace(':',''),
		host: a.hostname,
		port: a.port,
		query: a.search,
		params: (function(){
			var ret = {},
			seg = a.search.replace(/^\?/,'').split('&'),
			len = seg.length, i = 0, s;
			for (; i < len; i++) {
				if (!seg[i]) continue;
				s = seg[i].split('=');
				ret[s[0]] = s[1];
			}
			return ret;
		})(),
		file: (a.pathname.match(/\/([^\/?#]+)$/i) || [,''])[1],
		hash: a.hash.replace('#',''),
		path: a.pathname.replace(/^([^\/])/,'/$1'),
		relative: (a.href.match(/tps?:\/\/[^\/]+(.+)/) || [,''])[1],
		segments: a.pathname.replace(/^\//,'').split('/')
	};
};

var ss = {};
for (var c in _render.prototype) {
	if (!_render.prototype.hasOwnProperty(c)) continue;
	if (typeof _render.prototype[c] == "function") {
		_render.prototype[c].bind = _render.prototype.___bind;
		_render.prototype[c].id = "" + (Math.floor((Math.random() * 1000000000) + 1));
		ss[_render.prototype[c].id] = _render.prototype[c];
	}
}

try {
	render = new _render(window[entity].cfg, ss);
} catch (e) {
	if (window.console) window.console.log(__name_script + " > Объект ядра [" + __name_render + "] не создан, зависимые плагины не будут инициализированы. Сообщение интерпретатора: [" + e.name + "/" + e.message + "]");
	return;
}

(function(){

//--------------- Плагин PopUp [static] -------------------
/**
* Создание объекта всплывающего модального окна
* windowed boolean - показать рамку по умолчанию
* container elemDOM/name/string - id/имя контейнера/строка-содержание
* onclose object(function) - функция выполняемая после закрытия окна,
*			если функция возвращает false, то закрытие окна отменняется
*
* пример вызова: var myPuId = (render.pluginGet("popup")).add({
* 	windowed:false,
* 	content:"myPopupDivId",
* 	onclose:(function(){alert("ok");return true;})
*	showcloser:false,
* });
*
* @param object pars
*/
var _pu = function() {
	this._borderSize=	24;
	this._inited	=	false;
	this._items		=	[];
	this.$name		=	__name_popup;
	this._visible	=	[];
	this.elBase		=	null;
	this.elBox		=	null;
	this.elMama		=	null;
	this.elPage		= 	null;
	this.elShade	=	null;
	this.elWin		=	null;
	this.elWin_		=	null;
	this.plLib		=	null;
	this.plRender	=	null;
};
_pu.prototype._init = function(last) {
	if (this._inited) return true;
	if (typeof last != "boolean") last = false;
	if (!this.elPage)
		this.elPage = document.createElement("DIV");
	if (document.body) {
		document.body.appendChild(this.elPage);
	} else {
		if (last) {
			this.plRender.console(__name_script + " > " + this.$name + "._init(): WTF???");
			this._initErr = true;
			this._inited = true;
			return true;
		}
		return false;
	}
	if (!this.plLib) {
		this.plLib = this.plRender.pluginGet(__name_lib);
		if (!this.plLib) {
			if (last) {
				this.plRender.console(__name_script + " > " + this.$name + "._init(): Ошибка инициализации, таймаут ожидания требуемого плагина [" + __name_lib + "].");
				this._initErr = true;
				this._inited = true;
				return true;
			}
			return false;
		}
	}
	//затенение и основной контейнер
	this.elMama = document.createElement("DIV");
	this.elMama.style.display = "none";
	this.elPage.appendChild(this.elMama);
	this.elShade = document.createElement("DIV");
	this.elShade.className = "pu-shade";
	this.elShade.style.display = "none";
	this.elPage.appendChild(this.elShade);
	this.elBase = document.createElement("DIV");
	this.elBase.className = "pu";
	this.elBase.style.display = "none";
	this.elPage.appendChild(this.elBase);
	var el = document.createElement("DIV");
	el.className = "inner";
	this.elBase.appendChild(el);
	//контейнер для необрамленных поп-апов
	this.elBox = document.createElement("DIV");
	this.elBox.className = "box";
	this.elBox.style.display = "none";
	el.appendChild(this.elBox);
	//контейнер для поп-апов в виде стилизированного окна
	this.elWin_ = document.createElement("DIV");
	this.elWin_.className = "win";
	this.elWin_.style.display = "none";
	el.appendChild(this.elWin_);
	this.elWin = document.createElement("DIV");
	this.elWin.className = "rel";
	this.elWin_.appendChild(this.elWin);
	//левый верхний угол
	el = document.createElement("DIV");
	el.className = "c-tl";
	this.elWin.appendChild(el);
	//правый верхний угол
	el = document.createElement("DIV");
	el.className = "c-tr";
	this.elWin.appendChild(el);
	//правый нижний угол
	el = document.createElement("DIV");
	el.className = "c-br";
	this.elWin.appendChild(el);
	//левый нижний угол
	el = document.createElement("DIV");
	el.className = "c-bl";
	this.elWin.appendChild(el);
	//верхняя граница
	el = document.createElement("DIV");
	el.className = "l-t";
	this.elWin.appendChild(el);
	//правая граница
	el = document.createElement("DIV");
	el.className = "l-r";
	this.elWin.appendChild(el);
	//нижняя граница
	el = document.createElement("DIV");
	el.className = "l-b";
	this.elWin.appendChild(el);
	//левая граница
	el = document.createElement("DIV");
	el.className = "l-l";
	this.elWin.appendChild(el);
	this._inited = true;
	return true;
};
_pu.prototype.add = function(params) {
	if (!this._inited) return -1;
	var item = {
		closer:null,
		closers:[],
		content:null,
		onclose:[],
		owner:null,
		parent:null,
		showcloser:true,
		windowed:true
	}
	if ((typeof params != "object") || (typeof params === null)) return -1;
	//проверка контента
	if (typeof params.content != "undefined" && (params.content)) {
		if (typeof params.content == "string") {
			if (!document.getElementById(params.content)) {
				item.content = document.createElement("DIV");
				item.content.innerHTML = params.content;
				this.elMama.appendChild(item.content);
				item.parent = this.elMama;
			} else {
				item.content = document.getElementById(params.content);
				if (item.content.parentNode) item.parent = item.content.parentNode;
				else item.parent = this.elMama;
			}
		} else {
			if (typeof params.content != "object") return -1;
			else {
				item.content = params.content;
				if (item.content.parentNode) item.parent = item.content.parentNode;
				else item.parent = this.elMama;
			}
		}
	}
	//проверка владельца
	if ((typeof params.owner == "object") && (params.owner))
		item.owner = params.owner;
	else
		item.owner = this;
	//проверка клоузеров
	if (typeof params.closers != "undefined") {
		if (typeof params.closers == "object") {
			if (params.closers instanceof Array) {
				var c;
				for (var i in params.closers) {
					if (!params.closers.hasOwnProperty(i)) continue;
					c = params.closers[i];
					if (typeof c == "string") {
						if (document.getElementById(c)) item.closers.push(document.getElementById(c));
					} else {
						if (typeof c == "object") item.closers.push(c);
					}
				}
			} else item.closers.push(params.closers);
		} else {
			if (typeof params.closers == "string") {
				if (document.getElementById(params.closers)) item.closers.push(document.getElementById(params.closers));
			}
		}
	}
	//проверка функции onclose
	if (typeof params.onclose != "undefined") {
		if (typeof params.onclose == "object") {
			if (params.onclose instanceof Array) {
				var c;
				for (var i in params.onclose) {
					if (!params.hasOwnProperty(i)) return;
					c = params.onclose[i];
					if (c instanceof Function) {
						item.onclose.push(c);
					} else {
						if (typeof c == "string") item.onclose.push((function(code){
							eval(code + ";");
						}).bind(item.owner, c));
					}
				}
			}
		} else {
			if (params.onclose instanceof Function) {
				item.onclose.push(params.onclose);
			}
			else {
				if (typeof params.onclose == "string") item.onclose.push((function(code){
					eval(code + ";");
				}).bind(item.owner, params.onclose));
			}

		}
	}
	//проверка кнопки закрытия
	if (typeof params.showcloser != "undefined") {
		if (typeof params.showcloser == "boolean")
			item.showcloser = params.showcloser;
	}
	//проверка опции обрамляющего окна
	if (typeof params.windowed != "undefined") {
		if (typeof params.windowed == "boolean")
			item.windowed = params.windowed;
	}
	this._items.push(item);
	var id = this._items.length - 1;
	item.funcOnClose = this.hide.bind(this, id);
	for (var i in item.closers) {
		if (!item.closers.hasOwnProperty(i)) continue;
		this.plLib.eventAdd(item.closers[i], "click", item.funcOnClose);
	}
	if (item.showcloser) {
		item.closer = document.createElement("DIV");
		item.closer.className = "close";
		var el = document.createElement("DIV");
		el.className = "btn";
		this.plLib.eventAdd(el, "click", item.funcOnClose);
		item.closer.appendChild(el);
	}
	return id;
};
_pu.prototype.content = function(id, content, closers) {
	//проверяем аргументы
	if (typeof this._items[id] == "undefined") return false;
	if (typeof content == "undefined") return false;
	if (!content) return false;
	var item = this._items[id];
	//проверяем состояние видимости
	var visible = false;
	for (i in this._visible) {
		if (!this._visible.hasOwnProperty(i)) continue;
		if (this._visible[i] == id) {
			visible = true;
			break;
		}
	}
	//отвязываем предыдущий контент и клоузеры
	if (item.content) {
		if (item.parent) item.parent.appendChild(item.content);
		if (item.closers.length) {
			for (var i in item.closers) {
				if (!closers.hasOwnProperty(i)) continue;
				this.plLib.eventRemove(item.closers[i], "click", item.funcOnClose);
			}
		}
		item.closers = [];
		if (visible && item.showcloser && item.closer) item.closer.parentNode.removeChild(item.closer);
	}
	//привязываем новый контент
	if (typeof content == "string") {
		if (!document.getElementById(content)) {
			item.content = document.createElement("DIV");
			item.content.innerHTML = content;
			this.elMama.appendChild(item.content);
			item.parent = this.elMama;
		} else {
			item.content = document.getElementById(content);
			if (item.content.parentNode) item.parent = item.content.parentNode;
			else item.parent = this.elMama;
		}
	} else {
		if (typeof content != "object") return false;
		else {
			item.content = content;
			if (item.content.parentNode) item.parent = item.content.parentNode;
			else item.parent = this.elMama;
		}
	}
	if (typeof closers != "undefined") {
		if (typeof closers == "object") {
			if (closers instanceof Array) {
				var c;
				for (var i in closers) {
					if (!closers.hasOwnProperty(i)) continue;
					c = closers[i];
					if (typeof c == "string") {
						if (document.getElementById(c)) item.closers.push(document.getElementById(c));
					} else {
						if (typeof c == "object") item.closers.push(c);
					}
				}
			} else item.closers.push(closers);
		} else {
			if (typeof closers == "string") {
				if (document.getElementById(closers)) item.closers.push(document.getElementById(closers));
			}
		}
		for (var i in item.closers) {
			if (!item.closers.hasOwnProperty(i)) continue;
			this.plLib.eventAdd(item.closers[i], "click", item.funcOnClose);
		}
	}
	if (visible) {
		if (item.windowed) {
			this.elWin.appendChild(item.content);
		} else {
			this.elBox.appendChild(item.content)
		}
		if (item.showcloser && item.closer) item.content.parentNode.insertBefore(item.closer, item.content);
	}
	return true;
};
_pu.prototype.hide = function(id) {
	var f = false;
	for (i in this._visible) {
		if (!this._visible.hasOwnProperty(i)) continue;
		if (this._visible[i] == id) {
			f = parseInt(i, 10);
			break;
		}
	}
	if (f === false) return;
	var item = this._items[this._visible[f]];
	var res = true;
	var r;
	for (var i in item.onclose) {
		if (!item.onclose.hasOwnProperty(i)) continue;
		r = false;
		try {
			r = item.onclose[i]();
		} catch(e) {
			this.plRender.console(__name_script + " > " + this.$name + ".hide(): Ошибка выполнения callback-функции [itemId: " + i + "]. Сообщение интерпретатора: [" + e.name + "/" + e.message + "].");
		}
		if (typeof r == "boolean") res = res && r;
	}
	if (!res) return false;
	if (item.windowed)
		this.elWin_.style.display = "none";
	else
		this.elBox.style.display = "none";
	item.parent.appendChild(item.content);
	if (item.showcloser && item.closer) item.closer.parentNode.removeChild(item.closer);
	this._visible.splice(f, 1);
	var len = this._visible.length;
	if (len) {
		item = this._items[this._visible[len - 1]];
		if (item.windowed) {
			this.elWin_.style.display = "table-cell";
			this.elWin.appendChild(item.content);
		} else {
			this.elBox.style.display = "table-cell";
			this.elBox.appendChild(item.content)
		}
		if (item.showcloser && item.closer) item.content.parentNode.insertBefore(item.closer, item.content);
	} else {
		this.elShade.style.display = "none";
		this.elBase.style.display = "none";
	}
	return true;
};
_pu.prototype.show = function(id) {
	if (typeof this._items[id] == "undefined") {
		this.plRender.console(__name_script + " > " + this.$name + ".show(): Невозможно показать всплывающее окно, объект не найден [itemId: " + id + "].");
		return false;
	}
	if (!this._items[id].content) {
		this.plRender.console(__name_script + " > " + this.$name + ".show(): Невозможно показать всплывающее окно, контент не определен [itemId: " + id + "].");
		return false;
	}
	for (i in this._visible) {
		if (!this._visible.hasOwnProperty(i)) continue;
		if (this._visible[i] == id) return;
	}
	var len = this._visible.length;
	var item;
	if (len) {
		item = this._items[this._visible[len - 1]];
		if (item.windowed)
			this.elWin_.style.display = "none";
		else
			this.elBox.style.display = "none";
		item.parent.appendChild(item.content);
		if (item.showcloser && item.closer) item.closer.parentNode.removeChild(item.closer);
	} else {
		this.elShade.style.display = "block";
		this.elBase.style.display = "block";
	}
	item = this._items[id];
	if (item.windowed) {
		this.elWin_.style.display = "table-cell";
		this.elWin.appendChild(item.content);
	} else {
		this.elBox.style.display = "table-cell";
		this.elBox.appendChild(item.content)
	}
	if (item.showcloser && item.closer) item.content.parentNode.insertBefore(item.closer, item.content);
	this._visible.push(id);
	return true;
};

render.pluginReg(_pu, true);
})();

})();

})();