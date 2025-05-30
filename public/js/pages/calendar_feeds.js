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

/*
Copyright 2012 Igor Vaynberg

Version: 3.3.1 Timestamp: Wed Feb 20 09:57:22 PST 2013

This software is licensed under the Apache License, Version 2.0 (the "Apache License") or the GNU
General Public License version 2 (the "GPL License"). You may choose either license to govern your
use of this software only upon the condition that you accept all of the terms of either the Apache
License or the GPL License.

You may obtain a copy of the Apache License and the GPL License at:

    http://www.apache.org/licenses/LICENSE-2.0
    http://www.gnu.org/licenses/gpl-2.0.html

Unless required by applicable law or agreed to in writing, software distributed under the
Apache License or the GPL Licesnse is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
CONDITIONS OF ANY KIND, either express or implied. See the Apache License and the GPL License for
the specific language governing permissions and limitations under the Apache License and the GPL License.
*/

/* ========================================================================
 * Bootstrap: alert.js v3.0.3
 * http://getbootstrap.com/javascript/#alerts
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
 * Bootstrap: button.js v3.0.3
 * http://getbootstrap.com/javascript/#buttons
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
 * Bootstrap: collapse.js v3.0.3
 * http://getbootstrap.com/javascript/#collapse
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
}), timely.define("external_libs/bootstrap/tab", ["jquery_timely"], function (e) {
    var t = function (t) {
        this.element = e(t)
    };
    t.prototype.show = function () {
        var t = this.element, n = t.closest("ul:not(.ai1ec-dropdown-menu)"), r = t.data("target");
        r || (r = t.attr("href"), r = r && r.replace(/.*(?=#[^\s]*$)/, ""));
        if (t.parent("li").hasClass("ai1ec-active")) return;
        var i = n.find(".ai1ec-active:last a")[0], s = e.Event("show.bs.tab", {relatedTarget: i});
        t.trigger(s);
        if (s.isDefaultPrevented()) return;
        var o = e(r);
        this.activate(t.parent("li"), n), this.activate(o, o.parent(), function () {
            t.trigger({type: "shown.bs.tab", relatedTarget: i})
        })
    }, t.prototype.activate = function (t, n, r) {
        function o() {
            i.removeClass("ai1ec-active").find("> .ai1ec-dropdown-menu > .ai1ec-active").removeClass("ai1ec-active"), t.addClass("ai1ec-active"), s ? (t[0].offsetWidth, t.addClass("ai1ec-in")) : t.removeClass("ai1ec-fade"), t.parent(".ai1ec-dropdown-menu") && t.closest("li.ai1ec-dropdown").addClass("ai1ec-active"), r && r()
        }

        var i = n.find("> .ai1ec-active"), s = r && e.support.transition && i.hasClass("ai1ec-fade");
        s ? i.one(e.support.transition.end, o).emulateTransitionEnd(150) : o(), i.removeClass("ai1ec-in")
    };
    var n = e.fn.tab;
    e.fn.tab = function (n) {
        return this.each(function () {
            var r = e(this), i = r.data("bs.tab");
            i || r.data("bs.tab", i = new t(this)), typeof n == "string" && i[n]()
        })
    }, e.fn.tab.Constructor = t, e.fn.tab.noConflict = function () {
        return e.fn.tab = n, this
    }, e(document).on("click.bs.tab.data-api", '[data-toggle="ai1ec-tab"], [data-toggle="ai1ec-pill"]', function (t) {
        t.preventDefault(), e(this).tab("show")
    })
}), timely.define("libs/utils", ["jquery_timely", "external_libs/bootstrap/tab"], function (e) {
    var t = function () {
        return {
            is_float: function (e) {
                return !isNaN(parseFloat(e))
            }, is_valid_coordinate: function (e, t) {
                var n = t ? 90 : 180;
                return this.is_float(e) && Math.abs(e) < n
            }, convert_comma_to_dot: function (e) {
                return e.replace(",", ".")
            }, field_has_value: function (t) {
                var n = "#" + t, r = e(n), i = !1;
                return r.length === 1 && (i = e.trim(r.val()) !== ""), i
            }, make_alert: function (t, n, r) {
                var i = "";
                switch (n) {
                    case"error":
                        i = "ai1ec-alert ai1ec-alert-danger";
                        break;
                    case"success":
                        i = "ai1ec-alert ai1ec-alert-success";
                        break;
                    default:
                        i = "ai1ec-alert ai1ec-alert-info"
                }
                var s = e("<div />", {"class": i, html: t});
                if (!r) {
                    var o = e("<button>", {
                        type: "button",
                        "class": "ai1ec-close",
                        "data-dismiss": "ai1ec-alert",
                        text: "×"
                    });
                    s.prepend(o)
                }
                return s
            }, get_ajax_url: function () {
                return typeof window.ajaxurl == "undefined" ? "http://localhost/wordpress/wp-admin/admin-ajax.php" : window.ajaxurl
            }, isUrl: function (e) {
                var t = /(http|https|webcal):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
                return t.test(e)
            }, isValidEmail: function (e) {
                var t = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return t.test(e)
            }, activate_saved_tab_on_page_load: function (t) {
                null === t || undefined === t ? e("ul.ai1ec-nav a:first").tab("show") : e("ul.ai1ec-nav a[href=" + t + "]").tab("show")
            }, add_query_arg: function (e, t) {
                if ("string" != typeof e) return !1;
                var n = e.indexOf("?") === -1 ? "?" : "&";
                return -1 !== e.indexOf(n + t[0] + "=") ? e : e + n + t[0] + "=" + t[1]
            }, create_ai1ec_to_send: function (t) {
                var n = e(t), r = [],
                    i = ["action", "cat_ids", "auth_ids", "tag_ids", "exact_date", "display_filters", "no_navigation", "events_limit"];
                return n.each(function () {
                    e.each(this.attributes, function () {
                        this.specified && this.value && this.name.match(/^data-/) && (-1 < e.inArray(this.name.replace(/^data\-/, ""), i) || this.name.match(/_ids$/)) && r.push(this.name.replace(/^data\-/, "") + "~" + this.value)
                    })
                }), r.join("|")
            }, init_autoselect: function () {
                e(document).on("click", ".ai1ec-autoselect", function (t) {
                    if (e(this).data("clicked") && t.originalEvent.detail < 2) return;
                    e(this).data("clicked", !0);
                    var n;
                    document.body.createTextRange ? (n = document.body.createTextRange(), n.moveToElementText(this), n.select()) : window.getSelection && (selection = window.getSelection(), n = document.createRange(), n.selectNodeContents(this), selection.removeAllRanges(), selection.addRange(n))
                })
            }
        }
    }();
    return t
}), timely.define("scripts/calendar_feeds/ics/ics_ajax_handlers", ["jquery_timely", "libs/utils"], function (e, t) {
    var n = function (n) {
        var r = e("#osec_add_new_ics"), o = e("#osec_feed_url");
        r.button("reset");
        if (n.error) {
            var u = t.make_alert(n.message, "error");
            e("#ics-alerts").append(u)
        } else {
            s(), e("#ai1ec-feeds-after").addClass("ai1ec-well ai1ec-well-sm").insertAfter("#ics .ai1ec-form-horizontal");
            var a = n.update.data.feed_id, f = e(n.message),
                l = e('.ai1ec_feed_id[value="' + a + '"] ').closest(".ai1ec-feed-container");
            f.find(".ai1ec-collapse").removeClass("ai1ec-collapse");
            var l = e('.ai1ec_feed_id[value="' + a + '"] ').closest(".ai1ec-feed-container");
            l.length ? l.replaceWith(f) : e("#ai1ec-feeds-after").after(f), n.update && n.update.data && !n.update.data.error && i(n.update.data)
        }
    }, r = function (n) {
        var r = e("input[value=" + n.feed_id + "]").closest(".ai1ec-feed-container"), i = n.error ? "error" : "success",
            s = t.make_alert(n.message, i);
        n.error ? e(".osec_update_ics", r).button("reset") : r.remove(), e("#ics-alerts").append(s)
    }, i = function (n) {
        var r = e("input[value=" + n.feed_id + "]").closest(".ai1ec-feed-container"), i = n.error ? "error" : "success",
            s = t.make_alert(n.message, i);
        e(".osec_update_ics", r).button("reset"), e("#ics-alerts").append(s)
    }, s = function () {
        e("#osec_feed_url").val(" ").prop("readonly", !1), e('#ai1ec-feeds-after input[type="checkbox"]').prop("checked", !1), e("#osec_feed_id").remove(), e("#osec_feed_category").select2("val", ""), e("#osec_feed_tags").select2("val", ""), e('[id^="ai1ec_feed_cfg_"]').select2("val", ""), e("#osec_ics_add_new, #osec_add_new_ics > i").removeClass("ai1ec-hidden"), e("#osec_ics_update").addClass("ai1ec-hidden"), e("#ics .ai1ec-alert").remove()
    };
    return {handle_add_new_ics: n, handle_delete_ics: r, handle_update_ics: i, reset_form: s}
}), timely.define("external_libs/select2", ["jquery_timely"], function (e) {
    (function (e) {
        typeof e.fn.each2 == "undefined" && e.fn.extend({
            each2: function (t) {
                var n = e([0]), r = -1, i = this.length;
                while (++r < i && (n.context = n[0] = this[r]) && t.call(n[0], r, n) !== !1) ;
                return this
            }
        })
    })(e), function (e, t) {
        function l(e, t) {
            var n = 0, r = t.length;
            for (; n < r; n += 1) if (c(e, t[n])) return n;
            return -1
        }

        function c(e, n) {
            return e === n ? !0 : e === t || n === t ? !1 : e === null || n === null ? !1 : e.constructor === String ? e === n + "" : n.constructor === String ? n === e + "" : !1
        }

        function h(t, n) {
            var r, i, s;
            if (t === null || t.length < 1) return [];
            r = t.split(n);
            for (i = 0, s = r.length; i < s; i += 1) r[i] = e.trim(r[i]);
            return r
        }

        function p(e) {
            return e.outerWidth(!1) - e.width()
        }

        function d(n) {
            var r = "keyup-change-value";
            n.bind("keydown", function () {
                e.data(n, r) === t && e.data(n, r, n.val())
            }), n.bind("keyup", function () {
                var i = e.data(n, r);
                i !== t && n.val() !== i && (e.removeData(n, r), n.trigger("keyup-change"))
            })
        }

        function v(n) {
            n.bind("mousemove", function (n) {
                var r = a;
                (r === t || r.x !== n.pageX || r.y !== n.pageY) && e(n.target).trigger("mousemove-filtered", n)
            })
        }

        function m(e, n, r) {
            r = r || t;
            var i;
            return function () {
                var t = arguments;
                window.clearTimeout(i), i = window.setTimeout(function () {
                    n.apply(r, t)
                }, e)
            }
        }

        function g(e) {
            var t = !1, n;
            return function () {
                return t === !1 && (n = e(), t = !0), n
            }
        }

        function y(e, t) {
            var n = m(e, function (e) {
                t.trigger("scroll-debounced", e)
            });
            t.bind("scroll", function (e) {
                l(e.target, t.get()) >= 0 && n(e)
            })
        }

        function b(e) {
            if (e[0] === document.activeElement) return;
            window.setTimeout(function () {
                var t = e[0], n = e.val().length, r;
                e.focus(), t.setSelectionRange ? t.setSelectionRange(n, n) : t.createTextRange && (r = t.createTextRange(), r.collapse(!0), r.moveEnd("character", n), r.moveStart("character", n), r.select())
            }, 0)
        }

        function w(e) {
            e.preventDefault(), e.stopPropagation()
        }

        function E(e) {
            e.preventDefault(), e.stopImmediatePropagation()
        }

        function S(t) {
            if (!u) {
                var n = t[0].currentStyle || window.getComputedStyle(t[0], null);
                u = e(document.createElement("div")).css({
                    position: "absolute",
                    left: "-10000px",
                    top: "-10000px",
                    display: "none",
                    fontSize: n.fontSize,
                    fontFamily: n.fontFamily,
                    fontStyle: n.fontStyle,
                    fontWeight: n.fontWeight,
                    letterSpacing: n.letterSpacing,
                    textTransform: n.textTransform,
                    whiteSpace: "nowrap"
                }), u.attr("class", "select2-sizer"), e("body").append(u)
            }
            return u.text(t.val()), u.width()
        }

        function x(t, n, r) {
            var i, s = [], o;
            i = t.attr("class"), typeof i == "string" && e(i.split(" ")).each2(function () {
                this.indexOf("select2-") === 0 && s.push(this)
            }), i = n.attr("class"), typeof i == "string" && e(i.split(" ")).each2(function () {
                this.indexOf("select2-") !== 0 && (o = r(this), typeof o == "string" && o.length > 0 && s.push(this))
            }), t.attr("class", s.join(" "))
        }

        function T(e, t, n, r) {
            var i = e.toUpperCase().indexOf(t.toUpperCase()), s = t.length;
            if (i < 0) {
                n.push(r(e));
                return
            }
            n.push(r(e.substring(0, i))), n.push("<span class='select2-match'>"), n.push(r(e.substring(i, i + s))), n.push("</span>"), n.push(r(e.substring(i + s, e.length)))
        }

        function N(t) {
            var n, r = 0, i = null, s = t.quietMillis || 100, o = t.url, u = this;
            return function (a) {
                window.clearTimeout(n), n = window.setTimeout(function () {
                    r += 1;
                    var n = r, s = t.data, f = o, l = t.transport || e.ajax, c = t.type || "GET", h = {};
                    s = s ? s.call(u, a.term, a.page, a.context) : null, f = typeof f == "function" ? f.call(u, a.term, a.page, a.context) : f, null !== i && i.abort(), t.params && (e.isFunction(t.params) ? e.extend(h, t.params.call(u)) : e.extend(h, t.params)), e.extend(h, {
                        url: f,
                        dataType: t.dataType,
                        data: s,
                        type: c,
                        cache: !1,
                        success: function (e) {
                            if (n < r) return;
                            var i = t.results(e, a.page);
                            a.callback(i)
                        }
                    }), i = l.call(u, h)
                }, s)
            }
        }

        function C(t) {
            var n = t, r, i, s = function (e) {
                return "" + e.text
            };
            e.isArray(n) && (i = n, n = {results: i}), e.isFunction(n) === !1 && (i = n, n = function () {
                return i
            });
            var o = n();
            return o.text && (s = o.text, e.isFunction(s) || (r = n.text, s = function (e) {
                return e[r]
            })), function (t) {
                var r = t.term, i = {results: []}, o;
                if (r === "") {
                    t.callback(n());
                    return
                }
                o = function (n, i) {
                    var u, a;
                    n = n[0];
                    if (n.children) {
                        u = {};
                        for (a in n) n.hasOwnProperty(a) && (u[a] = n[a]);
                        u.children = [], e(n.children).each2(function (e, t) {
                            o(t, u.children)
                        }), (u.children.length || t.matcher(r, s(u), n)) && i.push(u)
                    } else t.matcher(r, s(n), n) && i.push(n)
                }, e(n().results).each2(function (e, t) {
                    o(t, i.results)
                }), t.callback(i)
            }
        }

        function k(n) {
            var r = e.isFunction(n);
            return function (i) {
                var s = i.term, o = {results: []};
                e(r ? n() : n).each(function () {
                    var e = this.text !== t, n = e ? this.text : this;
                    (s === "" || i.matcher(s, n)) && o.results.push(e ? this : {id: this, text: this})
                }), i.callback(o)
            }
        }

        function L(t, n) {
            if (e.isFunction(t)) return !0;
            if (!t) return !1;
            throw new Error("formatterName must be a function or a falsy value")
        }

        function A(t) {
            return e.isFunction(t) ? t() : t
        }

        function O(t) {
            var n = 0;
            return e.each(t, function (e, t) {
                t.children ? n += O(t.children) : n++
            }), n
        }

        function M(e, n, r, i) {
            var s = e, o = !1, u, a, f, l, h;
            if (!i.createSearchChoice || !i.tokenSeparators || i.tokenSeparators.length < 1) return t;
            for (; ;) {
                a = -1;
                for (f = 0, l = i.tokenSeparators.length; f < l; f++) {
                    h = i.tokenSeparators[f], a = e.indexOf(h);
                    if (a >= 0) break
                }
                if (a < 0) break;
                u = e.substring(0, a), e = e.substring(a + h.length);
                if (u.length > 0) {
                    u = i.createSearchChoice(u, n);
                    if (u !== t && u !== null && i.id(u) !== t && i.id(u) !== null) {
                        o = !1;
                        for (f = 0, l = n.length; f < l; f++) if (c(i.id(u), i.id(n[f]))) {
                            o = !0;
                            break
                        }
                        o || r(u)
                    }
                }
            }
            if (s !== e) return e
        }

        function _(t, n) {
            var r = function () {
            };
            return r.prototype = new t, r.prototype.constructor = r, r.prototype.parent = t.prototype, r.prototype = e.extend(r.prototype, n), r
        }

        var n, r, i, s, o, u, a, f;
        n = {
            TAB: 9,
            ENTER: 13,
            ESC: 27,
            SPACE: 32,
            LEFT: 37,
            UP: 38,
            RIGHT: 39,
            DOWN: 40,
            SHIFT: 16,
            CTRL: 17,
            ALT: 18,
            PAGE_UP: 33,
            PAGE_DOWN: 34,
            HOME: 36,
            END: 35,
            BACKSPACE: 8,
            DELETE: 46,
            isArrow: function (e) {
                e = e.which ? e.which : e;
                switch (e) {
                    case n.LEFT:
                    case n.RIGHT:
                    case n.UP:
                    case n.DOWN:
                        return !0
                }
                return !1
            },
            isControl: function (e) {
                var t = e.which;
                switch (t) {
                    case n.SHIFT:
                    case n.CTRL:
                    case n.ALT:
                        return !0
                }
                return e.metaKey ? !0 : !1
            },
            isFunctionKey: function (e) {
                return e = e.which ? e.which : e, e >= 112 && e <= 123
            }
        }, f = e(document), o = function () {
            var e = 1;
            return function () {
                return e++
            }
        }(), f.bind("mousemove", function (e) {
            a = {x: e.pageX, y: e.pageY}
        }), r = _(Object, {
            bind: function (e) {
                var t = this;
                return function () {
                    e.apply(t, arguments)
                }
            }, init: function (n) {
                var r, i, s = ".select2-results", u;
                this.opts = n = this.prepareOpts(n), this.id = n.id, n.element.data("select2") !== t && n.element.data("select2") !== null && this.destroy(), this.enabled = !0, this.container = this.createContainer(), this.containerId = "s2id_" + (n.element.attr("id") || "autogen" + o()), this.containerSelector = "#" + this.containerId.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, "\\$1"), this.container.attr("id", this.containerId), this.body = g(function () {
                    return n.element.closest("body")
                }), x(this.container, this.opts.element, this.opts.adaptContainerCssClass), this.container.css(A(n.containerCss)), this.container.addClass(A(n.containerCssClass)), this.elementTabIndex = this.opts.element.attr("tabIndex"), this.opts.element.data("select2", this).addClass("select2-offscreen").bind("focus.select2", function () {
                    e(this).select2("focus")
                }).attr("tabIndex", "-1").before(this.container), this.container.data("select2", this), this.dropdown = this.container.find(".select2-drop"), this.dropdown.addClass(A(n.dropdownCssClass)), this.dropdown.data("select2", this), this.results = r = this.container.find(s), this.search = i = this.container.find("input.select2-input"), i.attr("tabIndex", this.elementTabIndex), this.resultsPage = 0, this.context = null, this.initContainer(), v(this.results), this.dropdown.delegate(s, "mousemove-filtered touchstart touchmove touchend", this.bind(this.highlightUnderEvent)), y(80, this.results), this.dropdown.delegate(s, "scroll-debounced", this.bind(this.loadMoreIfNeeded)), e.fn.mousewheel && r.mousewheel(function (e, t, n, i) {
                    var s = r.scrollTop(), o;
                    i > 0 && s - i <= 0 ? (r.scrollTop(0), w(e)) : i < 0 && r.get(0).scrollHeight - r.scrollTop() + i <= r.height() && (r.scrollTop(r.get(0).scrollHeight - r.height()), w(e))
                }), d(i), i.bind("keyup-change input paste", this.bind(this.updateResults)), i.bind("focus", function () {
                    i.addClass("select2-focused")
                }), i.bind("blur", function () {
                    i.removeClass("select2-focused")
                }), this.dropdown.delegate(s, "mouseup", this.bind(function (t) {
                    e(t.target).closest(".select2-result-selectable").length > 0 && (this.highlightUnderEvent(t), this.selectHighlighted(t))
                })), this.dropdown.bind("click mouseup mousedown", function (e) {
                    e.stopPropagation()
                }), e.isFunction(this.opts.initSelection) && (this.initSelection(), this.monitorSource()), (n.element.is(":disabled") || n.element.is("[readonly='readonly']")) && this.disable()
            }, destroy: function () {
                var e = this.opts.element.data("select2");
                this.propertyObserver && (delete this.propertyObserver, this.propertyObserver = null), e !== t && (e.container.remove(), e.dropdown.remove(), e.opts.element.removeClass("select2-offscreen").removeData("select2").unbind(".select2").attr({tabIndex: this.elementTabIndex}).show())
            }, prepareOpts: function (n) {
                var r, i, s, o;
                r = n.element, r.get(0).tagName.toLowerCase() === "select" && (this.select = i = n.element), i && e.each(["id", "multiple", "ajax", "query", "createSearchChoice", "initSelection", "data", "tags"], function () {
                    if (this in n) throw new Error("Option '" + this + "' is not allowed for Select2 when attached to a <select> element.")
                }), n = e.extend({}, {
                    populateResults: function (r, i, s) {
                        var o, u, a, f, l = this.opts.id, c = this;
                        o = function (r, i, u) {
                            var a, f, h, p, d, v, m, g, y, b;
                            r = n.sortResults(r, i, s);
                            for (a = 0, f = r.length; a < f; a += 1) h = r[a], d = h.disabled === !0, p = !d && l(h) !== t, v = h.children && h.children.length > 0, m = e("<li></li>"), m.addClass("select2-results-dept-" + u), m.addClass("select2-result"), m.addClass(p ? "select2-result-selectable" : "select2-result-unselectable"), d && m.addClass("select2-disabled"), v && m.addClass("select2-result-with-children"), m.addClass(c.opts.formatResultCssClass(h)), g = e(document.createElement("div")), g.addClass("select2-result-label"), b = n.formatResult(h, g, s, c.opts.escapeMarkup), b !== t && g.html(b), m.append(g), v && (y = e("<ul></ul>"), y.addClass("select2-result-sub"), o(h.children, y, u + 1), m.append(y)), m.data("select2-data", h), i.append(m)
                        }, o(i, r, 0)
                    }
                }, e.fn.select2.defaults, n), typeof n.id != "function" && (s = n.id, n.id = function (e) {
                    return e[s]
                });
                if (e.isArray(n.element.data("select2Tags"))) {
                    if ("tags" in n) throw "tags specified as both an attribute 'data-select2-tags' and in options of Select2 " + n.element.attr("id");
                    n.tags = n.element.attr("data-select2-tags")
                }
                i ? (n.query = this.bind(function (n) {
                    var i = {results: [], more: !1}, s = n.term, o, u, a;
                    a = function (e, t) {
                        var r;
                        e.is("option") ? n.matcher(s, e.text(), e) && t.push({
                            id: e.attr("value"),
                            text: e.text(),
                            element: e.get(),
                            css: e.attr("class"),
                            disabled: c(e.attr("disabled"), "disabled")
                        }) : e.is("optgroup") && (r = {
                            text: e.attr("label"),
                            children: [],
                            element: e.get(),
                            css: e.attr("class")
                        }, e.children().each2(function (e, t) {
                            a(t, r.children)
                        }), r.children.length > 0 && t.push(r))
                    }, o = r.children(), this.getPlaceholder() !== t && o.length > 0 && (u = o[0], e(u).text() === "" && (o = o.not(u))), o.each2(function (e, t) {
                        a(t, i.results)
                    }), n.callback(i)
                }), n.id = function (e) {
                    return e.id
                }, n.formatResultCssClass = function (e) {
                    return e.css
                }) : "query" in n || ("ajax" in n ? (o = n.element.data("ajax-url"), o && o.length > 0 && (n.ajax.url = o), n.query = N.call(n.element, n.ajax)) : "data" in n ? n.query = C(n.data) : "tags" in n && (n.query = k(n.tags), n.createSearchChoice === t && (n.createSearchChoice = function (e) {
                    return {id: e, text: e}
                }), n.initSelection === t && (n.initSelection = function (t, r) {
                    var i = [];
                    e(h(t.val(), n.separator)).each(function () {
                        var t = this, r = this, s = n.tags;
                        e.isFunction(s) && (s = s()), e(s).each(function () {
                            if (c(this.id, t)) return r = this.text, !1
                        }), i.push({id: t, text: r})
                    }), r(i)
                })));
                if (typeof n.query != "function") throw "query function not defined for Select2 " + n.element.attr("id");
                return n
            }, monitorSource: function () {
                var e = this.opts.element, t;
                e.bind("change.select2", this.bind(function (e) {
                    this.opts.element.data("select2-change-triggered") !== !0 && this.initSelection()
                })), t = this.bind(function () {
                    var e, t, n = this;
                    e = this.opts.element.attr("disabled") !== "disabled", t = this.opts.element.attr("readonly") === "readonly", e = e && !t, this.enabled !== e && (e ? this.enable() : this.disable()), x(this.container, this.opts.element, this.opts.adaptContainerCssClass), this.container.addClass(A(this.opts.containerCssClass)), x(this.dropdown, this.opts.element, this.opts.adaptDropdownCssClass), this.dropdown.addClass(A(this.opts.dropdownCssClass))
                }), e.bind("propertychange.select2 DOMAttrModified.select2", t), typeof WebKitMutationObserver != "undefined" && (this.propertyObserver && (delete this.propertyObserver, this.propertyObserver = null), this.propertyObserver = new WebKitMutationObserver(function (e) {
                    e.forEach(t)
                }), this.propertyObserver.observe(e.get(0), {attributes: !0, subtree: !1}))
            }, triggerChange: function (t) {
                t = t || {}, t = e.extend({}, t, {
                    type: "change",
                    val: this.val()
                }), this.opts.element.data("select2-change-triggered", !0), this.opts.element.trigger(t), this.opts.element.data("select2-change-triggered", !1), this.opts.element.click(), this.opts.blurOnChange && this.opts.element.blur()
            }, enable: function () {
                if (this.enabled) return;
                this.enabled = !0, this.container.removeClass("select2-container-disabled"), this.opts.element.removeAttr("disabled")
            }, disable: function () {
                if (!this.enabled) return;
                this.close(), this.enabled = !1, this.container.addClass("select2-container-disabled"), this.opts.element.attr("disabled", "disabled")
            }, opened: function () {
                return this.container.hasClass("select2-dropdown-open")
            }, positionDropdown: function () {
                var t = this.container.offset(), n = this.container.outerHeight(!1), r = this.container.outerWidth(!1),
                    i = this.dropdown.outerHeight(!1), s = e(window).scrollLeft() + e(window).width(),
                    o = e(window).scrollTop() + e(window).height(), u = t.top + n, a = t.left, f = u + i <= o,
                    l = t.top - i >= this.body().scrollTop(), c = this.dropdown.outerWidth(!1), h = a + c <= s,
                    p = this.dropdown.hasClass("select2-drop-above"), d, v, m;
                this.body().css("position") !== "static" && (d = this.body().offset(), u -= d.top, a -= d.left), p ? (v = !0, !l && f && (v = !1)) : (v = !1, !f && l && (v = !0)), h || (a = t.left + r - c), v ? (u = t.top - i, this.container.addClass("select2-drop-above"), this.dropdown.addClass("select2-drop-above")) : (this.container.removeClass("select2-drop-above"), this.dropdown.removeClass("select2-drop-above")), m = e.extend({
                    top: u,
                    left: a,
                    width: r
                }, A(this.opts.dropdownCss)), this.dropdown.css(m)
            }, shouldOpen: function () {
                var t;
                return this.opened() ? !1 : (t = e.Event("opening"), this.opts.element.trigger(t), !t.isDefaultPrevented())
            }, clearDropdownAlignmentPreference: function () {
                this.container.removeClass("select2-drop-above"), this.dropdown.removeClass("select2-drop-above")
            }, open: function () {
                return this.shouldOpen() ? (window.setTimeout(this.bind(this.opening), 1), !0) : !1
            }, opening: function () {
                var t = this.containerId, n = "scroll." + t, r = "resize." + t, i = "orientationchange." + t, s;
                this.clearDropdownAlignmentPreference(), this.container.addClass("select2-dropdown-open").addClass("select2-container-active"), this.dropdown[0] !== this.body().children().last()[0] && this.dropdown.detach().appendTo(this.body()), this.updateResults(!0), s = e("#select2-drop-mask"), s.length == 0 && (s = e(document.createElement("div")), s.attr("id", "select2-drop-mask").attr("class", "select2-drop-mask"), s.hide(), s.appendTo(this.body()), s.bind("mousedown touchstart", function (t) {
                    var n = e("#select2-drop"), r;
                    n.length > 0 && (r = n.data("select2"), r.opts.selectOnBlur && r.selectHighlighted({noFocus: !0}), r.close())
                })), this.dropdown.prev()[0] !== s[0] && this.dropdown.before(s), e("#select2-drop").removeAttr("id"), this.dropdown.attr("id", "select2-drop"), s.css({
                    width: document.documentElement.scrollWidth,
                    height: document.documentElement.scrollHeight
                }), s.show(), this.dropdown.show(), this.positionDropdown(), this.dropdown.addClass("select2-drop-active"), this.ensureHighlightVisible();
                var o = this;
                this.container.parents().add(window).each(function () {
                    e(this).bind(r + " " + n + " " + i, function (t) {
                        e("#select2-drop-mask").css({
                            width: document.documentElement.scrollWidth,
                            height: document.documentElement.scrollHeight
                        }), o.positionDropdown()
                    })
                }), this.focusSearch()
            }, close: function () {
                if (!this.opened()) return;
                var t = this.containerId, n = "scroll." + t, r = "resize." + t, i = "orientationchange." + t;
                this.container.parents().add(window).each(function () {
                    e(this).unbind(n).unbind(r).unbind(i)
                }), this.clearDropdownAlignmentPreference(), e("#select2-drop-mask").hide(), this.dropdown.removeAttr("id"), this.dropdown.hide(), this.container.removeClass("select2-dropdown-open"), this.results.empty(), this.clearSearch(), this.opts.element.trigger(e.Event("close"))
            }, clearSearch: function () {
            }, getMaximumSelectionSize: function () {
                return A(this.opts.maximumSelectionSize)
            }, ensureHighlightVisible: function () {
                var t = this.results, n, r, i, s, o, u, a;
                r = this.highlight();
                if (r < 0) return;
                if (r == 0) {
                    t.scrollTop(0);
                    return
                }
                n = this.findHighlightableChoices(), i = e(n[r]), s = i.offset().top + i.outerHeight(!0), r === n.length - 1 && (a = t.find("li.select2-more-results"), a.length > 0 && (s = a.offset().top + a.outerHeight(!0))), o = t.offset().top + t.outerHeight(!0), s > o && t.scrollTop(t.scrollTop() + (s - o)), u = i.offset().top - t.offset().top, u < 0 && i.css("display") != "none" && t.scrollTop(t.scrollTop() + u)
            }, findHighlightableChoices: function () {
                var e = this.results.find(".select2-result-selectable:not(.select2-selected):not(.select2-disabled)");
                return this.results.find(".select2-result-selectable:not(.select2-selected):not(.select2-disabled)")
            }, moveHighlight: function (t) {
                var n = this.findHighlightableChoices(), r = this.highlight();
                while (r > -1 && r < n.length) {
                    r += t;
                    var i = e(n[r]);
                    if (i.hasClass("select2-result-selectable") && !i.hasClass("select2-disabled") && !i.hasClass("select2-selected")) {
                        this.highlight(r);
                        break
                    }
                }
            }, highlight: function (t) {
                var n = this.findHighlightableChoices(), r, i;
                if (arguments.length === 0) return l(n.filter(".select2-highlighted")[0], n.get());
                t >= n.length && (t = n.length - 1), t < 0 && (t = 0), this.results.find(".select2-highlighted").removeClass("select2-highlighted"), r = e(n[t]), r.addClass("select2-highlighted"), this.ensureHighlightVisible(), i = r.data("select2-data"), i && this.opts.element.trigger({
                    type: "highlight",
                    val: this.id(i),
                    choice: i
                })
            }, countSelectableResults: function () {
                return this.findHighlightableChoices().length
            }, highlightUnderEvent: function (t) {
                var n = e(t.target).closest(".select2-result-selectable");
                if (n.length > 0 && !n.is(".select2-highlighted")) {
                    var r = this.findHighlightableChoices();
                    this.highlight(r.index(n))
                } else n.length == 0 && this.results.find(".select2-highlighted").removeClass("select2-highlighted")
            }, loadMoreIfNeeded: function () {
                var e = this.results, t = e.find("li.select2-more-results"), n, r = -1, i = this.resultsPage + 1,
                    s = this, o = this.search.val(), u = this.context;
                if (t.length === 0) return;
                n = t.offset().top - e.offset().top - e.height(), n <= this.opts.loadMorePadding && (t.addClass("select2-active"), this.opts.query({
                    element: this.opts.element,
                    term: o,
                    page: i,
                    context: u,
                    matcher: this.opts.matcher,
                    callback: this.bind(function (n) {
                        if (!s.opened()) return;
                        s.opts.populateResults.call(this, e, n.results, {
                            term: o,
                            page: i,
                            context: u
                        }), n.more === !0 ? (t.detach().appendTo(e).text(s.opts.formatLoadMore(i + 1)), window.setTimeout(function () {
                            s.loadMoreIfNeeded()
                        }, 10)) : t.remove(), s.positionDropdown(), s.resultsPage = i, s.context = n.context
                    })
                }))
            }, tokenize: function () {
            }, updateResults: function (n) {
                function f() {
                    i.scrollTop(0), r.removeClass("select2-active"), u.positionDropdown()
                }

                function l(e) {
                    i.html(e), f()
                }

                var r = this.search, i = this.results, s = this.opts, o, u = this, a;
                if (n !== !0 && (this.showSearchInput === !1 || !this.opened())) return;
                r.addClass("select2-active");
                var h = this.getMaximumSelectionSize();
                if (h >= 1) {
                    o = this.data();
                    if (e.isArray(o) && o.length >= h && L(s.formatSelectionTooBig, "formatSelectionTooBig")) {
                        l("<li class='select2-selection-limit'>" + s.formatSelectionTooBig(h) + "</li>");
                        return
                    }
                }
                if (r.val().length < s.minimumInputLength) {
                    L(s.formatInputTooShort, "formatInputTooShort") ? l("<li class='select2-no-results'>" + s.formatInputTooShort(r.val(), s.minimumInputLength) + "</li>") : l("");
                    return
                }
                s.formatSearching() && n === !0 && l("<li class='select2-searching'>" + s.formatSearching() + "</li>");
                if (s.maximumInputLength && r.val().length > s.maximumInputLength) {
                    L(s.formatInputTooLong, "formatInputTooLong") ? l("<li class='select2-no-results'>" + s.formatInputTooLong(r.val(), s.maximumInputLength) + "</li>") : l("");
                    return
                }
                a = this.tokenize(), a != t && a != null && r.val(a), this.resultsPage = 1, s.query({
                    element: s.element,
                    term: r.val(),
                    page: this.resultsPage,
                    context: null,
                    matcher: s.matcher,
                    callback: this.bind(function (o) {
                        var a;
                        if (!this.opened()) return;
                        this.context = o.context === t ? null : o.context, this.opts.createSearchChoice && r.val() !== "" && (a = this.opts.createSearchChoice.call(null, r.val(), o.results), a !== t && a !== null && u.id(a) !== t && u.id(a) !== null && e(o.results).filter(function () {
                            return c(u.id(this), u.id(a))
                        }).length === 0 && o.results.unshift(a));
                        if (o.results.length === 0 && L(s.formatNoMatches, "formatNoMatches")) {
                            l("<li class='select2-no-results'>" + s.formatNoMatches(r.val()) + "</li>");
                            return
                        }
                        i.empty(), u.opts.populateResults.call(this, i, o.results, {
                            term: r.val(),
                            page: this.resultsPage,
                            context: null
                        }), o.more === !0 && L(s.formatLoadMore, "formatLoadMore") && (i.append("<li class='select2-more-results'>" + u.opts.escapeMarkup(s.formatLoadMore(this.resultsPage)) + "</li>"), window.setTimeout(function () {
                            u.loadMoreIfNeeded()
                        }, 10)), this.postprocessResults(o, n), f()
                    })
                })
            }, cancel: function () {
                this.close()
            }, blur: function () {
                this.opts.selectOnBlur && this.selectHighlighted({noFocus: !0}), this.close(), this.container.removeClass("select2-container-active"), this.search[0] === document.activeElement && this.search.blur(), this.clearSearch(), this.selection.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus")
            }, focusSearch: function () {
                b(this.search)
            }, selectHighlighted: function (e) {
                var t = this.highlight(), n = this.results.find(".select2-highlighted"),
                    r = n.closest(".select2-result").data("select2-data");
                r && (this.highlight(t), this.onSelect(r, e))
            }, getPlaceholder: function () {
                return this.opts.element.attr("placeholder") || this.opts.element.attr("data-placeholder") || this.opts.element.data("placeholder") || this.opts.placeholder
            }, initContainerWidth: function () {
                function n() {
                    var n, r, i, s, o;
                    if (this.opts.width === "off") return null;
                    if (this.opts.width === "element") return this.opts.element.outerWidth(!1) === 0 ? "auto" : this.opts.element.outerWidth(!1) + "px";
                    if (this.opts.width === "copy" || this.opts.width === "resolve") {
                        n = this.opts.element.attr("style");
                        if (n !== t) {
                            r = n.split(";");
                            for (s = 0, o = r.length; s < o; s += 1) {
                                i = r[s].replace(/\s/g, "").match(/width:(([-+]?([0-9]*\.)?[0-9]+)(px|rem|em|ex|%|in|cm|mm|pt|pc))/);
                                if (i !== null && i.length >= 1) return i[1]
                            }
                        }
                        return this.opts.width === "resolve" ? (n = this.opts.element.css("width"), n.indexOf("%") > 0 ? n : this.opts.element.outerWidth(!1) === 0 ? "auto" : this.opts.element.outerWidth(!1) + "px") : null
                    }
                    return e.isFunction(this.opts.width) ? this.opts.width() : this.opts.width
                }

                var r = n.call(this);
                r !== null && this.container.css("width", r)
            }
        }), i = _(r, {
            createContainer: function () {
                var t = e(document.createElement("div")).attr({"class": "select2-container"}).html(["<a href='javascript:void(0)' onclick='return false;' class='select2-choice' tabindex='-1'>", "   <span></span><abbr class='select2-search-choice-close' style='display:none;'></abbr>", "   <div><b></b></div>", "</a>", "<input class='select2-focusser select2-offscreen' type='text'/>", "<div class='select2-drop' style='display:none'>", "   <div class='select2-search'>", "       <input type='text' autocomplete='off' class='select2-input'/>", "   </div>", "   <ul class='select2-results'>", "   </ul>", "</div>"].join(""));
                return t
            }, disable: function () {
                if (!this.enabled) return;
                this.parent.disable.apply(this, arguments), this.focusser.attr("disabled", "disabled")
            }, enable: function () {
                if (this.enabled) return;
                this.parent.enable.apply(this, arguments), this.focusser.removeAttr("disabled")
            }, opening: function () {
                this.parent.opening.apply(this, arguments), this.focusser.attr("disabled", "disabled"), this.opts.element.trigger(e.Event("open"))
            }, close: function () {
                if (!this.opened()) return;
                this.parent.close.apply(this, arguments), this.focusser.removeAttr("disabled"), b(this.focusser)
            }, focus: function () {
                this.opened() ? this.close() : (this.focusser.removeAttr("disabled"), this.focusser.focus())
            }, isFocused: function () {
                return this.container.hasClass("select2-container-active")
            }, cancel: function () {
                this.parent.cancel.apply(this, arguments), this.focusser.removeAttr("disabled"), this.focusser.focus()
            }, initContainer: function () {
                var e, t = this.container, r = this.dropdown, i = !1;
                this.showSearch(this.opts.minimumResultsForSearch >= 0), this.selection = e = t.find(".select2-choice"), this.focusser = t.find(".select2-focusser"), this.search.bind("keydown", this.bind(function (e) {
                    if (!this.enabled) return;
                    if (e.which === n.PAGE_UP || e.which === n.PAGE_DOWN) {
                        w(e);
                        return
                    }
                    switch (e.which) {
                        case n.UP:
                        case n.DOWN:
                            this.moveHighlight(e.which === n.UP ? -1 : 1), w(e);
                            return;
                        case n.TAB:
                        case n.ENTER:
                            this.selectHighlighted(), w(e);
                            return;
                        case n.ESC:
                            this.cancel(e), w(e);
                            return
                    }
                })), this.focusser.bind("keydown", this.bind(function (e) {
                    if (!this.enabled) return;
                    if (e.which === n.TAB || n.isControl(e) || n.isFunctionKey(e) || e.which === n.ESC) return;
                    if (this.opts.openOnEnter === !1 && e.which === n.ENTER) {
                        w(e);
                        return
                    }
                    if (e.which == n.DOWN || e.which == n.UP || e.which == n.ENTER && this.opts.openOnEnter) {
                        this.open(), w(e);
                        return
                    }
                    if (e.which == n.DELETE || e.which == n.BACKSPACE) {
                        this.opts.allowClear && this.clear(), w(e);
                        return
                    }
                })), d(this.focusser), this.focusser.bind("keyup-change input", this.bind(function (e) {
                    if (this.opened()) return;
                    this.open(), this.showSearchInput !== !1 && this.search.val(this.focusser.val()), this.focusser.val(""), w(e)
                })), e.delegate("abbr", "mousedown", this.bind(function (e) {
                    if (!this.enabled) return;
                    this.clear(), E(e), this.close(), this.selection.focus()
                })), e.bind("mousedown", this.bind(function (e) {
                    i = !0, this.opened() ? this.close() : this.enabled && this.open(), w(e), i = !1
                })), r.bind("mousedown", this.bind(function () {
                    this.search.focus()
                })), e.bind("focus", this.bind(function (e) {
                    w(e)
                })), this.focusser.bind("focus", this.bind(function () {
                    this.container.addClass("select2-container-active")
                })).bind("blur", this.bind(function () {
                    this.opened() || this.container.removeClass("select2-container-active")
                })), this.search.bind("focus", this.bind(function () {
                    this.container.addClass("select2-container-active")
                })), this.initContainerWidth(), this.setPlaceholder()
            }, clear: function () {
                var e = this.selection.data("select2-data");
                this.opts.element.val(""), this.selection.find("span").empty(), this.selection.removeData("select2-data"), this.setPlaceholder(), this.opts.element.trigger({
                    type: "removed",
                    val: this.id(e),
                    choice: e
                }), this.triggerChange({removed: e})
            }, initSelection: function () {
                var e;
                if (this.opts.element.val() === "" && this.opts.element.text() === "") this.close(), this.setPlaceholder(); else {
                    var n = this;
                    this.opts.initSelection.call(null, this.opts.element, function (e) {
                        e !== t && e !== null && (n.updateSelection(e), n.close(), n.setPlaceholder())
                    })
                }
            }, prepareOpts: function () {
                var t = this.parent.prepareOpts.apply(this, arguments);
                return t.element.get(0).tagName.toLowerCase() === "select" ? t.initSelection = function (t, n) {
                    var r = t.find(":selected");
                    e.isFunction(n) && n({id: r.attr("value"), text: r.text(), element: r})
                } : "data" in t && (t.initSelection = t.initSelection || function (n, r) {
                    var i = n.val();
                    t.query({
                        matcher: function (e, n, r) {
                            return c(i, t.id(r))
                        }, callback: e.isFunction(r) ? function (e) {
                            r(e.results.length ? e.results[0] : null)
                        } : e.noop
                    })
                }), t
            }, getPlaceholder: function () {
                return this.select && this.select.find("option").first().text() !== "" ? t : this.parent.getPlaceholder.apply(this, arguments)
            }, setPlaceholder: function () {
                var e = this.getPlaceholder();
                if (this.opts.element.val() === "" && e !== t) {
                    if (this.select && this.select.find("option:first").text() !== "") return;
                    this.selection.find("span").html(this.opts.escapeMarkup(e)), this.selection.addClass("select2-default"), this.selection.find("abbr").hide()
                }
            }, postprocessResults: function (e, t) {
                var n = 0, r = this, i = !0;
                this.findHighlightableChoices().each2(function (e, t) {
                    if (c(r.id(t.data("select2-data")), r.opts.element.val())) return n = e, !1
                }), this.highlight(n);
                if (t === !0) {
                    var s = this.opts.minimumResultsForSearch;
                    i = s < 0 ? !1 : O(e.results) >= s, this.showSearch(i)
                }
            }, showSearch: function (t) {
                this.showSearchInput = t, this.dropdown.find(".select2-search")[t ? "removeClass" : "addClass"]("select2-search-hidden"), e(this.dropdown, this.container)[t ? "addClass" : "removeClass"]("select2-with-searchbox")
            }, onSelect: function (e, t) {
                var n = this.opts.element.val();
                this.opts.element.val(this.id(e)), this.updateSelection(e), this.opts.element.trigger({
                    type: "selected",
                    val: this.id(e),
                    choice: e
                }), this.close(), (!t || !t.noFocus) && this.selection.focus(), c(n, this.id(e)) || this.triggerChange()
            }, updateSelection: function (e) {
                var n = this.selection.find("span"), r;
                this.selection.data("select2-data", e), n.empty(), r = this.opts.formatSelection(e, n), r !== t && n.append(this.opts.escapeMarkup(r)), this.selection.removeClass("select2-default"), this.opts.allowClear && this.getPlaceholder() !== t && this.selection.find("abbr").show()
            }, val: function () {
                var e, n = !1, r = null, i = this;
                if (arguments.length === 0) return this.opts.element.val();
                e = arguments[0], arguments.length > 1 && (n = arguments[1]);
                if (this.select) this.select.val(e).find(":selected").each2(function (e, t) {
                    return r = {id: t.attr("value"), text: t.text()}, !1
                }), this.updateSelection(r), this.setPlaceholder(), n && this.triggerChange(); else {
                    if (this.opts.initSelection === t) throw new Error("cannot call val() if initSelection() is not defined");
                    if (!e && e !== 0) {
                        this.clear(), n && this.triggerChange();
                        return
                    }
                    this.opts.element.val(e), this.opts.initSelection(this.opts.element, function (e) {
                        i.opts.element.val(e ? i.id(e) : ""), i.updateSelection(e), i.setPlaceholder(), n && i.triggerChange()
                    })
                }
            }, clearSearch: function () {
                this.search.val(""), this.focusser.val("")
            }, data: function (e) {
                var n;
                if (arguments.length === 0) return n = this.selection.data("select2-data"), n == t && (n = null), n;
                !e || e === "" ? this.clear() : (this.opts.element.val(e ? this.id(e) : ""), this.updateSelection(e))
            }
        }), s = _(r, {
            createContainer: function () {
                var t = e(document.createElement("div")).attr({"class": "select2-container select2-container-multi"}).html(["    <ul class='select2-choices'>", "  <li class='select2-search-field'>", "    <input type='text' autocomplete='off' class='select2-input'>", "  </li>", "</ul>", "<div class='select2-drop select2-drop-multi' style='display:none;'>", "   <ul class='select2-results'>", "   </ul>", "</div>"].join(""));
                return t
            }, prepareOpts: function () {
                var t = this.parent.prepareOpts.apply(this, arguments);
                return t.element.get(0).tagName.toLowerCase() === "select" ? t.initSelection = function (e, t) {
                    var n = [];
                    e.find(":selected").each2(function (e, t) {
                        n.push({id: t.attr("value"), text: t.text(), element: t[0]})
                    }), t(n)
                } : "data" in t && (t.initSelection = t.initSelection || function (n, r) {
                    var i = h(n.val(), t.separator);
                    t.query({
                        matcher: function (n, r, s) {
                            return e.grep(i, function (e) {
                                return c(e, t.id(s))
                            }).length
                        }, callback: e.isFunction(r) ? function (e) {
                            r(e.results)
                        } : e.noop
                    })
                }), t
            }, initContainer: function () {
                var t = ".select2-choices", r;
                this.searchContainer = this.container.find(".select2-search-field"), this.selection = r = this.container.find(t), this.search.bind("input paste", this.bind(function () {
                    if (!this.enabled) return;
                    this.opened() || this.open()
                })), this.search.bind("keydown", this.bind(function (e) {
                    if (!this.enabled) return;
                    if (e.which === n.BACKSPACE && this.search.val() === "") {
                        this.close();
                        var t, i = r.find(".select2-search-choice-focus");
                        if (i.length > 0) {
                            this.unselect(i.first()), this.search.width(10), w(e);
                            return
                        }
                        t = r.find(".select2-search-choice:not(.select2-locked)"), t.length > 0 && t.last().addClass("select2-search-choice-focus")
                    } else r.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus");
                    if (this.opened()) switch (e.which) {
                        case n.UP:
                        case n.DOWN:
                            this.moveHighlight(e.which === n.UP ? -1 : 1), w(e);
                            return;
                        case n.ENTER:
                        case n.TAB:
                            this.selectHighlighted(), w(e);
                            return;
                        case n.ESC:
                            this.cancel(e), w(e);
                            return
                    }
                    if (e.which === n.TAB || n.isControl(e) || n.isFunctionKey(e) || e.which === n.BACKSPACE || e.which === n.ESC) return;
                    if (e.which === n.ENTER) {
                        if (this.opts.openOnEnter === !1) return;
                        if (e.altKey || e.ctrlKey || e.shiftKey || e.metaKey) return
                    }
                    this.open(), (e.which === n.PAGE_UP || e.which === n.PAGE_DOWN) && w(e)
                })), this.search.bind("keyup", this.bind(this.resizeSearch)), this.search.bind("blur", this.bind(function (e) {
                    this.container.removeClass("select2-container-active"), this.search.removeClass("select2-focused"), this.opened() || this.clearSearch(), e.stopImmediatePropagation()
                })), this.container.delegate(t, "mousedown", this.bind(function (t) {
                    if (!this.enabled) return;
                    if (e(t.target).closest(".select2-search-choice").length > 0) return;
                    this.clearPlaceholder(), this.open(), this.focusSearch(), t.preventDefault()
                })), this.container.delegate(t, "focus", this.bind(function () {
                    if (!this.enabled) return;
                    this.container.addClass("select2-container-active"), this.dropdown.addClass("select2-drop-active"), this.clearPlaceholder()
                })), this.initContainerWidth(), this.clearSearch()
            }, enable: function () {
                if (this.enabled) return;
                this.parent.enable.apply(this, arguments), this.search.removeAttr("disabled")
            }, disable: function () {
                if (!this.enabled) return;
                this.parent.disable.apply(this, arguments), this.search.attr("disabled", !0)
            }, initSelection: function () {
                var e;
                this.opts.element.val() === "" && this.opts.element.text() === "" && (this.updateSelection([]), this.close(), this.clearSearch());
                if (this.select || this.opts.element.val() !== "") {
                    var n = this;
                    this.opts.initSelection.call(null, this.opts.element, function (e) {
                        e !== t && e !== null && (n.updateSelection(e), n.close(), n.clearSearch())
                    })
                }
            }, clearSearch: function () {
                var e = this.getPlaceholder();
                e !== t && this.getVal().length === 0 && this.search.hasClass("select2-focused") === !1 ? (this.search.val(e).addClass("select2-default"), this.resizeSearch()) : this.search.val("").width(10)
            }, clearPlaceholder: function () {
                this.search.hasClass("select2-default") && this.search.val("").removeClass("select2-default")
            }, opening: function () {
                this.parent.opening.apply(this, arguments), this.clearPlaceholder(), this.resizeSearch(), this.focusSearch(), this.opts.element.trigger(e.Event("open"))
            }, close: function () {
                if (!this.opened()) return;
                this.parent.close.apply(this, arguments)
            }, focus: function () {
                this.close(), this.search.focus(), this.opts.element.triggerHandler("focus")
            }, isFocused: function () {
                return this.search.hasClass("select2-focused")
            }, updateSelection: function (t) {
                var n = [], r = [], i = this;
                e(t).each(function () {
                    l(i.id(this), n) < 0 && (n.push(i.id(this)), r.push(this))
                }), t = r, this.selection.find(".select2-search-choice").remove(), e(t).each(function () {
                    i.addSelectedChoice(this)
                }), i.postprocessResults()
            }, tokenize: function () {
                var e = this.search.val();
                e = this.opts.tokenizer(e, this.data(), this.bind(this.onSelect), this.opts), e != null && e != t && (this.search.val(e), e.length > 0 && this.open())
            }, onSelect: function (e, t) {
                this.addSelectedChoice(e), this.opts.element.trigger({
                    type: "selected",
                    val: this.id(e),
                    choice: e
                }), (this.select || !this.opts.closeOnSelect) && this.postprocessResults(), this.opts.closeOnSelect ? (this.close(), this.search.width(10)) : this.countSelectableResults() > 0 ? (this.search.width(10), this.resizeSearch(), this.val().length >= this.getMaximumSelectionSize() && this.updateResults(!0), this.positionDropdown()) : (this.close(), this.search.width(10)), this.triggerChange({added: e}), (!t || !t.noFocus) && this.focusSearch()
            }, cancel: function () {
                this.close(), this.focusSearch()
            }, addSelectedChoice: function (n) {
                var r = !n.locked,
                    i = e("<li class='select2-search-choice'>    <div></div>    <a href='#' onclick='return false;' class='select2-search-choice-close' tabindex='-1'></a></li>"),
                    s = e("<li class='select2-search-choice select2-locked'><div></div></li>"), o = r ? i : s,
                    u = this.id(n), a = this.getVal(), f;
                f = this.opts.formatSelection(n, o.find("div")), f != t && o.find("div").replaceWith("<div>" + this.opts.escapeMarkup(f) + "</div>"), r && o.find(".select2-search-choice-close").bind("mousedown", w).bind("click dblclick", this.bind(function (t) {
                    if (!this.enabled) return;
                    e(t.target).closest(".select2-search-choice").fadeOut("fast", this.bind(function () {
                        this.unselect(e(t.target)), this.selection.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus"), this.close(), this.focusSearch()
                    })).dequeue(), w(t)
                })).bind("focus", this.bind(function () {
                    if (!this.enabled) return;
                    this.container.addClass("select2-container-active"), this.dropdown.addClass("select2-drop-active")
                })), o.data("select2-data", n), o.insertBefore(this.searchContainer), a.push(u), this.setVal(a)
            }, unselect: function (e) {
                var t = this.getVal(), n, r;
                e = e.closest(".select2-search-choice");
                if (e.length === 0) throw "Invalid argument: " + e + ". Must be .select2-search-choice";
                n = e.data("select2-data");
                if (!n) return;
                r = l(this.id(n).toString(), t), r >= 0 && (t.splice(r, 1), this.setVal(t), this.select && this.postprocessResults()), e.remove(), this.opts.element.trigger({
                    type: "removed",
                    val: this.id(n),
                    choice: n
                }), this.triggerChange({removed: n})
            }, postprocessResults: function () {
                var e = this.getVal(), t = this.results.find(".select2-result"),
                    n = this.results.find(".select2-result-with-children"), r = this;
                t.each2(function (t, n) {
                    var i = r.id(n.data("select2-data"));
                    l(i, e) >= 0 && (n.addClass("select2-selected"), n.find(".select2-result-selectable").addClass("select2-selected"))
                }), n.each2(function (e, t) {
                    !t.is(".select2-result-selectable") && t.find(".select2-result-selectable:not(.select2-selected)").length === 0 && t.addClass("select2-selected")
                }), this.highlight() == -1 && r.highlight(0)
            }, resizeSearch: function () {
                var e, t, n, r, i, s = p(this.search);
                e = S(this.search) + 10, t = this.search.offset().left, n = this.selection.width(), r = this.selection.offset().left, i = n - (t - r) - s, i < e && (i = n - s), i < 40 && (i = n - s), i <= 0 && (i = e), this.search.width(i)
            }, getVal: function () {
                var e;
                return this.select ? (e = this.select.val(), e === null ? [] : e) : (e = this.opts.element.val(), h(e, this.opts.separator))
            }, setVal: function (t) {
                var n;
                this.select ? this.select.val(t) : (n = [], e(t).each(function () {
                    l(this, n) < 0 && n.push(this)
                }), this.opts.element.val(n.length === 0 ? "" : n.join(this.opts.separator)))
            }, val: function () {
                var n, r = !1, i = [], s = this;
                if (arguments.length === 0) return this.getVal();
                n = arguments[0], arguments.length > 1 && (r = arguments[1]);
                if (!n && n !== 0) {
                    this.opts.element.val(""), this.updateSelection([]), this.clearSearch(), r && this.triggerChange();
                    return
                }
                this.setVal(n);
                if (this.select) this.opts.initSelection(this.select, this.bind(this.updateSelection)), r && this.triggerChange(); else {
                    if (this.opts.initSelection === t) throw new Error("val() cannot be called if initSelection() is not defined");
                    this.opts.initSelection(this.opts.element, function (t) {
                        var n = e(t).map(s.id);
                        s.setVal(n), s.updateSelection(t), s.clearSearch(), r && s.triggerChange()
                    })
                }
                this.clearSearch()
            }, onSortStart: function () {
                if (this.select) throw new Error("Sorting of elements is not supported when attached to <select>. Attach to <input type='hidden'/> instead.");
                this.search.width(0), this.searchContainer.hide()
            }, onSortEnd: function () {
                var t = [], n = this;
                this.searchContainer.show(), this.searchContainer.appendTo(this.searchContainer.parent()), this.resizeSearch(), this.selection.find(".select2-search-choice").each(function () {
                    t.push(n.opts.id(e(this).data("select2-data")))
                }), this.setVal(t), this.triggerChange()
            }, data: function (t) {
                var n = this, r;
                if (arguments.length === 0) return this.selection.find(".select2-search-choice").map(function () {
                    return e(this).data("select2-data")
                }).get();
                t || (t = []), r = e.map(t, function (e) {
                    return n.opts.id(e)
                }), this.setVal(r), this.updateSelection(t), this.clearSearch()
            }
        }), e.fn.select2 = function () {
            var n = Array.prototype.slice.call(arguments, 0), r, o, u, a,
                f = ["val", "destroy", "opened", "open", "close", "focus", "isFocused", "container", "onSortStart", "onSortEnd", "enable", "disable", "positionDropdown", "data"];
            return this.each(function () {
                if (n.length === 0 || typeof n[0] == "object") r = n.length === 0 ? {} : e.extend({}, n[0]), r.element = e(this), r.element.get(0).tagName.toLowerCase() === "select" ? a = r.element.attr("multiple") : (a = r.multiple || !1, "tags" in r && (r.multiple = a = !0)), o = a ? new s : new i, o.init(r); else {
                    if (typeof n[0] != "string") throw "Invalid arguments to select2 plugin: " + n;
                    if (l(n[0], f) < 0) throw "Unknown method: " + n[0];
                    u = t, o = e(this).data("select2");
                    if (o === t) return;
                    n[0] === "container" ? u = o.container : u = o[n[0]].apply(o, n.slice(1));
                    if (u !== t) return !1
                }
            }), u === t ? this : u
        }, e.fn.select2.defaults = {
            width: "copy",
            loadMorePadding: 0,
            closeOnSelect: !0,
            openOnEnter: !0,
            containerCss: {},
            dropdownCss: {},
            containerCssClass: "",
            dropdownCssClass: "",
            formatResult: function (e, t, n, r) {
                var i = [];
                return T(e.text, n.term, i, r), i.join("")
            },
            formatSelection: function (e, n) {
                return e ? e.text : t
            },
            sortResults: function (e, t, n) {
                return e
            },
            formatResultCssClass: function (e) {
                return t
            },
            formatNoMatches: function () {
                return "No matches found"
            },
            formatInputTooShort: function (e, t) {
                var n = t - e.length;
                return "Please enter " + n + " more character" + (n == 1 ? "" : "s")
            },
            formatInputTooLong: function (e, t) {
                var n = e.length - t;
                return "Please enter " + n + " less character" + (n == 1 ? "" : "s")
            },
            formatSelectionTooBig: function (e) {
                return "You can only select " + e + " item" + (e == 1 ? "" : "s")
            },
            formatLoadMore: function (e) {
                return "Loading more results..."
            },
            formatSearching: function () {
                return "Searching..."
            },
            minimumResultsForSearch: 0,
            minimumInputLength: 0,
            maximumInputLength: null,
            maximumSelectionSize: 0,
            id: function (e) {
                return e.id
            },
            matcher: function (e, t) {
                return t.toUpperCase().indexOf(e.toUpperCase()) >= 0
            },
            separator: ",",
            tokenSeparators: [],
            tokenizer: M,
            escapeMarkup: function (e) {
                var t = {
                    "\\": "&#92;",
                    "&": "&amp;",
                    "<": "&lt;",
                    ">": "&gt;",
                    '"': "&quot;",
                    "'": "&apos;",
                    "/": "&#47;"
                };
                return String(e).replace(/[&<>"'/\\]/g, function (e) {
                    return t[e[0]]
                })
            },
            blurOnChange: !1,
            selectOnBlur: !1,
            adaptContainerCssClass: function (e) {
                return e
            },
            adaptDropdownCssClass: function (e) {
                return null
            }
        }
    }(e)
}), timely.define("scripts/calendar_feeds/ics/ics_event_handlers", ["jquery_timely", "scripts/calendar_feeds/ics/ics_ajax_handlers", "libs/utils", "ai1ec_config", "external_libs/select2"], function ($, t, n, r) {
    var i = n.get_ajax_url(), s = function () {
        var s = $(this), o = $("#osec_feed_url"), u = o.val().replace("webcal://", "http://"),
            a = $("#osec_feed_id").val(), f = !1, l;
        $(".ai1ec-feed-url, #osec_feed_url").css("border-color", "#DFDFDF"), $("#ai1ec-feed-error").remove(), a || $(".ai1ec-feed-url").each(function () {
            this.value === u && ($(this).css("border-color", "#FF0000"), f = !0, l = r.duplicate_feed_message)
        }), n.isUrl(u) || (f = !0, l = r.invalid_url_message);
        if (f) o.addClass("input-error").focus().before(n.make_alert(l, "error")); else {
            s.button("loading");
            var c = $("#osec_comments_enabled").is(":checked") ? 1 : 0,
                h = $("#osec_map_display_enabled").is(":checked") ? 1 : 0,
                p = $("#osec_add_tag_categories").is(":checked") ? 1 : 0,
                d = $("#osec_keep_old_events").is(":checked") ? 1 : 0,
                v = $("#osec_feed_import_timezone").is(":checked") ? 1 : 0, m = {
                    action: "osec_add_ics",
                    nonce: r.calendar_feeds_nonce,
                    feed_url: u,
                    feed_category: $("#osec_feed_category").val(),
                    feed_tags: $("#osec_feed_tags").val(),
                    comments_enabled: c,
                    map_display_enabled: h,
                    keep_tags_categories: p,
                    keep_old_events: d,
                    feed_import_timezone: v
                };
            $(".ai1ec-feed-field").each(function () {
                var t = $(this).val();
                "checkbox" === $(this).attr("type") && !$(this).prop("checked") && (t = 0), m[$(this).attr("name")] = t
            }), a && (m.feed_id = a), $.post(i, m, t.handle_add_new_ics, "json")
        }
    }, o = function () {
        var t = $(this),
            n = t.closest(".ai1ec-feed-container"),
            r = $("#ai1ec-feeds-after"),
            i = $("#osec_ics_add_new, #osec_add_new_ics > i"),
            s = $("#osec_ics_update"),
            o = ($(".ai1ec-feed-category", n).data("ids") || "").toString(),
            u = ($(".ai1ec-feed-tags", n).data("ids") || "").toString(),
            a = $(".ai1ec-cfg-feed", n), f = [];
        a.each(function () {
            var t = $(this);
            f[t.attr("data-group_name")] = t.attr("data-terms")
        }),
            $("#osec_feed_url").val($(".ai1ec-feed-url", n).val()).prop("readonly", !0),
            $("#osec_comments_enabled").prop("checked", $(".ai1ec-feed-comments-enabled", n).data("state")),
            $("#osec_map_display_enabled").prop("checked", $(".ai1ec-feed-map-display-enabled", n).data("state")),
            $("#osec_add_tag_categories").prop("checked", $(".ai1ec-feed-keep-tags-categories", n).data("state")),
            $("#osec_keep_old_events").prop("checked", $(".ai1ec-feed-keep-old-events", n).data("state")),
            $("#osec_feed_import_timezone").prop("checked", $(".ai1ec-feed-import-timezone", n).data("state")),
            i.addClass("ai1ec-hidden"), s.removeClass("ai1ec-hidden"),
            $('<input type="hidden" id="osec_feed_id" name="osec_feed_id">').val($(".ai1ec_feed_id", n).val()).appendTo(r),
            $("#osec_feed_category").select2("val", o.split(",")),
            $("#osec_feed_tags").select2("val", u.split(","));
        for (var l in f) $('[id="ai1ec_feed_cfg_' + l.toLowerCase() + '"]').select2("val", f[l].split(",") || f[l]);
        var c = $(".ai1ec-feed-content", n);
        c.hide(), $("#osec_cancel_ics").show(), $("#ai1ec-feeds-after").removeClass("ai1ec-well ai1ec-well-sm").insertAfter(c), $("#ics .ai1ec-alert").remove()
    }, u = function (n) {
        return $("#ai1ec-feeds-after").addClass("ai1ec-well ai1ec-well-sm").insertAfter("#ics .ai1ec-form-horizontal"), $(".ai1ec-feed-content").show(), t.reset_form(), $("#osec_cancel_ics").hide(), !1
    }, a = function (n) {
        n.preventDefault();
        var r = $(this).hasClass("remove") ? !0 : !1, s = $($(this).data("el")), o = s.closest(".ai1ec-feed-container"),
            u = $(".ai1ec_feed_id", o).val(), a = {
                action: "osec_delete_ics",
                feed_id: u,
                remove_events: r,
                nonce: timely.requirejs.config('ai1ec_config').calendar_feeds_nonce
            };
        s.button("loading"), $("#osec-ics-modal").modal("hide"), $.post(i, a, t.handle_delete_ics, "json")
    }, f = function () {
        $("#osec-ics-modal .ai1ec-btn").data("el", this), $("#osec-ics-modal").modal({backdrop: "static"})
    }, l = function () {
        var n = $(this), r = n.closest(".ai1ec-feed-container"), s = $(".ai1ec_feed_id", r).val(),
            o = {action: "osec_update_ics", feed_id: s, nonce: timely.requirejs.config('ai1ec_config').calendar_feeds_nonce};
        n.button("loading"), $.post(i, o, t.handle_update_ics, "json")
    }, c = function () {
        var t = $(this).val(), n = /.google./i;
        n.test(t) && $("#osec_feed_import_timezone").prop("checked", !0)
    };
    return {
        add_new_feed: s,
        submit_delete_modal: a,
        open_delete_modal: f,
        update_feed: l,
        edit_feed: o,
        edit_cancel: u,
        feed_url_change: c
    }
}), timely.define("libs/select2_multiselect_helper", ["jquery_timely", "external_libs/select2"], function (e) {
    var t = function (t) {
        var n = e(t.element), r = n.data("color"), i = n.data("description"), s = "";
        return typeof r != "undefined" && r !== "" && (s += '<span class="ai1ec-color-swatch" style="background: ' + n.data("color") + '"></span> '), s += t.text, s = '<span title="' + i + '">' + s + "</span>", s
    }, n = function (t) {
        var n = e(t.element), r = n.data("color"), i = n.data("description"), s = "";
        return typeof r != "undefined" && r !== "" ? s += '<span class="ai1ec-color-swatch" style="background: ' + n.data("color") + '"></span> ' : s += '<span class="ai1ec-color-swatch-empty"></span> ', s += t.text, s = '<span title="' + i + '">' + s + "</span>", s
    }, r = function (r) {
        typeof r == "undefined" && (r = e(document)), e(".ai1ec-select2-multiselect-selector", r).select2({
            allowClear: !0,
            formatResult: n,
            formatSelection: t,
            escapeMarkup: function (e) {
                return e
            }
        })
    }, i = function (t) {
        e(".ai1ec-select2-multiselect-selector.select2-container", t).each(function () {
            e(this).data("select2").resizeSearch()
        })
    };
    return {init: r, refresh: i}
}), timely.define("libs/tags_select", ["jquery_timely", "external_libs/select2"], function (e) {
    var t = function (t) {
        typeof t == "undefined" && (t = e(document)), e(".ai1ec-tags-selector", t).each(function () {
            var t = e(this);
            t.select2({tags: t.data("ai1ecTags"), tokenSeparators: [","]})
        })
    }, n = function (t) {
        e(".ai1ec-tags-selector.select2-container", t).each(function () {
            e(this).data("select2").resizeSearch()
        })
    };
    return {init: t, refresh: n}
}), timely.define("external_libs/jquery_cookie", ["jquery_timely"], function (e) {
    function n(e) {
        return u.raw ? e : encodeURIComponent(e)
    }

    function r(e) {
        return u.raw ? e : decodeURIComponent(e)
    }

    function i(e) {
        return n(u.json ? JSON.stringify(e) : String(e))
    }

    function s(e) {
        e.indexOf('"') === 0 && (e = e.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, "\\"));
        try {
            return e = decodeURIComponent(e.replace(t, " ")), u.json ? JSON.parse(e) : e
        } catch (n) {
        }
    }

    function o(t, n) {
        var r = u.raw ? t : s(t);
        return e.isFunction(n) ? n(r) : r
    }

    var t = /\+/g, u = e.cookie = function (t, s, a) {
        if (s !== undefined && !e.isFunction(s)) {
            a = e.extend({}, u.defaults, a);
            if (typeof a.expires == "number") {
                var f = a.expires, l = a.expires = new Date;
                l.setTime(+l + f * 864e5)
            }
            return document.cookie = [n(t), "=", i(s), a.expires ? "; expires=" + a.expires.toUTCString() : "", a.path ? "; path=" + a.path : "", a.domain ? "; domain=" + a.domain : "", a.secure ? "; secure" : ""].join("")
        }
        var c = t ? undefined : {}, h = document.cookie ? document.cookie.split("; ") : [];
        for (var p = 0, d = h.length; p < d; p++) {
            var v = h[p].split("="), m = r(v.shift()), g = v.join("=");
            if (t && t === m) {
                c = o(g, s);
                break
            }
            !t && (g = o(g)) !== undefined && (c[m] = g)
        }
        return c
    };
    u.defaults = {}, e.removeCookie = function (t, n) {
        return e.cookie(t) === undefined ? !1 : (e.cookie(t, "", e.extend({}, n, {expires: -1})), !e.cookie(t))
    }
}), timely.define("external_libs/bootstrap/alert", ["jquery_timely"], function (e) {
    var t = '[data-dismiss="ai1ec-alert"]', n = function (n) {
        e(n).on("click", t, this.close)
    };
    n.prototype.close = function (t) {
        function s() {
            i.trigger("closed.bs.alert").remove()
        }

        var n = e(this), r = n.attr("data-target");
        r || (r = n.attr("href"), r = r && r.replace(/.*(?=#[^\s]*$)/, ""));
        var i = e(r);
        t && t.preventDefault(), i.length || (i = n.hasClass("ai1ec-alert") ? n : n.parent()), i.trigger(t = e.Event("close.bs.alert"));
        if (t.isDefaultPrevented()) return;
        i.removeClass("ai1ec-in"), e.support.transition && i.hasClass("ai1ec-fade") ? i.one(e.support.transition.end, s).emulateTransitionEnd(150) : s()
    };
    var r = e.fn.alert;
    e.fn.alert = function (t) {
        return this.each(function () {
            var r = e(this), i = r.data("bs.alert");
            i || r.data("bs.alert", i = new n(this)), typeof t == "string" && i[t].call(r)
        })
    }, e.fn.alert.Constructor = n, e.fn.alert.noConflict = function () {
        return e.fn.alert = r, this
    }, e(document).on("click.bs.alert.data-api", t, n.prototype.close)
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
}), timely.define("external_libs/bootstrap/button", ["jquery_timely"], function (e) {
    var t = function (n, r) {
        this.$element = e(n), this.options = e.extend({}, t.DEFAULTS, r)
    };
    t.DEFAULTS = {loadingText: "loading..."}, t.prototype.setState = function (e) {
        var t = "disabled", n = this.$element, r = n.is("input") ? "val" : "html", i = n.data();
        e += "Text", i.resetText || n.data("resetText", n[r]()), n[r](i[e] || this.options[e]), setTimeout(function () {
            e == "loadingText" ? n.addClass("ai1ec-" + t).attr(t, t) : n.removeClass("ai1ec-" + t).removeAttr(t)
        }, 0)
    }, t.prototype.toggle = function () {
        var e = this.$element.closest('[data-toggle="ai1ec-buttons"]'), t = !0;
        if (e.length) {
            var n = this.$element.find("input");
            n.prop("type") === "radio" && (n.prop("checked") && this.$element.hasClass("ai1ec-active") ? t = !1 : e.find(".ai1ec-active").removeClass("ai1ec-active")), t && n.prop("checked", !this.$element.hasClass("ai1ec-active")).trigger("change")
        }
        t && this.$element.toggleClass("ai1ec-active")
    };
    var n = e.fn.button;
    e.fn.button = function (n) {
        return this.each(function () {
            var r = e(this), i = r.data("bs.button"), s = typeof n == "object" && n;
            i || r.data("bs.button", i = new t(this, s)), n == "toggle" ? i.toggle() : n && i.setState(n)
        })
    }, e.fn.button.Constructor = t, e.fn.button.noConflict = function () {
        return e.fn.button = n, this
    }, e(document).on("click.bs.button.data-api", "[data-toggle^=ai1ec-button]", function (t) {
        var n = e(t.target);
        n.hasClass("ai1ec-btn") || (n = n.closest(".ai1ec-btn")), n.button("toggle"), t.preventDefault()
    })
}), timely.define("external_libs/bootstrap/collapse", ["jquery_timely"], function (e) {
    var t = function (n, r) {
        this.$element = e(n), this.options = e.extend({}, t.DEFAULTS, r), this.transitioning = null, this.options.parent && (this.$parent = e(this.options.parent)), this.options.toggle && this.toggle()
    };
    t.DEFAULTS = {toggle: !0}, t.prototype.dimension = function () {
        var e = this.$element.hasClass("ai1ec-width");
        return e ? "width" : "height"
    }, t.prototype.show = function () {
        if (this.transitioning || this.$element.hasClass("ai1ec-in")) return;
        var t = e.Event("show.bs.collapse");
        this.$element.trigger(t);
        if (t.isDefaultPrevented()) return;
        var n = this.$parent && this.$parent.find("> .ai1ec-panel > .ai1ec-in");
        if (n && n.length) {
            var r = n.data("bs.collapse");
            if (r && r.transitioning) return;
            n.collapse("hide"), r || n.data("bs.collapse", null)
        }
        var i = this.dimension();
        this.$element.removeClass("ai1ec-collapse").addClass("ai1ec-collapsing")[i](0), this.transitioning = 1;
        var s = function () {
            this.$element.removeClass("ai1ec-collapsing").addClass("ai1ec-in")[i]("auto"), this.transitioning = 0, this.$element.trigger("shown.bs.collapse")
        };
        if (!e.support.transition) return s.call(this);
        var o = e.camelCase(["scroll", i].join("-"));
        this.$element.one(e.support.transition.end, e.proxy(s, this)).emulateTransitionEnd(350)[i](this.$element[0][o])
    }, t.prototype.hide = function () {
        if (this.transitioning || !this.$element.hasClass("ai1ec-in")) return;
        var t = e.Event("hide.bs.collapse");
        this.$element.trigger(t);
        if (t.isDefaultPrevented()) return;
        var n = this.dimension();
        this.$element[n](this.$element[n]())[0].offsetHeight, this.$element.addClass("ai1ec-collapsing").removeClass("ai1ec-collapse").removeClass("ai1ec-in"), this.transitioning = 1;
        var r = function () {
            this.transitioning = 0, this.$element.trigger("hidden.bs.collapse").removeClass("ai1ec-collapsing").addClass("ai1ec-collapse")
        };
        if (!e.support.transition) return r.call(this);
        this.$element[n](0).one(e.support.transition.end, e.proxy(r, this)).emulateTransitionEnd(350)
    }, t.prototype.toggle = function () {
        this[this.$element.hasClass("ai1ec-in") ? "hide" : "show"]()
    };
    var n = e.fn.collapse;
    e.fn.collapse = function (n) {
        return this.each(function () {
            var r = e(this), i = r.data("bs.collapse"),
                s = e.extend({}, t.DEFAULTS, r.data(), typeof n == "object" && n);
            i || r.data("bs.collapse", i = new t(this, s)), typeof n == "string" && i[n]()
        })
    }, e.fn.collapse.Constructor = t, e.fn.collapse.noConflict = function () {
        return e.fn.collapse = n, this
    }, e(document).on("click.bs.collapse.data-api", "[data-toggle=ai1ec-collapse]", function (t) {
        var n = e(this), r,
            i = n.attr("data-target") || t.preventDefault() || (r = n.attr("href")) && r.replace(/.*(?=#[^\s]+$)/, ""),
            s = e(i), o = s.data("bs.collapse"), u = o ? "toggle" : n.data(), a = n.attr("data-parent"), f = a && e(a);
        if (!o || !o.transitioning) f && f.find('[data-toggle=ai1ec-collapse][data-parent="' + a + '"]').not(n).addClass("ai1ec-collapsed"), n[s.hasClass("ai1ec-in") ? "addClass" : "removeClass"]("ai1ec-collapsed");
        s.collapse(u)
    })
}), timely.define("scripts/calendar_feeds", ["jquery_timely", "domReady", "scripts/calendar_feeds/ics/ics_event_handlers", "libs/select2_multiselect_helper", "libs/tags_select", "libs/utils", "external_libs/jquery_cookie", "external_libs/bootstrap/tab", "external_libs/bootstrap/alert", "external_libs/bootstrap/modal", "external_libs/bootstrap/button", "external_libs/bootstrap/collapse"], function (e, t, n, r, i, s) {
    var o = function () {
        var t = e(this.hash);
        r.refresh(t), i.refresh(t)
    }, u = function (t) {
        var n = e(this).attr("href");
        e.cookie("feeds_active_tab", n)
    }, a = function () {
        var t = e("#ai1ec-feeds-after"), s = e(".ai1ec_submit_wrapper"), a = e(".ai1ec_file_upload_tags_categories");
        r.init(t), i.init(t), r.init(s), i.init(s), r.init(a), i.init(a), e("ul.ai1ec-nav a").on("click", u), e("ul.ai1ec-nav a").on("shown", o), e('select[name="cron_freq"]').on("change", function () {
            e.ajax({url: ajaxurl, type: "POST", data: {action: "ai1ec_feeds_page_post", cron_freq: this.value}})
        }), e("#osec-ics-modal").on("click", ".remove, .keep", n.submit_delete_modal), e(document).on("click", "#osec_add_new_ics", n.add_new_feed).on("click", ".osec_delete_ics", n.open_delete_modal).on("click", ".osec_update_ics", n.update_feed).on("click", ".ai1ec_edit_ics", n.edit_feed).on("click", "#osec_cancel_ics", n.edit_cancel).on("click", ".ai1ec-panel-heading > a", n.edit_cancel).on("blur", "#osec_feed_url", n.feed_url_change)
    }, f = function () {
        t(function () {
            s.activate_saved_tab_on_page_load(e.cookie("feeds_active_tab")), a()
        })
    };
    return {start: f}
}), timely.require(["scripts/calendar_feeds"], function (e) {
    e.start()
}), timely.define("pages/calendar_feeds", function () {
});
