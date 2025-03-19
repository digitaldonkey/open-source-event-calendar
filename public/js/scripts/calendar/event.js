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

/*!
 * jQuery.ScrollTo
 * Copyright (c) 2007-2012 Ariel Flesler - aflesler(at)gmail(dot)com | http://flesler.blogspot.com
 * Dual licensed under MIT and GPL.
 * Date: 12/14/2012
 *
 * @projectDescription Easy element scrolling using jQuery.
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 * @author Ariel Flesler
 * @version 1.4.5 BETA
 *
 * @id jQuery.scrollTo
 * @id jQuery.fn.scrollTo
 * @param {String, Number, DOMElement, jQuery, Object} target Where to scroll the matched elements.
 *	  The different options for target are:
 *		- A number position (will be applied to all axes).
 *		- A string position ('44', '100px', '+=90', etc ) will be applied to all axes
 *		- A jQuery/DOM element ( logically, child of the element to scroll )
 *		- A string selector, that will be relative to the element to scroll ( 'li:eq(2)', etc )
 *		- A hash { top:x, left:y }, x and y can be any kind of number/string like above.
 *		- A percentage of the container's dimension/s, for example: 50% to go to the middle.
 *		- The string 'max' for go-to-end.
 * @param {Number, Function} duration The OVERALL length of the animation, this argument can be the settings object instead.
 * @param {Object,Function} settings Optional set of settings or the onAfter callback.
 *	 @option {String} axis Which axis must be scrolled, use 'x', 'y', 'xy' or 'yx'.
 *	 @option {Number, Function} duration The OVERALL length of the animation.
 *	 @option {String} easing The easing method for the animation.
 *	 @option {Boolean} margin If true, the margin of the target element will be deducted from the final position.
 *	 @option {Object, Number} offset Add/deduct from the end position. One number for both axes or { top:x, left:y }.
 *	 @option {Object, Number} over Add/deduct the height/width multiplied by 'over', can be { top:x, left:y } when using both axes.
 *	 @option {Boolean} queue If true, and both axis are given, the 2nd axis will only be animated after the first one ends.
 *	 @option {Function} onAfter Function to be called after the scrolling ends.
 *	 @option {Function} onAfterFirst If queuing is activated, this function will be called after the first scrolling ends.
 * @return {jQuery} Returns the same jQuery object, for chaining.
 *
 * @desc Scroll to a fixed position
 * @example $('div').scrollTo( 340 );
 *
 * @desc Scroll relatively to the actual position
 * @example $('div').scrollTo( '+=340px', { axis:'y' } );
 *
 * @desc Scroll using a selector (relative to the scrolled element)
 * @example $('div').scrollTo( 'p.paragraph:eq(2)', 500, { easing:'swing', queue:true, axis:'xy' } );
 *
 * @desc Scroll to a DOM element (same for jQuery object)
 * @example var second_child = document.getElementById('container').firstChild.nextSibling;
 *			$('#container').scrollTo( second_child, { duration:500, axis:'x', onAfter:function(){
 *				alert('scrolled!!');
 *			}});
 *
 * @desc Scroll on both axes, to different values
 * @example $('div').scrollTo( { top: 300, left:'+=200' }, { axis:'xy', offset:-20 } );
 */

