/**!
 * @license RequireJS domReady 2.0.0 Copyright (c) 2010-2012, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/requirejs/domReady for details
 */
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
        if (document.addEventListener){
            document.addEventListener("DOMContentLoaded", f, !1), window.addEventListener("load", f, !1);
        }
        else if (window.attachEvent) {
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
    },
    c
}),
timely.define("scripts/event/maps_helper", ["jquery_timely"], function ($) {
    var handle_show_map_when_clicking_on_placeholder = function () {
            // handle_show_map_when_clicking_on_placeholder()
            var t = $(".osec-map-container-hidden:first");
            $(this).remove(),
                t.hide(),
                t.removeClass("osec-map-container-hidden"),
                t.fadeIn()
        };
    return {handle_show_map_when_clicking_on_placeholder}
}),
timely.define("scripts/event", ["jquery_timely", "domReady", "ai1ec_config", "scripts/event/maps_helper"], function ($, domReady, config, maps_helper) {
    var initMapPlaceholder = function () {
        // What is this?
        $(".osec-map-placeholder:first").click(maps_helper.handle_show_map_when_clicking_on_placeholder)
    },
    o = function () {
        $("#timely-description img[data-ai1ec-hidden]").each(function () {
            var t = $(this), n = $("#timely-event-poster img").attr("src");
            t.attr("src") != n && t.removeAttr("data-ai1ec-hidden")
        })
    },
    start = function () {
        domReady(function () {
            initMapPlaceholder(),
            o(),
            $(document).trigger("event_page_ready.ai1ec"),
            $("body").addClass("ai1ec-event-details-ready")
        })
    };
    return {start}
}),
timely.require(["scripts/event"], function (e) {
    e.start()
});
