/**
 * Пакет административных плагинов:
 * 1) Render (Admin) [render]: основной плагин модуля
 * 2) RenderBinds [render-binds]: плагин редактора байндингов
 * 3) RenderEditor [content-layout]: плагин редактора разметки страницы
 *
 * Версия: 1.0.0 (02.07.2013 13:21 +0400)
 * Copyright (c) 2003-2013 Bogdan Nazar
 *
 * Примеры и документация: http://www.flexengin.ru/docs/client/render/
 * Двойное лицензирование: MIT и FlexEngine License.
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.flexengin.ru/docs/license
 *
 * Требования: PHP FlexEngine Core 3.0.5 +
**/
(function(){

var __name_admin				= "admin";
var __name_lib					= "lib";
var __name_media				= "media";
var __name_popup				= "popup";
var __name_render				= "render";
var __name_render_admin			= __name_render + "-admin";
var __name_render_admin_binds	= __name_render_admin + "-binds";
var __name_render_admin_layout	= __name_render_admin + "-layout";
var __name_script				= "render-admin.js";

//ищем core
if ((typeof render != "object") || (!render) || (typeof render._name == "undefined")) {
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

//плагин render [static, independent]
(function(){
var _render = function() {
};
})();

//плагин байндингов модулей [allocatable, dependent]
(function(){
var _bindings = function() {
	this._inited		=	false;
	this.$name			=	__name_render_admin_binds;
	this.$nameProto		=	__name_render_admin_binds;
	this.plAdmin		=	null;
	this.plRender			=	null;
	this.plLib			=	null;
	this.plPu			=	null;
};
_bindings.prototype._init = function(last, config) {
	if (this._inited) return true;
	if (typeof last != "boolean") last = false;
	if (this.waitPlugin(__name_admin, "plAdmin", last, true)) return this._inited;
	this._inited = true;
	this.plLib = this.plAdmin.plLib;
	this.plPu = this.plAdmin.plPu;
	return true;
};
_bindings.prototype.run = function() {
};
_bindings.prototype.waitElement = render.sharedWaitElement;
_bindings.prototype.waitPlugin = render.sharedWaitPlugin;
render.pluginRegister(new _bindings(), true);
})();

//плагин выбора разметки страницы [allocatable]
(function(){
var _layout = function() {
	this._config		=	{
		loaded:				false,
		mid:				0,
		mode:				"popup",//block
	};
	this._dom			=	{};
	this._inited		=	false;
	this.$name			=	__name_layout;
	this.elPu			=	null;
	this.elWater		=	null;
	this.elWrap			=	null;
	this.plAdmin		=	null;
	this.plLib			=	null;
	this.plPu			=	null;
	this.plRender		=	null;
};
_layout.prototype._configImport = function(config) {
	return true;
};
_layout.prototype._init = function(last, config) {
	if (this._inited) return true;
	if (typeof last != "boolean") last = false;
	if (this.waitPlugin(__name_admin, "plAdmin", last, true)) return this._inited;
	if (this.waitConfig(config, last)) return this._inited;
	this._inited = true;
	this.plLib = this.plAdmin.plLib;
	this.plPu = this.plAdmin.plPu;
	return true;
};
_layout.prototype.waitConfig = render.sharedWaitConfig;
_layout.prototype.waitElement = render.sharedWaitElement;
_layout.prototype.waitPlugin = render.sharedWaitPlugin;
render.pluginRegisterProto(_layout, __name_layout);
})();

})();