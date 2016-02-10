/*
 * Плагин с общими функциями FlexEngine Helper Library
 * Версия: 3.1.0 build 1 (09.07.2013 12:42 +0400)
 * Copyright (c) 2003-2013 Bogdan Nazar
 *
 * Примеры и документация: http://www.flexengin.ru/docs/client/plugins/lib-js
 * Двойное лицензирование: MIT и FlexEngine License.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.flexengin.ru/docs/license
 *
 * Требования: FlexEngine Core Script 3.1+
 */
(function(){

var __name_lib = "lib";
var __name_script = "lib.js";

//продолжаем инициализацию...
var _lib = function() {
	this._imgsData		=	{
		empty: "data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==",
		folder: "data:image/gif;base64,R0lGODlhEAAOALMAAOazToeHh0tLS/7LZv/0jvb29t/f3//Ub//ge8WSLf/rhf/3kdbW1mxsbP//mf///yH5BAAAAAAALAAAAAAQAA4AAARe8L1Ekyky67QZ1hLnjM5UUde0ECwLJoExKcppV0aCcGCmTIHEIUEqjgaORCMxIC6e0CcguWw6aFjsVMkkIr7g77ZKPJjPZqIyd7sJAgVGoEGv2xsBxqNgYPj/gAwXEQA7",
		loading: "data:image/gif;base64,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"
	};
	this.$initErr		=	false;
	this.$inited		=	false;
	this.$msg			=	"";
	this.$name			=	__name_lib;
};
_lib.prototype._init = function(last) {
	if (this.$inited) return true;
	if (typeof last != "boolean") last = false;
	this.$inited = true;
	return true;
};
_lib.prototype.base64 = {
	_keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
	//метод для кодировки в base64 на javascript
	encode: function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0
		input = this._utf8_encode(input);
		while (i < input.length) {
			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);
			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;
			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}
			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
		}
		return output;
	},
	//метод для раскодировки из base64
	decode: function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;
		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
		while (i < input.length) {
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));
			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;
			output = output + String.fromCharCode(chr1);
			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
		}
		output = this._utf8_decode(output);
		return output;
	},
	// метод для кодировки в utf8
	_utf8_encode: function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";
		for (var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);
			if (c < 128) {
				utftext += String.fromCharCode(c);
			} else if ((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			} else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
		}
		return utftext;
	},
	//метод для раскодировки из utf8
	_utf8_decode: function (utftext) {
		var string = "";
		var i = 0;
		var c = 0;
		var c1 = 0;
		var c2 = 0;
		var c3 = 0;
		while( i < utftext.length ) {
			c = utftext.charCodeAt(i);
			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			} else if ((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			} else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
		}
		return string;
	}
};
_lib.prototype.cyr2lat = function(str) {
    var cyr2latChars = new Array(
['а', 'a'], ['б', 'b'], ['в', 'v'], ['г', 'g'],
['д', 'd'],  ['е', 'e'], ['ё', 'yo'], ['ж', 'zh'], ['з', 'z'],
['и', 'i'], ['й', 'y'], ['к', 'k'], ['л', 'l'],
['м', 'm'],  ['н', 'n'], ['о', 'o'], ['п', 'p'],  ['р', 'r'],
['с', 's'], ['т', 't'], ['у', 'u'], ['ф', 'f'],
['х', 'h'],  ['ц', 'c'], ['ч', 'ch'],['ш', 'sh'], ['щ', 'shch'],
['ъ', ''],  ['ы', 'y'], ['ь', ''],  ['э', 'e'], ['ю', 'yu'], ['я', 'ya'],

['А', 'A'], ['Б', 'B'],  ['В', 'V'], ['Г', 'G'],
['Д', 'D'], ['Е', 'E'], ['Ё', 'YO'],  ['Ж', 'ZH'], ['З', 'Z'],
['И', 'I'], ['Й', 'Y'],  ['К', 'K'], ['Л', 'L'],
['М', 'M'], ['Н', 'N'], ['О', 'O'],  ['П', 'P'],  ['Р', 'R'],
['С', 'S'], ['Т', 'T'],  ['У', 'U'], ['Ф', 'F'],
['Х', 'H'], ['Ц', 'C'], ['Ч', 'CH'], ['Ш', 'SH'], ['Щ', 'SHCH'],
['Ъ', ''],  ['Ы', 'Y'],
['Ь', ''],
['Э', 'E'],
['Ю', 'YU'],
['Я', 'YA'],

['a', 'a'], ['b', 'b'], ['c', 'c'], ['d', 'd'], ['e', 'e'],
['f', 'f'], ['g', 'g'], ['h', 'h'], ['i', 'i'], ['j', 'j'],
['k', 'k'], ['l', 'l'], ['m', 'm'], ['n', 'n'], ['o', 'o'],
['p', 'p'], ['q', 'q'], ['r', 'r'], ['s', 's'], ['t', 't'],
['u', 'u'], ['v', 'v'], ['w', 'w'], ['x', 'x'], ['y', 'y'],
['z', 'z'],

['A', 'A'], ['B', 'B'], ['C', 'C'], ['D', 'D'],['E', 'E'],
['F', 'F'],['G', 'G'],['H', 'H'],['I', 'I'],['J', 'J'],['K', 'K'],
['L', 'L'], ['M', 'M'], ['N', 'N'], ['O', 'O'],['P', 'P'],
['Q', 'Q'],['R', 'R'],['S', 'S'],['T', 'T'],['U', 'U'],['V', 'V'],
['W', 'W'], ['X', 'X'], ['Y', 'Y'], ['Z', 'Z'],

[' ', '-'],['0', '0'],['1', '1'],['2', '2'],['3', '3'],
['4', '4'],['5', '5'],['6', '6'],['7', '7'],['8', '8'],['9', '9'],
['-', '-']
    );
	var newStr = new String();
	var ch;
	for (var i = 0; i < str.length; i++) {
		ch = str.charAt(i);
		var newCh = '';
		for (var j = 0; j < cyr2latChars.length; j++) {
			if (ch == cyr2latChars[j][0]) {
				newCh = cyr2latChars[j][1];
			}
		}
		// Если найдено совпадение, то добавляется соответствие, если нет - пустая строка
		newStr += newCh;
	}
	// Удаляем повторяющие знаки - Именно на них заменяются пробелы.
	// Так же удаляем символы перевода строки, но это наверное уже лишнее
	return newStr.replace(/[-]{2,}/gim, '-').replace(/\n/gim, '');
};
_lib.prototype.elClassAdd = function(el, name) {
	if (!this.elClassHas(el, name))	el.className += " " + name;
};
_lib.prototype.elClassHas = function(el, name) {
	var re = new RegExp("\\b" + name + "\\b");
	return re.test(el.className);
};
_lib.prototype.elClassRemove = function(el, name) {
	var re = new RegExp("\\b" + name + "\\b");
	el.className = el.className.replace(re, "");
};
_lib.prototype.getBodyScrollLeft = function() {
  return self.pageXOffset || (document.documentElement && document.documentElement.scrollLeft) || (document.body && document.body.scrollLeft);
};
_lib.prototype.getBodyScrollTop = function() {
  return self.pageYOffset || (document.documentElement && document.documentElement.scrollTop) || (document.body && document.body.scrollTop);
};
_lib.prototype.getClientHeight = function() {
  return document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientHeight:document.body.clientHeight;
};
_lib.prototype.getClientWidth = function () {
  return document.compatMode=='CSS1Compat' && !window.opera?document.documentElement.clientWidth:document.body.clientWidth;
};
_lib.prototype.getElementPosition = function(elemId) {
	var elem;
	if (typeof elemId == "string")
		elem = document.getElementById(elemId);
	else
		elem = elemId;
	if (typeof elem != "object" || !elem) return {"left":0, "top":0, "width":0, "height":0};
	var w = elem.offsetWidth;
	var h = elem.offsetHeight;
	var l = 0;
	var t = 0;
	while (elem) {
	    l += elem.offsetLeft;
	    t += elem.offsetTop;
	    elem = elem.offsetParent;
	}
	return {"left": l, "top": t, "width": w, "height": h};
};
_lib.prototype.getFileExt = function(fileName) {
	return (-1 !== fileName.indexOf(".")) ? fileName.replace(/.*[.]/, "") : "";
};
_lib.prototype.getImage = function(name) {
	if (typeof this._imgsData[name] != "undefined") return this._imgsData[name];
	else return this._imgsData["empty"];
};
_lib.prototype.getStyle = function(el, styleProp) {
	var x;
	if (typeof el == "string")
		x = document.getElementById(el);
	else
		x = el;
	if (x.currentStyle)
		var y = x.currentStyle[styleProp];
	else if (window.getComputedStyle)
		var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(styleProp);
	return y;
};
_lib.prototype.lastMsg = function() {
	var msg = this.$msg;
	this.$msg = "";
	return msg;
};
_lib.prototype.numberFormat = function(number, decimals, dec_point, thousands_sep) {
	//function number_format
	// Format a number with grouped thousands
    //
    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +     bugfix by: Michael White (http://crestidg.com)
    var i, j, kw, kd, km;
    // input sanitation & defaults
    if (isNaN(decimals = Math.abs(decimals))) {
        decimals = 2;
    }
    if (dec_point == undefined) {
        dec_point = ",";
    }
    if (thousands_sep == undefined) {
        thousands_sep = ".";
    }
    i = parseInt(number = (+number || 0).toFixed(decimals)) + "";
    if ((j = i.length) > 3) {
        j = j % 3;
    } else {
        j = 0;
    }
    km = (j ? i.substr(0, j) + thousands_sep : "");
    kw = i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + thousands_sep);
    //kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).slice(2) : "");
    kd = (decimals ? dec_point + Math.abs(number - i).toFixed(decimals).replace(/-/, 0).slice(2) : "");
    return km + kw + kd;
};
_lib.prototype.pageElementPosAndSize = function(el) {
	if (typeof el == "string") {
		if (!document.getElementById(el)) return {left: 0, top: 0, width: 0, height: 0};
		el = document.getElementById(el);
	} else if (typeof el != "object") return {left: 0, top: 0, width: 0, height: 0};
	var w = el.offsetWidth;
	var h = el.offsetHeight;
	var l = 0;
	var t = 0;
	while (el) {
	    l += el.offsetLeft;
	    t += el.offsetTop;
	    el = el.offsetParent;
	}
	return {left: l, top: t, width: w, height: h};
};
_lib.prototype.pageSize = function() {
	var xScroll, yScroll;
	if (window.innerHeight && window.scrollMaxY) {
		xScroll = document.body.scrollWidth;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else if (document.documentElement && document.documentElement.scrollHeight > document.documentElement.offsetHeight){ // Explorer 6 strict mode
		xScroll = document.documentElement.scrollWidth;
		yScroll = document.documentElement.scrollHeight;
	} else { // Explorer Mac...would also work in Mozilla and Safari
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}
	var windowWidth, windowHeight;
	if (self.innerHeight) { // all except Explorer
		windowWidth = self.innerWidth;
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		windowWidth = document.documentElement.clientWidth;
		   windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}
	var pageWidth, pageHeight;
	// for small pages with total height less then height of the viewport
	if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else {
		pageHeight = yScroll;
	}
	// for small pages with total width less then width of the viewport
	if(xScroll < windowWidth){
		pageWidth = windowWidth;
	} else {
		pageWidth = xScroll;
	}
	return {pw: pageWidth, ph: pageHeight, ww: windowWidth, wh: windowHeight};
};
_lib.prototype.pageScrollTo = function(elem, offset) {
	if (typeof offset != "number") offset = false;
	function getOffsetSum(elem) {
		var top = 0, left = 0;
		while (elem) {
			top = top + parseInt(elem.offsetTop);
			left = left + parseInt(elem.offsetLeft);
			elem = elem.offsetParent;
		}
		return {top:top,left:left};
	};
	function getOffsetRect(elem) {
		// (1)
		var box = elem.getBoundingClientRect();
		// (2)
		var body = document.body;
		var docElem = document.documentElement;
		// (3)
		var scrollTop = window.pageYOffset || docElem.scrollTop || body.scrollTop;
		var scrollLeft = window.pageXOffset || docElem.scrollLeft || body.scrollLeft;
		// (4)
		var clientTop = docElem.clientTop || body.clientTop || 0;
		var clientLeft = docElem.clientLeft || body.clientLeft || 0;
		// (5)
		var top = box.top + scrollTop - clientTop;
		var left = box.left + scrollLeft - clientLeft;
		return {top: Math.round(top), left: Math.round(left)};
	};
	function getOffset(elem) {
		if (elem.getBoundingClientRect) {
			// "правильный" вариант
			return getOffsetRect(elem);
		} else {
			// пусть работает хоть как-то
			return getOffsetSum(elem);
		}
	};
	if (typeof elem == "string") {
		if (!document.getElementById(elem)) return;
		elem = document.getElementById(elem);
	}
	var coords = getOffset(elem);
	window.scrollTo(0, coords.top + (offset ? offset : 0));
};
_lib.prototype.pageScrolledBy = function () {
	//snippet source: http://learn.javascript.ru/metrics-window
	if (typeof window.pageXOffset != "undefined") {
		return {
			left: pageXOffset,
			top: pageYOffset
		};
	}
	var html = document.documentElement;
	var body = document.body;
	var top = html.scrollTop || body && body.scrollTop || 0;
	top -= html.clientTop;
	var left = html.scrollLeft || body && body.scrollLeft || 0;
	left -= html.clientLeft;
	return {"top": top, "left": left };
};
_lib.prototype.pageYScrolledTo = function(el) {
	if (typeof el == "string") {
		if (!document.getElementById(el)) return false;
		el = document.getElementById(el);
	} else if (typeof el != "object") return false;
	var ps = this.pageScrolledBy();
	var ep = this.pageElementPosAndSize(el);
	if ((document.documentElement.clientHeight + ps.top) >= ep.top) return true;
	else return false;
};
_lib.prototype.toggle = function(id, display) {
	if (typeof id == "string" && (id)) {
		if (!document.getElementById(id)) return false;
		else id = document.getElementById(id);
	} else if (!id) return false;
	if (typeof display != "string" || (!display)) display = "block";
	if (id.style.display == "none")
		id.style.display = display;
	else
		id.style.display = "none";
};
_lib.prototype.urlBuild = function(url, query, seed, encode) {
	var u = (typeof url == "string") ? url : window.location.href;
	u += ((typeof query == "string") && query) ? (((u.indexOf("?") == -1) ? "?" : "&") + query) : "";
	var _url = this.urlParse(u);
	if ((typeof seed == "boolean") && seed) _url.params["rnd"] = this.seed();
	encode = ((typeof encode != "boolean")) ? false : encode;
	var p = [];
	for (var id in _url.params)
		p.push("" + id + (_url.params[id] ? ("=" + (encode ? encodeURIComponent(_url.params[id]) : _url.params[id])) : ""));
	var q = p.join("&");
	u = _url.path + (q ? ("?" + q) : "") + (_url.hash ? ("#" + _url.hash) : "");
	return u;
};
_lib.prototype.validDateRu = function(dt) {
	//thnx to vemax
	//http://www.sql.ru/forum/actualthread.aspx?tid=637923
	var r = /^(\d{2})\.(\d{2})\.(\d{4})$/;
	if (r.test(dt)) {
		var d = RegExp.$1 * 1;
		var m = RegExp.$2 * 1;
		var y = RegExp.$3 * 1;
		var test = new Date(y, m - 1, d);
		return ((test.getFullYear() == y) && (test.getMonth() == (m - 1)) && (test.getDate() == d));
	} else return false;
};
_lib.prototype.validEmail = function(e) {
    var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
    return re.test(e);
};
_lib.prototype.validString = function(st, tp, minlen, maxlen, ignore_empty, fldname) {
	this.$msg = "";
	if (!st) {
		if (ignore_empty) return true;
		else {
			this.$msg = "Поле \"" + fldname + "\" не может быть пустым!";
			return false;
		}
	}
	if (!tp) {
		this.$msg = "Ошибка клиентского скрипта: неверные параметры функции!";
		return false;
	}
	if ((typeof(minlen) != "number") || (typeof(maxlen) != "number")) {
		this.$msg="Ошибка клиентского скрипта: неверные параметры функции!";
		return false;
	}
	var len = st.length;
	var chars = "";
	if (len < minlen) {
		this.$msg = "Поле \"" + fldname + "\" слишком короткое (мин. длина - " + minlen + ")!";
		return false;
	}
	if (len > maxlen) {
		this.$msg = "Поле \"" + fldname + "\" слишком длинное (макс. длина - " + maxlen + ")!";
		return false;
	}
	if (tp == "pass") chars = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890%@$*_";
	if (tp == "sent") chars = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890 .,-?";
	if (tp == "user") chars = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890_-";
	if (tp == "name") chars = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm .-";
	if (tp == "spon") chars = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890_-$.";
	if (tp == "addr") chars = "QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0123456789.- ";
	if (tp == "rusname") chars = "ЁЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЭЯЧСМИТЬБЮёйцукенгшщзхъфывапролджэячсмитьбю";
	if (tp == "int") chars = "0123456789";
	if (tp == "dec") chars = "0123456789.";
	if (tp == "phon") chars = "0123456789+- ()";
	var ch = "";
	for (var cnt = 0; cnt < len; cnt++) {
		ch = st.substr(cnt, 1);
		if (chars.indexOf(ch) == -1) {
			this.$msg = "Поле \"" + fldname + "\" содержит некорректные символы! Ожидаются ";
			if (tp == "pass") this.$msg = this.$msg + "[A-Z],[a-z],[0-9],[%@$*_]!";
			if (tp == "sent") this.$msg = this.$msg + "[A-Z],[a-z],[0-9],[ .,-?]!";
			if (tp == "user") this.$msg = this.$msg + "A-Z],[a-z],[0-9],[_-]!";
			if (tp == "name") this.$msg = this.$msg + "[A-Z],[a-z],[ .-]!";
			if (tp == "spon") this.$msg = this.$msg + "[A-Z],[a-z],[_-$.]!";
			if (tp == "addr") this.$msg = this.$msg + "[A-Z],[a-z],[0-9],[ .-]!";
			if (tp == "int") this.$msg = this.$msg + "[0-9]!";
			if (tp == "dec") this.$msg = this.$msg + "[0-9],[.]!";
			if (tp == "phon") this.$msg = this.$msg + "[0-9],[ +-()]";
			if (tp == "rusname") this.$msg = this.$msg + "[А-Я],[а-я],[ -.]";
			return false;
		}
	}
	return true;
};

})();