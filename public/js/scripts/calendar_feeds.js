timely.define(["jquery_timely", "domReady", "scripts/calendar_feeds/ics/ics_event_handlers", "libs/select2_multiselect_helper", "libs/tags_select", "libs/utils", "external_libs/jquery_cookie", "external_libs/bootstrap/tab", "external_libs/bootstrap/alert", "external_libs/bootstrap/modal", "external_libs/bootstrap/button", "external_libs/bootstrap/collapse"], function (e, t, n, r, i, s) {
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
});