timely.define("external_libs/bootstrap/tab", ["jquery_timely"], function (e) {
    var t = function (t) {
        this.element = e(t)
    };
    t.prototype.show = function () {
        var t = this.element, n = t.closest("ul:not(.ai1ec-dropdown-menu)"),
            r = t.data("target");
        r || (r = t.attr("href"), r = r && r.replace(/.*(?=#[^\s]*$)/, ""));
        if (t.parent("li").hasClass("ai1ec-active")) {
            return;
        }
        var i = n.find(".ai1ec-active:last a")[0],
            s = e.Event("show.bs.tab", {relatedTarget: i});
        t.trigger(s);
        if (s.isDefaultPrevented()) {
            return;
        }
        var o = e(r);
        this.activate(t.parent("li"), n), this.activate(o, o.parent(), function () {
            t.trigger({type: "shown.bs.tab", relatedTarget: i})
        })
    }, t.prototype.activate = function (t, n, r) {
        function o() {
            i.removeClass("ai1ec-active").find("> .ai1ec-dropdown-menu > .ai1ec-active").removeClass("ai1ec-active"), t.addClass("ai1ec-active"), s ? (t[0].offsetWidth, t.addClass("ai1ec-in")) : t.removeClass("ai1ec-fade"), t.parent(".ai1ec-dropdown-menu") && t.closest("li.ai1ec-dropdown").addClass("ai1ec-active"), r && r()
        }

        var i = n.find("> .ai1ec-active"),
            s = r && e.support.transition && i.hasClass("ai1ec-fade");
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
                if ("string" != typeof e) {
                    return !1;
                }
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
                    if (e(this).data("clicked") && t.originalEvent.detail < 2) {
                        return;
                    }
                    e(this).data("clicked", !0);
                    var n;
                    document.body.createTextRange ? (n = document.body.createTextRange(), n.moveToElementText(this), n.select()) : window.getSelection && (selection = window.getSelection(), n = document.createRange(), n.selectNodeContents(this), selection.removeAllRanges(), selection.addRange(n))
                })
            }
        }
    }();
    return t
}), timely.define("external_libs/jquery.scrollTo", ["jquery_timely"], function (e) {
    function n(e) {
        return typeof e == "object" ? e : {top: e, left: e}
    }

    var t = e.scrollTo = function (t, n, r) {
        e(window).scrollTo(t, n, r)
    };
    t.defaults = {
        axis: "xy",
        duration: parseFloat(e.fn.jquery) >= 1.3 ? 0 : 1,
        limit: !0
    }, t.window = function (t) {
        return e(window)._scrollable()
    }, e.fn._scrollable = function () {
        return this.map(function () {
            var t = this,
                n = !t.nodeName || e.inArray(t.nodeName.toLowerCase(), ["iframe", "#document", "html", "body"]) != -1;
            if (!n) {
                return t;
            }
            var r = (t.contentWindow || t).document || t.ownerDocument || t;
            return /webkit/i.test(navigator.userAgent) || r.compatMode == "BackCompat" ? r.body : r.documentElement
        })
    }, e.fn.scrollTo = function (r, i, s) {
        return typeof i == "object" && (s = i, i = 0), typeof s == "function" && (s = {onAfter: s}), r == "max" && (r = 9e9), s = e.extend({}, t.defaults, s), i = i || s.duration, s.queue = s.queue && s.axis.length > 1, s.queue && (i /= 2), s.offset = n(s.offset), s.over = n(s.over), this._scrollable().each(function () {
            function h(e) {
                u.animate(l, i, s.easing, e && function () {
                    e.call(this, r, s)
                })
            }

            if (r == null) {
                return;
            }
            var o = this, u = e(o), a = r, f, l = {}, c = u.is("html,body");
            switch (typeof a) {
                case"number":
                case"string":
                    if (/^([+-]=?)?\d+(\.\d+)?(px|%)?$/.test(a)) {
                        a = n(a);
                        break
                    }
                    a = e(a, this);
                    if (!a.length) {
                        return;
                    }
                case"object":
                    if (a.is || a.style) {
                        f = (a = e(a)).offset()
                    }
            }
            e.each(s.axis.split(""), function (e, n) {
                var r = n == "x" ? "Left" : "Top", i = r.toLowerCase(),
                    p = "scroll" + r, d = o[p], v = t.max(o, n);
                if (f) {
                    l[p] = f[i] + (c ? 0 : d - u.offset()[i]), s.margin && (l[p] -= parseInt(a.css("margin" + r)) || 0, l[p] -= parseInt(a.css("border" + r + "Width")) || 0), l[p] += s.offset[i] || 0, s.over[i] && (l[p] += a[n == "x" ? "width" : "height"]() * s.over[i]);
                } else {
                    var m = a[i];
                    l[p] = m.slice && m.slice(-1) == "%" ? parseFloat(m) / 100 * v : m
                }
                s.limit && /^\d+$/.test(l[p]) && (l[p] = l[p] <= 0 ? 0 : Math.min(l[p], v)), !e && s.queue && (d != l[p] && h(s.onAfterFirst), delete l[p])
            }), h(s.onAfter)
        }).end()
    }, t.max = function (t, n) {
        var r = n == "x" ? "Width" : "Height", i = "scroll" + r;
        if (!e(t).is("html,body")) {
            return t[i] - e(t)[r.toLowerCase()]();
        }
        var s = "client" + r, o = t.ownerDocument.documentElement,
            u = t.ownerDocument.body;
        return Math.max(o[i], u[i]) - Math.min(o[s], u[s])
    }
}), timely.define("scripts/calendar/event", ["jquery_timely", "libs/utils", "external_libs/jquery.scrollTo"], function (e, t) {
    var n = function (n) {
        n.preventDefault(), e("div.ai1ec-popover").remove();
        var r = "jsonp", i = e(this).closest(".timely-calendar"), s = {
            request_type: r,
            osec_doing_ajax: !0,
            ai1ec: t.create_ai1ec_to_send(i)
        };
        e.ajax({
            url: e(this).attr("href"),
            dataType: r,
            data: s,
            method: "get",
            success: function (t) {
                e(n.target).closest("#osec-calendar-view").html(t.html);
                var r = e(".ai1ec-calendar-link").attr("href"),
                    s = e(n.target).closest(".timely-calendar").data("action");
                s && (r = r + "action~" + s + "/"), e.scrollTo(i, 1e3, {
                    offset: {
                        left: 0,
                        top: -100
                    }
                }), timely.require(["pages/event"])
            }
        })
    };
    return {load_event_through_jsonp: n}
});
