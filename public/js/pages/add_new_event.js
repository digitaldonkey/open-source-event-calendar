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

/* =========================================================
 * bootstrap-datepicker.js
 * Repo: https://github.com/eternicode/bootstrap-datepicker/
 * Demo: http://eternicode.github.io/bootstrap-datepicker/
 * Docs: http://bootstrap-datepicker.readthedocs.org/
 * Forked from http://www.eyecon.ro/bootstrap-datepicker
 * =========================================================
 * Started by Stefan Petre; improvements by Andrew Rowls + contributors
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
 * ========================================================= */

/* ========================================================================
 * Bootstrap: transition.js v3.0.3
 * http://getbootstrap.com/javascript/#transitions
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

/*
Copyright 2012 Igor Vaynberg

Version: 3.3.1 Timestamp: Wed Feb 20 09:57:22 PST 2013

This software is licensed under the Apache License, Version 2.0 (the "Apache License") or the GNU
General Public License version 2 (the "GPL License"). You may choose either license to govern your
use of this software only upon the condition that you accept all the terms of either the Apache
License or the GPL License.

You may obtain a copy of the Apache License and the GPL License at:

    http://www.apache.org/licenses/LICENSE-2.0
    http://www.gnu.org/licenses/gpl-2.0.html

Unless required by applicable law or agreed to in writing, software distributed under the
Apache License or the GPL Licesnse is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
CONDITIONS OF ANY KIND, either express or implied. See the Apache License and the GPL License for
the specific language governing permissions and limitations under the Apache License and the GPL License.
*/

