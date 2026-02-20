timely.define(["jquery_timely", "libs/utils"], function (e, t) {
    var n = function (n) {
        var r = e("#osec_add_new_ics"),
            o = e("#osec_feed_url");
        r.button("reset");
        if (n.error) {
            var u = t.make_alert(n.message, "error");
            e("#ics-alerts").append(u)
        } else {
            s(), e("#ai1ec-feeds-after").addClass("ai1ec-well ai1ec-well-sm").insertAfter("#ics .ai1ec-form-horizontal");
            var a = n.update.data.feed_id, f = e(n.message),
                l = e('.ai1ec_feed_id[value="' + a + '"] ').closest(".osec-feed-container");
            f.find(".ai1ec-collapse").removeClass("ai1ec-collapse");
            var l = e('.ai1ec_feed_id[value="' + a + '"] ').closest(".osec-feed-container");
            l.length ? l.replaceWith(f) : e("#ai1ec-feeds-after").after(f), n.update && n.update.data && !n.update.data.error && i(n.update.data)
        }
    }, r = function (n) {
        var r = e("input[value=" + n.feed_id + "]").closest(".osec-feed-container"), i = n.error ? "error" : "success",
            s = t.make_alert(n.message, i);
        n.error ? e(".osec_update_ics", r).button("reset") : r.remove(), e("#ics-alerts").append(s)
    }, i = function (n) {
        var r = e("input[value=" + n.feed_id + "]").closest(".osec-feed-container"), i = n.error ? "error" : "success",
            s = t.make_alert(n.message, i);
        e(".osec_update_ics", r).button("reset"), e("#ics-alerts").append(s)
    }, s = function () {
        e("#osec_feed_url").val(" ").prop("readonly", !1), e('#ai1ec-feeds-after input[type="checkbox"]').prop("checked", !1), e("#osec_feed_id").remove(), e("#osec_feed_category").select2("val", ""), e("#osec_feed_tags").select2("val", ""), e('[id^="ai1ec_feed_cfg_"]').select2("val", ""), e("#osec_ics_add_new, #osec_add_new_ics > i").removeClass("ai1ec-hidden"), e("#osec_ics_update").addClass("ai1ec-hidden"), e("#ics .ai1ec-alert").remove()
    };
    return {handle_add_new_ics: n, handle_delete_ics: r, handle_update_ics: i, reset_form: s}
});
