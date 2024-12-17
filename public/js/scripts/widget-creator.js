/**
 * @license RequireJS domReady 2.0.0 Copyright (c) 2010-2012, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/requirejs/domReady for details
 */

/* ========================================================================
 * Bootstrap: tab.js v3.0.3
 * http://getbootstrap.com/javascript/#tabs
 * ========================================================================
 * Copyright 2013 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ======================================================================== */

timely.define("domReady",[],function(){function u(e){var t;for(t=0;t<e.length;t++)e[t](n)}function a(){var e=r;t&&e.length&&(r=[],u(e))}function f(){t||(t=!0,o&&clearInterval(o),a())}function c(e){return t?e(n):r.push(e),c}var e=typeof window!="undefined"&&window.document,t=!e,n=e?document:null,r=[],i,s,o;if(e){if(document.addEventListener)document.addEventListener("DOMContentLoaded",f,!1),window.addEventListener("load",f,!1);else if(window.attachEvent){window.attachEvent("onload",f),s=document.createElement("div");try{i=window.frameElement===null}catch(l){}s.doScroll&&i&&window.external&&(o=setInterval(function(){try{s.doScroll(),f()}catch(e){}},30))}(document.readyState==="complete"||document.readyState==="interactive")&&f()}return c.version="2.0.0",c.load=function(e,t,n,r){r.isBuild?n(null):c(n)},c}),timely.define("external_libs/bootstrap/tab",["jquery_timely"],function(e){var t=function(t){this.element=e(t)};t.prototype.show=function(){var t=this.element,n=t.closest("ul:not(.ai1ec-dropdown-menu)"),r=t.data("target");r||(r=t.attr("href"),r=r&&r.replace(/.*(?=#[^\s]*$)/,""));if(t.parent("li").hasClass("ai1ec-active"))return;var i=n.find(".ai1ec-active:last a")[0],s=e.Event("show.bs.tab",{relatedTarget:i});t.trigger(s);if(s.isDefaultPrevented())return;var o=e(r);this.activate(t.parent("li"),n),this.activate(o,o.parent(),function(){t.trigger({type:"shown.bs.tab",relatedTarget:i})})},t.prototype.activate=function(t,n,r){function o(){i.removeClass("ai1ec-active").find("> .ai1ec-dropdown-menu > .ai1ec-active").removeClass("ai1ec-active"),t.addClass("ai1ec-active"),s?(t[0].offsetWidth,t.addClass("ai1ec-in")):t.removeClass("ai1ec-fade"),t.parent(".ai1ec-dropdown-menu")&&t.closest("li.ai1ec-dropdown").addClass("ai1ec-active"),r&&r()}var i=n.find("> .ai1ec-active"),s=r&&e.support.transition&&i.hasClass("ai1ec-fade");s?i.one(e.support.transition.end,o).emulateTransitionEnd(150):o(),i.removeClass("ai1ec-in")};var n=e.fn.tab;e.fn.tab=function(n){return this.each(function(){var r=e(this),i=r.data("bs.tab");i||r.data("bs.tab",i=new t(this)),typeof n=="string"&&i[n]()})},e.fn.tab.Constructor=t,e.fn.tab.noConflict=function(){return e.fn.tab=n,this},e(document).on("click.bs.tab.data-api",'[data-toggle="ai1ec-tab"], [data-toggle="ai1ec-pill"]',function(t){t.preventDefault(),e(this).tab("show")})}),timely.define("libs/utils",["jquery_timely","external_libs/bootstrap/tab"],function(e){var t=function(){return{is_float:function(e){return!isNaN(parseFloat(e))},is_valid_coordinate:function(e,t){var n=t?90:180;return this.is_float(e)&&Math.abs(e)<n},convert_comma_to_dot:function(e){return e.replace(",",".")},field_has_value:function(t){var n="#"+t,r=e(n),i=!1;return r.length===1&&(i=e.trim(r.val())!==""),i},make_alert:function(t,n,r){var i="";switch(n){case"error":i="ai1ec-alert ai1ec-alert-danger";break;case"success":i="ai1ec-alert ai1ec-alert-success";break;default:i="ai1ec-alert ai1ec-alert-info"}var s=e("<div />",{"class":i,html:t});if(!r){var o=e("<button>",{type:"button","class":"ai1ec-close","data-dismiss":"ai1ec-alert",text:"×"});s.prepend(o)}return s},get_ajax_url:function(){return typeof window.ajaxurl=="undefined"?"http://localhost/wordpress/wp-admin/admin-ajax.php":window.ajaxurl},isUrl:function(e){var t=/(http|https|webcal):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;return t.test(e)},isValidEmail:function(e){var t=/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;return t.test(e)},activate_saved_tab_on_page_load:function(t){null===t||undefined===t?e("ul.ai1ec-nav a:first").tab("show"):e("ul.ai1ec-nav a[href="+t+"]").tab("show")},add_query_arg:function(e,t){if("string"!=typeof e)return!1;var n=e.indexOf("?")===-1?"?":"&";return-1!==e.indexOf(n+t[0]+"=")?e:e+n+t[0]+"="+t[1]},create_ai1ec_to_send:function(t){var n=e(t),r=[],i=["action","cat_ids","auth_ids","tag_ids","exact_date","display_filters","no_navigation","events_limit"];return n.each(function(){e.each(this.attributes,function(){this.specified&&this.value&&this.name.match(/^data-/)&&(-1<e.inArray(this.name.replace(/^data\-/,""),i)||this.name.match(/_ids$/))&&r.push(this.name.replace(/^data\-/,"")+"~"+this.value)})}),r.join("|")},init_autoselect:function(){e(document).on("click",".ai1ec-autoselect",function(t){if(e(this).data("clicked")&&t.originalEvent.detail<2)return;e(this).data("clicked",!0);var n;document.body.createTextRange?(n=document.body.createTextRange(),n.moveToElementText(this),n.select()):window.getSelection&&(selection=window.getSelection(),n=document.createRange(),n.selectNodeContents(this),selection.removeAllRanges(),selection.addRange(n))})}}}();return t}),timely.define("external_libs/jquery_cookie",["jquery_timely"],function(e){function n(e){return u.raw?e:encodeURIComponent(e)}function r(e){return u.raw?e:decodeURIComponent(e)}function i(e){return n(u.json?JSON.stringify(e):String(e))}function s(e){e.indexOf('"')===0&&(e=e.slice(1,-1).replace(/\\"/g,'"').replace(/\\\\/g,"\\"));try{return e=decodeURIComponent(e.replace(t," ")),u.json?JSON.parse(e):e}catch(n){}}function o(t,n){var r=u.raw?t:s(t);return e.isFunction(n)?n(r):r}var t=/\+/g,u=e.cookie=function(t,s,a){if(s!==undefined&&!e.isFunction(s)){a=e.extend({},u.defaults,a);if(typeof a.expires=="number"){var f=a.expires,l=a.expires=new Date;l.setTime(+l+f*864e5)}return document.cookie=[n(t),"=",i(s),a.expires?"; expires="+a.expires.toUTCString():"",a.path?"; path="+a.path:"",a.domain?"; domain="+a.domain:"",a.secure?"; secure":""].join("")}var c=t?undefined:{},h=document.cookie?document.cookie.split("; "):[];for(var p=0,d=h.length;p<d;p++){var v=h[p].split("="),m=r(v.shift()),g=v.join("=");if(t&&t===m){c=o(g,s);break}!t&&(g=o(g))!==undefined&&(c[m]=g)}return c};u.defaults={},e.removeCookie=function(t,n){return e.cookie(t)===undefined?!1:(e.cookie(t,"",e.extend({},n,{expires:-1})),!e.cookie(t))}}),timely.define("scripts/widget-creator",["jquery_timely","domReady","ai1ec_config","libs/utils","external_libs/bootstrap/tab","external_libs/jquery_cookie"],function(e,t,n,r){var i=function(t){var n=e(this).attr("href");e.cookie("widget_creator_active_tab",n)},s=function(t){t===null?e("ul.ai1ec-nav a:first").tab("show"):e("ul.ai1ec-nav a[href="+t+"]").tab("show")},o=function(t){"ai1ec-widget-loaded"===t.data&&e("#widget-preview-title").html(n.widget_creator.preview)},u=function(){var t=e("#ai1ec-superwidget-code"),r=e("<iframe />").appendTo(e("#osec-widget-preview").removeClass("ai1ec-hidden")),i=!1,s=function(){var r=n.set_calendar_page;if(n.calendar_page_id){var i=e(".ai1ec-tab-content .ai1ec-active"),s=i.attr("id"),o=t.data("widget-url"),u=n.javascript_widgets;r='&lt;script class="osec-widget-placeholder" data-widget="'+s+'"',e("select",i).each(function(){var t=e(this),n=t.val();if(n){var i=t.attr("id");t.attr("multiple")&&(n=n.join(","),i=t.data("id")),r+=" data-"+i+'="'+n+'"'}}),e("input",i).each(function(){var t=e(this),n=t.val();"checkbox"===t.attr("type")&&(n=this.checked);if(""!==n){var i=t.attr("id"),o=u[s];o[i]!=n&&(r+=" data-"+i+'="'+n+'"')}}),r+="&gt;<br>&nbsp;&nbsp;(function(){var d=document,s=d.createElement('script'),<br>&nbsp;&nbsp;i='ai1ec-script';if(d.getElementById(i))return;s.async=1;<br>&nbsp;&nbsp;s.id=i;s.src='"+o+"';<br>"+"&nbsp;&nbsp;d.getElementsByTagName('head')[0].appendChild(s);})();<br>"+"&lt;/script&gt;"}return t.html(r),r},o=function(){if(!i)return;e("#widget-preview-title").html(n.widget_creator.preview_loading);var o=r[0],u=s();if(!n.calendar_page_id)return;o=o.contentWindow?o.contentWindow:o.contentDocument.document?o.contentDocument.document:o.contentDocument,o.document.open(),o.document.write('<!DOCTYPE html><html><head><style type="text/css">body { padding: 20px 0; }</style></head><body>'+t.text()+"</body>"+"</html>"),o.document.close()};e(".timely").on("change",".ai1ec-form-group",o),e(".timely").on("shown.bs.tab",'a[data-toggle="ai1ec-tab"]',o),i=!0,o()};t(function(){r.activate_saved_tab_on_page_load(e.cookie("widget_creator_active_tab")),r.init_autoselect(),window.addEventListener("message",o,!1),u(),e(document).on("click","ul.ai1ec-nav a",i)})});
