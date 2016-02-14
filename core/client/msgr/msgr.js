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


_api.BoundAlert = function(id, type) {
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