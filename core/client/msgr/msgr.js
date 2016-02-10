/**
 * Плагин Messenger [msgr]
 *
 * Версия: 3.1.2 (07.08.2013 15:01 +0400)
 * Copyright (c) 2003-2013 Bogdan Nazar
 *
 * Примеры и документация: http://www.flexengin.ru/docs/client/msgr-js/
 * Двойное лицензирование: MIT и FlexEngine License.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.flexengin.ru/docs/license
 *
 * Требования: PHP FlexEngine Core 3.1.2+
*/
(function(){

var __name_lib = "lib";
var __name_msgr = "msgr";
var __name_popup = "popup";
var __name_script = "msgr.js";

//ищем core
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

//продолжаем инициализацию прототипов...
//--------------- Module msg Plugin -------------------
var _msgr = function() {
	this._alerts	=	[];
	this._confirms	=	[];
	this._initErr	=	false;
	this._inited	=	false;
	this.$name		=	__name_msgr;
	this._pus		=	[];
	this.elItems 	=	null;
	this.plLib		=	null;
	this.plPu		=	null;
	this.plRender	=	null;
};
_msgr.prototype._init = function(last) {
	if (this._inited) return true;
	if (typeof last != "boolean") last = false;
	if (this.waitPlugin(__name_lib, "plLib", last, true)) return this._inited;
	if (this.waitPlugin(__name_popup, "plPu", last, true)) return this._inited;
	if (this.waitElement(this.$name + "-items", "elItems", last)) return this._inited;
	this._inited = true;
	this.itemsShow();
	return true;
};
_msgr.prototype.dlgAlert = function(msg, type, wd) {
	var el = null;
	if ((typeof msg != "string") && (typeof msg.nodeType == "undefined")) {
		var err = false;
		if (typeof msg == "number") {
			if (typeof this._alerts[msg] != "undefined") {
				if (!this._inited) return msg;
				else {
					if (this._alerts[msg].pu != -1) {
						if (this.plPu.show(this._alerts[msg].pu)) return msg;
						return -1;
					} else {
						this.plRender.console(__name_script + " > " + this.$name + ".dlgAlert(): Невозможно создать модальное окно из стека [_alerts]: модальных окно не было создано в течение предыдущей операции.");
						return -1;
					}
				}
			} else err = true;
		} else err = true;
		if (err) {
			this.plRender.console(__name_script + " > " + this.$name + ".dlgAlert(): Невозможно создать модальное окно: неизвестный тип сообщения [msg].");
			this.plRender.console(msg);
			return -1;
		}
	} else {
		if (typeof msg == "string") {
			el = document.createElement("DIV");
			el.className = "ab-body";
			el.innerHTML = msg;
		} else {
			el = msg;
			el.className = (el.className ? el.className.concat(" ") : "").concat("ab-body");
		}
	}
	if (typeof type != "string") type = "inf";
	else {
		if ((type != "inf") && (type != "wrn") && (type != "err")) type = "inf";
	}
	if (typeof wd != "string") {
		wd = parseInt(wd, 10);
		if(isNaN(wd) || !wd) wd = 300;
	}
	if (typeof wd != "number") wd = 300;
	var m = document.createElement("DIV");
	m.className = this.$name;
	m.style.width = ("").concat(wd, "px");
	var el1 = document.createElement("DIV");
	el1.className = "alert-box";
	m.appendChild(el1);
	var el2 = document.createElement("DIV");
	el2.className = "ab-title " + type;
	switch (type) {
		case "wrn":
			el2.innerHTML = "<span>Предупреждение</span>";
			break;
		case "err":
			el2.innerHTML = "<span>Ошибка</span>";
			break;
		default:
			el2.innerHTML = "<span>Информация</span>";
			break;
	}
	el2.innerHTML = "<span class=\"alr\">&nbsp;</span>" + el2.innerHTML;
	el1.appendChild(el2);
	el1.appendChild(el);
	el2 = document.createElement("DIV");
	el2.className = "ab-buttons";
	var btn = document.createElement("DIV");
	btn.className = "btn cl";
	btn.innerHTML = "Закрыть";
	el2.appendChild(btn);
	el1.appendChild(el2);
	var obj = {msg: m, closer: btn, pu: -1, showed: false};
	this._alerts.push(obj);
	if (!this._inited) return (this._alerts.length - 1);
	obj.pu = this.plPu.add({content: m, windowed: true, showcloser: false, closers: btn});
	if (obj.pu != -1) {
		if (!this.plPu.show(obj.pu)) return -1;
	} else {
		this.plRender.console(__name_script + " > " + this.$name + ".dlgAlert(): Невозможно создать модальное окно: плагин модальных окон [" + __name_popup + "] вернул ошибочный ответ.");
		return -1;
	}
	return (this._alerts.length - 1);
};
_msgr.prototype.dlgConfirm = function(msg, cb, title, wd) {
	var el = null;
	if ((typeof msg != "string") && (typeof msg.nodeType == "undefined")) {
		var err = false;
		if (typeof msg == "number") {
			if (typeof this._confirms[msg] != "undefined") {
				if (!this._inited) return msg;
				else {
					if (this._confirms[msg].pu != -1) {
						if (this.plPu.show(this._confirms[msg].pu)) return msg;
						else return -1;
					} else {
						this.plRender.console(__name_script + " > " + this.$name + ".dlgConfirm(): Невозможно создать модальное окно из стека [_confirms]: модальных окно не было создано в течение предыдущей операции.");
						return -1;
					}
				}
			} else err = true;
		} else err = true;
		if (err) {
			this.plRender.console(__name_script + " > " + this.$name + ".dlgConfirm(): Невозможно создать модальное окно: неизвестный тип сообщения [msg].");
			this.plRender.console(msg);
			return -1;
		}
	} else {
		if (typeof msg == "string") {
			el = document.createElement("DIV");
			el.className = "ab-body";
			el.innerHTML = msg;
		} else {
			el = msg;
			el.className = (el.className ? el.className.concat(" ") : "").concat("ab-body");
		}
	}
	if (typeof cb != "function") cb = false;
	if (typeof title != "string") title = "Подтвердите действие";
	if (typeof wd != "string") {
		wd = parseInt(wd, 10);
		if(isNaN(wd) || !wd) wd = 300;
	}
	if (typeof wd != "number") wd = 300;
	var m = document.createElement("DIV");
	m.className = this.$name;
	m.style.width = ("").concat(wd, "px");
	var el1 = document.createElement("DIV");
	el1.className = "alert-box";
	m.appendChild(el1);
	var el2 = document.createElement("DIV");
	el2.className = "ab-title inf";
	el2.innerHTML = "<span class=\"alr\">&nbsp;</span><span>" + title + "</span>";
	el1.appendChild(el2);
	el1.appendChild(el);
	el2 = document.createElement("DIV");
	el2.className = "ab-buttons";
	var btn1 = document.createElement("DIV");
	btn1.className = "btn cl";
	btn1.innerHTML = "Отмена";
	el2.appendChild(btn1);
	var btn2 = document.createElement("DIV");
	btn2.className = "btn ok";
	btn2.innerHTML = "Да";
	el2.appendChild(btn2);
	el1.appendChild(el2);
	var obj = {msg: m, "cb": cb, pu: -1, showed: false};
	var f = function(res, obj) {
		if (obj.pu != -1) this.plPu.hide(obj.pu);
		if (typeof obj.cb == "function") {
			try {
				cb(res);
			} catch(e) {
				this.plRender.console(__name_script + " > " + this.$name + ".dlgConfirm(): Ошибка выполнения callback-функции при закрытии модального окна.");
			}
		}
	};
	this.plLib.eventAdd(btn1, "click", f.bind(this, false, obj));
	this.plLib.eventAdd(btn2, "click", f.bind(this, true, obj));
	this._confirms.push(obj);
	if (!this._inited) return (this._confirms.length - 1);
	obj.pu = this.plPu.add({content: m, windowed: true, showcloser: false});
	if (obj.pu != -1) this.plPu.show(obj.pu);
	else this.plRender.console(__name_script + " > " + this.$name + ".dlgConfirm(): Невозможно создать модальное окно: плагин модальных окон [" + __name_popup + "] вернул ошибочный ответ.");
	return (this._confirms.length - 1);
};
_msgr.prototype.itemsShow = function() {
	if (!this._inited || this._initErr) return;
	var items = [];
	var cnt = 0;
	for (var c in this.elItems.childNodes) {
		if (this.elItems.childNodes[c].nodeName && (this.elItems.childNodes[c].nodeName.toLowerCase() == "div")) {
			if (this.elItems.childNodes[c].id && this.elItems.childNodes[c].id.indexOf(this.$name + "-item-") != -1) {
				items.push(this.elItems.childNodes[c]);
				cnt++;
			}
		}
	}
	if (!cnt) return;
	for (var c in items) {
		if (!items.hasOwnProperty(c)) continue;
		this._pus.push(this.plPu.add({
			content: items[c],
			parent: this.elItems,
			windowed: true
		}));
	}
	for (var c in this._pus)
		this.plPu.show(this._pus[c]);
	for (var c in this._alerts) {
		if (!this._alerts.hasOwnProperty(c)) continue;
		if (this._alerts[c].showed) continue;
		this._alerts[c].showed = true;
		this._alerts[c].pu = this.plPu.add({content: this._alerts[c].msg, windowed: true, showcloser: false, closers: this._alerts[c].closer});
		if (this._alerts[c].pu != -1) this.plPu.show(this._alerts[c].pu);
		else this.plRender.console(__name_script + " > " + this.$name + ".itemShow(): Невозможно создать модальное окно из стека [_alerts]: плагин модальных окон [" + __name_popup + "] вернул ошибочный ответ.");
	}
};
_msgr.prototype.waitElement = render.sharedWaitElement;
_msgr.prototype.waitPlugin = render.sharedWaitPlugin;
render.pluginRegister(new _msgr(), true);

})();