timely.define(["jquery_timely", "ai1ec_config", "scripts/add_new_event/event_date_time/date_time_utility_functions", "external_libs/jquery.calendrical_timespan", "libs/utils", "external_libs/bootstrap/button"], function (e, t, n, r, i) {
    var s = i.get_ajax_url(), o = function () {
        var t = e("#osec_table_coordinates option:selected").val();
        switch (t) {
            case"0":
                e("#osec_until_holder, #osec_count_holder").collapse("hide");
                break;
            case"1":
                e("#osec_until_holder").collapse("hide"), e("#osec_count_holder").collapse("show");
                break;
            case"2":
                e("#osec_count_holder").collapse("hide"), e("#osec_until_holder").collapse("show")
        }
    }, u = function () {
        e("#publish").trigger("click")
    }, a = function () {
        var i = e(this), o = "", u = e("#osec_repeat_box .ai1ec-tab-pane.ai1ec-active"), a = u.data("freq"), f = !0;
        switch (a) {
            case"daily":
                o += "FREQ=DAILY;";
                var l = e("#osec_daily_count").val();
                l > 1 && (o += "INTERVAL=" + l + ";");
                break;
            case"weekly":
                o += "FREQ=WEEKLY;";
                var c = e("#osec_weekly_count").val();
                c > 1 && (o += "INTERVAL=" + c + ";");
                var h = e('input[name="osec_weekly_date_select"]:first').val(),
                    p = e('#osec_weekly_date_select > div:first > input[type="hidden"]:first').val();
                h.length > 0 && (o += "WKST=" + p + ";BYday=" + h + ";");
                break;
            case"monthly":
                o += "FREQ=MONTHLY;";
                var d = e("#osec_monthly_count").val(), v = e('input[name="osec_monthly_type"]:checked').val();
                d > 1 && (o += "INTERVAL=" + d + ";");
                var m = e('input[name="ai1ec_montly_date_select"]:first').val();
                if (m.length > 0 && v === "bymonthday") o += "BYMONTHDAY=" + m + ";"; else if (v === "byday") {
                    var g = e("#osec_monthly_byday_num").val(), y = e("#osec_monthly_byday_weekday").val();
                    o += "BYday=" + g + y + ";"
                }
                break;
            case"yearly":
                o += "FREQ=YEARLY;";
                var b = e("#osec_yearly_count").val();
                b > 1 && (o += "INTERVAL=" + b + ";");
                var w = e('input[name="osec_yearly_date_select"]:first').val();
                w.length > 0 && (o += "BYMONTH=" + w + ";");
                break;
            case"custom":
                "1" === e("#osec_is_box_repeat").val() ? o += "RDATE=" : o += "EXDATE=", o += e("#osec_rec_custom_dates").val(), f = !1
        }
        var E = e("#osec_table_coordinates").val();
        if ("1" === E && f) o += "COUNT=" + e("#osec_count").val() + ";"; else if ("2" === E && f) {
            var S = e("#osec_until-date-input").val();
            S = r.parseDate(S, t.date_format);
            var x = e("#osec_table_coordinates-time").val();
            x = r.parseDate(x, t.date_format), x = new Date(x);
            var T = S.getUTCDate(), N = S.getUTCMonth() + 1, C = x.getUTCHours(), k = x.getUTCMinutes();
            N = N < 10 ? "0" + N : N, T = T < 10 ? "0" + T : T, C = C < 10 ? "0" + C : C, k = k < 10 ? "0" + k : k, S = S.getUTCFullYear() + "" + N + T + "T235959Z", o += "UNTIL=" + S + ";"
        }
        var L = {action: "osec_rrule_to_text", rrule: o};
        i.button("loading").next().addClass("ai1ec-disabled"), e.post(s, L, function (t) {
            t.error ? (i.button("reset").next().removeClass("ai1ec-disabled"), "1" === e("#osec_is_box_repeat").val() ? n.repeat_form_error("#osec_rrule", "#osec_repeat_label", t, i) : n.repeat_form_error("#osec_exrule", "#osec_exclude_label", t, i)) : "1" === e("#osec_is_box_repeat").val() ? n.repeat_form_success("#osec_rrule", "#osec_repeat_label", "#osec_repeat_text > a", o, i, t) : n.repeat_form_success("#osec_exrule", "#osec_exclude_label", "#osec_exclude_text > a", o, i, t)
        }, "json")
    }, f = function () {
        return e("#osec_is_box_repeat").val() === "1" ? n.click_on_modal_cancel("#osec_repeat_text > a", "#osec_repeat", "#osec_repeat_label") : n.click_on_modal_cancel("#osec_exclude_text > a", "#osec_exclude", "#osec_exclude_label"), e("#osec_repeat_box").modal("hide"), !1
    }, l = function () {
        e(this).is("#osec_monthly_type_bymonthday") ? (e("#osec_repeat_monthly_byday").collapse("hide"), e("#osec_repeat_monthly_bymonthday").collapse("show")) : (e("#osec_repeat_monthly_bymonthday").collapse("hide"), e("#osec_repeat_monthly_byday").collapse("show"))
    }, c = function () {
        var t = e(this), n = [], r = t.closest(".ai1ec-btn-group-grid"), i;
        t.toggleClass("ai1ec-active"), e("a", r).each(function () {
            var t = e(this);
            t.is(".ai1ec-active") && (i = t.next().val(), n.push(i))
        }), r.next().val(n.join())
    }, h = function () {
        n.click_on_ics_rule_text("#osec_repeat_text > a", "#osec_repeat", "#osec_repeat_label", {
            action: "osec_get_repeat_box",
            repeat: 1,
            post_id: e("#post_ID").val()
        }, n.init_modal_widgets), n.click_on_ics_rule_text("#osec_exclude_text > a", "#osec_exclude", "#osec_exclude_label", {
            action: "osec_get_repeat_box",
            repeat: 0,
            post_id: e("#post_ID").val()
        }, n.init_modal_widgets), n.click_on_checkbox("#osec_repeat", "#osec_repeat_text > a", "#osec_repeat_label", {
            action: "osec_get_repeat_box",
            repeat: 1,
            post_id: e("#post_ID").val()
        }, n.init_modal_widgets), n.click_on_checkbox("#osec_exclude", "#osec_exclude_text > a", "#osec_exclude_label", {
            action: "osec_get_repeat_box",
            repeat: 0,
            post_id: e("#post_ID").val()
        }, n.init_modal_widgets)
    }, p = function (t) {
        return e("#osec_widget_calendar").toggle(), !1
    }, d = function () {
        e(".ai1ec-modal-content", this).not(".ai1ec-loading ").remove().end().removeClass("ai1ec-hide")
    }, v = function () {
        var t = e("#osec_repeat_box").find("ul.ai1ec-nav").find("li.ai1ec-active"),
            n = e("#osec_repeat_box").find(".ai1ec-end-field");
        t.hasClass("ai1ec-freq-custom") ? n.addClass("ai1ec-hidden") : n.removeClass("ai1ec-hidden"), t.hasClass("ai1ec-freq-monthly") && l()
    }, m = function () {
        var t = e("#ai1ec-tab-content").data("activeFreq"), n = e("#osec_recurrence_calendar");
        e(".ai1ec-freq").removeClass("ai1ec-active"), e(".ai1ec-freq-" + t).addClass("ai1ec-active"), e(document).on("shown.bs.tab", v), o(), v()
    };
    return e(document).on("ai1ec.recurrence-modal.inited", m), {
        show_end_fields: o,
        trigger_publish: u,
        handle_click_on_apply_button: a,
        handle_click_on_cancel_modal: f,
        handle_checkbox_monthly_tab_modal: l,
        execute_pseudo_handlers: h,
        handle_animation_of_calendar_widget: p,
        handle_click_on_toggle_buttons: c,
        handle_modal_hide: d
    }
});
