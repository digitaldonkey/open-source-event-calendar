timely.define(["jquery_timely", "scripts/setting/cache/cache_ajax_handlers", "libs/utils"], function (e, t, n) {
    var r = n.get_ajax_url(), i = function () {
        var n = e(this);
        n.button("loading");
        var i = {action: "osec_rescan_cache"};
        return e.post(r, i, t.handle_rescan_cache, "json"), !1
    };
    return {perform_rescan: i}
});
