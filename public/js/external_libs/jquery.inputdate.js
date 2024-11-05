console.log('Hellow World');

timely.define([
        "jquery_timely",
        "external_libs/jquery.calendrical_timespan"
    ],
    function (jQueryTimely, jQueryTimespan) {
        function n(e) {
            e.addClass("error").fadeOut("normal", function () {
                e.val(e.data("timespan.stored")).removeClass("error").fadeIn("fast")
            })
        }

        function r() {
            jQueryTimely(this).data("timespan.stored", this.value)
        }

        function i(e, n, i, s, o) {
            n.val(n.data("timespan.initial_value"));
            var u = parseInt(n.val());
            isNaN(parseInt(u)) ? u = new Date(o) : u = new Date(parseInt(u) * 1e3), e.val(jQueryTimespan.formatDate(u, s)), e.each(r)
        }

        var s = {
                start_date_input: "date-input",
                start_time: "time",
                twentyfour_hour: !1,
                date_format: "def",
                now: new Date
            },
            o = {
                init: function (o) {
                    var u = jQueryTimely.extend({}, s, o), startDateInput = jQueryTimely(u.start_date_input), f = jQueryTimely(u.start_time), l = startDateInput, whatIsC = startDateInput;


                    return whatIsC.bind("focus.timespan", r), l.calendricalDate({
                        today: new Date(u.now.getFullYear(), u.now.getMonth(), u.now.getDate()),
                        dateFormat: u.date_format,
                        monthNames: u.month_names,
                        dayNames: u.day_names,
                        weekStartDay: u.week_start_day
                    }), l.bind("blur.timespan", function () {
                        var r = jQueryTimespan.parseDate(this.value, u.date_format);
                        isNaN(r) ? n(jQueryTimely(this)) : (jQueryTimely(this).data("timespan.stored", this.value), jQueryTimely(this).val(jQueryTimespan.formatDate(r, u.date_format)))
                    }), startDateInput.bind("focus.timespan", function () {
                        var e = jQueryTimespan.parseDate(startDateInput.val(), u.date_format).getTime() / 1e3
                    }).bind("blur.timespan", function () {
                        var e = jQueryTimespan.parseDate(startDateInput.data("timespan.stored"), u.date_format)
                    }), startDateInput.closest("form").bind("submit.timespan", function () {
                        var e = jQueryTimespan.parseDate(startDateInput.val(), u.date_format).getTime() / 1e3;
                        isNaN(e) && (e = ""), f.val(e)
                    }), f.data("timespan.initial_value", f.val()), i(startDateInput, f, u.twentyfour_hour, u.date_format, u.now), this
                }, reset: function (t) {
                    var n = jQueryTimely.extend({}, s, t);
                    return i(jQueryTimely(n.start_date_input), jQueryTimely(n.start_time), n.twentyfour_hour, n.date_format, n.now), this
                }, destroy: function (t) {
                    return t = jQueryTimely.extend({}, s, t), jQueryTimely.each(t, function (t, n) {
                        jQueryTimely(n).unbind(".timespan")
                    }), jQueryTimely(t.start_date_input).closest("form").unbind(".timespan"), this
                }
            };
        jQueryTimely.inputdate = function (t) {
            if (o[t]) return o[t].apply(this, Array.prototype.slice.call(arguments, 1));
            if (typeof t == "object" || !t) return o.init.apply(this, arguments);
            jQueryTimely.error("Method " + t + " does not exist on jQuery.timespan")
        }
    });
