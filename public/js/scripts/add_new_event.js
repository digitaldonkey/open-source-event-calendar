timely.define([
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
    "external_libs/select2"
], function (jQuery, domReady, config, gMaps, i, s, o, u, a) {
    var f = function () {
            var t = new Date(config.now * 1e3), r = {
                allday: "#osec_all_day_event",
                start_date_input: "#osec_start-date-input",
                start_time_input: "#osec_start-time-input",
                start_time: "#osec_start-time",
                end_date_input: "#osec_end-date-input",
                end_time_input: "#osec_end-time-input",
                end_time: "#osec_end-time",
                date_format: config.date_format,
                month_names: config.month_names,
                day_names: config.day_names,
                week_start_day: config.week_start_day,
                twentyfour_hour: config.twentyfour_hour,
                now: t
            };
            jQuery.timespan(r)
        },
        l = function () {
            jQuery(".ai1ec-panel-collapse").on("hide", function () {
                jQuery(this).parent().removeClass("ai1ec-overflow-visible")
            }),
                jQuery(".ai1ec-panel-collapse").on("shown", function () {
                    var t = jQuery(this);
                    window.setTimeout(function () {
                        t.parent().addClass("ai1ec-overflow-visible")
                    }, 350)
                })
        },
        c = function () {
            f(), timely.require(["libs/gmaps"], function (e) {
                e(gMaps.init_gmaps)
            })
        },
        h = function (t, n) {
            var r = null;
            "[object Array]" === Object.prototype.toString.call(n) ? r = n.join("<br>") : r = n, jQuery("#osec_event_inline_alert").html(r), jQuery("#osec_event_inline_alert").removeClass("ai1ec-hidden"), t.preventDefault(), jQuery("#publish, #osec_additional_publish_button").removeClass("button-primary-disabled"), jQuery("#publish, #osec_additional_publish_button").removeClass("disabled"), jQuery("#publish, #osec_additional_publish_button").siblings("#ajax-loading, .spinner").css("visibility", "hidden")
        },
        p = function (t) {
            s.ai1ec_check_lat_long_fields_filled_when_publishing_event(t) === !0 && (s.ai1ec_convert_commas_to_dots_for_coordinates(), s.ai1ec_check_lat_long_ok_for_search(t));
            var r = !1, i = [];
            jQuery("#osec_ticket_url, #osec_contact_url").each(function () {
                var t = this.value;
                jQuery(this).removeClass("ai1ec-input-warn"), jQuery(this).closest(".ai1ec-panel-collapse").parent().find(".ai1ec-panel-heading .ai1ec-fa-warning").addClass("ai1ec-hidden").parent().css("color", "");
                if ("" !== t) {
                    var s = /(http|https):\/\//;
                    if (!s.test(t)) {
                        jQuery(this).closest(".ai1ec-panel-collapse").parent().find(".ai1ec-panel-heading .ai1ec-fa-warning").removeClass("ai1ec-hidden").parent().css("color", "rgb(255, 79, 79)"), r || jQuery(this).closest(".ai1ec-panel-collapse").collapse("show"), r = !0;
                        var o = jQuery(this).attr("id") + "_not_valid";
                        i.push(config[o]), jQuery(this).addClass("ai1ec-input-warn")
                    }
                }
            }), r && (i.push(config.general_url_not_valid), h(t, i))
        },
        d = function () {
            jQuery("#osec_google_map").click(i.toggle_visibility_of_google_map_on_click),
                jQuery("#osec_input_coordinates").change(i.toggle_visibility_of_coordinate_fields_on_click),
                jQuery("#post").submit(p), jQuery("input.ai1ec-coordinates").blur(i.update_map_from_coordinates_on_blur),
                jQuery("#osec_additional_publish_button").on("click", o.trigger_publish),
                jQuery(document).on("change", "#osec_table_coordinates", o.show_end_fields).on("click", "#osec_repeat_apply", o.handle_click_on_apply_button).on("click", "#osec_repeat_cancel", o.handle_click_on_cancel_modal).on("click", "#osec_monthly_type_bymonthday, #osec_monthly_type_byday", o.handle_checkbox_monthly_tab_modal).on("click", ".ai1ec-btn-group-grid a", o.handle_click_on_toggle_buttons),
                jQuery("#osec_repeat_box").on("hidden.bs.modal", o.handle_modal_hide), o.execute_pseudo_handlers(),
                jQuery("#widgetField > a").on("click", o.handle_animation_of_calendar_widget),
                jQuery("#osec_is_free_event").on("change", u.handle_change_is_free)
        },
        g = function () {
            jQuery("#osec_event").insertAfter("#osec_event_inline_alert"),
                jQuery("#post").addClass("ai1ec-visible")
        },
        y = function () {
            jQuery("#timezone-select").select2()
        }, b = function () {
            c(), domReady(function () {
                l(), g(), d(), y()
            })
        };
    return {start: b}
});
