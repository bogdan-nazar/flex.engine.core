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
(function(){
var __name_this = "client",
	__name_script = __name + ".js";
var __name_lib = "lib";
var __name_msgr = "msgr";
var __name_popup = "popup";

var _client = function(cfg, ss) {
	//защищенные свойства ядра
	var self		=	this,
		secStack	=	ss,
		console		=	!!window.console,
		dirs		=	{
			admin:		"admin",
			app:		"core",
			modules:	"classes",
			require:	"require",
			source:		"",
			templates:	"templates",
			userdata:	"data"
		},
		elNames		=	{
			Action:		cfg.elNameAction || "",
			Form:		cfg.elNameForm || ""
		},
		elems		=	{
			action:		null,
			form:		null
		},
		events		=	{
			"onPageLoaded":	{done: false, listeners: [], type: "single"},
			"onFormSubmit":	{done: false, listeners: [], type: "multiple"}
		},
		form		=	{
			action:		"",
			target:		""
		},
		historyCbs	=	{},
		name		=	__name_this,
		page		=	{
			alias:		cfg.pageAlias,
			loaded:		false,
			template:	cfg.pageTemplate,
			title:		cfg.pageTitle,
			url:		{},
			urlMode:	"first"//or "last"
		};
	this._onLoad					=	[];
	this._onSubmit					=	[];
	this._waiter					=	false;

	//------------------------ methods --------------------------
	var _depends = function() {
		return {
			cfg: true,
			elems: ["form", "action"],
			plugs: ["msgr"]
		};
	};

	var _init = function() {
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
		this.waiterInit();
	};
	//--------------------- public methods ----------------------
	this._depends = _depends;
	this._init = _init;

	//------------------------- events --------------------------
	if (html5) this.evWinAdd(window, "popstate", this.$(["funcs", "onPopState"]));
};
_client.prototype.footerUpdate = function() {
	var n = this.$("name");
	var fm = document.getElementById(n + "-footer-margin");
	var f = document.getElementById(n + "-footer");
	if(!fm || !f) return;
	var fh = $(f).outerHeight(true);
	f.style.marginTop = "-" + fh + "px";
	fm.style.height = "" + fh + "px";
};
_client.prototype.getDir = function(name) {
	if (typeof this.$dirs[name] != "undefined") return this.$dirs[name];
	else return "";
};
_client.prototype.getOwnElem = function(name, prop) {
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
_client.prototype.getOwnProp = function(prop, child) {
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
_client.prototype.getRoot = function() {
	return this.$("appRoot");
};
_client.prototype.getTemplate = function() {
	return this.$(["page", "template"]);
};
_client.prototype.historyWrite = function(uri, title, params) {
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
_client.prototype.historyPop = function(e) {
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
_client.prototype.onSubmit = function() {
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
_client.prototype.onSubmitAdd = function(func) {
	if (typeof func == "undefined") return;
	if (typeof func == "string") func = (function(funcname) {eval(funcname + "();");}).bind(this, func);
	this._onSubmit.push(func);
};
_client.prototype.urlParse = function(url) {
	return this.z_sharedUrlParse(url);
};
_client.prototype.waiterHide = function() {
	if (this._pu == -1) {
		this.console(__name_script + " > " + this.$name + ".waiterHide(): Плагин всплывающего (модального) окна [" + __name_popup + "] отсутствует или не инициализирован.");
		return;
	}
	this._plugins[__name_popup].obj.hide(this._pu);
	this._waiter = false;
};
_client.prototype.waiterInit = function() {
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
_client.prototype.waiterShow = function() {
	if (this._pu == -1) {
		this.console(__name_script + " > " + this.$name + ".waiterShow(): Плагин всплывающего (модального) окна [" + __name_popup + "] отсутствует или не инициализирован.");
		return;
	}
	this._plugins[__name_popup].obj.show(this._pu);
	this._waiter = true;
};
var api = {};
_api.action = function(action, path, query, target, seed) {
	return this.z_sharedBoundAction(action, path, query, target, seed)
};
_api.console = function(msg, crit) {
	if (crit) this.dlgAlert(msg);
	else {
		if (this.$("console")) window.console.log(msg);
	}
};
_api.evWinAdd = function(el, evnt, func) {
	if (el.addEventListener) {
		el.addEventListener(evnt, func, false);
	} else if (el.attachEvent) {
		el.attachEvent("on" + evnt, func);
	} else {
		el[evnt] = func;
	}
};
_api.evWinFix = function(e) {
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
_api.evWinRem = function(el, evnt, func) {
	if (el.removeEventListener) {
		el.removeEventListener(evnt, func, false);
	} else if (el.attachEvent) {
		el.detachEvent("on" + evnt, func);
	} else {
		el[evnt] = null;
	}
};
_api.evWinStop = function(e) {
	if (typeof e == "undefined") return;
	if (e.preventDefault) {
		e.preventDefault();
		e.stopPropagation();
	} else {
		e.returnValue = false;
		e.cancelBubble = true;
	}
};

_api.BoundAction = function(action, path, query, target, seed) {
	var n = this.$("elNames");
	n.Action.value = "" + action;
	n.Form.target = ((typeof target == "string") && target) ? target : this.$("form").target;
	n.Form.action = this._plugins[__name_lib].obj.urlBuild(path, query, seed);
	n.Form.submit();
};

_api.BoundPluginNew = function(name, readyCb, struct) {
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
_api.BoundPluginReg = function(plugin, name, init) {
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
_api.BoundSilent = function(req) {
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
_api.BoundSilentX = function(req) {
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
_api.Clone = function(o, deep, skip) {
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
_api.ConfigWait = function(config, last, func) {
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
_api.Console = function(msg, crit) {
};
_api.DEl = function(elname, prop, last, store_as_object) {
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
_api.DElName = function(name) {
	return this.$name + this.$instance + "-" + name;
};
_api.PluginWait = function(name, prop, last, inited, config, parent, cb) {
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
_api.UrlParse = function(url) {
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
for (var c in _client.prototype) {
	if (!_client.prototype.hasOwnProperty(c)) continue;
	if (typeof _client.prototype[c] == "function") {
		_client.prototype[c].bind = _client.prototype.___bind;
		_client.prototype[c].id = "" + (Math.floor((Math.random() * 1000000000) + 1));
		ss[_client.prototype[c].id] = _client.prototype[c];
	}
}

try {
	render = new _render(window[entity].cfg, ss);
} catch (e) {
	if (window.console) window.console.log(__name_script + " > Объект ядра [" + __name_render + "] не создан, зависимые плагины не будут инициализированы. Сообщение интерпретатора: [" + e.name + "/" + e.message + "]");
	return;
}

})();