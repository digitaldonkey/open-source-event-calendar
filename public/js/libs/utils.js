timely.define(["jquery_timely", "external_libs/bootstrap/tab"], function (e) {
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
                return window.ajaxurl
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
});
