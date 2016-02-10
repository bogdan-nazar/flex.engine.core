/**
 * Плагин Auth [auth]
 *
 * Версия: 3.1.0 (09.07.2013 13:21 +0400)
 * Copyright (c) 2003-2013 Bogdan Nazar
 *
 * Примеры и документация: http://www.flexengin.ru/docs/client/auth-js/
 * Двойное лицензирование: MIT и FlexEngine License.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.flexengin.ru/docs/license
 *
 * Требования: PHP FlexEngine Core 3.1.0 +
*/
(function(){

var __name_auth = "auth";
var __name_script = "auth.js";

//ищем render
if ((typeof render != "object") || (!render) || (typeof render.$name == "undefined")) {
	console.log(__name_script + " > Object [render] not found or is incompatible version.");
	return;
}
if (typeof render.pluginGet !="function") {
	console.log(__name_script + " > Object [render] has no method [pluginGet] or is incompatible version.");
	return;
}
if (typeof render.pluginRegister !="function") {
	console.log(__name_script + " > Object [render] has no method [pluginRegister] or is incompatible version.");
	return;
}

//плагин Auth [static]
_auth = function() {
	this._inited		=	false;
	this.$name			=	__name_auth;
	this.fRegCheckLogin	=	null;
	this.plRender		=	null;
};
_auth.prototype._init = function(last) {
	if (this._inited) return true;
	if (typeof last != "boolean") last = false;
	this._inited = true;
	//this.fRegCheckLogin	= this.regLoginCheck.bind(this);
	//window.setInterval(this.fRegCheckLogin, 2500);
	return true;
};
_auth.prototype.actionLoginCheck = function() {
	var req = this.plRender.reqBuild();
	req.action = this.$name + "-reg-logincheck";
};
_auth.prototype.login = function() {
	var l = document.getElementById(this.$name + "-login-name");
	var p = document.getElementById(this.$name + "-login-pass");
	if (!l || !p) {
		alert("Действие невозможно: ошибка набора элементов сттраницы!");
		return false;
	}
	if (!l.value || (!p.value)) {
		alert("Все поля обязательны к заполнению!");
		return false;
	}
	this.plRender.action(this.$name + "-login");
};
_auth.prototype.logoff = function() {
	this.plRender.action(this.$name + "-logoff");
};
_auth.prototype.register = function() {
	var l = document.getElementById(this.$name + "-reg-name");
	var p = document.getElementById(this.$name + "-reg-pass");
	var p2 = document.getElementById(this.$name + "-reg-pass2");
	var e = document.getElementById(this.$name + "-reg-email");
	var d = document.getElementById(this.$name + "-reg-display");
	if (!l || !p || !p2 || !e || !d) {
		alert("Действие невозможно: ошибка набора элементов страницы!");
		return false;
	}
	if (!l.value) {
		alert("Введите логин!");
		l.focus();
		return false;
	}
	if (l.value.length < 4) {
		alert("Длина логина должна быть не менее 4-х символов!");
		l.focus();
		return false;
	}
	if (!p.value) {
		alert("Введите пароль!");
		p.focus();
		return false;
	}
	if (p.value.length < 6) {
		alert("Длина пароля должна быть не менее 6-ти символов!");
		p.focus();
		return false;
	}
	if (!p2.value || (p.value != p2.value)) {
		alert("Введенные пароли не совпадают!");
		if (!p2.value) p2.focus();
		else p.focus();
		return false;
	}
	if (!d.value) {
		alert("Укажите свое имя (как к вам обращаться)!");
		d.focus();
		return false;
	}
	if (!e.value) {
		alert("Введите корректный e-mail! В случае утери пароля, новый будет выслан вам по электронной почте.");
		e.focus();
		return false;
	}
	this.plRender.action(this.$name + "-register");
};
_auth.prototype.regLoginCheck = function() {
	var chk = true;
	chk = (!l.value && (l.value.length < 4)) && chk;
	chk = (!e.value && (e.value.length < 6)) && chk;
	if (chk) this.actionLoginCheck();
};
render.pluginRegister(new _auth(), true);

})();