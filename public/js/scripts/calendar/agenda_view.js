timely.define(["jquery_timely"], function (e) {
    var t = function () {
        e(this).closest(".ai1ec-event").toggleClass("ai1ec-expanded").find(".ai1ec-event-summary").slideToggle(300)
    }, n = function () {
        var t = e(this).closest(".ai1ec-calendar");
        t.find(".ai1ec-expanded .ai1ec-event-toggle").click()
    }, r = function () {
        var t = e(this).closest(".ai1ec-calendar");
        t.find(".ai1ec-event:not(.ai1ec-expanded) .ai1ec-event-toggle").click()
    };
    return {toggle_event: t, collapse_all: n, expand_all: r}
});
