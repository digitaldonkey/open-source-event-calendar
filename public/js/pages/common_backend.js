/**
 * @license RequireJS domReady 2.0.0 Copyright (c) 2010-2012, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/requirejs/domReady for details
 */

/**
 * AJAX result after clicking Dismiss in license warning.
 * @param  {object} response Data returned by HTTP response
 */

/**
 * Dismiss button clicked in invalid license warning.
 *
 * @param  {Event} e jQuery event object
 */

/*
 * The MIT License
 *
 * Copyright (c) 2012 James Allardice
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/* ========================================================================
 * Bootstrap: tooltip.js v3.0.3
 * http://getbootstrap.com/javascript/#tooltip
 * Inspired by the original jQuery.tipsy by Jason Frame
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

/* ========================================================================
 * Bootstrap: popover.js v3.0.3
 * http://getbootstrap.com/javascript/#popovers
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

/* ========================================================================
 * Bootstrap: modal.js v3.0.3
 * http://getbootstrap.com/javascript/#modals
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

/* ========================================================================
 * Bootstrap: dropdown.js v3.0.3
 * http://getbootstrap.com/javascript/#dropdowns
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

timely.define("domReady", [], function () {
    function u(e) {
        var t;
        for (t = 0; t < e.length; t++) e[t](n)
    }

    function a() {
        var e = r;
        t && e.length && (r = [], u(e))
    }

    function f() {
        t || (t = !0, o && clearInterval(o), a())
    }

    function c(e) {
        return t ? e(n) : r.push(e), c
    }

    var e = typeof window != "undefined" && window.document, t = !e, n = e ? document : null, r = [], i, s, o;
    if (e) {
        if (document.addEventListener) document.addEventListener("DOMContentLoaded", f, !1), window.addEventListener("load", f, !1); else if (window.attachEvent) {
            window.attachEvent("onload", f), s = document.createElement("div");
            try {
                i = window.frameElement === null
            } catch (l) {
            }
            s.doScroll && i && window.external && (o = setInterval(function () {
                try {
                    s.doScroll(), f()
                } catch (e) {
                }
            }, 30))
        }
        (document.readyState === "complete" || document.readyState === "interactive") && f()
    }
    return c.version = "2.0.0", c.load = function (e, t, n, r) {
        r.isBuild ? n(null) : c(n)
    }, c
}), timely.define("scripts/common_scripts/backend/common_ajax_handlers", ["jquery_timely"], function (e) {
    var t = function (t) {
        t && (typeof t.message != "undefined" ? window.alert(t.message) : e(".ai1ec-facebook-cron-dismiss-notification").closest(".message").fadeOut())
    }, n = function (t) {
        t.error ? window.alert(t.message) : e(".ai1ec-dismiss-notification").closest(".message").fadeOut()
    }, r = function (t) {
        t.error ? window.alert(t.message) : e(".ai1ec-dismiss-intro-video").closest(".message").fadeOut()
    }, i = function (t) {
        t.error ? window.alert(t.message) : e(".ai1ec-dismiss-license-warning").closest(".message").fadeOut()
    };
    return {
        handle_dismiss_plugins: t,
        handle_dismiss_notification: n,
        handle_dismiss_intro_video: r,
        handle_dismiss_license_warning: i
    }
}), timely.define("scripts/common_scripts/backend/common_event_handlers", ["jquery_timely", "scripts/common_scripts/backend/common_ajax_handlers"], function (e, t) {
    var n = function (n) {
        var r = {action: "ai1ec_facebook_cron_dismiss"};
        e.post(ajaxurl, r, t.handle_dismiss_plugins, "json")
    }, r = function (n) {
        var r = e(this);
        r.attr("disabled", !0);
        var i = {action: "ai1ec_disable_notification", note: !1};
        e.post(ajaxurl, i, t.handle_dismiss_notification)
    }, i = function (n) {
        var r = e(this);
        r.attr("disabled", !0);
        var i = {action: "ai1ec_disable_intro_video", note: !1};
        e.post(ajaxurl, i, t.handle_dismiss_intro_video)
    }, s = function (n) {
        var r = e(this);
        r.attr("disabled", !0);
        var i = {action: "ai1ec_set_license_warning", value: "dismissed"};
        e.post(ajaxurl, i, t.handle_dismiss_license_warning)
    }, o = function (t) {
        e(this).parent().next(".ai1ec-limit-by-options-container").toggle().find("option").removeAttr("selected")
    };
    return {
        dismiss_plugins_messages_handler: n,
        dismiss_notification_handler: r,
        dismiss_intro_video_handler: i,
        dismiss_license_warning_handler: s,
        handle_multiselect_containers_widget_page: o
    }
}), timely.define("external_libs/Placeholders", [], function () {
    function e(e, t, n) {
        if (e.addEventListener) return e.addEventListener(t, n, !1);
        if (e.attachEvent) return e.attachEvent("on" + t, n)
    }

    function t(e, t) {
        var n, r;
        for (n = 0, r = e.length; n < r; n++) if (e[n] === t) return !0;
        return !1
    }

    function n(e, t) {
        var n;
        e.createTextRange ? (n = e.createTextRange(), n.move("character", t), n.select()) : e.selectionStart && (e.focus(), e.setSelectionRange(t, t))
    }

    function r(e, t) {
        try {
            return e.type = t, !0
        } catch (n) {
            return !1
        }
    }

    function P(e) {
        var t;
        return e.value === e.getAttribute(h) && e.getAttribute(p) === "true" ? (e.setAttribute(p, "false"), e.value = "", e.className = e.className.replace(f, ""), t = e.getAttribute(d), t && (e.type = t), !0) : !1
    }

    function H(e) {
        var t;
        return e.value === "" ? (e.setAttribute(p, "true"), e.value = e.getAttribute(h), e.className += " " + a, t = e.getAttribute(d), t ? e.type = "text" : e.type === "password" && S.changeType(e, "text") && e.setAttribute(d, "password"), !0) : !1
    }

    function B(e, t) {
        var n, r, i, s, o;
        if (e && e.getAttribute(h)) t(e); else {
            n = e ? e.getElementsByTagName("input") : n, r = e ? e.getElementsByTagName("textarea") : r;
            for (o = 0, s = n.length + r.length; o < s; o++) i = o < n.length ? n[o] : r[o - n.length], t(i)
        }
    }

    function j(e) {
        B(e, P)
    }

    function F(e) {
        B(e, H)
    }

    function I(e) {
        return function () {
            x && e.value === e.getAttribute(h) && e.getAttribute(p) === "true" ? S.moveCaret(e, 0) : P(e)
        }
    }

    function q(e) {
        return function () {
            H(e)
        }
    }

    function R(e) {
        return function (t) {
            N = e.value;
            if (e.getAttribute(p) === "true") return N !== e.getAttribute(h) || !S.inArray(o, t.keyCode)
        }
    }

    function U(e) {
        return function () {
            var t;
            e.getAttribute(p) === "true" && e.value !== N && (e.className = e.className.replace(f, ""), e.value = e.value.replace(e.getAttribute(h), ""), e.setAttribute(p, !1), t = e.getAttribute(d), t && (e.type = t)), e.value === "" && (e.blur(), S.moveCaret(e, 0))
        }
    }

    function z(e) {
        return function () {
            e === document.activeElement && e.value === e.getAttribute(h) && e.getAttribute(p) === "true" && S.moveCaret(e, 0)
        }
    }

    function W(e) {
        return function () {
            j(e)
        }
    }

    function X(e) {
        e.form && (O = e.form, O.getAttribute(v) || (S.addEventListener(O, "submit", W(O)), O.setAttribute(v, "true"))), S.addEventListener(e, "focus", I(e)), S.addEventListener(e, "blur", q(e)), x && (S.addEventListener(e, "keydown", R(e)), S.addEventListener(e, "keyup", U(e)), S.addEventListener(e, "click", z(e))), e.setAttribute(m, "true"), e.setAttribute(h, L), H(e)
    }

    var i = {Utils: {addEventListener: e, inArray: t, moveCaret: n, changeType: r}},
        s = ["text", "search", "url", "tel", "email", "password", "number", "textarea"],
        o = [27, 33, 34, 35, 36, 37, 38, 39, 40, 8, 46], u = "#ccc", a = "placeholdersjs",
        f = new RegExp("\\b" + a + "\\b"), l, c, h = "data-placeholder-value", p = "data-placeholder-active",
        d = "data-placeholder-type", v = "data-placeholder-submit", m = "data-placeholder-bound",
        g = "data-placeholder-focus", y = "data-placeholder-live", b = document.createElement("input"),
        w = document.getElementsByTagName("head")[0], E = document.documentElement, S = i.Utils, x, T, N, C, k, L, A, O,
        M, _, D;
    if (b.placeholder === void 0) {
        l = document.getElementsByTagName("input"), c = document.getElementsByTagName("textarea"), x = E.getAttribute(g) === "false", T = E.getAttribute(y) !== "false", C = document.createElement("style"), C.type = "text/css", k = document.createTextNode("." + a + " { color:" + u + "; }"), C.styleSheet ? C.styleSheet.cssText = k.nodeValue : C.appendChild(k), w.insertBefore(C, w.firstChild);
        for (D = 0, _ = l.length + c.length; D < _; D++) M = D < l.length ? l[D] : c[D - l.length], L = M.getAttribute("placeholder"), L && S.inArray(s, M.type) && X(M);
        A = setInterval(function () {
            for (D = 0, _ = l.length + c.length; D < _; D++) {
                M = D < l.length ? l[D] : c[D - l.length], L = M.getAttribute("placeholder");
                if (L && S.inArray(s, M.type)) {
                    M.getAttribute(m) || X(M);
                    if (L !== M.getAttribute(h) || M.type === "password" && !M.getAttribute(d)) M.type === "password" && !M.getAttribute(d) && S.changeType(M, "text") && M.setAttribute(d, "password"), M.value === M.getAttribute(h) && (M.value = L), M.setAttribute(h, L)
                }
            }
            T || clearInterval(A)
        }, 100)
    }
    return i.disable = j, i.enable = F, i
}), timely.define("external_libs/bootstrap/tooltip", ["jquery_timely"], function (e) {
    var t = function (e, t) {
        this.type = this.options = this.enabled = this.timeout = this.hoverState = this.$element = null, this.init("tooltip", e, t)
    };
    t.DEFAULTS = {
        animation: !0,
        placement: "top",
        selector: !1,
        template: '<div class="ai1ec-tooltip"><div class="ai1ec-tooltip-arrow"></div><div class="ai1ec-tooltip-inner"></div></div>',
        trigger: "hover focus",
        title: "",
        delay: 0,
        html: !1,
        container: !1
    }, t.prototype.init = function (t, n, r) {
        this.enabled = !0, this.type = t, this.$element = e(n), this.options = this.getOptions(r);
        var i = this.options.trigger.split(" ");
        for (var s = i.length; s--;) {
            var o = i[s];
            if (o == "click") this.$element.on("click." + this.type, this.options.selector, e.proxy(this.toggle, this)); else if (o != "manual") {
                var u = o == "hover" ? "mouseenter" : "focus", a = o == "hover" ? "mouseleave" : "blur";
                this.$element.on(u + "." + this.type, this.options.selector, e.proxy(this.enter, this)), this.$element.on(a + "." + this.type, this.options.selector, e.proxy(this.leave, this))
            }
        }
        this.options.selector ? this._options = e.extend({}, this.options, {
            trigger: "manual",
            selector: ""
        }) : this.fixTitle()
    }, t.prototype.getDefaults = function () {
        return t.DEFAULTS
    }, t.prototype.getOptions = function (t) {
        return t = e.extend({}, this.getDefaults(), this.$element.data(), t), t.delay && typeof t.delay == "number" && (t.delay = {
            show: t.delay,
            hide: t.delay
        }), t
    }, t.prototype.getDelegateOptions = function () {
        var t = {}, n = this.getDefaults();
        return this._options && e.each(this._options, function (e, r) {
            n[e] != r && (t[e] = r)
        }), t
    }, t.prototype.enter = function (t) {
        var n = t instanceof this.constructor ? t : e(t.currentTarget)[this.type](this.getDelegateOptions()).data("bs." + this.type);
        clearTimeout(n.timeout), n.hoverState = "in";
        if (!n.options.delay || !n.options.delay.show) return n.show();
        n.timeout = setTimeout(function () {
            n.hoverState == "in" && n.show()
        }, n.options.delay.show)
    }, t.prototype.leave = function (t) {
        var n = t instanceof this.constructor ? t : e(t.currentTarget)[this.type](this.getDelegateOptions()).data("bs." + this.type);
        clearTimeout(n.timeout), n.hoverState = "out";
        if (!n.options.delay || !n.options.delay.hide) return n.hide();
        n.timeout = setTimeout(function () {
            n.hoverState == "out" && n.hide()
        }, n.options.delay.hide)
    }, t.prototype.show = function () {
        var t = e.Event("show.bs." + this.type);
        if (this.hasContent() && this.enabled) {
            this.$element.trigger(t);
            if (t.isDefaultPrevented()) return;
            var n = this.tip();
            this.setContent(), this.options.animation && n.addClass("ai1ec-fade");
            var r = typeof this.options.placement == "function" ? this.options.placement.call(this, n[0], this.$element[0]) : this.options.placement,
                i = /\s?auto?\s?/i, s = i.test(r);
            s && (r = r.replace(i, "") || "top"), n.detach().css({
                top: 0,
                left: 0,
                display: "block"
            }).addClass("ai1ec-" + r), this.options.container ? n.appendTo(this.options.container) : n.insertAfter(this.$element);
            var o = this.getPosition(), u = n[0].offsetWidth, a = n[0].offsetHeight;
            if (s) {
                var f = this.$element.parent(), l = r,
                    c = document.documentElement.scrollTop || document.body.scrollTop,
                    h = this.options.container == "body" ? window.innerWidth : f.outerWidth(),
                    p = this.options.container == "body" ? window.innerHeight : f.outerHeight(),
                    d = this.options.container == "body" ? 0 : f.offset().left;
                r = r == "bottom" && o.top + o.height + a - c > p ? "top" : r == "top" && o.top - c - a < 0 ? "bottom" : r == "right" && o.right + u > h ? "left" : r == "left" && o.left - u < d ? "right" : r, n.removeClass("ai1ec-" + l).addClass("ai1ec-" + r)
            }
            var v = this.getCalculatedOffset(r, o, u, a);
            this.applyPlacement(v, r), this.$element.trigger("shown.bs." + this.type)
        }
    }, t.prototype.applyPlacement = function (e, t) {
        var n, r = this.tip(), i = r[0].offsetWidth, s = r[0].offsetHeight, o = parseInt(r.css("margin-top"), 10),
            u = parseInt(r.css("margin-left"), 10);
        isNaN(o) && (o = 0), isNaN(u) && (u = 0), e.top = e.top + o, e.left = e.left + u, r.offset(e).addClass("ai1ec-in");
        var a = r[0].offsetWidth, f = r[0].offsetHeight;
        t == "top" && f != s && (n = !0, e.top = e.top + s - f);
        if (/bottom|top/.test(t)) {
            var l = 0;
            e.left < 0 && (l = e.left * -2, e.left = 0, r.offset(e), a = r[0].offsetWidth, f = r[0].offsetHeight), this.replaceArrow(l - i + a, a, "left")
        } else this.replaceArrow(f - s, f, "top");
        n && r.offset(e)
    }, t.prototype.replaceArrow = function (e, t, n) {
        this.arrow().css(n, e ? 50 * (1 - e / t) + "%" : "")
    }, t.prototype.setContent = function () {
        var e = this.tip(), t = this.getTitle();
        e.find(".ai1ec-tooltip-inner")[this.options.html ? "html" : "text"](t), e.removeClass("ai1ec-fade ai1ec-in ai1ec-top ai1ec-bottom ai1ec-left ai1ec-right")
    }, t.prototype.hide = function () {
        function i() {
            t.hoverState != "in" && n.detach()
        }

        var t = this, n = this.tip(), r = e.Event("hide.bs." + this.type);
        this.$element.trigger(r);
        if (r.isDefaultPrevented()) return;
        return n.removeClass("ai1ec-in"), e.support.transition && this.$tip.hasClass("ai1ec-fade") ? n.one(e.support.transition.end, i).emulateTransitionEnd(150) : i(), this.$element.trigger("hidden.bs." + this.type), this
    }, t.prototype.fixTitle = function () {
        var e = this.$element;
        (e.attr("title") || typeof e.attr("data-original-title") != "string") && e.attr("data-original-title", e.attr("title") || "").attr("title", "")
    }, t.prototype.hasContent = function () {
        return this.getTitle()
    }, t.prototype.getPosition = function () {
        var t = this.$element[0];
        return e.extend({}, typeof t.getBoundingClientRect == "function" ? t.getBoundingClientRect() : {
            width: t.offsetWidth,
            height: t.offsetHeight
        }, this.$element.offset())
    }, t.prototype.getCalculatedOffset = function (e, t, n, r) {
        return e == "bottom" ? {
            top: t.top + t.height,
            left: t.left + t.width / 2 - n / 2
        } : e == "top" ? {
            top: t.top - r,
            left: t.left + t.width / 2 - n / 2
        } : e == "left" ? {top: t.top + t.height / 2 - r / 2, left: t.left - n} : {
            top: t.top + t.height / 2 - r / 2,
            left: t.left + t.width
        }
    }, t.prototype.getTitle = function () {
        var e, t = this.$element, n = this.options;
        return e = t.attr("data-original-title") || (typeof n.title == "function" ? n.title.call(t[0]) : n.title), e
    }, t.prototype.tip = function () {
        return this.$tip = this.$tip || e(this.options.template)
    }, t.prototype.arrow = function () {
        return this.$arrow = this.$arrow || this.tip().find(".ai1ec-tooltip-arrow")
    }, t.prototype.validate = function () {
        this.$element[0].parentNode || (this.hide(), this.$element = null, this.options = null)
    }, t.prototype.enable = function () {
        this.enabled = !0
    }, t.prototype.disable = function () {
        this.enabled = !1
    }, t.prototype.toggleEnabled = function () {
        this.enabled = !this.enabled
    }, t.prototype.toggle = function (t) {
        var n = t ? e(t.currentTarget)[this.type](this.getDelegateOptions()).data("bs." + this.type) : this;
        n.tip().hasClass("ai1ec-in") ? n.leave(n) : n.enter(n)
    }, t.prototype.destroy = function () {
        this.hide().$element.off("." + this.type).removeData("bs." + this.type)
    };
    var n = e.fn.tooltip;
    e.fn.tooltip = function (n) {
        return this.each(function () {
            var r = e(this), i = r.data("bs.tooltip"), s = typeof n == "object" && n;
            i || r.data("bs.tooltip", i = new t(this, s)), typeof n == "string" && i[n]()
        })
    }, e.fn.tooltip.Constructor = t, e.fn.tooltip.noConflict = function () {
        return e.fn.tooltip = n, this
    }
}), timely.define("external_libs/bootstrap/popover", ["jquery_timely", "external_libs/bootstrap/tooltip"], function (e) {
    var t = function (e, t) {
        this.init("popover", e, t)
    };
    if (!e.fn.tooltip) throw new Error("Popover requires tooltip.js");
    t.DEFAULTS = e.extend({}, e.fn.tooltip.Constructor.DEFAULTS, {
        placement: "right",
        trigger: "click",
        content: "",
        template: '<div class="ai1ec-popover"><div class="ai1ec-arrow"></div><h3 class="ai1ec-popover-title"></h3><div class="ai1ec-popover-content"></div></div>'
    }), t.prototype = e.extend({}, e.fn.tooltip.Constructor.prototype), t.prototype.constructor = t, t.prototype.getDefaults = function () {
        return t.DEFAULTS
    }, t.prototype.setContent = function () {
        var e = this.tip(), t = this.getTitle(), n = this.getContent();
        e.find(".ai1ec-popover-title")[this.options.html ? "html" : "text"](t), e.find(".ai1ec-popover-content")[this.options.html ? "html" : "text"](n), e.removeClass("ai1ec-fade ai1ec-top ai1ec-bottom ai1ec-left ai1ec-right ai1ec-in"), e.find(".ai1ec-popover-title").html() || e.find(".ai1ec-popover-title").hide()
    }, t.prototype.hasContent = function () {
        return this.getTitle() || this.getContent()
    }, t.prototype.getContent = function () {
        var e = this.$element, t = this.options;
        return e.attr("data-content") || (typeof t.content == "function" ? t.content.call(e[0]) : t.content)
    }, t.prototype.arrow = function () {
        return this.$arrow = this.$arrow || this.tip().find(".ai1ec-arrow")
    }, t.prototype.tip = function () {
        return this.$tip || (this.$tip = e(this.options.template)), this.$tip
    };
    var n = e.fn.popover;
    e.fn.popover = function (n) {
        return this.each(function () {
            var r = e(this), i = r.data("bs.popover"), s = typeof n == "object" && n;
            i || r.data("bs.popover", i = new t(this, s)), typeof n == "string" && i[n]()
        })
    }, e.fn.popover.Constructor = t, e.fn.popover.noConflict = function () {
        return e.fn.popover = n, this
    }
}), timely.define("external_libs/bootstrap/modal", ["jquery_timely"], function (e) {
    var t = function (t, n) {
        this.options = n, this.$element = e(t), this.$backdrop = this.isShown = null, this.options.remote && this.$element.load(this.options.remote)
    };
    t.DEFAULTS = {backdrop: !0, keyboard: !0, show: !0}, t.prototype.toggle = function (e) {
        return this[this.isShown ? "hide" : "show"](e)
    }, t.prototype.show = function (t) {
        var n = this, r = e.Event("show.bs.modal", {relatedTarget: t});
        this.$element.trigger(r);
        if (this.isShown || r.isDefaultPrevented()) return;
        this.isShown = !0, this.escape(), this.$element.on("click.dismiss.modal", '[data-dismiss="ai1ec-modal"]', e.proxy(this.hide, this)), this.backdrop(function () {
            var r = e.support.transition && n.$element.hasClass("ai1ec-fade");
            n.$element.parent().length || n.$element.appendTo(document.body), n.$element.show(), r && n.$element[0].offsetWidth, n.$element.addClass("ai1ec-in").attr("aria-hidden", !1), n.enforceFocus();
            var i = e.Event("shown.bs.modal", {relatedTarget: t});
            r ? n.$element.find(".ai1ec-modal-dialog").one(e.support.transition.end, function () {
                n.$element.focus().trigger(i)
            }).emulateTransitionEnd(300) : n.$element.focus().trigger(i)
        })
    }, t.prototype.hide = function (t) {
        t && t.preventDefault(), t = e.Event("hide.bs.modal"), this.$element.trigger(t);
        if (!this.isShown || t.isDefaultPrevented()) return;
        this.isShown = !1, this.escape(), e(document).off("focusin.bs.modal"), this.$element.removeClass("ai1ec-in").attr("aria-hidden", !0).off("click.dismiss.modal"), e.support.transition && this.$element.hasClass("ai1ec-fade") ? this.$element.one(e.support.transition.end, e.proxy(this.hideModal, this)).emulateTransitionEnd(300) : this.hideModal()
    }, t.prototype.enforceFocus = function () {
        e(document).off("focusin.bs.modal").on("focusin.bs.modal", e.proxy(function (e) {
            this.$element[0] !== e.target && !this.$element.has(e.target).length && this.$element.focus()
        }, this))
    }, t.prototype.escape = function () {
        this.isShown && this.options.keyboard ? this.$element.on("keyup.dismiss.bs.modal", e.proxy(function (e) {
            e.which == 27 && this.hide()
        }, this)) : this.isShown || this.$element.off("keyup.dismiss.bs.modal")
    }, t.prototype.hideModal = function () {
        var e = this;
        this.$element.hide(), this.backdrop(function () {
            e.removeBackdrop(), e.$element.trigger("hidden.bs.modal")
        })
    }, t.prototype.removeBackdrop = function () {
        this.$backdrop && this.$backdrop.remove(), this.$backdrop = null
    }, t.prototype.backdrop = function (t) {
        var n = this, r = this.$element.hasClass("ai1ec-fade") ? "ai1ec-fade" : "";
        if (this.isShown && this.options.backdrop) {
            var i = e.support.transition && r;
            this.$backdrop = e('<div class="ai1ec-modal-backdrop ' + r + '" />').appendTo(document.body), this.$element.on("click.dismiss.modal", e.proxy(function (e) {
                if (e.target !== e.currentTarget) return;
                this.options.backdrop == "static" ? this.$element[0].focus.call(this.$element[0]) : this.hide.call(this)
            }, this)), i && this.$backdrop[0].offsetWidth, this.$backdrop.addClass("ai1ec-in");
            if (!t) return;
            i ? this.$backdrop.one(e.support.transition.end, t).emulateTransitionEnd(150) : t()
        } else !this.isShown && this.$backdrop ? (this.$backdrop.removeClass("ai1ec-in"), e.support.transition && this.$element.hasClass("ai1ec-fade") ? this.$backdrop.one(e.support.transition.end, t).emulateTransitionEnd(150) : t()) : t && t()
    };
    var n = e.fn.modal;
    e.fn.modal = function (n, r) {
        return this.each(function () {
            var i = e(this), s = i.data("bs.modal"), o = e.extend({}, t.DEFAULTS, i.data(), typeof n == "object" && n);
            s || i.data("bs.modal", s = new t(this, o)), typeof n == "string" ? s[n](r) : o.show && s.show(r)
        })
    }, e.fn.modal.Constructor = t, e.fn.modal.noConflict = function () {
        return e.fn.modal = n, this
    }, e(document).on("click.bs.modal.data-api", '[data-toggle="ai1ec-modal"]', function (t) {
        var n = e(this), r = n.attr("href"), i = e(n.attr("data-target") || r && r.replace(/.*(?=#[^\s]+$)/, "")),
            s = i.data("modal") ? "toggle" : e.extend({remote: !/#/.test(r) && r}, i.data(), n.data());
        t.preventDefault(), i.modal(s, this).one("hide", function () {
            n.is(":visible") && n.focus()
        })
    }), e(document).on("show.bs.modal", ".ai1ec-modal", function () {
        e(document.body).addClass("ai1ec-modal-open")
    }).on("hidden.bs.modal", ".ai1ec-modal", function () {
        e(document.body).removeClass("ai1ec-modal-open")
    })
}), timely.define("external_libs/bootstrap/dropdown", ["jquery_timely"], function (e) {
    function i() {
        e(t).remove(), e(n).each(function (t) {
            var n = s(e(this));
            if (!n.hasClass("ai1ec-open")) return;
            n.trigger(t = e.Event("hide.bs.dropdown"));
            if (t.isDefaultPrevented()) return;
            n.removeClass("ai1ec-open").trigger("hidden.bs.dropdown")
        })
    }

    function s(t) {
        var n = t.attr("data-target");
        n || (n = t.attr("href"), n = n && /#/.test(n) && n.replace(/.*(?=#[^\s]*$)/, ""));
        var r = n && e(n);
        return r && r.length ? r : t.parent()
    }

    var t = ".ai1ec-dropdown-backdrop", n = "[data-toggle=ai1ec-dropdown]", r = function (t) {
        e(t).on("click.bs.dropdown", this.toggle)
    };
    r.prototype.toggle = function (t) {
        var n = e(this);
        if (n.is(".ai1ec-disabled, :disabled")) return;
        var r = s(n), o = r.hasClass("ai1ec-open");
        i();
        if (!o) {
            "ontouchstart" in document.documentElement && !r.closest(".ai1ec-navbar-nav").length && e('<div class="ai1ec-dropdown-backdrop"/>').insertAfter(e(this)).on("click", i), r.trigger(t = e.Event("show.bs.dropdown"));
            if (t.isDefaultPrevented()) return;
            r.toggleClass("ai1ec-open").trigger("shown.bs.dropdown"), n.focus()
        }
        return !1
    }, r.prototype.keydown = function (t) {
        if (!/(38|40|27)/.test(t.keyCode)) return;
        var r = e(this);
        t.preventDefault(), t.stopPropagation();
        if (r.is(".ai1ec-disabled, :disabled")) return;
        var i = s(r), o = i.hasClass("ai1ec-open");
        if (!o || o && t.keyCode == 27) return t.which == 27 && i.find(n).focus(), r.click();
        var u = e("[role=menu] li:not(.ai1ec-divider):visible a", i);
        if (!u.length) return;
        var a = u.index(u.filter(":focus"));
        t.keyCode == 38 && a > 0 && a--, t.keyCode == 40 && a < u.length - 1 && a++, ~a || (a = 0), u.eq(a).focus()
    };
    var o = e.fn.dropdown;
    e.fn.dropdown = function (t) {
        return this.each(function () {
            var n = e(this), i = n.data("bs.dropdown");
            i || n.data("bs.dropdown", i = new r(this)), typeof t == "string" && i[t].call(n)
        })
    }, e.fn.dropdown.Constructor = r, e.fn.dropdown.noConflict = function () {
        return e.fn.dropdown = o, this
    }, e(document).on("click.bs.dropdown.data-api", i).on("click.bs.dropdown.data-api", ".ai1ec-dropdown form", function (e) {
        e.stopPropagation()
    }).on("click.bs.dropdown.data-api", n, r.prototype.toggle).on("keydown.bs.dropdown.data-api", n + ", [role=menu]", r.prototype.keydown)
}), timely.define("scripts/common_scripts/backend/common_backend", ["jquery_timely", "domReady", "ai1ec_config", "scripts/common_scripts/backend/common_event_handlers", "external_libs/Placeholders", "external_libs/bootstrap/tooltip", "external_libs/bootstrap/popover", "external_libs/bootstrap/modal", "external_libs/bootstrap/dropdown"], function (e, t, n, r) {
    var i = function () {
        e("#ai1ec-facebook-filter option[value=exportable]:selected").length > 0 && e("table.wp-list-table tr.no-items").length === 0 && n.facebook_logged_in === "1" && (e("<option>").val("export-facebook").text("Export to facebook").appendTo("select[name='action']"), e("<option>").val("export-facebook").text("Export to facebook").appendTo("select[name='action2']"))
    }, s = function () {
        // disabled.
    }, o = function () {
        e("#ai1ec-video").length && (e.ajax({
            cache: !0,
            async: !0,
            dataType: "script",
            url: "//www.youtube.com/iframe_api"
        }), window.onYouTubeIframeAPIReady = function () {
            var t = new YT.Player("ai1ec-video", {height: "368", width: "600", videoId: window.ai1ecVideo.youtubeId});
            e("#ai1ec-video").css("display", "block"), e("#ai1ec-video-modal").on("hide", function () {
                t.stopVideo()
            })
        })
    }, u = function () {
        e(document).on("click", ".ai1ec-facebook-cron-dismiss-notification", r.dismiss_plugins_messages_handler).on("click", ".ai1ec-dismiss-notification", r.dismiss_notification_handler).on("click", ".ai1ec-dismiss-intro-video", r.dismiss_intro_video_handler).on("click", ".ai1ec-dismiss-license-warning", r.dismiss_license_warning_handler).on("click", ".ai1ec-limit-by-cat, .ai1ec-limit-by-tag, .ai1ec-limit-by-event", r.handle_multiselect_containers_widget_page).on("click", ".ai1ec-dismissable", function () {
            var t = {action: "osec_dismiss_notice", key: e(this).data("key")}, n = this;
            e.post(ajaxurl, t, function (t) {
                e(n).closest(".ai1ec-message").remove()
            })
        })
    }, a = function () {
        e("#ai1ec-support .ai1ec-download a[title]").popover({placement: "left"}), e(".ai1ec-tooltip-toggle").tooltip({container: "body"})
    }, f = function () {
        var t = e(".ai1ec-taxonomy-header"), n = e(".ai1ec-taxonomy-edit-link"), r;
        t.length && (e("form#edittag").length || n.removeClass("ai1ec-hide").appendTo(".wrap > h2:first"), e(".wrap").prepend(t.removeClass("ai1ec-hide")), t.find("li.ai1ec-active").length || (r = e("[data-ai1ec_active_tab]").data("ai1ec_active_tab"), r && e(r).addClass("ai1ec-active")), e('#menu-posts-osec_event a[href="edit-tags.php?taxonomy=events_categories&post_type=ai1ec_event"]').closest("li").addClass("current"))
    }, l = function () {
        t(function () {
            i(), f(), o(), u(), s(), a()
        })
    };
    return {start: l}
}), timely.require(["scripts/common_scripts/backend/common_backend"], function (e) {
    e.start()
}), timely.define("pages/common_backend", function () {
});