timely.define("domReady", [], function () {
    function u(e) {
        var t;
        for (t = 0; t < e.length; t++) e[t](n);
    }

    function a() {
        var e = r;
        t && e.length && ((r = []), u(e));
    }

    function f() {
        t || ((t = !0), o && clearInterval(o), a());
    }

    function c(e) {
        return t ? e(n) : r.push(e), c;
    }

    var e = typeof window != "undefined" && window.document,
        t = !e,
        n = e ? document : null,
        r = [],
        i,
        s,
        o;
    if (e) {
        if (document.addEventListener) document.addEventListener("DOMContentLoaded", f, !1), window.addEventListener("load", f, !1);
        else if (window.attachEvent) {
            window.attachEvent("onload", f), (s = document.createElement("div"));
            try {
                i = window.frameElement === null;
            } catch (l) {
            }
            s.doScroll &&
            i &&
            window.external &&
            (o = setInterval(function () {
                try {
                    s.doScroll(), f();
                } catch (e) {
                }
            }, 30));
        }
        (document.readyState === "complete" || document.readyState === "interactive") && f();
    }
    return (
        (c.version = "2.0.0"),
            (c.load = function (e, t, n, r) {
                r.isBuild ? n(null) : c(n);
            }),
            c
    );
}),
    timely.define("external_libs/bootstrap/tab", ["jquery_timely"], function (e) {
        var t = function (t) {
            this.element = e(t);
        };
        (t.prototype.show = function () {
            var t = this.element,
                n = t.closest("ul:not(.ai1ec-dropdown-menu)"),
                r = t.data("target");
            r || ((r = t.attr("href")), (r = r && r.replace(/.*(?=#[^\s]*$)/, "")));
            if (t.parent("li").hasClass("ai1ec-active")) return;
            var i = n.find(".ai1ec-active:last a")[0],
                s = e.Event("show.bs.tab", {relatedTarget: i});
            t.trigger(s);
            if (s.isDefaultPrevented()) return;
            var o = e(r);
            this.activate(t.parent("li"), n),
                this.activate(o, o.parent(), function () {
                    t.trigger({type: "shown.bs.tab", relatedTarget: i});
                });
        }),
            (t.prototype.activate = function (t, n, r) {
                function o() {
                    i.removeClass("ai1ec-active").find("> .ai1ec-dropdown-menu > .ai1ec-active").removeClass("ai1ec-active"),
                        t.addClass("ai1ec-active"),
                        s ? (t[0].offsetWidth, t.addClass("ai1ec-in")) : t.removeClass("ai1ec-fade"),
                    t.parent(".ai1ec-dropdown-menu") && t.closest("li.ai1ec-dropdown").addClass("ai1ec-active"),
                    r && r();
                }

                var i = n.find("> .ai1ec-active"),
                    s = r && e.support.transition && i.hasClass("ai1ec-fade");
                s ? i.one(e.support.transition.end, o).emulateTransitionEnd(150) : o(), i.removeClass("ai1ec-in");
            });
        var n = e.fn.tab;
        (e.fn.tab = function (n) {
            return this.each(function () {
                var r = e(this),
                    i = r.data("bs.tab");
                i || r.data("bs.tab", (i = new t(this))), typeof n == "string" && i[n]();
            });
        }),
            (e.fn.tab.Constructor = t),
            (e.fn.tab.noConflict = function () {
                return (e.fn.tab = n), this;
            }),
            e(document).on("click.bs.tab.data-api", '[data-toggle="ai1ec-tab"], [data-toggle="ai1ec-pill"]', function (t) {
                t.preventDefault(), e(this).tab("show");
            });
    }),
    timely.define("libs/utils", ["jquery_timely", "external_libs/bootstrap/tab"], function (e) {
        var t = (function () {
            return {
                is_float: function (e) {
                    return !isNaN(parseFloat(e));
                },
                is_valid_coordinate: function (e, t) {
                    var n = t ? 90 : 180;
                    return this.is_float(e) && Math.abs(e) < n;
                },
                convert_comma_to_dot: function (e) {
                    return e.replace(",", ".");
                },
                field_has_value: function (t) {
                    var n = "#" + t,
                        r = e(n),
                        i = !1;
                    return r.length === 1 && (i = e.trim(r.val()) !== ""), i;
                },
                make_alert: function (t, n, r) {
                    var i = "";
                    switch (n) {
                        case "error":
                            i = "ai1ec-alert ai1ec-alert-danger";
                            break;
                        case "success":
                            i = "ai1ec-alert ai1ec-alert-success";
                            break;
                        default:
                            i = "ai1ec-alert ai1ec-alert-info";
                    }
                    var s = e("<div />", {class: i, html: t});
                    if (!r) {
                        var o = e("<button>", {
                            type: "button",
                            class: "ai1ec-close",
                            "data-dismiss": "ai1ec-alert",
                            text: "×"
                        });
                        s.prepend(o);
                    }
                    return s;
                },
                get_ajax_url: function () {
                    return typeof window.ajaxurl == "undefined" ? "http://localhost/wordpress/wp-admin/admin-ajax.php" : window.ajaxurl;
                },
                isUrl: function (e) {
                    var t = /(http|https|webcal):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;
                    return t.test(e);
                },
                isValidEmail: function (e) {
                    var t = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                    return t.test(e);
                },
                activate_saved_tab_on_page_load: function (t) {
                    null === t || undefined === t ? e("ul.ai1ec-nav a:first").tab("show") : e("ul.ai1ec-nav a[href=" + t + "]").tab("show");
                },
                add_query_arg: function (e, t) {
                    if ("string" != typeof e) return !1;
                    var n = e.indexOf("?") === -1 ? "?" : "&";
                    return -1 !== e.indexOf(n + t[0] + "=") ? e : e + n + t[0] + "=" + t[1];
                },
                create_ai1ec_to_send: function (t) {
                    var n = e(t),
                        r = [],
                        i = ["action", "cat_ids", "auth_ids", "tag_ids", "exact_date", "display_filters", "no_navigation", "events_limit"];
                    return (
                        n.each(function () {
                            e.each(this.attributes, function () {
                                this.specified && this.value && this.name.match(/^data-/) && (-1 < e.inArray(this.name.replace(/^data\-/, ""), i) || this.name.match(/_ids$/)) && r.push(this.name.replace(/^data\-/, "") + "~" + this.value);
                            });
                        }),
                            r.join("|")
                    );
                },
                init_autoselect: function () {
                    e(document).on("click", ".ai1ec-autoselect", function (t) {
                        if (e(this).data("clicked") && t.originalEvent.detail < 2) return;
                        e(this).data("clicked", !0);
                        var n;
                        document.body.createTextRange
                            ? ((n = document.body.createTextRange()), n.moveToElementText(this), n.select())
                            : window.getSelection && ((selection = window.getSelection()), (n = document.createRange()), n.selectNodeContents(this), selection.removeAllRanges(), selection.addRange(n));
                    });
                },
            };
        })();
        return t;
    }),
    timely.define("scripts/add_new_event/event_location/input_coordinates_utility_functions", ["jquery_timely", "ai1ec_config", "libs/utils"], function (e, t, n) {
        var r = function () {
                e("#osec_input_coordinates:checked").length > 0 &&
                e("#osec_table_coordinates input.ai1ec-coordinates").each(function () {
                    this.value = n.convert_comma_to_dot(this.value);
                });
            },
            i = function (t, n) {
                var r = e("<div />", {text: n, class: "ai1ec-error"});
                e(t).after(r);
            },
            s = function (t, n) {
                t.target.id === "post" && (t.stopImmediatePropagation(), t.preventDefault(), e("#publish").removeClass("button-primary-disabled"), e("#publish").siblings(".spinner").css("visibility", "hidden")), e(n).focus();
            },
            o = function () {
                var t = n.field_has_value("osec_address"),
                    r = !0;
                return (
                    e("input.ai1ec-coordinates").each(function () {
                        var e = n.field_has_value(this.id);
                        e || (r = !1);
                    }),
                    t || r
                );
            },
            u = function (n) {
                var r = !0,
                    o = !1;
                return (
                    e("#osec_input_coordinates:checked").length > 0 &&
                    (e("div.ai1ec-error").remove(),
                        e("#osec_table_coordinates input.ai1ec-coordinates").each(function () {
                            var n = e(this).hasClass("latitude"),
                                s = n ? t.error_message_not_entered_lat : t.error_message_not_entered_long;
                            this.value === "" && ((r = !1), o === !1 && (o = this), i(this, s));
                        })),
                    r === !1 && s(n, o),
                        r
                );
            },
            a = function (r) {
                if (e("#osec_input_coordinates:checked").length === 1) {
                    e("div.ai1ec-error").remove();
                    var o = !0,
                        u = !1,
                        a = !1;
                    return (
                        e("#osec_table_coordinates input.ai1ec-coordinates").each(function () {
                            if (this.value === "") {
                                a = !0;
                                return;
                            }
                            var r = e(this).hasClass("latitude"),
                                s = r ? t.error_message_not_valid_lat : t.error_message_not_valid_long;
                            n.is_valid_coordinate(this.value, r) || ((o = !1), u === !1 && (u = this), i(this, s));
                        }),
                        o === !1 && s(r, u),
                        a === !0 && (o = !1),
                            o
                    );
                }
            };
        return {
            ai1ec_convert_commas_to_dots_for_coordinates: r,
            ai1ec_show_error_message_after_element: i,
            check_if_address_or_coordinates_are_set: o,
            ai1ec_check_lat_long_fields_filled_when_publishing_event: u,
            ai1ec_check_lat_long_ok_for_search: a,
        };
    }),
    timely.define("external_libs/jquery.autocomplete_geomod", ["jquery_timely"], function (e) {
        e.fn.extend({
            autocomplete: function (t, n) {
                var r = typeof t == "string";
                return (
                    (n = e.extend({}, e.Autocompleter.defaults, {
                        url: r ? t : null,
                        data: r ? null : t,
                        delay: r ? e.Autocompleter.defaults.delay : 10,
                        max: n && !n.scroll ? 10 : 150
                    }, n)),
                        (n.highlight =
                            n.highlight ||
                            function (e) {
                                return e;
                            }),
                        (n.formatMatch = n.formatMatch || n.formatItem),
                        this.each(function () {
                            new e.Autocompleter(this, n);
                        })
                );
            },
            result: function (e) {
                return this.bind("result", e);
            },
            search: function (e) {
                return this.trigger("search", [e]);
            },
            flushCache: function () {
                return this.trigger("flushCache");
            },
            setOptions: function (e) {
                return this.trigger("setOptions", [e]);
            },
            unautocomplete: function () {
                return this.trigger("unautocomplete");
            },
        }),
            (e.Autocompleter = function (t, n) {
                function d() {
                    var r = h.selected();
                    if (!r) return !1;
                    var s = r.result;
                    o = s;
                    if (n.multiple) {
                        var u = m(i.val());
                        if (u.length > 1) {
                            var a = n.multipleSeparator.length,
                                f = e(t).selection().start,
                                l,
                                c = 0;
                            e.each(u, function (e, t) {
                                c += t.length;
                                if (f <= c) return (l = e), !1;
                                c += a;
                            }),
                                (u[l] = s),
                                (s = u.join(n.multipleSeparator));
                        }
                        s += n.multipleSeparator;
                    }
                    return i.val(s), w(), i.trigger("result", [r.data, r.value]), !0;
                }

                function v(e, t) {
                    if (f == r.DEL) {
                        h.hide();
                        return;
                    }
                    var s = i.val();
                    if (!t && s == o) return;
                    (o = s), (s = g(s)), s.length >= n.minChars ? (i.addClass(n.loadingClass), n.matchCase || (s = s.toLowerCase()), S(s, E, w)) : (T(), h.hide());
                }

                function m(t) {
                    return t
                        ? n.multiple
                            ? e.map(t.split(n.multipleSeparator), function (n) {
                                return e.trim(t).length ? e.trim(n) : null;
                            })
                            : [e.trim(t)]
                        : [""];
                }

                function g(r) {
                    if (!n.multiple) return r;
                    var i = m(r);
                    if (i.length == 1) return i[0];
                    var s = e(t).selection().start;
                    return s == r.length ? (i = m(r)) : (i = m(r.replace(r.substring(s), ""))), i[i.length - 1];
                }

                function y(s, u) {
                    n.autoFill && g(i.val()).toLowerCase() == s.toLowerCase() && f != r.BACKSPACE && (i.val(i.val() + u.substring(g(o).length)), e(t).selection(o.length, o.length + u.length));
                }

                function b() {
                    clearTimeout(s), (s = setTimeout(w, 200));
                }

                function w() {
                    var e = h.visible();
                    h.hide(),
                        clearTimeout(s),
                        T(),
                    n.mustMatch &&
                    i.search(function (e) {
                        if (!e)
                            if (n.multiple) {
                                var t = m(i.val()).slice(0, -1);
                                i.val(t.join(n.multipleSeparator) + (t.length ? n.multipleSeparator : ""));
                            } else i.val(""), i.trigger("result", null);
                    });
                }

                function E(e, t) {
                    t && t.length && a ? (T(), h.display(t, e), y(e, t[0].value), h.show()) : w();
                }

                function S(r, i, s) {
                    n.matchCase || (r = r.toLowerCase());
                    var o = u.load(r);
                    if (o && o.length) i(r, o);
                    else if (n.geocoder) {
                        var a = g(r),
                            f = {address: a};
                        n.region && (f.region = n.region),
                            n.geocoder.geocode(f, function (e, t) {
                                var s = n.parse(e, t, a);
                                u.add(r, s), i(r, s);
                            });
                    } else if (typeof n.url == "string" && n.url.length > 0) {
                        var l = {timestamp: +new Date()};
                        e.each(n.extraParams, function (e, t) {
                            l[e] = typeof t == "function" ? t() : t;
                        }),
                            e.ajax({
                                mode: "abort",
                                port: "autocomplete" + t.name,
                                dataType: n.dataType,
                                url: n.url,
                                data: e.extend({q: g(r), limit: n.max}, l),
                                success: function (e) {
                                    var t = (n.parse && n.parse(e)) || x(e);
                                    u.add(r, t), i(r, t);
                                },
                            });
                    } else h.emptyList(), s(r);
                }

                function x(t) {
                    var r = [],
                        i = t.split("\n");
                    for (var s = 0; s < i.length; s++) {
                        var o = e.trim(i[s]);
                        o && ((o = o.split("|")), (r[r.length] = {
                            data: o,
                            value: o[0],
                            result: (n.formatResult && n.formatResult(o, o[0])) || o[0]
                        }));
                    }
                    return r;
                }

                function T() {
                    i.removeClass(n.loadingClass);
                }

                var r = {
                        UP: 38,
                        DOWN: 40,
                        DEL: 46,
                        TAB: 9,
                        RETURN: 13,
                        ESC: 27,
                        COMMA: 188,
                        PAGEUP: 33,
                        PAGEDOWN: 34,
                        BACKSPACE: 8
                    },
                    i = e(t).attr("autocomplete", "off").addClass(n.inputClass),
                    s,
                    o = "",
                    u = e.Autocompleter.Cache(n),
                    a = 0,
                    f,
                    l = navigator.userAgent.match(/opera/i),
                    c = {mouseDownOnSelect: !1},
                    h = e.Autocompleter.Select(n, t, d, c),
                    p;
                l &&
                e(t.form).bind("submit.autocomplete", function () {
                    if (p) return (p = !1), !1;
                }),
                    i
                        .bind((l ? "keypress" : "keydown") + ".autocomplete", function (t) {
                            (a = 1), (f = t.keyCode);
                            switch (t.keyCode) {
                                case r.UP:
                                    t.preventDefault(), h.visible() ? h.prev() : v(0, !0);
                                    break;
                                case r.DOWN:
                                    t.preventDefault(), h.visible() ? h.next() : v(0, !0);
                                    break;
                                case r.PAGEUP:
                                    t.preventDefault(), h.visible() ? h.pageUp() : v(0, !0);
                                    break;
                                case r.PAGEDOWN:
                                    t.preventDefault(), h.visible() ? h.pageDown() : v(0, !0);
                                    break;
                                case n.multiple && e.trim(n.multipleSeparator) == "," && r.COMMA:
                                case r.TAB:
                                case r.RETURN:
                                    if (d()) return t.preventDefault(), (p = !0), !1;
                                    break;
                                case r.ESC:
                                    h.hide();
                                    break;
                                default:
                                    clearTimeout(s), (s = setTimeout(v, n.delay));
                            }
                        })
                        .focus(function () {
                            a++;
                        })
                        .blur(function () {
                            (a = 0), c.mouseDownOnSelect || b();
                        })
                        .click(function () {
                            a++ > 1 && !h.visible() && v(0, !0);
                        })
                        .bind("search", function () {
                            function n(e, n) {
                                var r;
                                if (n && n.length)
                                    for (var s = 0; s < n.length; s++)
                                        if (n[s].result.toLowerCase() == e.toLowerCase()) {
                                            r = n[s];
                                            break;
                                        }
                                typeof t == "function" ? t(r) : i.trigger("result", r && [r.data, r.value]);
                            }

                            var t = arguments.length > 1 ? arguments[1] : null;
                            e.each(m(i.val()), function (e, t) {
                                S(t, n, n);
                            });
                        })
                        .bind("flushCache", function () {
                            u.flush();
                        })
                        .bind("setOptions", function () {
                            e.extend(n, arguments[1]), "data" in arguments[1] && u.populate();
                        })
                        .bind("unautocomplete", function () {
                            h.unbind(), i.unbind(), e(t.form).unbind(".autocomplete");
                        });
            }),
            (e.Autocompleter.defaults = {
                inputClass: "ac_input",
                resultsClass: "ac_results",
                loadingClass: "ac_loading",
                minChars: 1,
                delay: 400,
                matchCase: !1,
                matchSubset: !0,
                matchContains: !1,
                cacheLength: 10,
                max: 100,
                mustMatch: !1,
                extraParams: {},
                selectFirst: !0,
                formatItem: function (e) {
                    return e[0];
                },
                formatMatch: null,
                autoFill: !1,
                width: 0,
                multiple: !1,
                multipleSeparator: ", ",
                highlight: function (e, t) {
                    return e.replace(new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + t.replace(/([\^\$\(\)\[\]\{\}\*\.\+\?\|\\])/gi, "\\$1") + ")(?![^<>]*>)(?![^&;]+;)", "gi"), "<strong>$1</strong>");
                },
                scroll: !0,
                scrollHeight: 180,
            }),
            (e.Autocompleter.Cache = function (t) {
                function i(e, n) {
                    t.matchCase || (e = e.toLowerCase());
                    var r = e.indexOf(n);
                    return t.matchContains == "word" && (r = e.toLowerCase().search("\\b" + n.toLowerCase())), r == -1 ? !1 : r == 0 || t.matchContains;
                }

                function s(e, i) {
                    r > t.cacheLength && u(), n[e] || r++, (n[e] = i);
                }

                function o() {
                    if (!t.data) return !1;
                    var n = {},
                        r = 0;
                    t.url || (t.cacheLength = 1), (n[""] = []);
                    for (var i = 0, o = t.data.length; i < o; i++) {
                        var u = t.data[i];
                        u = typeof u == "string" ? [u] : u;
                        var a = t.formatMatch(u, i + 1, t.data.length);
                        if (a === !1) continue;
                        var f = a.charAt(0).toLowerCase();
                        n[f] || (n[f] = []);
                        var l = {value: a, data: u, result: (t.formatResult && t.formatResult(u)) || a};
                        n[f].push(l), r++ < t.max && n[""].push(l);
                    }
                    e.each(n, function (e, n) {
                        t.cacheLength++, s(e, n);
                    });
                }

                function u() {
                    (n = {}), (r = 0);
                }

                var n = {},
                    r = 0;
                return (
                    setTimeout(o, 25),
                        {
                            flush: u,
                            add: s,
                            populate: o,
                            load: function (s) {
                                if (!t.cacheLength || !r) return null;
                                if (!t.url && t.matchContains) {
                                    var o = [];
                                    for (var u in n)
                                        if (u.length > 0) {
                                            var a = n[u];
                                            e.each(a, function (e, t) {
                                                i(t.value, s) && o.push(t);
                                            });
                                        }
                                    return o;
                                }
                                if (n[s]) return n[s];
                                if (t.matchSubset)
                                    for (var f = s.length - 1; f >= t.minChars; f--) {
                                        var a = n[s.substr(0, f)];
                                        if (a) {
                                            var o = [];
                                            return (
                                                e.each(a, function (e, t) {
                                                    i(t.value, s) && (o[o.length] = t);
                                                }),
                                                    o
                                            );
                                        }
                                    }
                                return null;
                            },
                        }
                );
            }),
            (e.Autocompleter.Select = function (t, n, r, i) {
                function p() {
                    if (!l) return;
                    (c = e("<div/>").hide().addClass(t.resultsClass).css("position", "absolute").appendTo(document.body)),
                        (h = e("<ul/>")
                            .appendTo(c)
                            .mouseover(function (t) {
                                d(t).nodeName && d(t).nodeName.toUpperCase() == "LI" && ((u = e("li", h).removeClass(s.ACTIVE).index(d(t))), e(d(t)).addClass(s.ACTIVE));
                            })
                            .click(function (t) {
                                return e(d(t)).addClass(s.ACTIVE), r(), n.focus(), !1;
                            })
                            .mousedown(function () {
                                i.mouseDownOnSelect = !0;
                            })
                            .mouseup(function () {
                                i.mouseDownOnSelect = !1;
                            })),
                    t.width > 0 && c.css("width", t.width),
                        (l = !1);
                }

                function d(e) {
                    var t = e.target;
                    while (t && t.tagName != "LI") t = t.parentNode;
                    return t ? t : [];
                }

                function v(e) {
                    o.slice(u, u + 1).removeClass(s.ACTIVE), m(e);
                    var n = o.slice(u, u + 1).addClass(s.ACTIVE);
                    if (t.scroll) {
                        var r = 0;
                        o.slice(0, u).each(function () {
                            r += this.offsetHeight;
                        }),
                            r + n[0].offsetHeight - h.scrollTop() > h[0].clientHeight ? h.scrollTop(r + n[0].offsetHeight - h.innerHeight()) : r < h.scrollTop() && h.scrollTop(r);
                    }
                }

                function m(e) {
                    (u += e), u < 0 ? (u = o.size() - 1) : u >= o.size() && (u = 0);
                }

                function g(e) {
                    return t.max && t.max < e ? t.max : e;
                }

                function y() {
                    h.empty();
                    var n = g(a.length);
                    for (var r = 0; r < n; r++) {
                        if (!a[r]) continue;
                        var i = t.formatItem(a[r].data, r + 1, n, a[r].value, f);
                        if (i === !1) continue;
                        var l = e("<li/>")
                            .html(t.highlight(i, f))
                            .addClass(r % 2 == 0 ? "ac_even" : "ac_odd")
                            .appendTo(h)[0];
                        e.data(l, "ac_data", a[r]);
                    }
                    (o = h.find("li")), t.selectFirst && (o.slice(0, 1).addClass(s.ACTIVE), (u = 0)), e.fn.bgiframe && h.bgiframe();
                }

                var s = {ACTIVE: "ac_over"},
                    o,
                    u = -1,
                    a,
                    f = "",
                    l = !0,
                    c,
                    h;
                return {
                    display: function (e, t) {
                        p(), (a = e), (f = t), y();
                    },
                    next: function () {
                        v(1);
                    },
                    prev: function () {
                        v(-1);
                    },
                    pageUp: function () {
                        u != 0 && u - 8 < 0 ? v(-u) : v(-8);
                    },
                    pageDown: function () {
                        u != o.size() - 1 && u + 8 > o.size() ? v(o.size() - 1 - u) : v(8);
                    },
                    hide: function () {
                        c && c.hide(), o && o.removeClass(s.ACTIVE), (u = -1);
                    },
                    visible: function () {
                        return c && c.is(":visible");
                    },
                    current: function () {
                        return this.visible() && (o.filter("." + s.ACTIVE)[0] || (t.selectFirst && o[0]));
                    },
                    show: function () {
                        var r = e(n).offset();
                        c.css({
                            width: typeof t.width == "string" || t.width > 0 ? t.width : e(n).width(),
                            top: r.top + n.offsetHeight,
                            left: r.left
                        }).show();
                        if (t.scroll) {
                            h.scrollTop(0), h.css({maxHeight: t.scrollHeight, overflow: "auto"});
                            if (navigator.userAgent.match(/msie/i) && typeof document.body.style.maxHeight == "undefined") {
                                var i = 0;
                                o.each(function () {
                                    i += this.offsetHeight;
                                });
                                var s = i > t.scrollHeight;
                                h.css("height", s ? t.scrollHeight : i), s || o.width(h.width() - parseInt(o.css("padding-left")) - parseInt(o.css("padding-right")));
                            }
                        }
                    },
                    selected: function () {
                        var t = o && o.filter("." + s.ACTIVE).removeClass(s.ACTIVE);
                        return t && t.length && e.data(t[0], "ac_data");
                    },
                    emptyList: function () {
                        h && h.empty();
                    },
                    unbind: function () {
                        c && c.remove();
                    },
                };
            }),
            (e.fn.selection = function (e, t) {
                if (e !== undefined)
                    return this.each(function () {
                        if (this.createTextRange) {
                            var n = this.createTextRange();
                            t === undefined || e == t ? (n.move("character", e), n.select()) : (n.collapse(!0), n.moveStart("character", e), n.moveEnd("character", t), n.select());
                        } else this.setSelectionRange ? this.setSelectionRange(e, t) : this.selectionStart && ((this.selectionStart = e), (this.selectionEnd = t));
                    });
                var n = this[0];
                if (n.createTextRange) {
                    var r = document.selection.createRange(),
                        i = n.value,
                        s = "<->",
                        o = r.text.length;
                    r.text = s;
                    var u = n.value.indexOf(s);
                    return (n.value = i), this.selection(u, u + o), {start: u, end: u + o};
                }
                if (n.selectionStart !== undefined) return {start: n.selectionStart, end: n.selectionEnd};
            });
    }),
    timely.define("external_libs/geo_autocomplete", ["jquery_timely", "external_libs/jquery.autocomplete_geomod"], function (e) {
        e.fn.extend({
            geo_autocomplete: function (t, n) {
                return (
                    (options = e.extend(
                        {},
                        e.Autocompleter.defaults,
                        {
                            geocoder: t,
                            mapwidth: 100,
                            mapheight: 100,
                            maptype: "terrain",
                            mapkey: "ABQIAAAAbnvDoAoYOSW2iqoXiGTpYBT2yXp_ZAY8_ufC3CFXhHIE1NvwkxQNumU68AwGqjbSNF9YO8NokKst8w",
                            mapsensor: !1,
                            parse: function (t, n, r) {
                                var i = [];
                                return (
                                    t &&
                                    n &&
                                    n == "OK" &&
                                    e.each(t, function (t, n) {
                                        if (n.geometry && n.geometry.viewport) {
                                            var s = n.formatted_address.split(","),
                                                o = s[0];
                                            e.each(s, function (t, n) {
                                                if (n.toLowerCase().indexOf(r.toLowerCase()) != -1) return (o = e.trim(n)), !1;
                                            }),
                                                i.push({data: n, value: o, result: o});
                                        }
                                    }),
                                        i
                                );
                            },
                            formatItem: function (e, t, n, r) {
                                var i =
                                        "https://maps.google.com/maps/api/staticmap?visible=" +
                                        e.geometry.viewport.getSouthWest().toUrlValue() +
                                        "|" +
                                        e.geometry.viewport.getNorthEast().toUrlValue() +
                                        "&size=" +
                                        options.mapwidth +
                                        "x" +
                                        options.mapheight +
                                        "&maptype=" +
                                        options.maptype +
                                        "&key=" +
                                        options.mapkey +
                                        "&sensor=" +
                                        (options.mapsensor ? "true" : "false"),
                                    s = e.formatted_address.replace(/,/gi, ",<br/>");
                                return '<img src="' + i + '" width="' + options.mapwidth + '" height="' + options.mapheight + '" /> ' + s + '<br clear="both"/>';
                            },
                        },
                        n
                    )),
                        (options.highlight =
                            options.highlight ||
                            function (e) {
                                return e;
                            }),
                        (options.formatMatch = options.formatMatch || options.formatItem),
                        (options.resultsClass = "ai1ec-geo-ac-results-not-ready"),
                        this.each(function () {
                            e(this).one("focus", function () {
                                var t = setInterval(function () {
                                    var n = e(".ai1ec-geo-ac-results-not-ready");
                                    n.length && (n.removeClass("ai1ec-geo-ac-results-not-ready").addClass("ai1ec-geo-ac-results").children("ul").addClass("ai1ec-dropdown-menu"), clearInterval(t));
                                }, 500);
                            }),
                                new e.Autocompleter(this, options);
                        })
                );
            },
        });
    }),
    timely.define(
        "scripts/add_new_event/event_location/gmaps_helper",
        ["jquery_timely", "domReady", "ai1ec_config", "scripts/add_new_event/event_location/input_coordinates_utility_functions", "external_libs/jquery.autocomplete_geomod", "external_libs/geo_autocomplete"],
        function (e, t, n, r) {
            var i,
                s,
                o,
                u,
                a,
                f,
                l = function (t) {
                    e("#osec_latitude").val(t.latLng.lat()), e("#osec_longitude").val(t.latLng.lng()), e("#osec_input_coordinates:checked").length === 0 && e("#osec_input_coordinates").trigger("click");
                },
                c = function () {
                    n.disable_autocompletion ||
                    e("#osec_address")
                        .geo_autocomplete(new google.maps.Geocoder(), {
                            selectFirst: !1,
                            minChars: 3,
                            cacheLength: 50,
                            width: 300,
                            scroll: !0,
                            scrollHeight: 330,
                            region: n.region
                        })
                        .result(function (e, t) {
                            t && p(t);
                        })
                        .change(function () {
                            if (e(this).val().length > 0) {
                                var t = e(this).val();
                                i.geocode({address: t, region: n.region}, function (e, t) {
                                    t === google.maps.GeocoderStatus.OK && p(e[0]);
                                });
                            }
                        });
                },
                h = function () {
                    (i = new google.maps.Geocoder()),
                        (s = new google.maps.LatLng(9.965, -83.327)),
                        (o = {zoom: 0, mapTypeId: google.maps.MapTypeId.ROADMAP, center: s}),
                        t(function () {
                            e("#osec_map_canvas").length > 0 &&
                            ((u = new google.maps.Map(e("#osec_map_canvas").get(0), o)),
                                (a = new google.maps.Marker({map: u, draggable: !0})),
                                google.maps.event.addListener(a, "dragend", l),
                                a.setPosition(s),
                                c(),
                                v(),
                                e('a[href="#osec-event-location-box"]').on("click", function () {
                                    window.setTimeout(function () {
                                        google.maps.event.trigger(u, "resize"), u.setCenter(a.getPosition());
                                    }, 150);
                                }));
                        });
                },
                p = function (t) {
                    u.setCenter(t.geometry.location),
                        u.setZoom(15),
                        a.setPosition(t.geometry.location),
                        e("#osec_address").val(t.formatted_address),
                        e("#osec_latitude").val(t.geometry.location.lat()),
                        e("#osec_longitude").val(t.geometry.location.lng()),
                    e("#osec_input_coordinates").is(":checked") || e("#osec_input_coordinates").click();
                    var n = "",
                        r = "",
                        i = "",
                        s = 0,
                        o = 0,
                        f = "",
                        l;
                    for (var c = 0; c < t.address_components.length; c++)
                        switch (t.address_components[c].types[0]) {
                            case "street_number":
                                n = t.address_components[c].long_name;
                                break;
                            case "route":
                                r = t.address_components[c].long_name;
                                break;
                            case "locality":
                                i = t.address_components[c].long_name;
                                break;
                            case "administrative_area_level_1":
                                f = t.address_components[c].long_name;
                                break;
                            case "postal_code":
                                s = t.address_components[c].long_name;
                                break;
                            case "country":
                                (l = t.address_components[c].short_name), (o = t.address_components[c].long_name);
                        }
                    var h = n.length > 0 ? n + " " : "";
                    (h += r.length > 0 ? r : ""), (s = s !== 0 ? s : ""), e("#osec_city").val(i), e("#osec_province").val(f), e("#osec_postal_code").val(s), e("#osec_country").val(o), e("#osec_country_short").val(l);
                },
                d = function () {
                    var t = parseFloat(e("#osec_latitude").val()),
                        n = parseFloat(e("#osec_longitude").val()),
                        r = new google.maps.LatLng(t, n);
                    u.setCenter(r), u.setZoom(15), a.setPosition(r);
                },
                v = function () {
                    e("#osec_input_coordinates:checked").length === 0 ? (e("#osec_table_coordinates").hide(), e("#osec_address").change()) : d();
                },
                m = function () {
                    return a;
                },
                g = function () {
                    return f;
                };
            return {init_gmaps: h, ai1ec_update_map_from_coordinates: d, get_marker: m, get_position: g};
        }
    ),
    timely.define(
        "scripts/add_new_event/event_location/input_coordinates_event_handlers",
        ["jquery_timely", "scripts/add_new_event/event_location/input_coordinates_utility_functions", "scripts/add_new_event/event_location/gmaps_helper", "ai1ec_config"],
        function (e, t, n, r) {
            var i = function (t) {
                    this.checked ? e(".ai1ec-map-preview").addClass("ai1ec-map-visible") : e(".ai1ec-map-preview").removeClass("ai1ec-map-visible");
                },
                s = function (t) {
                    this.checked ? e("#osec_table_coordinates").fadeIn("fast") : e("#osec_table_coordinates").fadeOut("fast");
                },
                o = function (e) {
                    t.ai1ec_convert_commas_to_dots_for_coordinates();
                    var r = t.ai1ec_check_lat_long_ok_for_search(e);
                    r === !0 && n.ai1ec_update_map_from_coordinates();
                };
            return {
                toggle_visibility_of_google_map_on_click: i,
                toggle_visibility_of_coordinate_fields_on_click: s,
                update_map_from_coordinates_on_blur: o
            };
        }
    ),
    timely.define("external_libs/jquery.calendrical_timespan", ["jquery_timely"], function (e) {
        function l() {
            var e = new Date();
            return new Date(e.getFullYear(), e.getMonth(), e.getDate());
        }

        function c(e, t) {
            return typeof e == "string" && (e = new Date(e)), typeof t == "string" && (t = new Date(t)), e.getUTCDate() === t.getUTCDate() && e.getUTCMonth() === t.getUTCMonth() && e.getUTCFullYear() === t.getUTCFullYear() ? !0 : !1;
        }

        function h(e, t) {
            if (e instanceof Date) return h(e.getUTCFullYear(), e.getUTCMonth());
            if (t == 1) {
                var n = e % 4 == 0 && (e % 100 != 0 || e % 400 == 0);
                return n ? 29 : 28;
            }
            return t == 3 || t == 5 || t == 8 || t == 10 ? 30 : 31;
        }

        function p(e) {
            return new Date(e.getTime() + 864e5);
        }

        function d(e) {
            return new Date(e.getTime() - 864e5);
        }

        function v(e, t) {
            return t == 11 ? new Date(e + 1, 0, 1) : new Date(e, t + 1, 1);
        }

        function m(t, n, r, i) {
            var s = i.monthNames.split(","),
                o = e("<thead />"),
                u = e("<tr />").appendTo(o);
            e("<th />")
                .addClass("monthCell")
                .append(
                    e('<a href="javascript:;">&laquo;</a>')
                        .addClass("prevMonth")
                        .mousedown(function (e) {
                            g(t, r == 0 ? n - 1 : n, r == 0 ? 11 : r - 1, i), e.preventDefault();
                        })
                )
                .appendTo(u),
                e("<th />")
                    .addClass("monthCell")
                    .attr("colSpan", 5)
                    .append(e('<a href="javascript:;">' + s[r] + " " + n + "</a>").addClass("monthName"))
                    .appendTo(u),
                e("<th />")
                    .addClass("monthCell")
                    .append(
                        e('<a href="javascript:;">&raquo;</a>')
                            .addClass("nextMonth")
                            .mousedown(function () {
                                g(t, r == 11 ? n + 1 : n, r == 11 ? 0 : r + 1, i);
                            })
                    )
                    .appendTo(u);
            var a = i.dayNames.split(","),
                f = parseInt(i.weekStartDay),
                l = [];
            for (var c = 0, h = a.length; c < h; c++) l[c] = a[(c + f) % h];
            var p = e("<tr />").appendTo(o);
            return (
                e.each(l, function (t, n) {
                    e("<td />").addClass("dayName").append(n).appendTo(p);
                }),
                    o
            );
        }

        function g(t, n, r, i) {
            i = i || {};
            var s = parseInt(i.weekStartDay),
                o = i.today ? i.today : l();
            o.setHours(0), o.setMinutes(0);
            var u = new Date(n, r, 1),
                a = v(n, r),
                f = Math.abs(o.getTimezoneOffset());
            f != 0 &&
            (o.setHours(o.getHours() + f / 60),
                o.setMinutes(o.getMinutes() + (f % 60)),
                u.setHours(u.getHours() + f / 60),
                u.setMinutes(u.getMinutes() + (f % 60)),
                a.setHours(a.getHours() + f / 60),
                a.setMinutes(a.getMinutes() + (f % 60)));
            var h = a.getUTCDay() - s;
            h < 0 ? (h = Math.abs(h) - 1) : (h = 6 - h);
            for (var g = 0; g < h; g++) a = p(a);
            var y = e("<table />");
            m(t, n, r, i).appendTo(y);
            var b = e("<tbody />").appendTo(y),
                w = e("<tr />"),
                E = u.getUTCDay() - s;
            E < 0 && (E = 7 + E);
            for (var g = 0; g < E; g++) u = d(u);
            while (u <= a) {
                var S = e("<td />")
                        .addClass("day")
                        .append(
                            e('<a href="javascript:;">' + u.getUTCDate() + "</a>").click(
                                (function () {
                                    var e = u;
                                    return function () {
                                        i && i.selectDate && i.selectDate(e);
                                    };
                                })()
                            )
                        )
                        .appendTo(w),
                    x = c(u, o),
                    T = i.selected && c(i.selected, u);
                x && S.addClass("today"), T && S.addClass("selected"), x && T && S.addClass("today_selected"), u.getUTCMonth() != r && S.addClass("nonMonth");
                var N = u.getUTCDay();
                (N + 1) % 7 == s && (b.append(w), (w = e("<tr />"))), (u = p(u));
            }
            w.children().length ? b.append(w) : w.remove(), t.empty().append(y);
        }

        function y(t, n) {
            var r = n.selection && f(n.selection);
            r && (r.minute = Math.floor(r.minute / 15) * 15);
            var i = n.startTime && n.startTime.hour * 60 + n.startTime.minute,
                s,
                o = e("<ul />");
            for (var a = 0; a < 24; a++)
                for (var l = 0; l < 60; l += 15) {
                    if (i && i > a * 60 + l) continue;
                    (function () {
                        var t = u(a, l, n.isoTime),
                            f = t;
                        if (i != null) {
                            var c = a * 60 + l - i;
                            c < 60 ? (f += " (" + c + " min)") : c == 60 ? (f += " (1 hr)") : (f += " (" + Math.floor(c / 60) + " hr " + (c % 60) + " min)");
                        }
                        var h = e("<li />")
                            .append(
                                e('<a href="javascript:;">' + f + "</a>")
                                    .click(function () {
                                        n && n.selectTime && n.selectTime(t);
                                    })
                                    .mousemove(function () {
                                        e("li.selected", o).removeClass("selected");
                                    })
                            )
                            .appendTo(o);
                        !s && a == n.defaultHour && (s = h), r && r.hour == a && r.minute == l && (h.addClass("selected"), (s = h));
                    })();
                }
            s &&
            setTimeout(function () {
                t[0].scrollTop = s[0].offsetTop - s.height() * 2;
            }, 0),
                t.empty().append(o);
        }

        function b(e) {
            e.addClass("error").fadeOut("normal", function () {
                e.val(e.data("timespan.stored")).removeClass("error").fadeIn("fast");
            });
        }

        function w() {
            e(this).data("timespan.stored", this.value);
        }

        function E(t, n, r, i, a, f, l, c, h, p) {
            r.val(r.data("timespan.initial_value")), f.val(f.data("timespan.initial_value")), (l.get(0).checked = l.data("timespan.initial_value"));
            var d = s(r, p, 0, 15);
            n.val(u(d.getUTCHours(), d.getUTCMinutes(), c)), t.val(o(d, h));
            var v = s(f, d.getTime(), 1, 15);
            a.val(u(v.getUTCHours(), v.getUTCMinutes(), c)),
            l.get(0).checked && v.setUTCDate(v.getUTCDate() - 1),
                i.val(o(v, h)),
                t.each(w),
                n.each(w),
                i.each(w),
                a.each(w),
                l.trigger("change.timespan"),
                e("#osec_instant_event").trigger("change.timespan");
        }

        var t = {
                us: {
                    pattern: /([\d]{1,2})\/([\d]{1,2})\/([\d]{4}|[\d]{2})/,
                    format: "m/d/y",
                    order: "middleEndian",
                    zeroPad: !1
                },
                iso: {
                    pattern: /([\d]{4}|[\d]{2})-([\d]{1,2})-([\d]{1,2})/,
                    format: "y-m-d",
                    order: "bigEndian",
                    zeroPad: !0
                },
                dot: {
                    pattern: /([\d]{1,2}).([\d]{1,2}).([\d]{4}|[\d]{2})/,
                    format: "d.m.y",
                    order: "littleEndian",
                    zeroPad: !1
                },
                def: {
                    pattern: /([\d]{1,2})\/([\d]{1,2})\/([\d]{4}|[\d]{2})/,
                    format: "d/m/y",
                    order: "littleEndian",
                    zeroPad: !1
                },
            },
            n = function (e) {
                return e < 10 ? "0" + e : e;
            },
            r = function (e, t) {
                typeof t == "undefined" && (t = !1);
                var r = e.getUTCFullYear() + "-" + n(e.getUTCMonth() + 1) + "-" + n(e.getUTCDate());
                return t && (r += "T" + n(e.getUTCHours()) + ":" + n(e.getUTCMinutes()) + ":00"), r;
            },
            i = function (e, t) {
                var n = e.val(),
                    r = null;
                if (n.length < 4) r = new Date(t);
                else {
                    r = new Date(n);
                    var i = n.split("T"),
                        s = i[0].split("-"),
                        o = i[1].split(":");
                    r.setUTCFullYear(s[0], s[1] - 1, s[2]), r.setUTCHours(o[0], o[1], o[2], 0);
                }
                return r;
            },
            s = function (e, t, n, r) {
                return (t += n * 36e5), (t -= t % (r * 6e4)), i(e, t);
            },
            o = function (e, n, r) {
                var i, s, o;
                typeof t[n] == "undefined" && (n = "def"),
                typeof r == "undefined" && (r = !1),
                    !0 === r
                        ? ((i = e.getFullYear().toString()), (s = (e.getMonth() + 1).toString()), (o = e.getDate().toString()))
                        : ((i = e.getUTCFullYear().toString()), (s = (e.getUTCMonth() + 1).toString()), (o = e.getUTCDate().toString())),
                t[n].zeroPad && (s.length == 1 && (s = "0" + s), o.length == 1 && (o = "0" + o));
                var u = t[n].format;
                return (u = u.replace("d", o)), (u = u.replace("m", s)), (u = u.replace("y", i)), u;
            },
            u = function (e, t, n) {
                var r = t;
                t < 10 && (r = "0" + t);
                if (n) {
                    var i = e;
                    return i < 10 && (i = "0" + e), i + ":" + r;
                }
                var i = e % 12;
                i == 0 && (i = 12);
                var s = e < 12 ? "am" : "pm";
                return i + ":" + r + s;
            },
            a = function (e, n) {
                typeof t[n] == "undefined" && (n = "def");
                var r = e.match(t[n].pattern);
                if (!r || r.length != 4) return Date("invalid");
                switch (t[n].order) {
                    case "bigEndian":
                        var i = r[3],
                            s = r[2],
                            o = r[1];
                        break;
                    case "littleEndian":
                        var i = r[1],
                            s = r[2],
                            o = r[3];
                        break;
                    case "middleEndian":
                        var i = r[2],
                            s = r[1],
                            o = r[3];
                        break;
                    default:
                        var i = r[1],
                            s = r[2],
                            o = r[3];
                }
                return o.length == 2 && (o = new Date().getUTCFullYear().toString().substr(0, 2) + o), new Date(s + "/" + i + "/" + o + " GMT");
            },
            f = function (e) {
                var t = (t = /(\d+)\s*[:\-\.,]\s*(\d+)\s*(am|pm)?/i.exec(e));
                if (t && t.length >= 3) {
                    var n = Number(t[1]),
                        r = Number(t[2]);
                    return n == 12 && t[3] && (n -= 12), t[3] && t[3].toLowerCase() == "pm" && (n += 12), {
                        hour: n,
                        minute: r
                    };
                }
                return null;
            };
        (e.fn.calendricalDate = function (t) {
            return (
                (t = t || {}),
                    (t.padding = t.padding || 4),
                    (t.monthNames = t.monthNames || "January,February,March,April,May,June,July,August,September,October,November,December"),
                    (t.dayNames = t.dayNames || "S,M,T,W,T,F,S"),
                    (t.weekStartDay = t.weekStartDay || 0),
                    this.each(function () {
                        var n = e(this),
                            r,
                            i = !1;
                        n.bind("focus", function () {
                            if (r) return;
                            var s = n.position(),
                                u = n.css("padding-left");
                            (r = e("<div />")
                                .addClass("calendricalDatePopup")
                                .mouseenter(function () {
                                    i = !0;
                                })
                                .mouseleave(function () {
                                    i = !1;
                                })
                                .mousedown(function (e) {
                                    e.preventDefault();
                                })
                                .css({position: "absolute", left: s.left, top: s.top + n.height() + t.padding * 2})),
                                n.after(r);
                            var f = a(n.val(), t.dateFormat);
                            f.getUTCFullYear() || (f = t.today ? t.today : l()),
                                g(r, f.getUTCFullYear(), f.getUTCMonth(), {
                                    today: t.today,
                                    selected: f,
                                    monthNames: t.monthNames,
                                    dayNames: t.dayNames,
                                    weekStartDay: t.weekStartDay,
                                    selectDate: function (e) {
                                        (i = !1), n.val(o(e, t.dateFormat)), r.remove(), (r = null);
                                        if (t.endDate) {
                                            var s = a(t.endDate.val(), t.dateFormat);
                                            s >= f && t.endDate.val(o(new Date(e.getTime() + s.getTime() - f.getTime()), t.dateFormat));
                                        }
                                    },
                                });
                        }).blur(function () {
                            if (i) {
                                r && n.focus();
                                return;
                            }
                            if (!r) return;
                            r.remove(), (r = null);
                        });
                    })
            );
        }),
            (e.fn.calendricalDateRange = function (t) {
                return this.length >= 2 && (e(this[0]).calendricalDate(e.extend({endDate: e(this[1])}, t)), e(this[1]).calendricalDate(t)), this;
            }),
            (e.fn.calendricalDateRangeSingle = function (t) {
                return this.length == 1 && e(this).calendricalDate(t), this;
            }),
            (e.fn.calendricalTime = function (t) {
                return (
                    (t = t || {}),
                        (t.padding = t.padding || 4),
                        this.each(function () {
                            var n = e(this),
                                r,
                                i = !1;
                            n.bind("focus click", function () {
                                if (r) return;
                                var s = t.startTime;
                                s && t.startDate && t.endDate && !c(a(t.startDate.val()), a(t.endDate.val())) && (s = !1);
                                var o = n.position();
                                (r = e("<div />")
                                    .addClass("calendricalTimePopup")
                                    .mouseenter(function () {
                                        i = !0;
                                    })
                                    .mouseleave(function () {
                                        i = !1;
                                    })
                                    .mousedown(function (e) {
                                        e.preventDefault();
                                    })
                                    .css({
                                        position: "absolute",
                                        left: o.left,
                                        top: o.top + n.height() + t.padding * 2
                                    })),
                                s && r.addClass("calendricalEndTimePopup"),
                                    n.after(r);
                                var u = {
                                    selection: n.val(),
                                    selectTime: function (e) {
                                        (i = !1), n.val(e), r.remove(), (r = null);
                                    },
                                    isoTime: t.isoTime || !1,
                                    defaultHour: t.defaultHour != null ? t.defaultHour : 8,
                                };
                                s && (u.startTime = f(t.startTime.val())), y(r, u);
                            }).blur(function () {
                                if (i) {
                                    r && n.focus();
                                    return;
                                }
                                if (!r) return;
                                r.remove(), (r = null);
                            });
                        })
                );
            }),
            (e.fn.calendricalTimeRange = function (t) {
                return this.length >= 2 && (e(this[0]).calendricalTime(t), e(this[1]).calendricalTime(e.extend({startTime: e(this[0])}, t))), this;
            }),
            (e.fn.calendricalDateTimeRange = function (t) {
                return (
                    this.length >= 4 &&
                    (e(this[0]).calendricalDate(e.extend({endDate: e(this[2])}, t)),
                        e(this[1]).calendricalTime(t),
                        e(this[2]).calendricalDate(t),
                        e(this[3]).calendricalTime(e.extend({
                            startTime: e(this[1]),
                            startDate: e(this[0]),
                            endDate: e(this[2])
                        }, t))),
                        this
                );
            });
        var S = {
                allday: "#allday",
                start_date_input: "#start-date-input",
                start_time_input: "#start-time-input",
                start_time: "#start-time",
                end_date_input: "#end-date-input",
                end_time_input: "#end-time-input",
                end_time: "#end-time",
                twentyfour_hour: !1,
                date_format: "def",
                now: new Date(),
            },
            x = {
                init: function (t) {
                    function C() {
                        var e = a(s.val(), n.date_format).getTime() / 1e3,
                            t = f(l.val());
                        e += t.hour * 3600 + t.minute * 60;
                        var r = a(h.val(), n.date_format).getTime() / 1e3,
                            i = f(p.val());
                        return (r += i.hour * 3600 + i.minute * 60), r - e;
                    }

                    function k() {
                        var e = a(s.data("timespan.stored"), n.date_format),
                            t = f(l.data("timespan.stored")),
                            r = e.getTime() / 1e3 + t.hour * 3600 + t.minute * 60 + s.data("time_diff");
                        return (r = new Date(r * 1e3)), h.val(o(r, n.date_format)), p.val(u(r.getUTCHours(), r.getUTCMinutes(), n.twentyfour_hour)), !0;
                    }

                    var n = e.extend({}, S, t),
                        i = e(n.allday),
                        s = e(n.start_date_input),
                        l = e(n.start_time_input),
                        c = e(n.start_time),
                        h = e(n.end_date_input),
                        p = e(n.end_time_input),
                        d = e(n.end_time),
                        v = e("#osec_instant_event"),
                        m = h.add(p),
                        g = s.add(n.end_date_input),
                        y = l.add(n.end_time_input),
                        x = s.add(n.start_time_input).add(n.end_date_input).add(n.end_time_input);
                    x.bind("focus.timespan", w),
                        v.bind("change.timespan", function () {
                            this.checked ? (m.closest("tr").fadeOut(), i.attr("disabled", !0)) : (i.removeAttr("disabled"), m.closest("tr").fadeIn());
                        });
                    var T = new Date(n.now.getFullYear(), n.now.getMonth(), n.now.getDate()),
                        N = !1;
                    return (
                        (i
                            .bind("change.timespan", function () {
                                this.checked ? (y.fadeOut(), v.attr("disabled", !0)) : (v.removeAttr("disabled"), y.fadeIn()),
                                N || ((N = !0), x.calendricalDateTimeRange({
                                    today: T,
                                    dateFormat: n.date_format,
                                    isoTime: n.twentyfour_hour,
                                    monthNames: n.month_names,
                                    dayNames: n.day_names,
                                    weekStartDay: n.week_start_day
                                }));
                            })
                            .get().checked = !1),
                            g.bind("blur.timespan", function () {
                                var t = a(this.value, n.date_format);
                                isNaN(t) ? b(e(this)) : (e(this).data("timespan.stored", this.value), e(this).val(o(t, n.date_format)));
                            }),
                            y.bind("blur.timespan", function () {
                                var t = f(this.value);
                                t ? (e(this).data("timespan.stored", this.value), e(this).val(u(t.hour, t.minute, n.twentyfour_hour))) : b(e(this));
                            }),
                            s
                                .add(n.start_time_input)
                                .bind("focus.timespan", function () {
                                    s.data("time_diff", C());
                                })
                                .bind("blur.timespan", function () {
                                    s.data("time_diff") < 0 && s.data("time_diff", 900);
                                    var e = k();
                                }),
                            h.add(n.start_time_input).bind("blur.timespan", function () {
                                if (C() < 0) {
                                    s.data("time_diff", 900);
                                    var e = k();
                                }
                            }),
                            s.closest("form").bind("submit.timespan", function () {
                                var e = a(s.val(), n.date_format).getTime() / 1e3;
                                if (!isNaN(e)) {
                                    if (!i.get(0).checked) {
                                        var t = f(l.val());
                                        t ? (e += t.hour * 3600 + t.minute * 60) : (e = "");
                                    }
                                } else e = "";
                                e > 0 && c.val(r(new Date(e * 1e3), !0));
                                var o = a(h.val(), n.date_format).getTime() / 1e3;
                                if (!isNaN(o))
                                    if (i.get(0).checked) o += 86400;
                                    else {
                                        var t = f(p.val());
                                        t ? (o += t.hour * 3600 + t.minute * 60) : (o = "");
                                    }
                                else o = "";
                                o > 0 && d.val(r(new Date(o * 1e3), !0));
                            }),
                            c.data("timespan.initial_value", c.val()),
                            d.data("timespan.initial_value", d.val()),
                            i.data("timespan.initial_value", i.get(0).checked),
                            E(s, l, c, h, p, d, i, n.twentyfour_hour, n.date_format, n.now),
                            this
                    );
                },
                reset: function (t) {
                    var n = e.extend({}, S, t);
                    return E(e(n.start_date_input), e(n.start_time_input), e(n.start_time), e(n.end_date_input), e(n.end_time_input), e(n.end_time), e(n.allday), n.twentyfour_hour, n.date_format, n.now), this;
                },
                destroy: function (t) {
                    return (
                        (t = e.extend({}, S, t)),
                            e.each(t, function (t, n) {
                                e(n).unbind(".timespan");
                            }),
                            e(t.start_date_input).closest("form").unbind(".timespan"),
                            this
                    );
                },
            };
        return (
            (e.timespan = function (t) {
                if (x[t]) return x[t].apply(this, Array.prototype.slice.call(arguments, 1));
                if (typeof t == "object" || !t) return x.init.apply(this, arguments);
                e.error("Method " + t + " does not exist on jQuery.timespan");
            }),
                {formatDate: o, parseDate: a}
        );
    }),
    timely.define("scripts/add_new_event/event_date_time/date_time_utility_functions", ["jquery_timely", "ai1ec_config", "libs/utils", "external_libs/jquery.calendrical_timespan"], function (e, t, n, r) {
        var i = n.get_ajax_url(),
            s = function (t, n, r, i, s, o) {
                e(t).val(i), e("#osec_repeat_box").modal("hide");
                var u = e.trim(e(n).text());
                u.lastIndexOf(":") === -1 && ((u = u.substring(0, u.length - 3)), e(n).text(u + ":")),
                    e(s).attr("disabled", !1),
                    e(r).fadeOut("fast", function () {
                        e(this).text(o.message), e(this).fadeIn("fast");
                    });
            },
            o = function (t, n, r, i) {
                e("#osec_repeat_box .ai1ec-alert-danger").text(r.message).removeClass("ai1ec-hide"), e(i).attr("disabled", !1), e(t).val("");
                var s = e.trim(e(n).text());
                s.lastIndexOf("...") === -1 && ((s = s.substring(0, s.length - 1)), e(n).text(s + "...")), e(this).closest("tr").find(".osec_rule_text").text() === "" && e(t).siblings("input:checkbox").removeAttr("checked");
            },
            u = function (t, n, r, i, s) {
                e(document).on("click", t, function () {
                    if (!e(n).is(":checked")) {
                        e(n).attr("checked", !0);
                        var t = e.trim(e(r).text());
                        (t = t.substring(0, t.length - 3)), e(r).text(t + ":");
                    }
                    return c(i, s), !1;
                });
            },
            a = function (t, n, r, i, s) {
                e(t).click(function () {
                    if (e(this).is(":checked")) this.id === "osec_repeat" && e("#osec_exclude").removeAttr("disabled"), c(i, s);
                    else {
                        this.id === "osec_repeat" && (e("#osec_exclude").removeAttr("checked").attr("disabled", !0), e("#osec_exclude_text > a").text("")), e(n).text("");
                        var t = e.trim(e(r).text());
                        (t = t.substring(0, t.length - 1)), e(r).text(t + "...");
                    }
                });
            },
            f = function (t, n, r) {
                if (e.trim(e(t).text()) === "") {
                    e(n).removeAttr("checked"), e("#osec_repeat").is(":checked") || e("#osec_exclude").attr("disabled", !0);
                    var i = e.trim(e(r).text());
                    i.lastIndexOf("...") === -1 && ((i = i.substring(0, i.length - 1)), e(r).text(i + "..."));
                }
            },
            l = function () {
                e("#osec_count, #osec_daily_count, #osec_weekly_count, #osec_monthly_count, #osec_yearly_count").rangeinput({
                    css: {
                        input: "ai1ec-range",
                        slider: "ai1ec-slider",
                        progress: "ai1ec-progress",
                        handle: "ai1ec-handle"
                    }
                });
                var n = e("#osec_recurrence_calendar");
                n.datepicker({multidate: !0, weekStart: t.week_start_day}),
                    n.on("changeDate", function (n) {
                        var i = [],
                            s = [];
                        for (var o = 0; o < n.dates.length; o++) {
                            var u = new Date(n.dates[o]),
                                a = "" + u.getFullYear() + ("0" + (u.getMonth() + 1)).slice(-2) + ("0" + u.getDate()).slice(-2) + "T000000Z",
                                f = '<span class="ai1ec-label ai1ec-label-default">' + r.formatDate(u, t.date_format, !0) + "</span>";
                            i.push(a), s.push(f);
                        }
                        e("#osec_rec_dates_list").html(s.join(" ")), e("#osec_rec_custom_dates").val(i.join(","));
                    });
                var i = {
                    start_date_input: "#osec_until-date-input",
                    start_time: "#osec_until-time",
                    date_format: t.date_format,
                    month_names: t.month_names,
                    day_names: t.day_names,
                    week_start_day: t.week_start_day,
                    twentyfour_hour: t.twentyfour_hour,
                    now: new Date(t.now * 1e3),
                };
                e.inputdate(i), e(document).trigger("ai1ec.recurrence-modal.inited");
            },
            c = function (t, n) {
                var r = e("#osec_repeat_box"),
                    s = e(".ai1ec-loading", r);
                r.modal({backdrop: "static"}),
                    e.post(
                        i,
                        t,
                        function (e) {
                            e.error ? (window.alert(e.message), r.modal("hide")) : (s.addClass("ai1ec-hide").after(e.message), typeof n == "function" && n());
                        },
                        "json"
                    );
            };
        return {
            show_repeat_tabs: c,
            init_modal_widgets: l,
            click_on_modal_cancel: f,
            click_on_checkbox: a,
            click_on_ics_rule_text: u,
            repeat_form_error: o,
            repeat_form_success: s
        };
    }),
    timely.define("external_libs/bootstrap/button", ["jquery_timely"], function (e) {
        var t = function (n, r) {
            (this.$element = e(n)), (this.options = e.extend({}, t.DEFAULTS, r));
        };
        (t.DEFAULTS = {loadingText: "loading..."}),
            (t.prototype.setState = function (e) {
                var t = "disabled",
                    n = this.$element,
                    r = n.is("input") ? "val" : "html",
                    i = n.data();
                (e += "Text"),
                i.resetText || n.data("resetText", n[r]()),
                    n[r](i[e] || this.options[e]),
                    setTimeout(function () {
                        e == "loadingText" ? n.addClass("ai1ec-" + t).attr(t, t) : n.removeClass("ai1ec-" + t).removeAttr(t);
                    }, 0);
            }),
            (t.prototype.toggle = function () {
                var e = this.$element.closest('[data-toggle="ai1ec-buttons"]'),
                    t = !0;
                if (e.length) {
                    var n = this.$element.find("input");
                    n.prop("type") === "radio" && (n.prop("checked") && this.$element.hasClass("ai1ec-active") ? (t = !1) : e.find(".ai1ec-active").removeClass("ai1ec-active")),
                    t && n.prop("checked", !this.$element.hasClass("ai1ec-active")).trigger("change");
                }
                t && this.$element.toggleClass("ai1ec-active");
            });
        var n = e.fn.button;
        (e.fn.button = function (n) {
            return this.each(function () {
                var r = e(this),
                    i = r.data("bs.button"),
                    s = typeof n == "object" && n;
                i || r.data("bs.button", (i = new t(this, s))), n == "toggle" ? i.toggle() : n && i.setState(n);
            });
        }),
            (e.fn.button.Constructor = t),
            (e.fn.button.noConflict = function () {
                return (e.fn.button = n), this;
            }),
            e(document).on("click.bs.button.data-api", "[data-toggle^=ai1ec-button]", function (t) {
                var n = e(t.target);
                n.hasClass("ai1ec-btn") || (n = n.closest(".ai1ec-btn")), n.button("toggle"), t.preventDefault();
            });
    }),
    timely.define(
        "scripts/add_new_event/event_date_time/date_time_event_handlers",
        ["jquery_timely", "ai1ec_config", "scripts/add_new_event/event_date_time/date_time_utility_functions", "external_libs/jquery.calendrical_timespan", "libs/utils", "external_libs/bootstrap/button"],
        function (e, t, n, r, i) {
            var s = i.get_ajax_url(),
                o = function () {
                    var t = e("#osec_table_coordinates option:selected").val();
                    switch (t) {
                        case "0":
                            e("#osec_until_holder, #osec_count_holder").collapse("hide");
                            break;
                        case "1":
                            e("#osec_until_holder").collapse("hide"), e("#osec_count_holder").collapse("show");
                            break;
                        case "2":
                            e("#osec_count_holder").collapse("hide"), e("#osec_until_holder").collapse("show");
                    }
                },
                u = function () {
                    e("#publish").trigger("click");
                },
                a = function () {
                    var i = e(this),
                        o = "",
                        u = e("#osec_repeat_box .ai1ec-tab-pane.ai1ec-active"),
                        a = u.data("freq"),
                        f = !0;
                    switch (a) {
                        case "daily":
                            o += "FREQ=DAILY;";
                            var l = e("#osec_daily_count").val();
                            l > 1 && (o += "INTERVAL=" + l + ";");
                            break;
                        case "weekly":
                            o += "FREQ=WEEKLY;";
                            var c = e("#osec_weekly_count").val();
                            c > 1 && (o += "INTERVAL=" + c + ";");
                            var h = e('input[name="osec_weekly_date_select"]:first').val(),
                                p = e('#osec_weekly_date_select > div:first > input[type="hidden"]:first').val();
                            h.length > 0 && (o += "WKST=" + p + ";BYday=" + h + ";");
                            break;
                        case "monthly":
                            o += "FREQ=MONTHLY;";
                            var d = e("#osec_monthly_count").val(),
                                v = e('input[name="osec_monthly_type"]:checked').val();
                            d > 1 && (o += "INTERVAL=" + d + ";");
                            var m = e('input[name="ai1ec_montly_date_select"]:first').val();
                            if (m.length > 0 && v === "bymonthday") o += "BYMONTHDAY=" + m + ";";
                            else if (v === "byday") {
                                var g = e("#osec_monthly_byday_num").val(),
                                    y = e("#osec_monthly_byday_weekday").val();
                                o += "BYday=" + g + y + ";";
                            }
                            break;
                        case "yearly":
                            o += "FREQ=YEARLY;";
                            var b = e("#osec_yearly_count").val();
                            b > 1 && (o += "INTERVAL=" + b + ";");
                            var w = e('input[name="osec_yearly_date_select"]:first').val();
                            w.length > 0 && (o += "BYMONTH=" + w + ";");
                            break;
                        case "custom":
                            "1" === e("#osec_is_box_repeat").val() ? (o += "RDATE=") : (o += "EXDATE="), (o += e("#osec_rec_custom_dates").val()), (f = !1);
                    }
                    var E = e("#osec_table_coordinates").val();
                    if ("1" === E && f) o += "COUNT=" + e("#osec_count").val() + ";";
                    else if ("2" === E && f) {
                        var S = e("#osec_until-date-input").val();
                        S = r.parseDate(S, t.date_format);
                        var x = e("#osec_table_coordinates-time").val();
                        (x = r.parseDate(x, t.date_format)), (x = new Date(x));
                        var T = S.getUTCDate(),
                            N = S.getUTCMonth() + 1,
                            C = x.getUTCHours(),
                            k = x.getUTCMinutes();
                        (N = N < 10 ? "0" + N : N), (T = T < 10 ? "0" + T : T), (C = C < 10 ? "0" + C : C), (k = k < 10 ? "0" + k : k), (S = S.getUTCFullYear() + "" + N + T + "T235959Z"), (o += "UNTIL=" + S + ";");
                    }
                    var L = {action: "osec_rrule_to_text", rrule: o};
                    i.button("loading").next().addClass("ai1ec-disabled"),
                        e.post(
                            s,
                            L,
                            function (t) {
                                t.error
                                    ? (i.button("reset").next().removeClass("ai1ec-disabled"),
                                        "1" === e("#osec_is_box_repeat").val() ? n.repeat_form_error("#osec_rrule", "#osec_repeat_label", t, i) : n.repeat_form_error("#osec_exrule", "#osec_exclude_label", t, i))
                                    : "1" === e("#osec_is_box_repeat").val()
                                        ? n.repeat_form_success("#osec_rrule", "#osec_repeat_label", "#osec_repeat_text > a", o, i, t)
                                        : n.repeat_form_success("#osec_exrule", "#osec_exclude_label", "#osec_exclude_text > a", o, i, t);
                            },
                            "json"
                        );
                },
                f = function () {
                    return (
                        e("#osec_is_box_repeat").val() === "1"
                            ? n.click_on_modal_cancel("#osec_repeat_text > a", "#osec_repeat", "#osec_repeat_label")
                            : n.click_on_modal_cancel("#osec_exclude_text > a", "#osec_exclude", "#osec_exclude_label"),
                            e("#osec_repeat_box").modal("hide"),
                            !1
                    );
                },
                l = function () {
                    e(this).is("#osec_monthly_type_bymonthday")
                        ? (e("#osec_repeat_monthly_byday").collapse("hide"), e("#osec_repeat_monthly_bymonthday").collapse("show"))
                        : (e("#osec_repeat_monthly_bymonthday").collapse("hide"), e("#osec_repeat_monthly_byday").collapse("show"));
                },
                c = function () {
                    var t = e(this),
                        n = [],
                        r = t.closest(".ai1ec-btn-group-grid"),
                        i;
                    t.toggleClass("ai1ec-active"),
                        e("a", r).each(function () {
                            var t = e(this);
                            t.is(".ai1ec-active") && ((i = t.next().val()), n.push(i));
                        }),
                        r.next().val(n.join());
                },
                h = function () {
                    n.click_on_ics_rule_text("#osec_repeat_text > a", "#osec_repeat", "#osec_repeat_label", {
                        action: "osec_get_repeat_box",
                        repeat: 1,
                        post_id: e("#post_ID").val()
                    }, n.init_modal_widgets),
                        n.click_on_ics_rule_text("#osec_exclude_text > a", "#osec_exclude", "#osec_exclude_label", {
                            action: "osec_get_repeat_box",
                            repeat: 0,
                            post_id: e("#post_ID").val()
                        }, n.init_modal_widgets),
                        n.click_on_checkbox("#osec_repeat", "#osec_repeat_text > a", "#osec_repeat_label", {
                            action: "osec_get_repeat_box",
                            repeat: 1,
                            post_id: e("#post_ID").val()
                        }, n.init_modal_widgets),
                        n.click_on_checkbox("#osec_exclude", "#osec_exclude_text > a", "#osec_exclude_label", {
                            action: "osec_get_repeat_box",
                            repeat: 0,
                            post_id: e("#post_ID").val()
                        }, n.init_modal_widgets);
                },
                p = function (t) {
                    return e("#osec_widget_calendar").toggle(), !1;
                },
                d = function () {
                    e(".ai1ec-modal-content", this).not(".ai1ec-loading ").remove().end().removeClass("ai1ec-hide");
                },
                v = function () {
                    var t = e("#osec_repeat_box").find("ul.ai1ec-nav").find("li.ai1ec-active"),
                        n = e("#osec_repeat_box").find(".ai1ec-end-field");
                    t.hasClass("ai1ec-freq-custom") ? n.addClass("ai1ec-hidden") : n.removeClass("ai1ec-hidden"), t.hasClass("ai1ec-freq-monthly") && l();
                },
                m = function () {
                    var t = e("#ai1ec-tab-content").data("activeFreq"),
                        n = e("#osec_recurrence_calendar");
                    e(".ai1ec-freq").removeClass("ai1ec-active"), e(".ai1ec-freq-" + t).addClass("ai1ec-active"), e(document).on("shown.bs.tab", v), o(), v();
                };
            return (
                e(document).on("ai1ec.recurrence-modal.inited", m),
                    {
                        show_end_fields: o,
                        trigger_publish: u,
                        handle_click_on_apply_button: a,
                        handle_click_on_cancel_modal: f,
                        handle_checkbox_monthly_tab_modal: l,
                        execute_pseudo_handlers: h,
                        handle_animation_of_calendar_widget: p,
                        handle_click_on_toggle_buttons: c,
                        handle_modal_hide: d,
                    }
            );
        }
    ),
    timely.define("scripts/add_new_event/event_cost_helper", ["jquery_timely", "ai1ec_config"], function (e, t) {
        var n = function () {
                return e("#osec_is_free_event").is(":checked");
            },
            r = function () {
                return e("#osec_cost").val() !== "";
            },
            i = function (r) {
                var i = e(this).parents("table:eq(0)"),
                    s = e("#osec_cost", i),
                    o = t.label_a_buy_tickets_url;
                n() ? (s.attr("value", "").addClass("ai1ec-hidden"), (o = t.label_a_rsvp_url)) : s.removeClass("ai1ec-hidden"), e("label[for=osec_ticket_url]", i).text(o);
            };
        return {handle_change_is_free: i, check_is_free: n, check_is_price_entered: r};
    }),
    timely.define("external_libs/jquery.inputdate", ["jquery_timely", "external_libs/jquery.calendrical_timespan"], function (e, t) {
        function n(e) {
            e.addClass("error").fadeOut("normal", function () {
                e.val(e.data("timespan.stored")).removeClass("error").fadeIn("fast");
            });
        }

        function r() {
            e(this).data("timespan.stored", this.value);
        }

        function i(e, n, i, s, o) {
            n.val(n.data("timespan.initial_value"));
            var u = parseInt(n.val());
            isNaN(parseInt(u)) ? (u = new Date(o)) : (u = new Date(parseInt(u) * 1e3)), e.val(t.formatDate(u, s)), e.each(r);
        }

        var s = {
                start_date_input: "date-input",
                start_time: "time",
                twentyfour_hour: !1,
                date_format: "def",
                now: new Date()
            },
            o = {
                init: function (o) {
                    var u = e.extend({}, s, o),
                        a = e(u.start_date_input),
                        f = e(u.start_time),
                        l = a,
                        c = a;
                    return (
                        c.bind("focus.timespan", r),
                            l.calendricalDate({
                                today: new Date(u.now.getFullYear(), u.now.getMonth(), u.now.getDate()),
                                dateFormat: u.date_format,
                                monthNames: u.month_names,
                                dayNames: u.day_names,
                                weekStartDay: u.week_start_day
                            }),
                            l.bind("blur.timespan", function () {
                                var r = t.parseDate(this.value, u.date_format);
                                isNaN(r) ? n(e(this)) : (e(this).data("timespan.stored", this.value), e(this).val(t.formatDate(r, u.date_format)));
                            }),
                            a
                                .bind("focus.timespan", function () {
                                    var e = t.parseDate(a.val(), u.date_format).getTime() / 1e3;
                                })
                                .bind("blur.timespan", function () {
                                    var e = t.parseDate(a.data("timespan.stored"), u.date_format);
                                }),
                            a.closest("form").bind("submit.timespan", function () {
                                var e = t.parseDate(a.val(), u.date_format).getTime() / 1e3;
                                isNaN(e) && (e = ""), f.val(e);
                            }),
                            f.data("timespan.initial_value", f.val()),
                            i(a, f, u.twentyfour_hour, u.date_format, u.now),
                            this
                    );
                },
                reset: function (t) {
                    var n = e.extend({}, s, t);
                    return i(e(n.start_date_input), e(n.start_time), n.twentyfour_hour, n.date_format, n.now), this;
                },
                destroy: function (t) {
                    return (
                        (t = e.extend({}, s, t)),
                            e.each(t, function (t, n) {
                                e(n).unbind(".timespan");
                            }),
                            e(t.start_date_input).closest("form").unbind(".timespan"),
                            this
                    );
                },
            };
        e.inputdate = function (t) {
            if (o[t]) return o[t].apply(this, Array.prototype.slice.call(arguments, 1));
            if (typeof t == "object" || !t) return o.init.apply(this, arguments);
            e.error("Method " + t + " does not exist on jQuery.timespan");
        };
    }),
    timely.define("external_libs/jquery.tools", ["jquery_timely"], function (e) {
        function i(e, t) {
            var n = Math.pow(10, t);
            return Math.round(e * n) / n;
        }

        function s(e, t) {
            var n = parseInt(e.css(t), 10);
            if (n) return n;
            var r = e[0].currentStyle;
            return r && r.width && parseInt(r.width, 10);
        }

        function o(e) {
            var t = e.data("events");
            return t && t.onSlide;
        }

        function u(t, n) {
            function x(e, s, o, u) {
                o === undefined ? (o = (s / h) * m) : u && (o -= n.min), g && (o = Math.round(o / g) * g);
                if (s === undefined || g) s = (o * h) / m;
                if (isNaN(o)) return r;
                (s = Math.max(0, Math.min(s, h))), (o = (s / h) * m);
                if (u || !f) o += n.min;
                f && (u ? (s = h - s) : (o = n.max - o)), (o = i(o, y));
                var a = e.type == "click";
                if (S && l !== undefined && !a) {
                    (e.type = "onSlide"), E.trigger(e, [o, s]);
                    if (e.isDefaultPrevented()) return r;
                }
                var c = a ? n.speed : 0,
                    b = a
                        ? function () {
                            (e.type = "change"), E.trigger(e, [o]);
                        }
                        : null;
                return (
                    f ? (d.animate({top: s}, c, b), n.progress && v.animate({height: h - s + d.height() / 2}, c)) : (d.animate({left: s}, c, b), n.progress && v.animate({width: s + d.width() / 2}, c)), (l = o), (p = s), t.val(o), r
                );
            }

            function T() {
                (f = n.vertical || s(a, "height") > s(a, "width")), f ? ((h = s(a, "height") - s(d, "height")), (c = a.offset().top + h)) : ((h = s(a, "width") - s(d, "width")), (c = a.offset().left));
            }

            function N() {
                T(), r.setValue(n.value !== undefined ? n.value : n.min);
            }

            var r = this,
                u = n.css,
                a = e("<div><div/><a href='#'/></div>").data("rangeinput", r),
                f,
                l,
                c,
                h,
                p;
            t.before(a);
            var d = a.addClass(u.slider).find("a").addClass(u.handle),
                v = a.find("div").addClass(u.progress);
            e.each("min,max,step,value".split(","), function (e, r) {
                var i = t.attr(r);
                parseFloat(i) && (n[r] = parseFloat(i, 10));
            });
            var m = n.max - n.min,
                g = n.step == "any" ? 0 : n.step,
                y = n.precision;
            y === undefined && ((y = g.toString().split(".")), (y = y.length === 2 ? y[1].length : 0));
            if (t.attr("type") == "range") {
                var b = t.clone().wrap("<div/>").parent().html(),
                    w = e(b.replace(/type/i, "type=text data-orig-type"));
                w.val(n.value), t.replaceWith(w), (t = w);
            }
            t.addClass(u.input);
            var E = e(r).add(t),
                S = !0;
            e.extend(r, {
                getValue: function () {
                    return l;
                },
                setValue: function (t, n) {
                    return T(), x(n || e.Event("api"), undefined, t, !0);
                },
                getConf: function () {
                    return n;
                },
                getProgress: function () {
                    return v;
                },
                getHandle: function () {
                    return d;
                },
                getInput: function () {
                    return t;
                },
                step: function (t, i) {
                    i = i || e.Event();
                    var s = n.step == "any" ? 1 : n.step;
                    r.setValue(l + s * (t || 1), i);
                },
                stepUp: function (e) {
                    return r.step(e || 1);
                },
                stepDown: function (e) {
                    return r.step(-e || -1);
                },
            }),
                e.each("onSlide,change".split(","), function (t, i) {
                    e.isFunction(n[i]) && e(r).on(i, n[i]),
                        (r[i] = function (t) {
                            return t && e(r).on(i, t), r;
                        });
                }),
                d
                    .drag({drag: !1})
                    .on("dragStart", function () {
                        T(), (S = o(e(r)) || o(t));
                    })
                    .on("drag", function (e, n, r) {
                        if (t.is(":disabled")) return !1;
                        x(e, f ? n : r);
                    })
                    .on("dragEnd", function (e) {
                        e.isDefaultPrevented() || ((e.type = "change"), E.trigger(e, [l]));
                    })
                    .click(function (e) {
                        return e.preventDefault();
                    }),
                a.click(function (e) {
                    if (t.is(":disabled") || e.target == d[0]) return e.preventDefault();
                    T();
                    var n = f ? d.height() / 2 : d.width() / 2;
                    x(e, f ? h - c - n + e.pageY : e.pageX - c - n);
                }),
            n.keyboard &&
            t.keydown(function (n) {
                if (t.attr("readonly")) return;
                var i = n.keyCode,
                    s = e([75, 76, 38, 33, 39]).index(i) != -1,
                    o = e([74, 72, 40, 34, 37]).index(i) != -1;
                if ((s || o) && !(n.shiftKey || n.altKey || n.ctrlKey)) return s ? r.step(i == 33 ? 10 : 1, n) : o && r.step(i == 34 ? -10 : -1, n), n.preventDefault();
            }),
                t.blur(function (t) {
                    var n = e(this).val();
                    n !== l && r.setValue(n, t);
                }),
                e.extend(t[0], {stepUp: r.stepUp, stepDown: r.stepDown}),
                N(),
            h || e(window).load(N);
        }

        e.tools = e.tools || {version: "1.2.7"};
        var t;
        t = e.tools.rangeinput = {
            conf: {
                min: 0,
                max: 100,
                step: "any",
                steps: 0,
                value: 0,
                precision: undefined,
                vertical: 0,
                keyboard: !0,
                progress: !1,
                speed: 100,
                css: {input: "range", slider: "slider", progress: "progress", handle: "handle"}
            },
        };
        var n, r;
        (e.fn.drag = function (t) {
            return (
                (document.ondragstart = function () {
                    return !1;
                }),
                    (t = e.extend({x: !0, y: !0, drag: !0}, t)),
                    (n =
                        n ||
                        e(document).on("mousedown mouseup", function (i) {
                            var s = e(i.target);
                            if (i.type == "mousedown" && s.data("drag")) {
                                var o = s.position(),
                                    u = i.pageX - o.left,
                                    a = i.pageY - o.top,
                                    f = !0;
                                n.on("mousemove.drag", function (e) {
                                    var n = e.pageX - u,
                                        i = e.pageY - a,
                                        o = {};
                                    t.x && (o.left = n), t.y && (o.top = i), f && (s.trigger("dragStart"), (f = !1)), t.drag && s.css(o), s.trigger("drag", [i, n]), (r = s);
                                }),
                                    i.preventDefault();
                            } else
                                try {
                                    r && r.trigger("dragEnd");
                                } finally {
                                    n.off("mousemove.drag"), (r = null);
                                }
                        })),
                    this.data("drag", !0)
            );
        }),
            (e.expr[":"].range = function (t) {
                var n = t.getAttribute("type");
                return (n && n == "range") || !!e(t).filter("input").data("rangeinput");
            }),
            (e.fn.rangeinput = function (n) {
                if (this.data("rangeinput")) return this;
                n = e.extend(!0, {}, t.conf, n);
                var r;
                return (
                    this.each(function () {
                        var t = new u(e(this), e.extend(!0, {}, n)),
                            i = t.getInput().data("rangeinput", t);
                        r = r ? r.add(i) : i;
                    }),
                        r ? r : this
                );
            });
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.bg", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.bg = {
                    days: ["Неделя", "Понеделник", "Вторник", "Сряда", "Четвъртък", "Петък", "Събота", "Неделя"],
                    daysShort: ["Нед", "Пон", "Вто", "Сря", "Чет", "Пет", "Съб", "Нед"],
                    daysMin: ["Н", "П", "В", "С", "Ч", "П", "С", "Н"],
                    months: ["Януари", "Февруари", "Март", "Април", "Май", "Юни", "Юли", "Август", "Септември", "Октомври", "Ноември", "Декември"],
                    monthsShort: ["Ян", "Фев", "Мар", "Апр", "Май", "Юни", "Юли", "Авг", "Сеп", "Окт", "Ное", "Дек"],
                    today: "днес",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.br", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.br = {
                    days: ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado", "Domingo"],
                    daysShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"],
                    daysMin: ["Do", "Se", "Te", "Qu", "Qu", "Se", "Sa", "Do"],
                    months: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"],
                    monthsShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.cs", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.cs = {
                    days: ["Neděle", "Pondělí", "Úterý", "Středa", "Čtvrtek", "Pátek", "Sobota", "Neděle"],
                    daysShort: ["Ned", "Pon", "Úte", "Stř", "Čtv", "Pát", "Sob", "Ned"],
                    daysMin: ["Ne", "Po", "Út", "St", "Čt", "Pá", "So", "Ne"],
                    months: ["Leden", "Únor", "Březen", "Duben", "Květen", "Červen", "Červenec", "Srpen", "Září", "Říjen", "Listopad", "Prosinec"],
                    monthsShort: ["Led", "Úno", "Bře", "Dub", "Kvě", "Čer", "Čnc", "Srp", "Zář", "Říj", "Lis", "Pro"],
                    today: "Dnes",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.da", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.da = {
                    days: ["Søndag", "Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "Lørdag", "Søndag"],
                    daysShort: ["Søn", "Man", "Tir", "Ons", "Tor", "Fre", "Lør", "Søn"],
                    daysMin: ["Sø", "Ma", "Ti", "On", "To", "Fr", "Lø", "Sø"],
                    months: ["Januar", "Februar", "Marts", "April", "Maj", "Juni", "Juli", "August", "September", "Oktober", "November", "December"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Maj", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dec"],
                    today: "I Dag",
                    clear: "Nulstil",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.de", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.de = {
                    days: ["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "Sonntag"],
                    daysShort: ["Son", "Mon", "Die", "Mit", "Don", "Fre", "Sam", "Son"],
                    daysMin: ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa", "So"],
                    months: ["Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember"],
                    monthsShort: ["Jan", "Feb", "Mär", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dez"],
                    today: "Heute",
                    clear: "Löschen",
                    weekStart: 1,
                    format: "dd.mm.yyyy",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.es", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.es = {
                    days: ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"],
                    daysShort: ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb", "Dom"],
                    daysMin: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa", "Do"],
                    months: ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
                    monthsShort: ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"],
                    today: "Hoy",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.fi", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.fi = {
                    days: ["sunnuntai", "maanantai", "tiistai", "keskiviikko", "torstai", "perjantai", "lauantai", "sunnuntai"],
                    daysShort: ["sun", "maa", "tii", "kes", "tor", "per", "lau", "sun"],
                    daysMin: ["su", "ma", "ti", "ke", "to", "pe", "la", "su"],
                    months: ["tammikuu", "helmikuu", "maaliskuu", "huhtikuu", "toukokuu", "kesäkuu", "heinäkuu", "elokuu", "syyskuu", "lokakuu", "marraskuu", "joulukuu"],
                    monthsShort: ["tam", "hel", "maa", "huh", "tou", "kes", "hei", "elo", "syy", "lok", "mar", "jou"],
                    today: "tänään",
                    weekStart: 1,
                    format: "d.m.yyyy",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.fr", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.fr = {
                    days: ["Dimanche", "Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche"],
                    daysShort: ["Dim", "Lun", "Mar", "Mer", "Jeu", "Ven", "Sam", "Dim"],
                    daysMin: ["D", "L", "Ma", "Me", "J", "V", "S", "D"],
                    months: ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"],
                    monthsShort: ["Jan", "Fév", "Mar", "Avr", "Mai", "Jui", "Jul", "Aou", "Sep", "Oct", "Nov", "Déc"],
                    today: "Aujourd'hui",
                    clear: "Effacer",
                    weekStart: 1,
                    format: "dd/mm/yyyy",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.id", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.id = {
                    days: ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu", "Minggu"],
                    daysShort: ["Mgu", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Mgu"],
                    daysMin: ["Mg", "Sn", "Sl", "Ra", "Ka", "Ju", "Sa", "Mg"],
                    months: ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ags", "Sep", "Okt", "Nov", "Des"],
                    today: "Hari Ini",
                    clear: "Kosongkan",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.is", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.is = {
                    days: ["Sunnudagur", "Mánudagur", "Þriðjudagur", "Miðvikudagur", "Fimmtudagur", "Föstudagur", "Laugardagur", "Sunnudagur"],
                    daysShort: ["Sun", "Mán", "Þri", "Mið", "Fim", "Fös", "Lau", "Sun"],
                    daysMin: ["Su", "Má", "Þr", "Mi", "Fi", "Fö", "La", "Su"],
                    months: ["Janúar", "Febrúar", "Mars", "Apríl", "Maí", "Júní", "Júlí", "Ágúst", "September", "Október", "Nóvember", "Desember"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Maí", "Jún", "Júl", "Ágú", "Sep", "Okt", "Nóv", "Des"],
                    today: "Í Dag",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.it", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.it = {
                    days: ["Domenica", "Lunedì", "Martedì", "Mercoledì", "Giovedì", "Venerdì", "Sabato", "Domenica"],
                    daysShort: ["Dom", "Lun", "Mar", "Mer", "Gio", "Ven", "Sab", "Dom"],
                    daysMin: ["Do", "Lu", "Ma", "Me", "Gi", "Ve", "Sa", "Do"],
                    months: ["Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"],
                    monthsShort: ["Gen", "Feb", "Mar", "Apr", "Mag", "Giu", "Lug", "Ago", "Set", "Ott", "Nov", "Dic"],
                    today: "Oggi",
                    clear: "Cancella",
                    weekStart: 1,
                    format: "dd/mm/yyyy",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.ja", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.ja = {
                    days: ["日曜", "月曜", "火曜", "水曜", "木曜", "金曜", "土曜", "日曜"],
                    daysShort: ["日", "月", "火", "水", "木", "金", "土", "日"],
                    daysMin: ["日", "月", "火", "水", "木", "金", "土", "日"],
                    months: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
                    monthsShort: ["1月", "2月", "3月", "4月", "5月", "6月", "7月", "8月", "9月", "10月", "11月", "12月"],
                    today: "今日",
                    format: "yyyy/mm/dd",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.kr", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.kr = {
                    days: ["일요일", "월요일", "화요일", "수요일", "목요일", "금요일", "토요일", "일요일"],
                    daysShort: ["일", "월", "화", "수", "목", "금", "토", "일"],
                    daysMin: ["일", "월", "화", "수", "목", "금", "토", "일"],
                    months: ["1월", "2월", "3월", "4월", "5월", "6월", "7월", "8월", "9월", "10월", "11월", "12월"],
                    monthsShort: ["1월", "2월", "3월", "4월", "5월", "6월", "7월", "8월", "9월", "10월", "11월", "12월"],
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.lt", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.lt = {
                    days: ["Sekmadienis", "Pirmadienis", "Antradienis", "Trečiadienis", "Ketvirtadienis", "Penktadienis", "Šeštadienis", "Sekmadienis"],
                    daysShort: ["S", "Pr", "A", "T", "K", "Pn", "Š", "S"],
                    daysMin: ["Sk", "Pr", "An", "Tr", "Ke", "Pn", "Št", "Sk"],
                    months: ["Sausis", "Vasaris", "Kovas", "Balandis", "Gegužė", "Birželis", "Liepa", "Rugpjūtis", "Rugsėjis", "Spalis", "Lapkritis", "Gruodis"],
                    monthsShort: ["Sau", "Vas", "Kov", "Bal", "Geg", "Bir", "Lie", "Rugp", "Rugs", "Spa", "Lap", "Gru"],
                    today: "Šiandien",
                    weekStart: 1,
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.lv", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.lv = {
                    days: ["Svētdiena", "Pirmdiena", "Otrdiena", "Trešdiena", "Ceturtdiena", "Piektdiena", "Sestdiena", "Svētdiena"],
                    daysShort: ["Sv", "P", "O", "T", "C", "Pk", "S", "Sv"],
                    daysMin: ["Sv", "Pr", "Ot", "Tr", "Ce", "Pk", "Se", "Sv"],
                    months: ["Janvāris", "Februāris", "Marts", "Aprīlis", "Maijs", "Jūnijs", "Jūlijs", "Augusts", "Septembris", "Oktobris", "Novembris", "Decembris"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Mai", "Jūn", "Jūl", "Aug", "Sep", "Okt", "Nov", "Dec"],
                    today: "Šodien",
                    weekStart: 1,
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.ms", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.ms = {
                    days: ["Ahad", "Isnin", "Selasa", "Rabu", "Khamis", "Jumaat", "Sabtu", "Ahad"],
                    daysShort: ["Aha", "Isn", "Sel", "Rab", "Kha", "Jum", "Sab", "Aha"],
                    daysMin: ["Ah", "Is", "Se", "Ra", "Kh", "Ju", "Sa", "Ah"],
                    months: ["Januari", "Februari", "Mac", "April", "Mei", "Jun", "Julai", "Ogos", "September", "Oktober", "November", "Disember"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Ogo", "Sep", "Okt", "Nov", "Dis"],
                    today: "Hari Ini",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.nb", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.nb = {
                    days: ["Søndag", "Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "Lørdag", "Søndag"],
                    daysShort: ["Søn", "Man", "Tir", "Ons", "Tor", "Fre", "Lør", "Søn"],
                    daysMin: ["Sø", "Ma", "Ti", "On", "To", "Fr", "Lø", "Sø"],
                    months: ["Januar", "Februar", "Mars", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Desember"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Mai", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Des"],
                    today: "I Dag",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.nl", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.nl = {
                    days: ["Zondag", "Maandag", "Dinsdag", "Woensdag", "Donderdag", "Vrijdag", "Zaterdag", "Zondag"],
                    daysShort: ["Zo", "Ma", "Di", "Wo", "Do", "Vr", "Za", "Zo"],
                    daysMin: ["Zo", "Ma", "Di", "Wo", "Do", "Vr", "Za", "Zo"],
                    months: ["Januari", "Februari", "Maart", "April", "Mei", "Juni", "Juli", "Augustus", "September", "Oktober", "November", "December"],
                    monthsShort: ["Jan", "Feb", "Mrt", "Apr", "Mei", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dec"],
                    today: "Vandaag",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.pl", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.pl = {
                    days: ["Niedziela", "Poniedziałek", "Wtorek", "Środa", "Czwartek", "Piątek", "Sobota", "Niedziela"],
                    daysShort: ["Nie", "Pn", "Wt", "Śr", "Czw", "Pt", "So", "Nie"],
                    daysMin: ["N", "Pn", "Wt", "Śr", "Cz", "Pt", "So", "N"],
                    months: ["Styczeń", "Luty", "Marzec", "Kwiecień", "Maj", "Czerwiec", "Lipiec", "Sierpień", "Wrzesień", "Październik", "Listopad", "Grudzień"],
                    monthsShort: ["Sty", "Lu", "Mar", "Kw", "Maj", "Cze", "Lip", "Sie", "Wrz", "Pa", "Lis", "Gru"],
                    today: "Dzisiaj",
                    weekStart: 1,
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.pt-BR", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates["pt-BR"] = {
                    days: ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado", "Domingo"],
                    daysShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"],
                    daysMin: ["Do", "Se", "Te", "Qu", "Qu", "Se", "Sa", "Do"],
                    months: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"],
                    monthsShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
                    today: "Hoje",
                    clear: "Limpar",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.pt", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.pt = {
                    days: ["Domingo", "Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado", "Domingo"],
                    daysShort: ["Dom", "Seg", "Ter", "Qua", "Qui", "Sex", "Sáb", "Dom"],
                    daysMin: ["Do", "Se", "Te", "Qu", "Qu", "Se", "Sa", "Do"],
                    months: ["Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro"],
                    monthsShort: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
                    today: "Hoje",
                    clear: "Limpar",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.ru", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.ru = {
                    days: ["Воскресенье", "Понедельник", "Вторник", "Среда", "Четверг", "Пятница", "Суббота", "Воскресенье"],
                    daysShort: ["Вск", "Пнд", "Втр", "Срд", "Чтв", "Птн", "Суб", "Вск"],
                    daysMin: ["Вс", "Пн", "Вт", "Ср", "Чт", "Пт", "Сб", "Вс"],
                    months: ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"],
                    monthsShort: ["Янв", "Фев", "Мар", "Апр", "Май", "Июн", "Июл", "Авг", "Сен", "Окт", "Ноя", "Дек"],
                    today: "Сегодня",
                    weekStart: 1,
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.sl", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.sl = {
                    days: ["Nedelja", "Ponedeljek", "Torek", "Sreda", "Četrtek", "Petek", "Sobota", "Nedelja"],
                    daysShort: ["Ned", "Pon", "Tor", "Sre", "Čet", "Pet", "Sob", "Ned"],
                    daysMin: ["Ne", "Po", "To", "Sr", "Če", "Pe", "So", "Ne"],
                    months: ["Januar", "Februar", "Marec", "April", "Maj", "Junij", "Julij", "Avgust", "September", "Oktober", "November", "December"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Maj", "Jun", "Jul", "Avg", "Sep", "Okt", "Nov", "Dec"],
                    today: "Danes",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.sv", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.sv = {
                    days: ["Söndag", "Måndag", "Tisdag", "Onsdag", "Torsdag", "Fredag", "Lördag", "Söndag"],
                    daysShort: ["Sön", "Mån", "Tis", "Ons", "Tor", "Fre", "Lör", "Sön"],
                    daysMin: ["Sö", "Må", "Ti", "On", "To", "Fr", "Lö", "Sö"],
                    months: ["Januari", "Februari", "Mars", "April", "Maj", "Juni", "Juli", "Augusti", "September", "Oktober", "November", "December"],
                    monthsShort: ["Jan", "Feb", "Mar", "Apr", "Maj", "Jun", "Jul", "Aug", "Sep", "Okt", "Nov", "Dec"],
                    today: "I Dag",
                    format: "yyyy-mm-dd",
                    weekStart: 1,
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.th", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.th = {
                    days: ["อาทิตย์", "จันทร์", "อังคาร", "พุธ", "พฤหัส", "ศุกร์", "เสาร์", "อาทิตย์"],
                    daysShort: ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส", "อา"],
                    daysMin: ["อา", "จ", "อ", "พ", "พฤ", "ศ", "ส", "อา"],
                    months: ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"],
                    monthsShort: ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."],
                    today: "วันนี้",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.tr", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates.tr = {
                    days: ["Pazar", "Pazartesi", "Salı", "Çarşamba", "Perşembe", "Cuma", "Cumartesi", "Pazar"],
                    daysShort: ["Pz", "Pzt", "Sal", "Çrş", "Prş", "Cu", "Cts", "Pz"],
                    daysMin: ["Pz", "Pzt", "Sa", "Çr", "Pr", "Cu", "Ct", "Pz"],
                    months: ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"],
                    monthsShort: ["Oca", "Şub", "Mar", "Nis", "May", "Haz", "Tem", "Ağu", "Eyl", "Eki", "Kas", "Ara"],
                    today: "Bugün",
                    format: "dd.mm.yyyy",
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.zh-CN", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates["zh-CN"] = {
                    days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
                    daysShort: ["周日", "周一", "周二", "周三", "周四", "周五", "周六", "周日"],
                    daysMin: ["日", "一", "二", "三", "四", "五", "六", "日"],
                    months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    today: "今日",
                    format: "yyyy年mm月dd日",
                    weekStart: 1,
                };
            },
        };
    }),
    timely.define("external_libs/locales/bootstrap-datepicker.zh-TW", ["jquery_timely"], function (e) {
        return {
            localize: function () {
                e.fn.datepicker.dates["zh-TW"] = {
                    days: ["星期日", "星期一", "星期二", "星期三", "星期四", "星期五", "星期六", "星期日"],
                    daysShort: ["週日", "週一", "週二", "週三", "週四", "週五", "週六", "週日"],
                    daysMin: ["日", "一", "二", "三", "四", "五", "六", "日"],
                    months: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    monthsShort: ["一月", "二月", "三月", "四月", "五月", "六月", "七月", "八月", "九月", "十月", "十一月", "十二月"],
                    today: "今天",
                    format: "yyyy年mm月dd日",
                    weekStart: 1,
                };
            },
        };
    }),
    timely.define(
        "external_libs/bootstrap_datepicker",
        [
            "jquery_timely",
            "ai1ec_config",
            "external_libs/locales/bootstrap-datepicker.bg",
            "external_libs/locales/bootstrap-datepicker.br",
            "external_libs/locales/bootstrap-datepicker.cs",
            "external_libs/locales/bootstrap-datepicker.da",
            "external_libs/locales/bootstrap-datepicker.de",
            "external_libs/locales/bootstrap-datepicker.es",
            "external_libs/locales/bootstrap-datepicker.fi",
            "external_libs/locales/bootstrap-datepicker.fr",
            "external_libs/locales/bootstrap-datepicker.id",
            "external_libs/locales/bootstrap-datepicker.is",
            "external_libs/locales/bootstrap-datepicker.it",
            "external_libs/locales/bootstrap-datepicker.ja",
            "external_libs/locales/bootstrap-datepicker.kr",
            "external_libs/locales/bootstrap-datepicker.lt",
            "external_libs/locales/bootstrap-datepicker.lv",
            "external_libs/locales/bootstrap-datepicker.ms",
            "external_libs/locales/bootstrap-datepicker.nb",
            "external_libs/locales/bootstrap-datepicker.nl",
            "external_libs/locales/bootstrap-datepicker.pl",
            "external_libs/locales/bootstrap-datepicker.pt-BR",
            "external_libs/locales/bootstrap-datepicker.pt",
            "external_libs/locales/bootstrap-datepicker.ru",
            "external_libs/locales/bootstrap-datepicker.sl",
            "external_libs/locales/bootstrap-datepicker.sv",
            "external_libs/locales/bootstrap-datepicker.th",
            "external_libs/locales/bootstrap-datepicker.tr",
            "external_libs/locales/bootstrap-datepicker.zh-CN",
            "external_libs/locales/bootstrap-datepicker.zh-TW",
        ],
        function (e, t) {
            function r() {
                return new Date(Date.UTC.apply(Date, arguments));
            }

            function i() {
                var e = new Date();
                return r(e.getFullYear(), e.getMonth(), e.getDate());
            }

            function s(e) {
                return function () {
                    return this[e].apply(this, arguments);
                };
            }

            function f(t, n) {
                var r = e(t).data(),
                    i = {},
                    s,
                    o = new RegExp("^" + n.toLowerCase() + "([A-Z])"),
                    n = new RegExp("^" + n.toLowerCase());
                for (var u in r)
                    n.test(u) &&
                    ((s = u.replace(o, function (e, t) {
                        return t.toLowerCase();
                    })),
                        (i[s] = r[u]));
                return i;
            }

            function l(t) {
                var n = {};
                if (!d[t]) {
                    t = t.split("-")[0];
                    if (!d[t]) return;
                }
                var r = d[t];
                return (
                    e.each(p, function (e, t) {
                        t in r && (n[t] = r[t]);
                    }),
                        n
                );
            }

            var n = e(window),
                o = (function () {
                    var t = {
                        get: function (e) {
                            return this.slice(e)[0];
                        },
                        contains: function (e) {
                            var t = e && e.valueOf();
                            for (var n = 0, r = this.length; n < r; n++) if (this[n].valueOf() === t) return n;
                            return -1;
                        },
                        remove: function (e) {
                            this.splice(e, 1);
                        },
                        replace: function (t) {
                            if (!t) return;
                            e.isArray(t) || (t = [t]), this.clear(), this.push.apply(this, t);
                        },
                        clear: function () {
                            this.splice(0);
                        },
                        copy: function () {
                            var e = new o();
                            return e.replace(this), e;
                        },
                    };
                    return function () {
                        var n = [];
                        return n.push.apply(n, arguments), e.extend(n, t), n;
                    };
                })(),
                u = function (t, n) {
                    (this.dates = new o()),
                        (this.viewDate = i()),
                        (this.focusDate = null),
                        this._process_options(n),
                        (this.element = e(t)),
                        (this.isInline = !1),
                        (this.isInput = this.element.is("input")),
                        (this.component = this.element.is(".ai1ec-date") ? this.element.find(".ai1ec-input-group, .ai1ec-input-group-addon, .ai1ec-btn") : !1),
                        (this.hasInput = this.component && this.element.find("input").length),
                    this.component && this.component.length === 0 && (this.component = !1),
                        (this.picker = e(v.template)),
                        this._buildEvents(),
                        this._attachEvents(),
                        this.isInline ? this.picker.addClass("ai1ec-datepicker-inline").appendTo(this.element) : this.picker.addClass("ai1ec-datepicker-dropdown ai1ec-dropdown-menu"),
                    this.o.rtl && this.picker.addClass("ai1ec-datepicker-rtl"),
                        (this.viewMode = this.o.startView),
                    this.o.calendarWeeks &&
                    this.picker.find("tfoot th.ai1ec-today").attr("colspan", function (e, t) {
                        return parseInt(t) + 1;
                    }),
                        (this._allow_update = !1),
                        this.setStartDate(this._o.startDate),
                        this.setEndDate(this._o.endDate),
                        this.setDaysOfWeekDisabled(this.o.daysOfWeekDisabled),
                        this.fillDow(),
                        this.fillMonths(),
                        (this._allow_update = !0),
                        this.update(),
                        this.showMode(),
                    this.isInline && this.show();
                };
            u.prototype = {
                constructor: u,
                _process_options: function (n) {
                    this._o = e.extend({}, this._o, n);
                    var r = (this.o = e.extend({}, this._o)),
                        i = r.language;
                    d[i] || ((i = i.split("-")[0]), d[i] || ((i = t.language), d[i] || (i = h.language))), (r.language = i);
                    switch (r.startView) {
                        case 2:
                        case "decade":
                            r.startView = 2;
                            break;
                        case 1:
                        case "year":
                            r.startView = 1;
                            break;
                        default:
                            r.startView = 0;
                    }
                    switch (r.minViewMode) {
                        case 1:
                        case "months":
                            r.minViewMode = 1;
                            break;
                        case 2:
                        case "years":
                            r.minViewMode = 2;
                            break;
                        default:
                            r.minViewMode = 0;
                    }
                    (r.startView = Math.max(r.startView, r.minViewMode)),
                    r.multidate !== !0 && ((r.multidate = Number(r.multidate) || !1), r.multidate !== !1 ? (r.multidate = Math.max(0, r.multidate)) : (r.multidate = 1)),
                        (r.multidateSeparator = String(r.multidateSeparator)),
                        (r.weekStart %= 7),
                        (r.weekEnd = (r.weekStart + 6) % 7);
                    var s = v.parseFormat(r.format);
                    r.startDate !== -Infinity &&
                    (r.startDate ? (r.startDate instanceof Date ? (r.startDate = this._local_to_utc(this._zero_time(r.startDate))) : (r.startDate = v.parseDate(r.startDate, s, r.language))) : (r.startDate = -Infinity)),
                    r.endDate !== Infinity && (r.endDate ? (r.endDate instanceof Date ? (r.endDate = this._local_to_utc(this._zero_time(r.endDate))) : (r.endDate = v.parseDate(r.endDate, s, r.language))) : (r.endDate = Infinity)),
                        (r.daysOfWeekDisabled = r.daysOfWeekDisabled || []),
                    e.isArray(r.daysOfWeekDisabled) || (r.daysOfWeekDisabled = r.daysOfWeekDisabled.split(/[,\s]*/)),
                        (r.daysOfWeekDisabled = e.map(r.daysOfWeekDisabled, function (e) {
                            return parseInt(e, 10);
                        }));
                    var o = String(r.orientation).toLowerCase().split(/\s+/g),
                        u = r.orientation.toLowerCase();
                    (o = e.grep(o, function (e) {
                        return /^auto|left|right|top|bottom$/.test(e);
                    })),
                        (r.orientation = {x: "auto", y: "auto"});
                    if (!!u && u !== "auto")
                        if (o.length === 1)
                            switch (o[0]) {
                                case "top":
                                case "bottom":
                                    r.orientation.y = o[0];
                                    break;
                                case "left":
                                case "right":
                                    r.orientation.x = o[0];
                            }
                        else
                            (u = e.grep(o, function (e) {
                                return /^left|right$/.test(e);
                            })),
                                (r.orientation.x = u[0] || "auto"),
                                (u = e.grep(o, function (e) {
                                    return /^top|bottom$/.test(e);
                                })),
                                (r.orientation.y = u[0] || "auto");
                },
                _events: [],
                _secondaryEvents: [],
                _applyEvents: function (e) {
                    for (var t = 0, n, r, i; t < e.length; t++) (n = e[t][0]), e[t].length == 2 ? ((r = undefined), (i = e[t][1])) : e[t].length == 3 && ((r = e[t][1]), (i = e[t][2])), n.on(i, r);
                },
                _unapplyEvents: function (e) {
                    for (var t = 0, n, r, i; t < e.length; t++) (n = e[t][0]), e[t].length == 2 ? ((i = undefined), (r = e[t][1])) : e[t].length == 3 && ((i = e[t][1]), (r = e[t][2])), n.off(r, i);
                },
                _buildEvents: function () {
                    this.isInput
                        ? (this._events = [
                            [
                                this.element,
                                {
                                    focus: e.proxy(this.show, this),
                                    keyup: e.proxy(function (t) {
                                        e.inArray(t.keyCode, [27, 37, 39, 38, 40, 32, 13, 9]) === -1 && this.update();
                                    }, this),
                                    keydown: e.proxy(this.keydown, this),
                                },
                            ],
                        ])
                        : this.component && this.hasInput
                            ? (this._events = [
                                [
                                    this.element.find("input"),
                                    {
                                        focus: e.proxy(this.show, this),
                                        keyup: e.proxy(function (t) {
                                            e.inArray(t.keyCode, [27, 37, 39, 38, 40, 32, 13, 9]) === -1 && this.update();
                                        }, this),
                                        keydown: e.proxy(this.keydown, this),
                                    },
                                ],
                                [this.component, {click: e.proxy(this.show, this)}],
                            ])
                            : this.element.is("div")
                                ? (this.isInline = !0)
                                : (this._events = [[this.element, {click: e.proxy(this.show, this)}]]),
                        this._events.push(
                            [
                                this.element,
                                "*",
                                {
                                    blur: e.proxy(function (e) {
                                        this._focused_from = e.target;
                                    }, this),
                                },
                            ],
                            [
                                this.element,
                                {
                                    blur: e.proxy(function (e) {
                                        this._focused_from = e.target;
                                    }, this),
                                },
                            ]
                        ),
                        (this._secondaryEvents = [
                            [this.picker, {click: e.proxy(this.click, this)}],
                            [e(window), {resize: e.proxy(this.place, this)}],
                            [
                                e(document),
                                {
                                    "mousedown touchstart": e.proxy(function (e) {
                                        this.element.is(e.target) || this.element.find(e.target).length || this.picker.is(e.target) || this.picker.find(e.target).length || this.hide();
                                    }, this),
                                },
                            ],
                        ]);
                },
                _attachEvents: function () {
                    this._detachEvents(), this._applyEvents(this._events);
                },
                _detachEvents: function () {
                    this._unapplyEvents(this._events);
                },
                _attachSecondaryEvents: function () {
                    this._detachSecondaryEvents(), this._applyEvents(this._secondaryEvents);
                },
                _detachSecondaryEvents: function () {
                    this._unapplyEvents(this._secondaryEvents);
                },
                _trigger: function (t, n) {
                    var r = n || this.dates.get(-1),
                        i = this._utc_to_local(r);
                    this.element.trigger({
                        type: t,
                        date: i,
                        dates: e.map(this.dates, this._utc_to_local),
                        format: e.proxy(function (e, t) {
                            arguments.length === 0 ? ((e = this.dates.length - 1), (t = this.o.format)) : typeof e == "string" && ((t = e), (e = this.dates.length - 1)), (t = t || this.o.format);
                            var n = this.dates.get(e);
                            return v.formatDate(n, t, this.o.language);
                        }, this),
                    });
                },
                show: function (e) {
                    this.isInline || this.picker.appendTo("body"),
                        this.picker.show(),
                        (this.height = this.component ? this.component.outerHeight() : this.element.outerHeight()),
                        this.place(),
                        this._attachSecondaryEvents(),
                        this._trigger("show");
                },
                hide: function () {
                    if (this.isInline) return;
                    if (!this.picker.is(":visible")) return;
                    (this.focusDate = null),
                        this.picker.hide().detach(),
                        this._detachSecondaryEvents(),
                        (this.viewMode = this.o.startView),
                        this.showMode(),
                    this.o.forceParse && ((this.isInput && this.element.val()) || (this.hasInput && this.element.find("input").val())) && this.setValue(),
                        this._trigger("hide");
                },
                remove: function () {
                    this.hide(), this._detachEvents(), this._detachSecondaryEvents(), this.picker.remove(), delete this.element.data().datepicker, this.isInput || delete this.element.data().date;
                },
                _utc_to_local: function (e) {
                    return e && new Date(e.getTime() + e.getTimezoneOffset() * 6e4);
                },
                _local_to_utc: function (e) {
                    return e && new Date(e.getTime() - e.getTimezoneOffset() * 6e4);
                },
                _zero_time: function (e) {
                    return e && new Date(e.getFullYear(), e.getMonth(), e.getDate());
                },
                _zero_utc_time: function (e) {
                    return e && new Date(Date.UTC(e.getUTCFullYear(), e.getUTCMonth(), e.getUTCDate()));
                },
                getDates: function () {
                    return e.map(this.dates, this._utc_to_local);
                },
                getUTCDates: function () {
                    return e.map(this.dates, function (e) {
                        return new Date(e);
                    });
                },
                getDate: function () {
                    return this._utc_to_local(this.getUTCDate());
                },
                getUTCDate: function () {
                    return new Date(this.dates.get(-1));
                },
                setDates: function () {
                    this.update.apply(this, arguments), this._trigger("changeDate"), this.setValue();
                },
                setUTCDates: function () {
                    this.update.apply(this, e.map(arguments, this._utc_to_local)), this._trigger("changeDate"), this.setValue();
                },
                setDate: s("setDates"),
                setUTCDate: s("setUTCDates"),
                setValue: function () {
                    var e = this.getFormattedDate();
                    this.isInput ? this.element.val(e).change() : this.component && this.element.find("input").val(e).change();
                },
                getFormattedDate: function (t) {
                    t === undefined && (t = this.o.format);
                    var n = this.o.language;
                    return e
                        .map(this.dates, function (e) {
                            return v.formatDate(e, t, n);
                        })
                        .join(this.o.multidateSeparator);
                },
                setStartDate: function (e) {
                    this._process_options({startDate: e}), this.update(), this.updateNavArrows();
                },
                setEndDate: function (e) {
                    this._process_options({endDate: e}), this.update(), this.updateNavArrows();
                },
                setDaysOfWeekDisabled: function (e) {
                    this._process_options({daysOfWeekDisabled: e}), this.update(), this.updateNavArrows();
                },
                place: function () {
                    if (this.isInline) return;
                    var t = this.picker.outerWidth(),
                        r = this.picker.outerHeight(),
                        i = 10,
                        s = n.width(),
                        o = n.height(),
                        u = n.scrollTop(),
                        a =
                            parseInt(
                                this.element
                                    .parents()
                                    .filter(function () {
                                        return e(this).css("z-index") != "auto";
                                    })
                                    .first()
                                    .css("z-index")
                            ) + 10,
                        f = this.component ? this.component.parent().offset() : this.element.offset(),
                        l = this.component ? this.component.outerHeight(!0) : this.element.outerHeight(!1),
                        c = this.component ? this.component.outerWidth(!0) : this.element.outerWidth(!1),
                        h = f.left,
                        p = f.top;
                    this.picker.removeClass("ai1ec-datepicker-orient-top ai1ec-datepicker-orient-bottom ai1ec-datepicker-orient-right ai1ec-datepicker-orient-left"),
                        this.o.orientation.x !== "auto"
                            ? (this.picker.addClass("ai1ec-datepicker-orient-" + this.o.orientation.x), this.o.orientation.x === "right" && (h -= t - c))
                            : (this.picker.addClass("ai1ec-datepicker-orient-left"), f.left < 0 ? (h -= f.left - i) : f.left + t > s && (h = s - t - i));
                    var d = this.o.orientation.y,
                        v,
                        m;
                    d === "auto" && ((v = -u + f.top - r), (m = u + o - (f.top + l + r)), Math.max(v, m) === m ? (d = "top") : (d = "bottom")),
                        this.picker.addClass("ai1ec-datepicker-orient-" + d),
                        d === "top" ? (p += l) : (p -= r + parseInt(this.picker.css("padding-top"))),
                        this.picker.css({top: p, left: h, zIndex: a});
                },
                _allow_update: !0,
                update: function () {
                    if (!this._allow_update) return;
                    var t = this.dates.copy(),
                        n = [],
                        r = !1;
                    arguments.length
                        ? (e.each(
                            arguments,
                            e.proxy(function (e, t) {
                                t instanceof Date && (t = this._local_to_utc(t)), n.push(t);
                            }, this)
                        ),
                            (r = !0))
                        : ((n = this.isInput ? this.element.val() : this.element.data("date") || this.element.find("input").val()),
                            n && this.o.multidate ? (n = n.split(this.o.multidateSeparator)) : (n = [n]),
                            delete this.element.data().date),
                        (n = e.map(
                            n,
                            e.proxy(function (e) {
                                return v.parseDate(e, this.o.format, this.o.language);
                            }, this)
                        )),
                        (n = e.grep(
                            n,
                            e.proxy(function (e) {
                                return e < this.o.startDate || e > this.o.endDate || !e;
                            }, this),
                            !0
                        )),
                        this.dates.replace(n),
                        this.dates.length
                            ? (this.viewDate = new Date(this.dates.get(-1)))
                            : this.viewDate < this.o.startDate
                                ? (this.viewDate = new Date(this.o.startDate))
                                : this.viewDate > this.o.endDate && (this.viewDate = new Date(this.o.endDate)),
                        r ? this.setValue() : n.length && String(t) !== String(this.dates) && this._trigger("changeDate"),
                    !this.dates.length && t.length && this._trigger("clearDate"),
                        this.fill();
                },
                fillDow: function () {
                    var e = this.o.weekStart,
                        t = "<tr>";
                    if (this.o.calendarWeeks) {
                        var n = '<th class="ai1ec-cw">&nbsp;</th>';
                        (t += n), this.picker.find(".ai1ec-datepicker-days thead tr:first-child").prepend(n);
                    }
                    while (e < this.o.weekStart + 7) t += '<th class="ai1ec-dow">' + d[this.o.language].daysMin[e++ % 7] + "</th>";
                    (t += "</tr>"), this.picker.find(".ai1ec-datepicker-days thead").append(t);
                },
                fillMonths: function () {
                    var e = "",
                        t = 0;
                    while (t < 12) e += '<span class="ai1ec-month">' + d[this.o.language].monthsShort[t++] + "</span>";
                    this.picker.find(".ai1ec-datepicker-months td").html(e);
                },
                setRange: function (t) {
                    !t || !t.length
                        ? delete this.range
                        : (this.range = e.map(t, function (e) {
                            return e.valueOf();
                        })),
                        this.fill();
                },
                getClassNames: function (t) {
                    var n = [],
                        r = this.viewDate.getUTCFullYear(),
                        i = this.viewDate.getUTCMonth(),
                        s = new Date();
                    return (
                        t.getUTCFullYear() < r || (t.getUTCFullYear() == r && t.getUTCMonth() < i) ? n.push("ai1ec-old") : (t.getUTCFullYear() > r || (t.getUTCFullYear() == r && t.getUTCMonth() > i)) && n.push("ai1ec-new"),
                        this.focusDate && t.valueOf() === this.focusDate.valueOf() && n.push("ai1ec-focused"),
                        this.o.todayHighlight && t.getUTCFullYear() == s.getFullYear() && t.getUTCMonth() == s.getMonth() && t.getUTCDate() == s.getDate() && n.push("ai1ec-today"),
                        this.dates.contains(t) !== -1 && n.push("ai1ec-active"),
                        (t.valueOf() < this.o.startDate || t.valueOf() > this.o.endDate || e.inArray(t.getUTCDay(), this.o.daysOfWeekDisabled) !== -1) && n.push("ai1ec-disabled"),
                        this.range && (t > this.range[0] && t < this.range[this.range.length - 1] && n.push("ai1ec-range"), e.inArray(t.valueOf(), this.range) != -1 && n.push("ai1ec-selected")),
                            n
                    );
                },
                fill: function () {
                    var t = new Date(this.viewDate),
                        n = t.getUTCFullYear(),
                        i = t.getUTCMonth(),
                        s = this.o.startDate !== -Infinity ? this.o.startDate.getUTCFullYear() : -Infinity,
                        o = this.o.startDate !== -Infinity ? this.o.startDate.getUTCMonth() : -Infinity,
                        u = this.o.endDate !== Infinity ? this.o.endDate.getUTCFullYear() : Infinity,
                        a = this.o.endDate !== Infinity ? this.o.endDate.getUTCMonth() : Infinity,
                        f,
                        l;
                    this.picker.find(".ai1ec-datepicker-days thead th.ai1ec-datepicker-switch").text(d[this.o.language].months[i] + " " + n),
                        this.picker
                            .find("tfoot th.ai1ec-today")
                            .text(d[this.o.language].today)
                            .toggle(this.o.todayBtn !== !1),
                        this.picker
                            .find("tfoot th.ai1ec-clear")
                            .text(d[this.o.language].clear)
                            .toggle(this.o.clearBtn !== !1),
                        this.updateNavArrows(),
                        this.fillMonths();
                    var c = r(n, i - 1, 28),
                        h = v.getDaysInMonth(c.getUTCFullYear(), c.getUTCMonth());
                    c.setUTCDate(h), c.setUTCDate(h - ((c.getUTCDay() - this.o.weekStart + 7) % 7));
                    var p = new Date(c);
                    p.setUTCDate(p.getUTCDate() + 42), (p = p.valueOf());
                    var m = [],
                        g;
                    while (c.valueOf() < p) {
                        if (c.getUTCDay() == this.o.weekStart) {
                            m.push("<tr>");
                            if (this.o.calendarWeeks) {
                                var y = new Date(+c + ((this.o.weekStart - c.getUTCDay() - 7) % 7) * 864e5),
                                    b = new Date(+y + ((11 - y.getUTCDay()) % 7) * 864e5),
                                    w = new Date(+(w = r(b.getUTCFullYear(), 0, 1)) + ((11 - w.getUTCDay()) % 7) * 864e5),
                                    E = (b - w) / 864e5 / 7 + 1;
                                m.push('<td class="ai1ec-cw">' + E + "</td>");
                            }
                        }
                        (g = this.getClassNames(c)), g.push("ai1ec-day");
                        if (this.o.beforeShowDay !== e.noop) {
                            var S = this.o.beforeShowDay(this._utc_to_local(c));
                            S === undefined ? (S = {}) : typeof S == "boolean" ? (S = {enabled: S}) : typeof S == "string" && (S = {classes: S}),
                            S.enabled === !1 && g.push("ai1ec-disabled"),
                            S.classes && (g = g.concat(S.classes.split(/\s+/))),
                            S.tooltip && (f = S.tooltip);
                        }
                        (g = e.unique(g)), m.push('<td class="' + g.join(" ") + '"' + (f ? ' title="' + f + '"' : "") + ">" + c.getUTCDate() + "</td>"), c.getUTCDay() == this.o.weekEnd && m.push("</tr>"), c.setUTCDate(c.getUTCDate() + 1);
                    }
                    this.picker.find(".ai1ec-datepicker-days tbody").empty().append(m.join(""));
                    var x = this.picker.find(".ai1ec-datepicker-months").find("th:eq(1)").text(n).end().find("span").removeClass("ai1ec-active");
                    e.each(this.dates, function (e, t) {
                        t.getUTCFullYear() == n && x.eq(t.getUTCMonth()).addClass("ai1ec-active");
                    }),
                    (n < s || n > u) && x.addClass("ai1ec-disabled"),
                    n == s && x.slice(0, o).addClass("ai1ec-disabled"),
                    n == u && x.slice(a + 1).addClass("ai1ec-disabled"),
                        (m = ""),
                        (n = parseInt(n / 10, 10) * 10);
                    var T = this.picker
                        .find(".ai1ec-datepicker-years")
                        .find("th:eq(1)")
                        .text(n + "-" + (n + 9))
                        .end()
                        .find("td");
                    n -= 1;
                    var N = e.map(this.dates, function (e) {
                            return e.getUTCFullYear();
                        }),
                        C;
                    for (var k = -1; k < 11; k++)
                        (C = ["ai1ec-year"]),
                            k === -1 ? C.push("ai1ec-old") : k === 10 && C.push("ai1ec-new"),
                        e.inArray(n, N) !== -1 && C.push("ai1ec-active"),
                        (n < s || n > u) && C.push("ai1ec-disabled"),
                            (m += '<span class="' + C.join(" ") + '">' + n + "</span>"),
                            (n += 1);
                    T.html(m);
                },
                updateNavArrows: function () {
                    if (!this._allow_update) return;
                    var e = new Date(this.viewDate),
                        t = e.getUTCFullYear(),
                        n = e.getUTCMonth();
                    switch (this.viewMode) {
                        case 0:
                            this.o.startDate !== -Infinity && t <= this.o.startDate.getUTCFullYear() && n <= this.o.startDate.getUTCMonth()
                                ? this.picker.find(".ai1ec-prev").css({visibility: "hidden"})
                                : this.picker.find(".ai1ec-prev").css({visibility: "visible"}),
                                this.o.endDate !== Infinity && t >= this.o.endDate.getUTCFullYear() && n >= this.o.endDate.getUTCMonth()
                                    ? this.picker.find(".ai1ec-next").css({visibility: "hidden"})
                                    : this.picker.find(".ai1ec-next").css({visibility: "visible"});
                            break;
                        case 1:
                        case 2:
                            this.o.startDate !== -Infinity && t <= this.o.startDate.getUTCFullYear() ? this.picker.find(".ai1ec-prev").css({visibility: "hidden"}) : this.picker.find(".ai1ec-prev").css({visibility: "visible"}),
                                this.o.endDate !== Infinity && t >= this.o.endDate.getUTCFullYear() ? this.picker.find(".ai1ec-next").css({visibility: "hidden"}) : this.picker.find(".ai1ec-next").css({visibility: "visible"});
                    }
                },
                click: function (t) {
                    t.preventDefault();
                    var n = e(t.target).closest("span, td, th"),
                        i,
                        s,
                        o;
                    if (n.length == 1)
                        switch (n[0].nodeName.toLowerCase()) {
                            case "th":
                                switch (n[0].className) {
                                    case "ai1ec-datepicker-switch":
                                        this.showMode(1);
                                        break;
                                    case "ai1ec-prev":
                                    case "ai1ec-next":
                                        var u = v.modes[this.viewMode].navStep * (n[0].className == "ai1ec-prev" ? -1 : 1);
                                        switch (this.viewMode) {
                                            case 0:
                                                (this.viewDate = this.moveMonth(this.viewDate, u)), this._trigger("changeMonth", this.viewDate);
                                                break;
                                            case 1:
                                            case 2:
                                                (this.viewDate = this.moveYear(this.viewDate, u)), this.viewMode === 1 && this._trigger("changeYear", this.viewDate);
                                        }
                                        this.fill();
                                        break;
                                    case "ai1ec-today":
                                        var a = new Date();
                                        (a = r(a.getFullYear(), a.getMonth(), a.getDate(), 0, 0, 0)), this.showMode(-2);
                                        var f = this.o.todayBtn == "linked" ? null : "view";
                                        this._setDate(a, f);
                                        break;
                                    case "ai1ec-clear":
                                        var l;
                                        this.isInput ? (l = this.element) : this.component && (l = this.element.find("input")), l && l.val("").change(), this.update(), this._trigger("changeDate"), this.o.autoclose && this.hide();
                                }
                                break;
                            case "span":
                                n.is(".ai1ec-disabled") ||
                                (this.viewDate.setUTCDate(1),
                                    n.is(".ai1ec-month")
                                        ? ((o = 1),
                                            (s = n.parent().find("span").index(n)),
                                            (i = this.viewDate.getUTCFullYear()),
                                            this.viewDate.setUTCMonth(s),
                                            this._trigger("changeMonth", this.viewDate),
                                        this.o.minViewMode === 1 && this._setDate(r(i, s, o)))
                                        : ((o = 1), (s = 0), (i = parseInt(n.text(), 10) || 0), this.viewDate.setUTCFullYear(i), this._trigger("changeYear", this.viewDate), this.o.minViewMode === 2 && this._setDate(r(i, s, o))),
                                    this.showMode(-1),
                                    this.fill());
                                break;
                            case "td":
                                n.is(".ai1ec-day") &&
                                !n.is(".ai1ec-disabled") &&
                                ((o = parseInt(n.text(), 10) || 1),
                                    (i = this.viewDate.getUTCFullYear()),
                                    (s = this.viewDate.getUTCMonth()),
                                    n.is(".ai1ec-old") ? (s === 0 ? ((s = 11), (i -= 1)) : (s -= 1)) : n.is(".ai1ec-new") && (s == 11 ? ((s = 0), (i += 1)) : (s += 1)),
                                    this._setDate(r(i, s, o)));
                        }
                    this.picker.is(":visible") && this._focused_from && e(this._focused_from).focus(), delete this._focused_from;
                },
                _toggle_multidate: function (e) {
                    var t = this.dates.contains(e);
                    e ? (t !== -1 ? this.dates.remove(t) : this.dates.push(e)) : this.dates.clear();
                    if (typeof this.o.multidate == "number") while (this.dates.length > this.o.multidate) this.dates.remove(0);
                },
                _setDate: function (e, t) {
                    (!t || t == "date") && this._toggle_multidate(e && new Date(e));
                    if (!t || t == "view") this.viewDate = e && new Date(e);
                    this.fill(), this.setValue(), this._trigger("changeDate");
                    var n;
                    this.isInput ? (n = this.element) : this.component && (n = this.element.find("input")), n && n.change(), this.o.autoclose && (!t || t == "date") && this.hide();
                },
                moveMonth: function (e, t) {
                    if (!e) return undefined;
                    if (!t) return e;
                    var n = new Date(e.valueOf()),
                        r = n.getUTCDate(),
                        i = n.getUTCMonth(),
                        s = Math.abs(t),
                        o,
                        u;
                    t = t > 0 ? 1 : -1;
                    if (s == 1) {
                        (u =
                            t == -1
                                ? function () {
                                    return n.getUTCMonth() == i;
                                }
                                : function () {
                                    return n.getUTCMonth() != o;
                                }),
                            (o = i + t),
                            n.setUTCMonth(o);
                        if (o < 0 || o > 11) o = (o + 12) % 12;
                    } else {
                        for (var a = 0; a < s; a++) n = this.moveMonth(n, t);
                        (o = n.getUTCMonth()),
                            n.setUTCDate(r),
                            (u = function () {
                                return o != n.getUTCMonth();
                            });
                    }
                    while (u()) n.setUTCDate(--r), n.setUTCMonth(o);
                    return n;
                },
                moveYear: function (e, t) {
                    return this.moveMonth(e, t * 12);
                },
                dateWithinRange: function (e) {
                    return e >= this.o.startDate && e <= this.o.endDate;
                },
                keydown: function (e) {
                    if (this.picker.is(":not(:visible)")) {
                        e.keyCode == 27 && this.show();
                        return;
                    }
                    var t = !1,
                        n,
                        r,
                        s,
                        o = this.focusDate || this.viewDate;
                    switch (e.keyCode) {
                        case 27:
                            this.focusDate ? ((this.focusDate = null), (this.viewDate = this.dates.get(-1) || this.viewDate), this.fill()) : this.hide(), e.preventDefault();
                            break;
                        case 37:
                        case 39:
                            if (!this.o.keyboardNavigation) break;
                            (n = e.keyCode == 37 ? -1 : 1),
                                e.ctrlKey
                                    ? ((r = this.moveYear(this.dates.get(-1) || i(), n)), (s = this.moveYear(o, n)), this._trigger("changeYear", this.viewDate))
                                    : e.shiftKey
                                        ? ((r = this.moveMonth(this.dates.get(-1) || i(), n)), (s = this.moveMonth(o, n)), this._trigger("changeMonth", this.viewDate))
                                        : ((r = new Date(this.dates.get(-1) || i())), r.setUTCDate(r.getUTCDate() + n), (s = new Date(o)), s.setUTCDate(o.getUTCDate() + n)),
                            this.dateWithinRange(r) && ((this.focusDate = this.viewDate = s), this.setValue(), this.fill(), e.preventDefault());
                            break;
                        case 38:
                        case 40:
                            if (!this.o.keyboardNavigation) break;
                            (n = e.keyCode == 38 ? -1 : 1),
                                e.ctrlKey
                                    ? ((r = this.moveYear(this.dates.get(-1) || i(), n)), (s = this.moveYear(o, n)), this._trigger("changeYear", this.viewDate))
                                    : e.shiftKey
                                        ? ((r = this.moveMonth(this.dates.get(-1) || i(), n)), (s = this.moveMonth(o, n)), this._trigger("changeMonth", this.viewDate))
                                        : ((r = new Date(this.dates.get(-1) || i())), r.setUTCDate(r.getUTCDate() + n * 7), (s = new Date(o)), s.setUTCDate(o.getUTCDate() + n * 7)),
                            this.dateWithinRange(r) && ((this.focusDate = this.viewDate = s), this.setValue(), this.fill(), e.preventDefault());
                            break;
                        case 32:
                            break;
                        case 13:
                            (o = this.focusDate || this.dates.get(-1) || this.viewDate),
                                this._toggle_multidate(o),
                                (t = !0),
                                (this.focusDate = null),
                                (this.viewDate = this.dates.get(-1) || this.viewDate),
                                this.setValue(),
                                this.fill(),
                            this.picker.is(":visible") && (e.preventDefault(), this.o.autoclose && this.hide());
                            break;
                        case 9:
                            (this.focusDate = null), (this.viewDate = this.dates.get(-1) || this.viewDate), this.fill(), this.hide();
                    }
                    if (t) {
                        this.dates.length ? this._trigger("changeDate") : this._trigger("clearDate");
                        var u;
                        this.isInput ? (u = this.element) : this.component && (u = this.element.find("input")), u && u.change();
                    }
                },
                showMode: function (e) {
                    e && (this.viewMode = Math.max(this.o.minViewMode, Math.min(2, this.viewMode + e))),
                        this.picker
                            .find(">div")
                            .hide()
                            .filter(".ai1ec-datepicker-" + v.modes[this.viewMode].clsName)
                            .css("display", "block"),
                        this.updateNavArrows();
                },
            };
            var a = function (t, n) {
                (this.element = e(t)),
                    (this.inputs = e.map(n.inputs, function (e) {
                        return e.jquery ? e[0] : e;
                    })),
                    delete n.inputs,
                    e(this.inputs).datepicker(n).bind("changeDate", e.proxy(this.dateUpdated, this)),
                    (this.pickers = e.map(this.inputs, function (t) {
                        return e(t).data("datepicker");
                    })),
                    this.updateDates();
            };
            a.prototype = {
                updateDates: function () {
                    (this.dates = e.map(this.pickers, function (e) {
                        return e.getUTCDate();
                    })),
                        this.updateRanges();
                },
                updateRanges: function () {
                    var t = e.map(this.dates, function (e) {
                        return e.valueOf();
                    });
                    e.each(this.pickers, function (e, n) {
                        n.setRange(t);
                    });
                },
                dateUpdated: function (t) {
                    if (this.updating) return;
                    this.updating = !0;
                    var n = e(t.target).data("datepicker"),
                        r = n.getUTCDate(),
                        i = e.inArray(t.target, this.inputs),
                        s = this.inputs.length;
                    if (i == -1) return;
                    e.each(this.pickers, function (e, t) {
                        t.getUTCDate() || t.setUTCDate(r);
                    });
                    if (r < this.dates[i]) while (i >= 0 && r < this.dates[i]) this.pickers[i--].setUTCDate(r);
                    else if (r > this.dates[i]) while (i < s && r > this.dates[i]) this.pickers[i++].setUTCDate(r);
                    this.updateDates(), delete this.updating;
                },
                remove: function () {
                    e.map(this.pickers, function (e) {
                        e.remove();
                    }),
                        delete this.element.data().datepicker;
                },
            };
            var c = e.fn.datepicker;
            e.fn.datepicker = function (t) {
                var n = Array.apply(null, arguments);
                n.shift();
                var r;
                return (
                    this.each(function () {
                        var i = e(this),
                            s = i.data("datepicker"),
                            o = typeof t == "object" && t;
                        if (!s) {
                            var c = f(this, "date"),
                                p = e.extend({}, h, c, o),
                                d = l(p.language),
                                v = e.extend({}, h, d, c, o);
                            if (i.is(".ai1ec-input-daterange") || v.inputs) {
                                var m = {inputs: v.inputs || i.find("input").toArray()};
                                i.data("datepicker", (s = new a(this, e.extend(v, m))));
                            } else i.data("datepicker", (s = new u(this, v)));
                        }
                        if (typeof t == "string" && typeof s[t] == "function") {
                            r = s[t].apply(s, n);
                            if (r !== undefined) return !1;
                        }
                    }),
                        r !== undefined ? r : this
                );
            };
            var h = (e.fn.datepicker.defaults = {
                    autoclose: !1,
                    beforeShowDay: e.noop,
                    calendarWeeks: !1,
                    clearBtn: !1,
                    daysOfWeekDisabled: [],
                    endDate: Infinity,
                    forceParse: !0,
                    format: "mm/dd/yyyy",
                    keyboardNavigation: !0,
                    language: "en",
                    minViewMode: 0,
                    multidate: !1,
                    multidateSeparator: ",",
                    orientation: "auto",
                    rtl: !1,
                    startDate: -Infinity,
                    startView: 0,
                    todayBtn: !1,
                    todayHighlight: !1,
                    weekStart: 0,
                }),
                p = (e.fn.datepicker.locale_opts = ["format", "rtl", "weekStart"]);
            e.fn.datepicker.Constructor = u;
            var d = (e.fn.datepicker.dates = {
                    en: {
                        days: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"],
                        daysShort: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
                        daysMin: ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"],
                        months: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                        monthsShort: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                        today: "Today",
                        clear: "Clear",
                    },
                }),
                v = {
                    modes: [
                        {clsName: "days", navFnc: "Month", navStep: 1},
                        {clsName: "months", navFnc: "FullYear", navStep: 1},
                        {clsName: "years", navFnc: "FullYear", navStep: 10},
                    ],
                    isLeapYear: function (e) {
                        return (e % 4 === 0 && e % 100 !== 0) || e % 400 === 0;
                    },
                    getDaysInMonth: function (e, t) {
                        return [31, v.isLeapYear(e) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][t];
                    },
                    validParts: /dd?|DD?|mm?|MM?|yy(?:yy)?/g,
                    nonpunctuation: /[^ -\/:-@\[\u3400-\u9fff-`{-~\t\n\r]+/g,
                    parseFormat: function (e) {
                        var t = e.replace(this.validParts, "\0").split("\0"),
                            n = e.match(this.validParts);
                        if (!t || !t.length || !n || n.length === 0) throw new Error("Invalid date format.");
                        return {separators: t, parts: n};
                    },
                    parseDate: function (t, n, i) {
                        if (!t) return undefined;
                        if (t instanceof Date) return t;
                        typeof n == "string" && (n = v.parseFormat(n));
                        if (/^[\-+]\d+[dmwy]([\s,]+[\-+]\d+[dmwy])*$/.test(t)) {
                            var s = /([\-+]\d+)([dmwy])/,
                                o = t.match(/([\-+]\d+)([dmwy])/g),
                                a,
                                f;
                            t = new Date();
                            for (var l = 0; l < o.length; l++) {
                                (a = s.exec(o[l])), (f = parseInt(a[1]));
                                switch (a[2]) {
                                    case "d":
                                        t.setUTCDate(t.getUTCDate() + f);
                                        break;
                                    case "m":
                                        t = u.prototype.moveMonth.call(u.prototype, t, f);
                                        break;
                                    case "w":
                                        t.setUTCDate(t.getUTCDate() + f * 7);
                                        break;
                                    case "y":
                                        t = u.prototype.moveYear.call(u.prototype, t, f);
                                }
                            }
                            return r(t.getUTCFullYear(), t.getUTCMonth(), t.getUTCDate(), 0, 0, 0);
                        }
                        var o = (t && t.match(this.nonpunctuation)) || [],
                            t = new Date(),
                            c = {},
                            h = ["yyyy", "yy", "M", "MM", "m", "mm", "d", "dd"],
                            p = {
                                yyyy: function (e, t) {
                                    return e.setUTCFullYear(t);
                                },
                                yy: function (e, t) {
                                    return e.setUTCFullYear(2e3 + t);
                                },
                                m: function (e, t) {
                                    if (isNaN(e)) return e;
                                    t -= 1;
                                    while (t < 0) t += 12;
                                    (t %= 12), e.setUTCMonth(t);
                                    while (e.getUTCMonth() != t) e.setUTCDate(e.getUTCDate() - 1);
                                    return e;
                                },
                                d: function (e, t) {
                                    return e.setUTCDate(t);
                                },
                            },
                            m,
                            g,
                            a;
                        (p.M = p.MM = p.mm = p.m), (p.dd = p.d), (t = r(t.getFullYear(), t.getMonth(), t.getDate(), 0, 0, 0));
                        var y = n.parts.slice();
                        o.length != y.length &&
                        (y = e(y)
                            .filter(function (t, n) {
                                return e.inArray(n, h) !== -1;
                            })
                            .toArray());
                        if (o.length == y.length) {
                            for (var l = 0, b = y.length; l < b; l++) {
                                (m = parseInt(o[l], 10)), (a = y[l]);
                                if (isNaN(m))
                                    switch (a) {
                                        case "MM":
                                            (g = e(d[i].months).filter(function () {
                                                var e = this.slice(0, o[l].length),
                                                    t = o[l].slice(0, e.length);
                                                return e == t;
                                            })),
                                                (m = e.inArray(g[0], d[i].months) + 1);
                                            break;
                                        case "M":
                                            (g = e(d[i].monthsShort).filter(function () {
                                                var e = this.slice(0, o[l].length),
                                                    t = o[l].slice(0, e.length);
                                                return e == t;
                                            })),
                                                (m = e.inArray(g[0], d[i].monthsShort) + 1);
                                    }
                                c[a] = m;
                            }
                            for (var l = 0, w, E; l < h.length; l++) (E = h[l]), E in c && !isNaN(c[E]) && ((w = new Date(t)), p[E](w, c[E]), isNaN(w) || (t = w));
                        }
                        return t;
                    },
                    formatDate: function (t, n, r) {
                        if (!t) return "";
                        typeof n == "string" && (n = v.parseFormat(n));
                        var i = {
                            d: t.getUTCDate(),
                            D: d[r].daysShort[t.getUTCDay()],
                            DD: d[r].days[t.getUTCDay()],
                            m: t.getUTCMonth() + 1,
                            M: d[r].monthsShort[t.getUTCMonth()],
                            MM: d[r].months[t.getUTCMonth()],
                            yy: t.getUTCFullYear().toString().substring(2),
                            yyyy: t.getUTCFullYear(),
                        };
                        (i.dd = (i.d < 10 ? "0" : "") + i.d), (i.mm = (i.m < 10 ? "0" : "") + i.m);
                        var t = [],
                            s = e.extend([], n.separators);
                        for (var o = 0, u = n.parts.length; o <= u; o++) s.length && t.push(s.shift()), t.push(i[n.parts[o]]);
                        return t.join("");
                    },
                    headTemplate:
                        '<thead><tr><th class="ai1ec-prev"><i class="ai1ec-fa ai1ec-fa-arrow-left"></i></th><th colspan="5" class="ai1ec-datepicker-switch"></th><th class="ai1ec-next"><i class="ai1ec-fa ai1ec-fa-arrow-right"></i></th></tr></thead>',
                    contTemplate: '<tbody><tr><td colspan="7"></td></tr></tbody>',
                    footTemplate: '<tfoot><tr><th colspan="7" class="ai1ec-today"></th></tr><tr><th colspan="7" class="ai1ec-clear"></th></tr></tfoot>',
                };
            (v.template =
                '<div class="timely ai1ec-datepicker"><div class="ai1ec-datepicker-days"><table class=" ai1ec-table-condensed">' +
                v.headTemplate +
                "<tbody></tbody>" +
                v.footTemplate +
                "</table>" +
                "</div>" +
                '<div class="ai1ec-datepicker-months">' +
                '<table class="ai1ec-table-condensed">' +
                v.headTemplate +
                v.contTemplate +
                v.footTemplate +
                "</table>" +
                "</div>" +
                '<div class="ai1ec-datepicker-years">' +
                '<table class="ai1ec-table-condensed">' +
                v.headTemplate +
                v.contTemplate +
                v.footTemplate +
                "</table>" +
                "</div>" +
                "</div>"),
                (e.fn.datepicker.DPGlobal = v),
                (e.fn.datepicker.noConflict = function () {
                    return (e.fn.datepicker = c), this;
                }),
                e(document).on("focus.datepicker.data-api click.datepicker.data-api", '[data-provide="datepicker"]', function (t) {
                    var n = e(this);
                    if (n.data("datepicker")) return;
                    t.preventDefault(), n.datepicker("show");
                }),
                e(function () {
                    e('[data-provide="datepicker-inline"]').datepicker();
                });
            for (var m = 2, g = arguments.length; m < g; m++) arguments[m].localize();
        }
    ),
    timely.define("external_libs/bootstrap/transition", ["jquery_timely"], function (e) {
        function t() {
            var e = document.createElement("bootstrap"),
                t = {
                    WebkitTransition: "webkitTransitionEnd",
                    MozTransition: "transitionend",
                    OTransition: "oTransitionEnd otransitionend",
                    transition: "transitionend"
                };
            for (var n in t) if (e.style[n] !== undefined) return {end: t[n]};
        }

        (e.fn.emulateTransitionEnd = function (t) {
            var n = !1,
                r = this;
            e(this).one(e.support.transition.end, function () {
                n = !0;
            });
            var i = function () {
                n || e(r).trigger(e.support.transition.end);
            };
            return setTimeout(i, t), this;
        }),
            e(function () {
                e.support.transition = t();
            });
    }),
    timely.define("external_libs/bootstrap/collapse", ["jquery_timely"], function (e) {
        var t = function (n, r) {
            (this.$element = e(n)), (this.options = e.extend({}, t.DEFAULTS, r)), (this.transitioning = null), this.options.parent && (this.$parent = e(this.options.parent)), this.options.toggle && this.toggle();
        };
        (t.DEFAULTS = {toggle: !0}),
            (t.prototype.dimension = function () {
                var e = this.$element.hasClass("ai1ec-width");
                return e ? "width" : "height";
            }),
            (t.prototype.show = function () {
                if (this.transitioning || this.$element.hasClass("ai1ec-in")) return;
                var t = e.Event("show.bs.collapse");
                this.$element.trigger(t);
                if (t.isDefaultPrevented()) return;
                var n = this.$parent && this.$parent.find("> .ai1ec-panel > .ai1ec-in");
                if (n && n.length) {
                    var r = n.data("bs.collapse");
                    if (r && r.transitioning) return;
                    n.collapse("hide"), r || n.data("bs.collapse", null);
                }
                var i = this.dimension();
                this.$element.removeClass("ai1ec-collapse").addClass("ai1ec-collapsing")[i](0), (this.transitioning = 1);
                var s = function () {
                    this.$element.removeClass("ai1ec-collapsing").addClass("ai1ec-in")[i]("auto"), (this.transitioning = 0), this.$element.trigger("shown.bs.collapse");
                };
                if (!e.support.transition) return s.call(this);
                var o = e.camelCase(["scroll", i].join("-"));
                this.$element.one(e.support.transition.end, e.proxy(s, this)).emulateTransitionEnd(350)[i](this.$element[0][o]);
            }),
            (t.prototype.hide = function () {
                if (this.transitioning || !this.$element.hasClass("ai1ec-in")) return;
                var t = e.Event("hide.bs.collapse");
                this.$element.trigger(t);
                if (t.isDefaultPrevented()) return;
                var n = this.dimension();
                this.$element[n](this.$element[n]())[0].offsetHeight, this.$element.addClass("ai1ec-collapsing").removeClass("ai1ec-collapse").removeClass("ai1ec-in"), (this.transitioning = 1);
                var r = function () {
                    (this.transitioning = 0), this.$element.trigger("hidden.bs.collapse").removeClass("ai1ec-collapsing").addClass("ai1ec-collapse");
                };
                if (!e.support.transition) return r.call(this);
                this.$element[n](0).one(e.support.transition.end, e.proxy(r, this)).emulateTransitionEnd(350);
            }),
            (t.prototype.toggle = function () {
                this[this.$element.hasClass("ai1ec-in") ? "hide" : "show"]();
            });
        var n = e.fn.collapse;
        (e.fn.collapse = function (n) {
            return this.each(function () {
                var r = e(this),
                    i = r.data("bs.collapse"),
                    s = e.extend({}, t.DEFAULTS, r.data(), typeof n == "object" && n);
                i || r.data("bs.collapse", (i = new t(this, s))), typeof n == "string" && i[n]();
            });
        }),
            (e.fn.collapse.Constructor = t),
            (e.fn.collapse.noConflict = function () {
                return (e.fn.collapse = n), this;
            }),
            e(document).on("click.bs.collapse.data-api", "[data-toggle=ai1ec-collapse]", function (t) {
                var n = e(this),
                    r,
                    i = n.attr("data-target") || t.preventDefault() || ((r = n.attr("href")) && r.replace(/.*(?=#[^\s]+$)/, "")),
                    s = e(i),
                    o = s.data("bs.collapse"),
                    u = o ? "toggle" : n.data(),
                    a = n.attr("data-parent"),
                    f = a && e(a);
                if (!o || !o.transitioning)
                    f &&
                    f
                        .find('[data-toggle=ai1ec-collapse][data-parent="' + a + '"]')
                        .not(n)
                        .addClass("ai1ec-collapsed"),
                        n[s.hasClass("ai1ec-in") ? "addClass" : "removeClass"]("ai1ec-collapsed");
                s.collapse(u);
            });
    }),
    timely.define("external_libs/bootstrap/modal", ["jquery_timely"], function (e) {
        var t = function (t, n) {
            (this.options = n), (this.$element = e(t)), (this.$backdrop = this.isShown = null), this.options.remote && this.$element.load(this.options.remote);
        };
        (t.DEFAULTS = {backdrop: !0, keyboard: !0, show: !0}),
            (t.prototype.toggle = function (e) {
                return this[this.isShown ? "hide" : "show"](e);
            }),
            (t.prototype.show = function (t) {
                var n = this,
                    r = e.Event("show.bs.modal", {relatedTarget: t});
                this.$element.trigger(r);
                if (this.isShown || r.isDefaultPrevented()) return;
                (this.isShown = !0),
                    this.escape(),
                    this.$element.on("click.dismiss.modal", '[data-dismiss="ai1ec-modal"]', e.proxy(this.hide, this)),
                    this.backdrop(function () {
                        var r = e.support.transition && n.$element.hasClass("ai1ec-fade");
                        n.$element.parent().length || n.$element.appendTo(document.body), n.$element.show(), r && n.$element[0].offsetWidth, n.$element.addClass("ai1ec-in").attr("aria-hidden", !1), n.enforceFocus();
                        var i = e.Event("shown.bs.modal", {relatedTarget: t});
                        r
                            ? n.$element
                                .find(".ai1ec-modal-dialog")
                                .one(e.support.transition.end, function () {
                                    n.$element.focus().trigger(i);
                                })
                                .emulateTransitionEnd(300)
                            : n.$element.focus().trigger(i);
                    });
            }),
            (t.prototype.hide = function (t) {
                t && t.preventDefault(), (t = e.Event("hide.bs.modal")), this.$element.trigger(t);
                if (!this.isShown || t.isDefaultPrevented()) return;
                (this.isShown = !1),
                    this.escape(),
                    e(document).off("focusin.bs.modal"),
                    this.$element.removeClass("ai1ec-in").attr("aria-hidden", !0).off("click.dismiss.modal"),
                    e.support.transition && this.$element.hasClass("ai1ec-fade") ? this.$element.one(e.support.transition.end, e.proxy(this.hideModal, this)).emulateTransitionEnd(300) : this.hideModal();
            }),
            (t.prototype.enforceFocus = function () {
                e(document)
                    .off("focusin.bs.modal")
                    .on(
                        "focusin.bs.modal",
                        e.proxy(function (e) {
                            this.$element[0] !== e.target && !this.$element.has(e.target).length && this.$element.focus();
                        }, this)
                    );
            }),
            (t.prototype.escape = function () {
                this.isShown && this.options.keyboard
                    ? this.$element.on(
                        "keyup.dismiss.bs.modal",
                        e.proxy(function (e) {
                            e.which == 27 && this.hide();
                        }, this)
                    )
                    : this.isShown || this.$element.off("keyup.dismiss.bs.modal");
            }),
            (t.prototype.hideModal = function () {
                var e = this;
                this.$element.hide(),
                    this.backdrop(function () {
                        e.removeBackdrop(), e.$element.trigger("hidden.bs.modal");
                    });
            }),
            (t.prototype.removeBackdrop = function () {
                this.$backdrop && this.$backdrop.remove(), (this.$backdrop = null);
            }),
            (t.prototype.backdrop = function (t) {
                var n = this,
                    r = this.$element.hasClass("ai1ec-fade") ? "ai1ec-fade" : "";
                if (this.isShown && this.options.backdrop) {
                    var i = e.support.transition && r;
                    (this.$backdrop = e('<div class="ai1ec-modal-backdrop ' + r + '" />').appendTo(document.body)),
                        this.$element.on(
                            "click.dismiss.modal",
                            e.proxy(function (e) {
                                if (e.target !== e.currentTarget) return;
                                this.options.backdrop == "static" ? this.$element[0].focus.call(this.$element[0]) : this.hide.call(this);
                            }, this)
                        ),
                    i && this.$backdrop[0].offsetWidth,
                        this.$backdrop.addClass("ai1ec-in");
                    if (!t) return;
                    i ? this.$backdrop.one(e.support.transition.end, t).emulateTransitionEnd(150) : t();
                } else
                    !this.isShown && this.$backdrop
                        ? (this.$backdrop.removeClass("ai1ec-in"), e.support.transition && this.$element.hasClass("ai1ec-fade") ? this.$backdrop.one(e.support.transition.end, t).emulateTransitionEnd(150) : t())
                        : t && t();
            });
        var n = e.fn.modal;
        (e.fn.modal = function (n, r) {
            return this.each(function () {
                var i = e(this),
                    s = i.data("bs.modal"),
                    o = e.extend({}, t.DEFAULTS, i.data(), typeof n == "object" && n);
                s || i.data("bs.modal", (s = new t(this, o))), typeof n == "string" ? s[n](r) : o.show && s.show(r);
            });
        }),
            (e.fn.modal.Constructor = t),
            (e.fn.modal.noConflict = function () {
                return (e.fn.modal = n), this;
            }),
            e(document).on("click.bs.modal.data-api", '[data-toggle="ai1ec-modal"]', function (t) {
                var n = e(this),
                    r = n.attr("href"),
                    i = e(n.attr("data-target") || (r && r.replace(/.*(?=#[^\s]+$)/, ""))),
                    s = i.data("modal") ? "toggle" : e.extend({remote: !/#/.test(r) && r}, i.data(), n.data());
                t.preventDefault(),
                    i.modal(s, this).one("hide", function () {
                        n.is(":visible") && n.focus();
                    });
            }),
            e(document)
                .on("show.bs.modal", ".ai1ec-modal", function () {
                    e(document.body).addClass("ai1ec-modal-open");
                })
                .on("hidden.bs.modal", ".ai1ec-modal", function () {
                    e(document.body).removeClass("ai1ec-modal-open");
                });
    }),
    timely.define("external_libs/bootstrap/alert", ["jquery_timely"], function (e) {
        var t = '[data-dismiss="ai1ec-alert"]',
            n = function (n) {
                e(n).on("click", t, this.close);
            };
        n.prototype.close = function (t) {
            function s() {
                i.trigger("closed.bs.alert").remove();
            }

            var n = e(this),
                r = n.attr("data-target");
            r || ((r = n.attr("href")), (r = r && r.replace(/.*(?=#[^\s]*$)/, "")));
            var i = e(r);
            t && t.preventDefault(), i.length || (i = n.hasClass("ai1ec-alert") ? n : n.parent()), i.trigger((t = e.Event("close.bs.alert")));
            if (t.isDefaultPrevented()) return;
            i.removeClass("ai1ec-in"), e.support.transition && i.hasClass("ai1ec-fade") ? i.one(e.support.transition.end, s).emulateTransitionEnd(150) : s();
        };
        var r = e.fn.alert;
        (e.fn.alert = function (t) {
            return this.each(function () {
                var r = e(this),
                    i = r.data("bs.alert");
                i || r.data("bs.alert", (i = new n(this))), typeof t == "string" && i[t].call(r);
            });
        }),
            (e.fn.alert.Constructor = n),
            (e.fn.alert.noConflict = function () {
                return (e.fn.alert = r), this;
            }),
            e(document).on("click.bs.alert.data-api", t, n.prototype.close);
    }),
    timely.define("external_libs/select2", ["jquery_timely"], function (e) {
        (function (e) {
            typeof e.fn.each2 == "undefined" &&
            e.fn.extend({
                each2: function (t) {
                    var n = e([0]),
                        r = -1,
                        i = this.length;
                    while (++r < i && (n.context = n[0] = this[r]) && t.call(n[0], r, n) !== !1) ;
                    return this;
                },
            });
        })(e),
            (function (e, t) {
                function l(e, t) {
                    var n = 0,
                        r = t.length;
                    for (; n < r; n += 1) if (c(e, t[n])) return n;
                    return -1;
                }

                function c(e, n) {
                    return e === n ? !0 : e === t || n === t ? !1 : e === null || n === null ? !1 : e.constructor === String ? e === n + "" : n.constructor === String ? n === e + "" : !1;
                }

                function h(t, n) {
                    var r, i, s;
                    if (t === null || t.length < 1) return [];
                    r = t.split(n);
                    for (i = 0, s = r.length; i < s; i += 1) r[i] = e.trim(r[i]);
                    return r;
                }

                function p(e) {
                    return e.outerWidth(!1) - e.width();
                }

                function d(n) {
                    var r = "keyup-change-value";
                    n.bind("keydown", function () {
                        e.data(n, r) === t && e.data(n, r, n.val());
                    }),
                        n.bind("keyup", function () {
                            var i = e.data(n, r);
                            i !== t && n.val() !== i && (e.removeData(n, r), n.trigger("keyup-change"));
                        });
                }

                function v(n) {
                    n.bind("mousemove", function (n) {
                        var r = a;
                        (r === t || r.x !== n.pageX || r.y !== n.pageY) && e(n.target).trigger("mousemove-filtered", n);
                    });
                }

                function m(e, n, r) {
                    r = r || t;
                    var i;
                    return function () {
                        var t = arguments;
                        window.clearTimeout(i),
                            (i = window.setTimeout(function () {
                                n.apply(r, t);
                            }, e));
                    };
                }

                function g(e) {
                    var t = !1,
                        n;
                    return function () {
                        return t === !1 && ((n = e()), (t = !0)), n;
                    };
                }

                function y(e, t) {
                    var n = m(e, function (e) {
                        t.trigger("scroll-debounced", e);
                    });
                    t.bind("scroll", function (e) {
                        l(e.target, t.get()) >= 0 && n(e);
                    });
                }

                function b(e) {
                    if (e[0] === document.activeElement) return;
                    window.setTimeout(function () {
                        var t = e[0],
                            n = e.val().length,
                            r;
                        e.focus(), t.setSelectionRange ? t.setSelectionRange(n, n) : t.createTextRange && ((r = t.createTextRange()), r.collapse(!0), r.moveEnd("character", n), r.moveStart("character", n), r.select());
                    }, 0);
                }

                function w(e) {
                    e.preventDefault(), e.stopPropagation();
                }

                function E(e) {
                    e.preventDefault(), e.stopImmediatePropagation();
                }

                function S(t) {
                    if (!u) {
                        var n = t[0].currentStyle || window.getComputedStyle(t[0], null);
                        (u = e(document.createElement("div")).css({
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
                            whiteSpace: "nowrap",
                        })),
                            u.attr("class", "select2-sizer"),
                            e("body").append(u);
                    }
                    return u.text(t.val()), u.width();
                }

                function x(t, n, r) {
                    var i,
                        s = [],
                        o;
                    (i = t.attr("class")),
                    typeof i == "string" &&
                    e(i.split(" ")).each2(function () {
                        this.indexOf("select2-") === 0 && s.push(this);
                    }),
                        (i = n.attr("class")),
                    typeof i == "string" &&
                    e(i.split(" ")).each2(function () {
                        this.indexOf("select2-") !== 0 && ((o = r(this)), typeof o == "string" && o.length > 0 && s.push(this));
                    }),
                        t.attr("class", s.join(" "));
                }

                function T(e, t, n, r) {
                    var i = e.toUpperCase().indexOf(t.toUpperCase()),
                        s = t.length;
                    if (i < 0) {
                        n.push(r(e));
                        return;
                    }
                    n.push(r(e.substring(0, i))), n.push("<span class='select2-match'>"), n.push(r(e.substring(i, i + s))), n.push("</span>"), n.push(r(e.substring(i + s, e.length)));
                }

                function N(t) {
                    var n,
                        r = 0,
                        i = null,
                        s = t.quietMillis || 100,
                        o = t.url,
                        u = this;
                    return function (a) {
                        window.clearTimeout(n),
                            (n = window.setTimeout(function () {
                                r += 1;
                                var n = r,
                                    s = t.data,
                                    f = o,
                                    l = t.transport || e.ajax,
                                    c = t.type || "GET",
                                    h = {};
                                (s = s ? s.call(u, a.term, a.page, a.context) : null),
                                    (f = typeof f == "function" ? f.call(u, a.term, a.page, a.context) : f),
                                null !== i && i.abort(),
                                t.params && (e.isFunction(t.params) ? e.extend(h, t.params.call(u)) : e.extend(h, t.params)),
                                    e.extend(h, {
                                        url: f,
                                        dataType: t.dataType,
                                        data: s,
                                        type: c,
                                        cache: !1,
                                        success: function (e) {
                                            if (n < r) return;
                                            var i = t.results(e, a.page);
                                            a.callback(i);
                                        },
                                    }),
                                    (i = l.call(u, h));
                            }, s));
                    };
                }

                function C(t) {
                    var n = t,
                        r,
                        i,
                        s = function (e) {
                            return "" + e.text;
                        };
                    e.isArray(n) && ((i = n), (n = {results: i})),
                    e.isFunction(n) === !1 &&
                    ((i = n),
                        (n = function () {
                            return i;
                        }));
                    var o = n();
                    return (
                        o.text &&
                        ((s = o.text),
                        e.isFunction(s) ||
                        ((r = n.text),
                            (s = function (e) {
                                return e[r];
                            }))),
                            function (t) {
                                var r = t.term,
                                    i = {results: []},
                                    o;
                                if (r === "") {
                                    t.callback(n());
                                    return;
                                }
                                (o = function (n, i) {
                                    var u, a;
                                    n = n[0];
                                    if (n.children) {
                                        u = {};
                                        for (a in n) n.hasOwnProperty(a) && (u[a] = n[a]);
                                        (u.children = []),
                                            e(n.children).each2(function (e, t) {
                                                o(t, u.children);
                                            }),
                                        (u.children.length || t.matcher(r, s(u), n)) && i.push(u);
                                    } else t.matcher(r, s(n), n) && i.push(n);
                                }),
                                    e(n().results).each2(function (e, t) {
                                        o(t, i.results);
                                    }),
                                    t.callback(i);
                            }
                    );
                }

                function k(n) {
                    var r = e.isFunction(n);
                    return function (i) {
                        var s = i.term,
                            o = {results: []};
                        e(r ? n() : n).each(function () {
                            var e = this.text !== t,
                                n = e ? this.text : this;
                            (s === "" || i.matcher(s, n)) && o.results.push(e ? this : {id: this, text: this});
                        }),
                            i.callback(o);
                    };
                }

                function L(t, n) {
                    if (e.isFunction(t)) return !0;
                    if (!t) return !1;
                    throw new Error("formatterName must be a function or a falsy value");
                }

                function A(t) {
                    return e.isFunction(t) ? t() : t;
                }

                function O(t) {
                    var n = 0;
                    return (
                        e.each(t, function (e, t) {
                            t.children ? (n += O(t.children)) : n++;
                        }),
                            n
                    );
                }

                function M(e, n, r, i) {
                    var s = e,
                        o = !1,
                        u,
                        a,
                        f,
                        l,
                        h;
                    if (!i.createSearchChoice || !i.tokenSeparators || i.tokenSeparators.length < 1) return t;
                    for (; ;) {
                        a = -1;
                        for (f = 0, l = i.tokenSeparators.length; f < l; f++) {
                            (h = i.tokenSeparators[f]), (a = e.indexOf(h));
                            if (a >= 0) break;
                        }
                        if (a < 0) break;
                        (u = e.substring(0, a)), (e = e.substring(a + h.length));
                        if (u.length > 0) {
                            u = i.createSearchChoice(u, n);
                            if (u !== t && u !== null && i.id(u) !== t && i.id(u) !== null) {
                                o = !1;
                                for (f = 0, l = n.length; f < l; f++)
                                    if (c(i.id(u), i.id(n[f]))) {
                                        o = !0;
                                        break;
                                    }
                                o || r(u);
                            }
                        }
                    }
                    if (s !== e) return e;
                }

                function _(t, n) {
                    var r = function () {
                    };
                    return (r.prototype = new t()), (r.prototype.constructor = r), (r.prototype.parent = t.prototype), (r.prototype = e.extend(r.prototype, n)), r;
                }

                var n, r, i, s, o, u, a, f;
                (n = {
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
                                return !0;
                        }
                        return !1;
                    },
                    isControl: function (e) {
                        var t = e.which;
                        switch (t) {
                            case n.SHIFT:
                            case n.CTRL:
                            case n.ALT:
                                return !0;
                        }
                        return e.metaKey ? !0 : !1;
                    },
                    isFunctionKey: function (e) {
                        return (e = e.which ? e.which : e), e >= 112 && e <= 123;
                    },
                }),
                    (f = e(document)),
                    (o = (function () {
                        var e = 1;
                        return function () {
                            return e++;
                        };
                    })()),
                    f.bind("mousemove", function (e) {
                        a = {x: e.pageX, y: e.pageY};
                    }),
                    (r = _(Object, {
                        bind: function (e) {
                            var t = this;
                            return function () {
                                e.apply(t, arguments);
                            };
                        },
                        init: function (n) {
                            var r,
                                i,
                                s = ".select2-results",
                                u;
                            (this.opts = n = this.prepareOpts(n)),
                                (this.id = n.id),
                            n.element.data("select2") !== t && n.element.data("select2") !== null && this.destroy(),
                                (this.enabled = !0),
                                (this.container = this.createContainer()),
                                (this.containerId = "s2id_" + (n.element.attr("id") || "autogen" + o())),
                                (this.containerSelector = "#" + this.containerId.replace(/([;&,\.\+\*\~':"\!\^#$%@\[\]\(\)=>\|])/g, "\\$1")),
                                this.container.attr("id", this.containerId),
                                (this.body = g(function () {
                                    return n.element.closest("body");
                                })),
                                x(this.container, this.opts.element, this.opts.adaptContainerCssClass),
                                this.container.css(A(n.containerCss)),
                                this.container.addClass(A(n.containerCssClass)),
                                (this.elementTabIndex = this.opts.element.attr("tabIndex")),
                                this.opts.element
                                    .data("select2", this)
                                    .addClass("select2-offscreen")
                                    .bind("focus.select2", function () {
                                        e(this).select2("focus");
                                    })
                                    .attr("tabIndex", "-1")
                                    .before(this.container),
                                this.container.data("select2", this),
                                (this.dropdown = this.container.find(".select2-drop")),
                                this.dropdown.addClass(A(n.dropdownCssClass)),
                                this.dropdown.data("select2", this),
                                (this.results = r = this.container.find(s)),
                                (this.search = i = this.container.find("input.select2-input")),
                                i.attr("tabIndex", this.elementTabIndex),
                                (this.resultsPage = 0),
                                (this.context = null),
                                this.initContainer(),
                                v(this.results),
                                this.dropdown.delegate(s, "mousemove-filtered touchstart touchmove touchend", this.bind(this.highlightUnderEvent)),
                                y(80, this.results),
                                this.dropdown.delegate(s, "scroll-debounced", this.bind(this.loadMoreIfNeeded)),
                            e.fn.mousewheel &&
                            r.mousewheel(function (e, t, n, i) {
                                var s = r.scrollTop(),
                                    o;
                                i > 0 && s - i <= 0 ? (r.scrollTop(0), w(e)) : i < 0 && r.get(0).scrollHeight - r.scrollTop() + i <= r.height() && (r.scrollTop(r.get(0).scrollHeight - r.height()), w(e));
                            }),
                                d(i),
                                i.bind("keyup-change input paste", this.bind(this.updateResults)),
                                i.bind("focus", function () {
                                    i.addClass("select2-focused");
                                }),
                                i.bind("blur", function () {
                                    i.removeClass("select2-focused");
                                }),
                                this.dropdown.delegate(
                                    s,
                                    "mouseup",
                                    this.bind(function (t) {
                                        e(t.target).closest(".select2-result-selectable").length > 0 && (this.highlightUnderEvent(t), this.selectHighlighted(t));
                                    })
                                ),
                                this.dropdown.bind("click mouseup mousedown", function (e) {
                                    e.stopPropagation();
                                }),
                            e.isFunction(this.opts.initSelection) && (this.initSelection(), this.monitorSource()),
                            (n.element.is(":disabled") || n.element.is("[readonly='readonly']")) && this.disable();
                        },
                        destroy: function () {
                            var e = this.opts.element.data("select2");
                            this.propertyObserver && (delete this.propertyObserver, (this.propertyObserver = null)),
                            e !== t && (e.container.remove(), e.dropdown.remove(), e.opts.element.removeClass("select2-offscreen").removeData("select2").unbind(".select2").attr({tabIndex: this.elementTabIndex}).show());
                        },
                        prepareOpts: function (n) {
                            var r, i, s, o;
                            (r = n.element),
                            r.get(0).tagName.toLowerCase() === "select" && (this.select = i = n.element),
                            i &&
                            e.each(["id", "multiple", "ajax", "query", "createSearchChoice", "initSelection", "data", "tags"], function () {
                                if (this in n) throw new Error("Option '" + this + "' is not allowed for Select2 when attached to a <select> element.");
                            }),
                                (n = e.extend(
                                    {},
                                    {
                                        populateResults: function (r, i, s) {
                                            var o,
                                                u,
                                                a,
                                                f,
                                                l = this.opts.id,
                                                c = this;
                                            (o = function (r, i, u) {
                                                var a, f, h, p, d, v, m, g, y, b;
                                                r = n.sortResults(r, i, s);
                                                for (a = 0, f = r.length; a < f; a += 1)
                                                    (h = r[a]),
                                                        (d = h.disabled === !0),
                                                        (p = !d && l(h) !== t),
                                                        (v = h.children && h.children.length > 0),
                                                        (m = e("<li></li>")),
                                                        m.addClass("select2-results-dept-" + u),
                                                        m.addClass("select2-result"),
                                                        m.addClass(p ? "select2-result-selectable" : "select2-result-unselectable"),
                                                    d && m.addClass("select2-disabled"),
                                                    v && m.addClass("select2-result-with-children"),
                                                        m.addClass(c.opts.formatResultCssClass(h)),
                                                        (g = e(document.createElement("div"))),
                                                        g.addClass("select2-result-label"),
                                                        (b = n.formatResult(h, g, s, c.opts.escapeMarkup)),
                                                    b !== t && g.html(b),
                                                        m.append(g),
                                                    v && ((y = e("<ul></ul>")), y.addClass("select2-result-sub"), o(h.children, y, u + 1), m.append(y)),
                                                        m.data("select2-data", h),
                                                        i.append(m);
                                            }),
                                                o(i, r, 0);
                                        },
                                    },
                                    e.fn.select2.defaults,
                                    n
                                )),
                            typeof n.id != "function" &&
                            ((s = n.id),
                                (n.id = function (e) {
                                    return e[s];
                                }));
                            if (e.isArray(n.element.data("select2Tags"))) {
                                if ("tags" in n) throw "tags specified as both an attribute 'data-select2-tags' and in options of Select2 " + n.element.attr("id");
                                n.tags = n.element.attr("data-select2-tags");
                            }
                            i
                                ? ((n.query = this.bind(function (n) {
                                    var i = {results: [], more: !1},
                                        s = n.term,
                                        o,
                                        u,
                                        a;
                                    (a = function (e, t) {
                                        var r;
                                        e.is("option")
                                            ? n.matcher(s, e.text(), e) && t.push({
                                            id: e.attr("value"),
                                            text: e.text(),
                                            element: e.get(),
                                            css: e.attr("class"),
                                            disabled: c(e.attr("disabled"), "disabled")
                                        })
                                            : e.is("optgroup") &&
                                            ((r = {
                                                text: e.attr("label"),
                                                children: [],
                                                element: e.get(),
                                                css: e.attr("class")
                                            }),
                                                e.children().each2(function (e, t) {
                                                    a(t, r.children);
                                                }),
                                            r.children.length > 0 && t.push(r));
                                    }),
                                        (o = r.children()),
                                    this.getPlaceholder() !== t && o.length > 0 && ((u = o[0]), e(u).text() === "" && (o = o.not(u))),
                                        o.each2(function (e, t) {
                                            a(t, i.results);
                                        }),
                                        n.callback(i);
                                })),
                                    (n.id = function (e) {
                                        return e.id;
                                    }),
                                    (n.formatResultCssClass = function (e) {
                                        return e.css;
                                    }))
                                : "query" in n ||
                                ("ajax" in n
                                    ? ((o = n.element.data("ajax-url")), o && o.length > 0 && (n.ajax.url = o), (n.query = N.call(n.element, n.ajax)))
                                    : "data" in n
                                        ? (n.query = C(n.data))
                                        : "tags" in n &&
                                        ((n.query = k(n.tags)),
                                        n.createSearchChoice === t &&
                                        (n.createSearchChoice = function (e) {
                                            return {id: e, text: e};
                                        }),
                                        n.initSelection === t &&
                                        (n.initSelection = function (t, r) {
                                            var i = [];
                                            e(h(t.val(), n.separator)).each(function () {
                                                var t = this,
                                                    r = this,
                                                    s = n.tags;
                                                e.isFunction(s) && (s = s()),
                                                    e(s).each(function () {
                                                        if (c(this.id, t)) return (r = this.text), !1;
                                                    }),
                                                    i.push({id: t, text: r});
                                            }),
                                                r(i);
                                        })));
                            if (typeof n.query != "function") throw "query function not defined for Select2 " + n.element.attr("id");
                            return n;
                        },
                        monitorSource: function () {
                            var e = this.opts.element,
                                t;
                            e.bind(
                                "change.select2",
                                this.bind(function (e) {
                                    this.opts.element.data("select2-change-triggered") !== !0 && this.initSelection();
                                })
                            ),
                                (t = this.bind(function () {
                                    var e,
                                        t,
                                        n = this;
                                    (e = this.opts.element.attr("disabled") !== "disabled"),
                                        (t = this.opts.element.attr("readonly") === "readonly"),
                                        (e = e && !t),
                                    this.enabled !== e && (e ? this.enable() : this.disable()),
                                        x(this.container, this.opts.element, this.opts.adaptContainerCssClass),
                                        this.container.addClass(A(this.opts.containerCssClass)),
                                        x(this.dropdown, this.opts.element, this.opts.adaptDropdownCssClass),
                                        this.dropdown.addClass(A(this.opts.dropdownCssClass));
                                })),
                                e.bind("propertychange.select2 DOMAttrModified.select2", t),
                            typeof WebKitMutationObserver != "undefined" &&
                            (this.propertyObserver && (delete this.propertyObserver, (this.propertyObserver = null)),
                                (this.propertyObserver = new WebKitMutationObserver(function (e) {
                                    e.forEach(t);
                                })),
                                this.propertyObserver.observe(e.get(0), {attributes: !0, subtree: !1}));
                        },
                        triggerChange: function (t) {
                            (t = t || {}),
                                (t = e.extend({}, t, {type: "change", val: this.val()})),
                                this.opts.element.data("select2-change-triggered", !0),
                                this.opts.element.trigger(t),
                                this.opts.element.data("select2-change-triggered", !1),
                                this.opts.element.click(),
                            this.opts.blurOnChange && this.opts.element.blur();
                        },
                        enable: function () {
                            if (this.enabled) return;
                            (this.enabled = !0), this.container.removeClass("select2-container-disabled"), this.opts.element.removeAttr("disabled");
                        },
                        disable: function () {
                            if (!this.enabled) return;
                            this.close(), (this.enabled = !1), this.container.addClass("select2-container-disabled"), this.opts.element.attr("disabled", "disabled");
                        },
                        opened: function () {
                            return this.container.hasClass("select2-dropdown-open");
                        },
                        positionDropdown: function () {
                            var t = this.container.offset(),
                                n = this.container.outerHeight(!1),
                                r = this.container.outerWidth(!1),
                                i = this.dropdown.outerHeight(!1),
                                s = e(window).scrollLeft() + e(window).width(),
                                o = e(window).scrollTop() + e(window).height(),
                                u = t.top + n,
                                a = t.left,
                                f = u + i <= o,
                                l = t.top - i >= this.body().scrollTop(),
                                c = this.dropdown.outerWidth(!1),
                                h = a + c <= s,
                                p = this.dropdown.hasClass("select2-drop-above"),
                                d,
                                v,
                                m;
                            this.body().css("position") !== "static" && ((d = this.body().offset()), (u -= d.top), (a -= d.left)),
                                p ? ((v = !0), !l && f && (v = !1)) : ((v = !1), !f && l && (v = !0)),
                            h || (a = t.left + r - c),
                                v
                                    ? ((u = t.top - i), this.container.addClass("select2-drop-above"), this.dropdown.addClass("select2-drop-above"))
                                    : (this.container.removeClass("select2-drop-above"), this.dropdown.removeClass("select2-drop-above")),
                                (m = e.extend({top: u, left: a, width: r}, A(this.opts.dropdownCss))),
                                this.dropdown.css(m);
                        },
                        shouldOpen: function () {
                            var t;
                            return this.opened() ? !1 : ((t = e.Event("opening")), this.opts.element.trigger(t), !t.isDefaultPrevented());
                        },
                        clearDropdownAlignmentPreference: function () {
                            this.container.removeClass("select2-drop-above"), this.dropdown.removeClass("select2-drop-above");
                        },
                        open: function () {
                            return this.shouldOpen() ? (window.setTimeout(this.bind(this.opening), 1), !0) : !1;
                        },
                        opening: function () {
                            var t = this.containerId,
                                n = "scroll." + t,
                                r = "resize." + t,
                                i = "orientationchange." + t,
                                s;
                            this.clearDropdownAlignmentPreference(),
                                this.container.addClass("select2-dropdown-open").addClass("select2-container-active"),
                            this.dropdown[0] !== this.body().children().last()[0] && this.dropdown.detach().appendTo(this.body()),
                                this.updateResults(!0),
                                (s = e("#select2-drop-mask")),
                            s.length == 0 &&
                            ((s = e(document.createElement("div"))),
                                s.attr("id", "select2-drop-mask").attr("class", "select2-drop-mask"),
                                s.hide(),
                                s.appendTo(this.body()),
                                s.bind("mousedown touchstart", function (t) {
                                    var n = e("#select2-drop"),
                                        r;
                                    n.length > 0 && ((r = n.data("select2")), r.opts.selectOnBlur && r.selectHighlighted({noFocus: !0}), r.close());
                                })),
                            this.dropdown.prev()[0] !== s[0] && this.dropdown.before(s),
                                e("#select2-drop").removeAttr("id"),
                                this.dropdown.attr("id", "select2-drop"),
                                s.css({
                                    width: document.documentElement.scrollWidth,
                                    height: document.documentElement.scrollHeight
                                }),
                                s.show(),
                                this.dropdown.show(),
                                this.positionDropdown(),
                                this.dropdown.addClass("select2-drop-active"),
                                this.ensureHighlightVisible();
                            var o = this;
                            this.container
                                .parents()
                                .add(window)
                                .each(function () {
                                    e(this).bind(r + " " + n + " " + i, function (t) {
                                        e("#select2-drop-mask").css({
                                            width: document.documentElement.scrollWidth,
                                            height: document.documentElement.scrollHeight
                                        }), o.positionDropdown();
                                    });
                                }),
                                this.focusSearch();
                        },
                        close: function () {
                            if (!this.opened()) return;
                            var t = this.containerId,
                                n = "scroll." + t,
                                r = "resize." + t,
                                i = "orientationchange." + t;
                            this.container
                                .parents()
                                .add(window)
                                .each(function () {
                                    e(this).unbind(n).unbind(r).unbind(i);
                                }),
                                this.clearDropdownAlignmentPreference(),
                                e("#select2-drop-mask").hide(),
                                this.dropdown.removeAttr("id"),
                                this.dropdown.hide(),
                                this.container.removeClass("select2-dropdown-open"),
                                this.results.empty(),
                                this.clearSearch(),
                                this.opts.element.trigger(e.Event("close"));
                        },
                        clearSearch: function () {
                        },
                        getMaximumSelectionSize: function () {
                            return A(this.opts.maximumSelectionSize);
                        },
                        ensureHighlightVisible: function () {
                            var t = this.results,
                                n,
                                r,
                                i,
                                s,
                                o,
                                u,
                                a;
                            r = this.highlight();
                            if (r < 0) return;
                            if (r == 0) {
                                t.scrollTop(0);
                                return;
                            }
                            (n = this.findHighlightableChoices()),
                                (i = e(n[r])),
                                (s = i.offset().top + i.outerHeight(!0)),
                            r === n.length - 1 && ((a = t.find("li.select2-more-results")), a.length > 0 && (s = a.offset().top + a.outerHeight(!0))),
                                (o = t.offset().top + t.outerHeight(!0)),
                            s > o && t.scrollTop(t.scrollTop() + (s - o)),
                                (u = i.offset().top - t.offset().top),
                            u < 0 && i.css("display") != "none" && t.scrollTop(t.scrollTop() + u);
                        },
                        findHighlightableChoices: function () {
                            var e = this.results.find(".select2-result-selectable:not(.select2-selected):not(.select2-disabled)");
                            return this.results.find(".select2-result-selectable:not(.select2-selected):not(.select2-disabled)");
                        },
                        moveHighlight: function (t) {
                            var n = this.findHighlightableChoices(),
                                r = this.highlight();
                            while (r > -1 && r < n.length) {
                                r += t;
                                var i = e(n[r]);
                                if (i.hasClass("select2-result-selectable") && !i.hasClass("select2-disabled") && !i.hasClass("select2-selected")) {
                                    this.highlight(r);
                                    break;
                                }
                            }
                        },
                        highlight: function (t) {
                            var n = this.findHighlightableChoices(),
                                r,
                                i;
                            if (arguments.length === 0) return l(n.filter(".select2-highlighted")[0], n.get());
                            t >= n.length && (t = n.length - 1),
                            t < 0 && (t = 0),
                                this.results.find(".select2-highlighted").removeClass("select2-highlighted"),
                                (r = e(n[t])),
                                r.addClass("select2-highlighted"),
                                this.ensureHighlightVisible(),
                                (i = r.data("select2-data")),
                            i && this.opts.element.trigger({type: "highlight", val: this.id(i), choice: i});
                        },
                        countSelectableResults: function () {
                            return this.findHighlightableChoices().length;
                        },
                        highlightUnderEvent: function (t) {
                            var n = e(t.target).closest(".select2-result-selectable");
                            if (n.length > 0 && !n.is(".select2-highlighted")) {
                                var r = this.findHighlightableChoices();
                                this.highlight(r.index(n));
                            } else n.length == 0 && this.results.find(".select2-highlighted").removeClass("select2-highlighted");
                        },
                        loadMoreIfNeeded: function () {
                            var e = this.results,
                                t = e.find("li.select2-more-results"),
                                n,
                                r = -1,
                                i = this.resultsPage + 1,
                                s = this,
                                o = this.search.val(),
                                u = this.context;
                            if (t.length === 0) return;
                            (n = t.offset().top - e.offset().top - e.height()),
                            n <= this.opts.loadMorePadding &&
                            (t.addClass("select2-active"),
                                this.opts.query({
                                    element: this.opts.element,
                                    term: o,
                                    page: i,
                                    context: u,
                                    matcher: this.opts.matcher,
                                    callback: this.bind(function (n) {
                                        if (!s.opened()) return;
                                        s.opts.populateResults.call(this, e, n.results, {term: o, page: i, context: u}),
                                            n.more === !0
                                                ? (t
                                                    .detach()
                                                    .appendTo(e)
                                                    .text(s.opts.formatLoadMore(i + 1)),
                                                    window.setTimeout(function () {
                                                        s.loadMoreIfNeeded();
                                                    }, 10))
                                                : t.remove(),
                                            s.positionDropdown(),
                                            (s.resultsPage = i),
                                            (s.context = n.context);
                                    }),
                                }));
                        },
                        tokenize: function () {
                        },
                        updateResults: function (n) {
                            function f() {
                                i.scrollTop(0), r.removeClass("select2-active"), u.positionDropdown();
                            }

                            function l(e) {
                                i.html(e), f();
                            }

                            var r = this.search,
                                i = this.results,
                                s = this.opts,
                                o,
                                u = this,
                                a;
                            if (n !== !0 && (this.showSearchInput === !1 || !this.opened())) return;
                            r.addClass("select2-active");
                            var h = this.getMaximumSelectionSize();
                            if (h >= 1) {
                                o = this.data();
                                if (e.isArray(o) && o.length >= h && L(s.formatSelectionTooBig, "formatSelectionTooBig")) {
                                    l("<li class='select2-selection-limit'>" + s.formatSelectionTooBig(h) + "</li>");
                                    return;
                                }
                            }
                            if (r.val().length < s.minimumInputLength) {
                                L(s.formatInputTooShort, "formatInputTooShort") ? l("<li class='select2-no-results'>" + s.formatInputTooShort(r.val(), s.minimumInputLength) + "</li>") : l("");
                                return;
                            }
                            s.formatSearching() && n === !0 && l("<li class='select2-searching'>" + s.formatSearching() + "</li>");
                            if (s.maximumInputLength && r.val().length > s.maximumInputLength) {
                                L(s.formatInputTooLong, "formatInputTooLong") ? l("<li class='select2-no-results'>" + s.formatInputTooLong(r.val(), s.maximumInputLength) + "</li>") : l("");
                                return;
                            }
                            (a = this.tokenize()),
                            a != t && a != null && r.val(a),
                                (this.resultsPage = 1),
                                s.query({
                                    element: s.element,
                                    term: r.val(),
                                    page: this.resultsPage,
                                    context: null,
                                    matcher: s.matcher,
                                    callback: this.bind(function (o) {
                                        var a;
                                        if (!this.opened()) return;
                                        (this.context = o.context === t ? null : o.context),
                                        this.opts.createSearchChoice &&
                                        r.val() !== "" &&
                                        ((a = this.opts.createSearchChoice.call(null, r.val(), o.results)),
                                        a !== t &&
                                        a !== null &&
                                        u.id(a) !== t &&
                                        u.id(a) !== null &&
                                        e(o.results).filter(function () {
                                            return c(u.id(this), u.id(a));
                                        }).length === 0 &&
                                        o.results.unshift(a));
                                        if (o.results.length === 0 && L(s.formatNoMatches, "formatNoMatches")) {
                                            l("<li class='select2-no-results'>" + s.formatNoMatches(r.val()) + "</li>");
                                            return;
                                        }
                                        i.empty(),
                                            u.opts.populateResults.call(this, i, o.results, {
                                                term: r.val(),
                                                page: this.resultsPage,
                                                context: null
                                            }),
                                        o.more === !0 &&
                                        L(s.formatLoadMore, "formatLoadMore") &&
                                        (i.append("<li class='select2-more-results'>" + u.opts.escapeMarkup(s.formatLoadMore(this.resultsPage)) + "</li>"),
                                            window.setTimeout(function () {
                                                u.loadMoreIfNeeded();
                                            }, 10)),
                                            this.postprocessResults(o, n),
                                            f();
                                    }),
                                });
                        },
                        cancel: function () {
                            this.close();
                        },
                        blur: function () {
                            this.opts.selectOnBlur && this.selectHighlighted({noFocus: !0}),
                                this.close(),
                                this.container.removeClass("select2-container-active"),
                            this.search[0] === document.activeElement && this.search.blur(),
                                this.clearSearch(),
                                this.selection.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus");
                        },
                        focusSearch: function () {
                            b(this.search);
                        },
                        selectHighlighted: function (e) {
                            var t = this.highlight(),
                                n = this.results.find(".select2-highlighted"),
                                r = n.closest(".select2-result").data("select2-data");
                            r && (this.highlight(t), this.onSelect(r, e));
                        },
                        getPlaceholder: function () {
                            return this.opts.element.attr("placeholder") || this.opts.element.attr("data-placeholder") || this.opts.element.data("placeholder") || this.opts.placeholder;
                        },
                        initContainerWidth: function () {
                            function n() {
                                var n, r, i, s, o;
                                if (this.opts.width === "off") return null;
                                if (this.opts.width === "element") return this.opts.element.outerWidth(!1) === 0 ? "auto" : this.opts.element.outerWidth(!1) + "px";
                                if (this.opts.width === "copy" || this.opts.width === "resolve") {
                                    n = this.opts.element.attr("style");
                                    if (n !== t) {
                                        r = n.split(";");
                                        for (s = 0, o = r.length; s < o; s += 1) {
                                            i = r[s].replace(/\s/g, "").match(/width:(([-+]?([0-9]*\.)?[0-9]+)(px|em|ex|%|in|cm|mm|pt|pc))/);
                                            if (i !== null && i.length >= 1) return i[1];
                                        }
                                    }
                                    return this.opts.width === "resolve" ? ((n = this.opts.element.css("width")), n.indexOf("%") > 0 ? n : this.opts.element.outerWidth(!1) === 0 ? "auto" : this.opts.element.outerWidth(!1) + "px") : null;
                                }
                                return e.isFunction(this.opts.width) ? this.opts.width() : this.opts.width;
                            }

                            var r = n.call(this);
                            r !== null && this.container.css("width", r);
                        },
                    })),
                    (i = _(r, {
                        createContainer: function () {
                            var t = e(document.createElement("div"))
                                .attr({class: "select2-container"})
                                .html(
                                    [
                                        "<a href='javascript:void(0)' onclick='return false;' class='select2-choice' tabindex='-1'>",
                                        "   <span></span><abbr class='select2-search-choice-close' style='display:none;'></abbr>",
                                        "   <div><b></b></div>",
                                        "</a>",
                                        "<input class='select2-focusser select2-offscreen' type='text'/>",
                                        "<div class='select2-drop' style='display:none'>",
                                        "   <div class='select2-search'>",
                                        "       <input type='text' autocomplete='off' class='select2-input'/>",
                                        "   </div>",
                                        "   <ul class='select2-results'>",
                                        "   </ul>",
                                        "</div>",
                                    ].join("")
                                );
                            return t;
                        },
                        disable: function () {
                            if (!this.enabled) return;
                            this.parent.disable.apply(this, arguments), this.focusser.attr("disabled", "disabled");
                        },
                        enable: function () {
                            if (this.enabled) return;
                            this.parent.enable.apply(this, arguments), this.focusser.removeAttr("disabled");
                        },
                        opening: function () {
                            this.parent.opening.apply(this, arguments), this.focusser.attr("disabled", "disabled"), this.opts.element.trigger(e.Event("open"));
                        },
                        close: function () {
                            if (!this.opened()) return;
                            this.parent.close.apply(this, arguments), this.focusser.removeAttr("disabled"), b(this.focusser);
                        },
                        focus: function () {
                            this.opened() ? this.close() : (this.focusser.removeAttr("disabled"), this.focusser.focus());
                        },
                        isFocused: function () {
                            return this.container.hasClass("select2-container-active");
                        },
                        cancel: function () {
                            this.parent.cancel.apply(this, arguments), this.focusser.removeAttr("disabled"), this.focusser.focus();
                        },
                        initContainer: function () {
                            var e,
                                t = this.container,
                                r = this.dropdown,
                                i = !1;
                            this.showSearch(this.opts.minimumResultsForSearch >= 0),
                                (this.selection = e = t.find(".select2-choice")),
                                (this.focusser = t.find(".select2-focusser")),
                                this.search.bind(
                                    "keydown",
                                    this.bind(function (e) {
                                        if (!this.enabled) return;
                                        if (e.which === n.PAGE_UP || e.which === n.PAGE_DOWN) {
                                            w(e);
                                            return;
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
                                                return;
                                        }
                                    })
                                ),
                                this.focusser.bind(
                                    "keydown",
                                    this.bind(function (e) {
                                        if (!this.enabled) return;
                                        if (e.which === n.TAB || n.isControl(e) || n.isFunctionKey(e) || e.which === n.ESC) return;
                                        if (this.opts.openOnEnter === !1 && e.which === n.ENTER) {
                                            w(e);
                                            return;
                                        }
                                        if (e.which == n.DOWN || e.which == n.UP || (e.which == n.ENTER && this.opts.openOnEnter)) {
                                            this.open(), w(e);
                                            return;
                                        }
                                        if (e.which == n.DELETE || e.which == n.BACKSPACE) {
                                            this.opts.allowClear && this.clear(), w(e);

                                        }
                                    })
                                ),
                                d(this.focusser),
                                this.focusser.bind(
                                    "keyup-change input",
                                    this.bind(function (e) {
                                        if (this.opened()) return;
                                        this.open(), this.showSearchInput !== !1 && this.search.val(this.focusser.val()), this.focusser.val(""), w(e);
                                    })
                                ),
                                e.delegate(
                                    "abbr",
                                    "mousedown",
                                    this.bind(function (e) {
                                        if (!this.enabled) return;
                                        this.clear(), E(e), this.close(), this.selection.focus();
                                    })
                                ),
                                e.bind(
                                    "mousedown",
                                    this.bind(function (e) {
                                        (i = !0), this.opened() ? this.close() : this.enabled && this.open(), w(e), (i = !1);
                                    })
                                ),
                                r.bind(
                                    "mousedown",
                                    this.bind(function () {
                                        this.search.focus();
                                    })
                                ),
                                e.bind(
                                    "focus",
                                    this.bind(function (e) {
                                        w(e);
                                    })
                                ),
                                this.focusser
                                    .bind(
                                        "focus",
                                        this.bind(function () {
                                            this.container.addClass("select2-container-active");
                                        })
                                    )
                                    .bind(
                                        "blur",
                                        this.bind(function () {
                                            this.opened() || this.container.removeClass("select2-container-active");
                                        })
                                    ),
                                this.search.bind(
                                    "focus",
                                    this.bind(function () {
                                        this.container.addClass("select2-container-active");
                                    })
                                ),
                                this.initContainerWidth(),
                                this.setPlaceholder();
                        },
                        clear: function () {
                            var e = this.selection.data("select2-data");
                            this.opts.element.val(""),
                                this.selection.find("span").empty(),
                                this.selection.removeData("select2-data"),
                                this.setPlaceholder(),
                                this.opts.element.trigger({type: "removed", val: this.id(e), choice: e}),
                                this.triggerChange({removed: e});
                        },
                        initSelection: function () {
                            var e;
                            if (this.opts.element.val() === "" && this.opts.element.text() === "") this.close(), this.setPlaceholder();
                            else {
                                var n = this;
                                this.opts.initSelection.call(null, this.opts.element, function (e) {
                                    e !== t && e !== null && (n.updateSelection(e), n.close(), n.setPlaceholder());
                                });
                            }
                        },
                        prepareOpts: function () {
                            var t = this.parent.prepareOpts.apply(this, arguments);
                            return (
                                t.element.get(0).tagName.toLowerCase() === "select"
                                    ? (t.initSelection = function (t, n) {
                                        var r = t.find(":selected");
                                        e.isFunction(n) && n({id: r.attr("value"), text: r.text(), element: r});
                                    })
                                    : "data" in t &&
                                    (t.initSelection =
                                        t.initSelection ||
                                        function (n, r) {
                                            var i = n.val();
                                            t.query({
                                                matcher: function (e, n, r) {
                                                    return c(i, t.id(r));
                                                },
                                                callback: e.isFunction(r)
                                                    ? function (e) {
                                                        r(e.results.length ? e.results[0] : null);
                                                    }
                                                    : e.noop,
                                            });
                                        }),
                                    t
                            );
                        },
                        getPlaceholder: function () {
                            return this.select && this.select.find("option").first().text() !== "" ? t : this.parent.getPlaceholder.apply(this, arguments);
                        },
                        setPlaceholder: function () {
                            var e = this.getPlaceholder();
                            if (this.opts.element.val() === "" && e !== t) {
                                if (this.select && this.select.find("option:first").text() !== "") return;
                                this.selection.find("span").html(this.opts.escapeMarkup(e)), this.selection.addClass("select2-default"), this.selection.find("abbr").hide();
                            }
                        },
                        postprocessResults: function (e, t) {
                            var n = 0,
                                r = this,
                                i = !0;
                            this.findHighlightableChoices().each2(function (e, t) {
                                if (c(r.id(t.data("select2-data")), r.opts.element.val())) return (n = e), !1;
                            }),
                                this.highlight(n);
                            if (t === !0) {
                                var s = this.opts.minimumResultsForSearch;
                                (i = s < 0 ? !1 : O(e.results) >= s), this.showSearch(i);
                            }
                        },
                        showSearch: function (t) {
                            (this.showSearchInput = t),
                                this.dropdown.find(".select2-search")[t ? "removeClass" : "addClass"]("select2-search-hidden"),
                                e(this.dropdown, this.container)[t ? "addClass" : "removeClass"]("select2-with-searchbox");
                        },
                        onSelect: function (e, t) {
                            var n = this.opts.element.val();
                            this.opts.element.val(this.id(e)),
                                this.updateSelection(e),
                                this.opts.element.trigger({type: "selected", val: this.id(e), choice: e}),
                                this.close(),
                            (!t || !t.noFocus) && this.selection.focus(),
                            c(n, this.id(e)) || this.triggerChange();
                        },
                        updateSelection: function (e) {
                            var n = this.selection.find("span"),
                                r;
                            this.selection.data("select2-data", e),
                                n.empty(),
                                (r = this.opts.formatSelection(e, n)),
                            r !== t && n.append(this.opts.escapeMarkup(r)),
                                this.selection.removeClass("select2-default"),
                            this.opts.allowClear && this.getPlaceholder() !== t && this.selection.find("abbr").show();
                        },
                        val: function () {
                            var e,
                                n = !1,
                                r = null,
                                i = this;
                            if (arguments.length === 0) return this.opts.element.val();
                            (e = arguments[0]), arguments.length > 1 && (n = arguments[1]);
                            if (this.select)
                                this.select
                                    .val(e)
                                    .find(":selected")
                                    .each2(function (e, t) {
                                        return (r = {id: t.attr("value"), text: t.text()}), !1;
                                    }),
                                    this.updateSelection(r),
                                    this.setPlaceholder(),
                                n && this.triggerChange();
                            else {
                                if (this.opts.initSelection === t) throw new Error("cannot call val() if initSelection() is not defined");
                                if (!e && e !== 0) {
                                    this.clear(), n && this.triggerChange();
                                    return;
                                }
                                this.opts.element.val(e),
                                    this.opts.initSelection(this.opts.element, function (e) {
                                        i.opts.element.val(e ? i.id(e) : ""), i.updateSelection(e), i.setPlaceholder(), n && i.triggerChange();
                                    });
                            }
                        },
                        clearSearch: function () {
                            this.search.val(""), this.focusser.val("");
                        },
                        data: function (e) {
                            var n;
                            if (arguments.length === 0) return (n = this.selection.data("select2-data")), n == t && (n = null), n;
                            !e || e === "" ? this.clear() : (this.opts.element.val(e ? this.id(e) : ""), this.updateSelection(e));
                        },
                    })),
                    (s = _(r, {
                        createContainer: function () {
                            var t = e(document.createElement("div"))
                                .attr({class: "select2-container select2-container-multi"})
                                .html(
                                    [
                                        "    <ul class='select2-choices'>",
                                        "  <li class='select2-search-field'>",
                                        "    <input type='text' autocomplete='off' class='select2-input'>",
                                        "  </li>",
                                        "</ul>",
                                        "<div class='select2-drop select2-drop-multi' style='display:none;'>",
                                        "   <ul class='select2-results'>",
                                        "   </ul>",
                                        "</div>",
                                    ].join("")
                                );
                            return t;
                        },
                        prepareOpts: function () {
                            var t = this.parent.prepareOpts.apply(this, arguments);
                            return (
                                t.element.get(0).tagName.toLowerCase() === "select"
                                    ? (t.initSelection = function (e, t) {
                                        var n = [];
                                        e.find(":selected").each2(function (e, t) {
                                            n.push({id: t.attr("value"), text: t.text(), element: t[0]});
                                        }),
                                            t(n);
                                    })
                                    : "data" in t &&
                                    (t.initSelection =
                                        t.initSelection ||
                                        function (n, r) {
                                            var i = h(n.val(), t.separator);
                                            t.query({
                                                matcher: function (n, r, s) {
                                                    return e.grep(i, function (e) {
                                                        return c(e, t.id(s));
                                                    }).length;
                                                },
                                                callback: e.isFunction(r)
                                                    ? function (e) {
                                                        r(e.results);
                                                    }
                                                    : e.noop,
                                            });
                                        }),
                                    t
                            );
                        },
                        initContainer: function () {
                            var t = ".select2-choices",
                                r;
                            (this.searchContainer = this.container.find(".select2-search-field")),
                                (this.selection = r = this.container.find(t)),
                                this.search.bind(
                                    "input paste",
                                    this.bind(function () {
                                        if (!this.enabled) return;
                                        this.opened() || this.open();
                                    })
                                ),
                                this.search.bind(
                                    "keydown",
                                    this.bind(function (e) {
                                        if (!this.enabled) return;
                                        if (e.which === n.BACKSPACE && this.search.val() === "") {
                                            this.close();
                                            var t,
                                                i = r.find(".select2-search-choice-focus");
                                            if (i.length > 0) {
                                                this.unselect(i.first()), this.search.width(10), w(e);
                                                return;
                                            }
                                            (t = r.find(".select2-search-choice:not(.select2-locked)")), t.length > 0 && t.last().addClass("select2-search-choice-focus");
                                        } else r.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus");
                                        if (this.opened())
                                            switch (e.which) {
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
                                                    return;
                                            }
                                        if (e.which === n.TAB || n.isControl(e) || n.isFunctionKey(e) || e.which === n.BACKSPACE || e.which === n.ESC) return;
                                        if (e.which === n.ENTER) {
                                            if (this.opts.openOnEnter === !1) return;
                                            if (e.altKey || e.ctrlKey || e.shiftKey || e.metaKey) return;
                                        }
                                        this.open(), (e.which === n.PAGE_UP || e.which === n.PAGE_DOWN) && w(e);
                                    })
                                ),
                                this.search.bind("keyup", this.bind(this.resizeSearch)),
                                this.search.bind(
                                    "blur",
                                    this.bind(function (e) {
                                        this.container.removeClass("select2-container-active"), this.search.removeClass("select2-focused"), this.opened() || this.clearSearch(), e.stopImmediatePropagation();
                                    })
                                ),
                                this.container.delegate(
                                    t,
                                    "mousedown",
                                    this.bind(function (t) {
                                        if (!this.enabled) return;
                                        if (e(t.target).closest(".select2-search-choice").length > 0) return;
                                        this.clearPlaceholder(), this.open(), this.focusSearch(), t.preventDefault();
                                    })
                                ),
                                this.container.delegate(
                                    t,
                                    "focus",
                                    this.bind(function () {
                                        if (!this.enabled) return;
                                        this.container.addClass("select2-container-active"), this.dropdown.addClass("select2-drop-active"), this.clearPlaceholder();
                                    })
                                ),
                                this.initContainerWidth(),
                                this.clearSearch();
                        },
                        enable: function () {
                            if (this.enabled) return;
                            this.parent.enable.apply(this, arguments), this.search.removeAttr("disabled");
                        },
                        disable: function () {
                            if (!this.enabled) return;
                            this.parent.disable.apply(this, arguments), this.search.attr("disabled", !0);
                        },
                        initSelection: function () {
                            var e;
                            this.opts.element.val() === "" && this.opts.element.text() === "" && (this.updateSelection([]), this.close(), this.clearSearch());
                            if (this.select || this.opts.element.val() !== "") {
                                var n = this;
                                this.opts.initSelection.call(null, this.opts.element, function (e) {
                                    e !== t && e !== null && (n.updateSelection(e), n.close(), n.clearSearch());
                                });
                            }
                        },
                        clearSearch: function () {
                            var e = this.getPlaceholder();
                            e !== t && this.getVal().length === 0 && this.search.hasClass("select2-focused") === !1 ? (this.search.val(e).addClass("select2-default"), this.resizeSearch()) : this.search.val("").width(10);
                        },
                        clearPlaceholder: function () {
                            this.search.hasClass("select2-default") && this.search.val("").removeClass("select2-default");
                        },
                        opening: function () {
                            this.parent.opening.apply(this, arguments), this.clearPlaceholder(), this.resizeSearch(), this.focusSearch(), this.opts.element.trigger(e.Event("open"));
                        },
                        close: function () {
                            if (!this.opened()) return;
                            this.parent.close.apply(this, arguments);
                        },
                        focus: function () {
                            this.close(), this.search.focus(), this.opts.element.triggerHandler("focus");
                        },
                        isFocused: function () {
                            return this.search.hasClass("select2-focused");
                        },
                        updateSelection: function (t) {
                            var n = [],
                                r = [],
                                i = this;
                            e(t).each(function () {
                                l(i.id(this), n) < 0 && (n.push(i.id(this)), r.push(this));
                            }),
                                (t = r),
                                this.selection.find(".select2-search-choice").remove(),
                                e(t).each(function () {
                                    i.addSelectedChoice(this);
                                }),
                                i.postprocessResults();
                        },
                        tokenize: function () {
                            var e = this.search.val();
                            (e = this.opts.tokenizer(e, this.data(), this.bind(this.onSelect), this.opts)), e != null && e != t && (this.search.val(e), e.length > 0 && this.open());
                        },
                        onSelect: function (e, t) {
                            this.addSelectedChoice(e),
                                this.opts.element.trigger({type: "selected", val: this.id(e), choice: e}),
                            (this.select || !this.opts.closeOnSelect) && this.postprocessResults(),
                                this.opts.closeOnSelect
                                    ? (this.close(), this.search.width(10))
                                    : this.countSelectableResults() > 0
                                        ? (this.search.width(10), this.resizeSearch(), this.val().length >= this.getMaximumSelectionSize() && this.updateResults(!0), this.positionDropdown())
                                        : (this.close(), this.search.width(10)),
                                this.triggerChange({added: e}),
                            (!t || !t.noFocus) && this.focusSearch();
                        },
                        cancel: function () {
                            this.close(), this.focusSearch();
                        },
                        addSelectedChoice: function (n) {
                            var r = !n.locked,
                                i = e("<li class='select2-search-choice'>    <div></div>    <a href='#' onclick='return false;' class='select2-search-choice-close' tabindex='-1'></a></li>"),
                                s = e("<li class='select2-search-choice select2-locked'><div></div></li>"),
                                o = r ? i : s,
                                u = this.id(n),
                                a = this.getVal(),
                                f;
                            (f = this.opts.formatSelection(n, o.find("div"))),
                            f != t && o.find("div").replaceWith("<div>" + this.opts.escapeMarkup(f) + "</div>"),
                            r &&
                            o
                                .find(".select2-search-choice-close")
                                .bind("mousedown", w)
                                .bind(
                                    "click dblclick",
                                    this.bind(function (t) {
                                        if (!this.enabled) return;
                                        e(t.target)
                                            .closest(".select2-search-choice")
                                            .fadeOut(
                                                "fast",
                                                this.bind(function () {
                                                    this.unselect(e(t.target)), this.selection.find(".select2-search-choice-focus").removeClass("select2-search-choice-focus"), this.close(), this.focusSearch();
                                                })
                                            )
                                            .dequeue(),
                                            w(t);
                                    })
                                )
                                .bind(
                                    "focus",
                                    this.bind(function () {
                                        if (!this.enabled) return;
                                        this.container.addClass("select2-container-active"), this.dropdown.addClass("select2-drop-active");
                                    })
                                ),
                                o.data("select2-data", n),
                                o.insertBefore(this.searchContainer),
                                a.push(u),
                                this.setVal(a);
                        },
                        unselect: function (e) {
                            var t = this.getVal(),
                                n,
                                r;
                            e = e.closest(".select2-search-choice");
                            if (e.length === 0) throw "Invalid argument: " + e + ". Must be .select2-search-choice";
                            n = e.data("select2-data");
                            if (!n) return;
                            (r = l(this.id(n).toString(), t)),
                            r >= 0 && (t.splice(r, 1), this.setVal(t), this.select && this.postprocessResults()),
                                e.remove(),
                                this.opts.element.trigger({type: "removed", val: this.id(n), choice: n}),
                                this.triggerChange({removed: n});
                        },
                        postprocessResults: function () {
                            var e = this.getVal(),
                                t = this.results.find(".select2-result"),
                                n = this.results.find(".select2-result-with-children"),
                                r = this;
                            t.each2(function (t, n) {
                                var i = r.id(n.data("select2-data"));
                                l(i, e) >= 0 && (n.addClass("select2-selected"), n.find(".select2-result-selectable").addClass("select2-selected"));
                            }),
                                n.each2(function (e, t) {
                                    !t.is(".select2-result-selectable") && t.find(".select2-result-selectable:not(.select2-selected)").length === 0 && t.addClass("select2-selected");
                                }),
                            this.highlight() == -1 && r.highlight(0);
                        },
                        resizeSearch: function () {
                            var e,
                                t,
                                n,
                                r,
                                i,
                                s = p(this.search);
                            (e = S(this.search) + 10),
                                (t = this.search.offset().left),
                                (n = this.selection.width()),
                                (r = this.selection.offset().left),
                                (i = n - (t - r) - s),
                            i < e && (i = n - s),
                            i < 40 && (i = n - s),
                            i <= 0 && (i = e),
                                this.search.width(i);
                        },
                        getVal: function () {
                            var e;
                            return this.select ? ((e = this.select.val()), e === null ? [] : e) : ((e = this.opts.element.val()), h(e, this.opts.separator));
                        },
                        setVal: function (t) {
                            var n;
                            this.select
                                ? this.select.val(t)
                                : ((n = []),
                                    e(t).each(function () {
                                        l(this, n) < 0 && n.push(this);
                                    }),
                                    this.opts.element.val(n.length === 0 ? "" : n.join(this.opts.separator)));
                        },
                        val: function () {
                            var n,
                                r = !1,
                                i = [],
                                s = this;
                            if (arguments.length === 0) return this.getVal();
                            (n = arguments[0]), arguments.length > 1 && (r = arguments[1]);
                            if (!n && n !== 0) {
                                this.opts.element.val(""), this.updateSelection([]), this.clearSearch(), r && this.triggerChange();
                                return;
                            }
                            this.setVal(n);
                            if (this.select) this.opts.initSelection(this.select, this.bind(this.updateSelection)), r && this.triggerChange();
                            else {
                                if (this.opts.initSelection === t) throw new Error("val() cannot be called if initSelection() is not defined");
                                this.opts.initSelection(this.opts.element, function (t) {
                                    var n = e(t).map(s.id);
                                    s.setVal(n), s.updateSelection(t), s.clearSearch(), r && s.triggerChange();
                                });
                            }
                            this.clearSearch();
                        },
                        onSortStart: function () {
                            if (this.select) throw new Error("Sorting of elements is not supported when attached to <select>. Attach to <input type='hidden'/> instead.");
                            this.search.width(0), this.searchContainer.hide();
                        },
                        onSortEnd: function () {
                            var t = [],
                                n = this;
                            this.searchContainer.show(),
                                this.searchContainer.appendTo(this.searchContainer.parent()),
                                this.resizeSearch(),
                                this.selection.find(".select2-search-choice").each(function () {
                                    t.push(n.opts.id(e(this).data("select2-data")));
                                }),
                                this.setVal(t),
                                this.triggerChange();
                        },
                        data: function (t) {
                            var n = this,
                                r;
                            if (arguments.length === 0)
                                return this.selection
                                    .find(".select2-search-choice")
                                    .map(function () {
                                        return e(this).data("select2-data");
                                    })
                                    .get();
                            t || (t = []),
                                (r = e.map(t, function (e) {
                                    return n.opts.id(e);
                                })),
                                this.setVal(r),
                                this.updateSelection(t),
                                this.clearSearch();
                        },
                    })),
                    (e.fn.select2 = function () {
                        var n = Array.prototype.slice.call(arguments, 0),
                            r,
                            o,
                            u,
                            a,
                            f = ["val", "destroy", "opened", "open", "close", "focus", "isFocused", "container", "onSortStart", "onSortEnd", "enable", "disable", "positionDropdown", "data"];
                        return (
                            this.each(function () {
                                if (n.length === 0 || typeof n[0] == "object")
                                    (r = n.length === 0 ? {} : e.extend({}, n[0])),
                                        (r.element = e(this)),
                                        r.element.get(0).tagName.toLowerCase() === "select" ? (a = r.element.attr("multiple")) : ((a = r.multiple || !1), "tags" in r && (r.multiple = a = !0)),
                                        (o = a ? new s() : new i()),
                                        o.init(r);
                                else {
                                    if (typeof n[0] != "string") throw "Invalid arguments to select2 plugin: " + n;
                                    if (l(n[0], f) < 0) throw "Unknown method: " + n[0];
                                    (u = t), (o = e(this).data("select2"));
                                    if (o === t) return;
                                    n[0] === "container" ? (u = o.container) : (u = o[n[0]].apply(o, n.slice(1)));
                                    if (u !== t) return !1;
                                }
                            }),
                                u === t ? this : u
                        );
                    }),
                    (e.fn.select2.defaults = {
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
                            return T(e.text, n.term, i, r), i.join("");
                        },
                        formatSelection: function (e, n) {
                            return e ? e.text : t;
                        },
                        sortResults: function (e, t, n) {
                            return e;
                        },
                        formatResultCssClass: function (e) {
                            return t;
                        },
                        formatNoMatches: function () {
                            return "No matches found";
                        },
                        formatInputTooShort: function (e, t) {
                            var n = t - e.length;
                            return "Please enter " + n + " more character" + (n == 1 ? "" : "s");
                        },
                        formatInputTooLong: function (e, t) {
                            var n = e.length - t;
                            return "Please enter " + n + " less character" + (n == 1 ? "" : "s");
                        },
                        formatSelectionTooBig: function (e) {
                            return "You can only select " + e + " item" + (e == 1 ? "" : "s");
                        },
                        formatLoadMore: function (e) {
                            return "Loading more results...";
                        },
                        formatSearching: function () {
                            return "Searching...";
                        },
                        minimumResultsForSearch: 0,
                        minimumInputLength: 0,
                        maximumInputLength: null,
                        maximumSelectionSize: 0,
                        id: function (e) {
                            return e.id;
                        },
                        matcher: function (e, t) {
                            return t.toUpperCase().indexOf(e.toUpperCase()) >= 0;
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
                                return t[e[0]];
                            });
                        },
                        blurOnChange: !1,
                        selectOnBlur: !1,
                        adaptContainerCssClass: function (e) {
                            return e;
                        },
                        adaptDropdownCssClass: function (e) {
                            return null;
                        },
                    });
            })(e);
    }),
    timely.define(
        "scripts/add_new_event",
        [
            "jquery_timely",
            "domReady",
            "ai1ec_config",
            "scripts/add_new_event/event_location/gmaps_helper",
            "scripts/add_new_event/event_location/input_coordinates_event_handlers",
            "scripts/add_new_event/event_location/input_coordinates_utility_functions",
            "scripts/add_new_event/event_date_time/date_time_event_handlers",
            "scripts/add_new_event/event_cost_helper",
            "external_libs/jquery.calendrical_timespan",
            "external_libs/jquery.inputdate",
            "external_libs/jquery.tools",
            "external_libs/bootstrap_datepicker",
            "external_libs/bootstrap/transition",
            "external_libs/bootstrap/collapse",
            "external_libs/bootstrap/modal",
            "external_libs/bootstrap/alert",
            "external_libs/bootstrap/tab",
            "external_libs/select2",
        ],
        function (e, t, n, r, i, s, o, u, a) {
            var f = function () {
                    var t = new Date(n.now * 1e3),
                        r = {
                            allday: "#osec_all_day_event",
                            start_date_input: "#osec_start-date-input",
                            start_time_input: "#osec_start-time-input",
                            start_time: "#osec_start-time",
                            end_date_input: "#osec_end-date-input",
                            end_time_input: "#osec_end-time-input",
                            end_time: "#osec_end-time",
                            date_format: n.date_format,
                            month_names: n.month_names,
                            day_names: n.day_names,
                            week_start_day: n.week_start_day,
                            twentyfour_hour: n.twentyfour_hour,
                            now: t,
                        };
                    e.timespan(r);
                },
                l = function () {
                    e(".ai1ec-panel-collapse").on("hide", function () {
                        e(this).parent().removeClass("ai1ec-overflow-visible");
                    }),
                        e(".ai1ec-panel-collapse").on("shown", function () {
                            var t = e(this);
                            window.setTimeout(function () {
                                t.parent().addClass("ai1ec-overflow-visible");
                            }, 350);
                        });
                },
                c = function () {
                    f(),
                        timely.require(["libs/gmaps"], function (e) {
                            e(r.init_gmaps);
                        });
                },
                h = function (t, n) {
                    var r = null;
                    "[object Array]" === Object.prototype.toString.call(n) ? (r = n.join("<br>")) : (r = n),
                        e("#osec_event_inline_alert").html(r),
                        e("#osec_event_inline_alert").removeClass("ai1ec-hidden"),
                        t.preventDefault(),
                        e("#publish, #osec_additional_publish_button").removeClass("button-primary-disabled"),
                        e("#publish, #osec_additional_publish_button").removeClass("disabled"),
                        e("#publish, #osec_additional_publish_button").siblings("#ajax-loading, .spinner").css("visibility", "hidden");
                },
                p = function (t) {
                    s.ai1ec_check_lat_long_fields_filled_when_publishing_event(t) === !0 && (s.ai1ec_convert_commas_to_dots_for_coordinates(), s.ai1ec_check_lat_long_ok_for_search(t));
                    var r = !1,
                        i = [];
                    e("#osec_ticket_url, #osec_contact_url").each(function () {
                        var t = this.value;
                        e(this).removeClass("ai1ec-input-warn"), e(this).closest(".ai1ec-panel-collapse").parent().find(".ai1ec-panel-heading .ai1ec-fa-warning").addClass("ai1ec-hidden").parent().css("color", "");
                        if ("" !== t) {
                            var s = /(http|https):\/\//;
                            if (!s.test(t)) {
                                e(this).closest(".ai1ec-panel-collapse").parent().find(".ai1ec-panel-heading .ai1ec-fa-warning").removeClass("ai1ec-hidden").parent().css("color", "rgb(255, 79, 79)"),
                                r || e(this).closest(".ai1ec-panel-collapse").collapse("show"),
                                    (r = !0);
                                var o = e(this).attr("id") + "_not_valid";
                                i.push(n[o]), e(this).addClass("ai1ec-input-warn");
                            }
                        }
                    }),
                    r && (i.push(n.general_url_not_valid), h(t, i));
                },
                d = function () {
                    e("#osec_google_map").click(i.toggle_visibility_of_google_map_on_click),
                        e("#osec_input_coordinates").change(i.toggle_visibility_of_coordinate_fields_on_click),
                        e("#post").submit(p),
                        e("input.ai1ec-coordinates").blur(i.update_map_from_coordinates_on_blur),
                        e("#osec_additional_publish_button").on("click", o.trigger_publish),
                        e(document)
                            .on("change", "#osec_table_coordinates", o.show_end_fields)
                            .on("click", "#osec_repeat_apply", o.handle_click_on_apply_button)
                            .on("click", "#osec_repeat_cancel", o.handle_click_on_cancel_modal)
                            .on("click", "#osec_monthly_type_bymonthday, #osec_monthly_type_byday", o.handle_checkbox_monthly_tab_modal)
                            .on("click", ".ai1ec-btn-group-grid a", o.handle_click_on_toggle_buttons),
                        e("#osec_repeat_box").on("hidden.bs.modal", o.handle_modal_hide),
                        o.execute_pseudo_handlers(),
                        e("#widgetField > a").on("click", o.handle_animation_of_calendar_widget),
                        e("#osec_is_free_event").on("change", u.handle_change_is_free);
                },
                g = function () {
                    e("#osec_event").insertAfter("#osec_event_inline_alert"), e("#post").addClass("ai1ec-visible");
                },
                y = function () {
                    e("#timezone-select").select2();
                },
                b = function () {
                    c(),
                        t(function () {
                            l(), g(), d(), y();
                        });
                };
            return {start: b};
        }
    ),
    timely.require(["scripts/add_new_event"], function (e) {
        e.start();
    }),
    timely.define("pages/add_new_event", function () {
    });
