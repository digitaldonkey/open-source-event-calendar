timely.define(["jquery_timely", "ai1ec_config"], function (e, t) {
    console.log({e, t})
    var n = function () {
            return e("#osec_is_free_event").is(":checked");
        },
        r = function () {
            return String.prototype.trim(e("#osec_cost").val()) !== "";
        },
        i = function () {
            var i = e(this).parents("table:eq(0)"),
                s = e("#osec_cost", i),
                o = t.label_a_buy_tickets_url;
            (n() || r()) ? (s.attr("value", "").addClass("ai1ec-hidden"), (o = t.label_a_rsvp_url)) : s.removeClass("ai1ec-hidden"), e("label[for=osec_ticket_url]", i).text(o);
        };
    return {handle_change_is_free: i, check_is_free: n, check_is_price_entered: r};
});
