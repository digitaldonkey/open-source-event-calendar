timely.define(["jquery_timely", "libs/utils"], function (e, t) {
    var n = function (n) {
        var r = e("#ai1ec-button-refresh"), i = e("#osec-cache-scan-success"),
            s = e("#osec-cache-scan-danger"), o;
        r.button("reset"), n.error ? o = t.make_alert(n.message, "error") : "0" === n.state ? (i.toggleClass("ai1ec-hide", !0), s.toggleClass("ai1ec-hide", !1)) : (i.toggleClass("ai1ec-hide", !1), s.toggleClass("ai1ec-hide", !0))
    };
    return {handle_rescan_cache: n}
});
