timely.define(["jquery_timely", "ai1ec_config", "domReady", "external_libs/colorpicker"], function (e, t, n) {
    var r, i = function (n) {
        n.preventDefault(), typeof r == "undefined" && (r = wp.media.frames.file_frame = wp.media({
            title: t.choose_image_message,
            button: {text: t.choose_image_message},
            multiple: !1
        }), r.on("select", function () {
            var t = r.state().get("selection").first().toJSON();
            e("#osec_category_imag_preview").attr("src", t.url), e("#osec_category_image_url").val(t.url)
        })), r.open()
    };
    e("#tag-color").click(function () {
        var t = e("#tag-color").offset(), n = t.top + e("#tag-color").height(),
            r = t.left + 1, i = e('<ul class="timely colorpicker-list"></ul>'),
            o = e('<li class="ai1ec-btn ai1ec-btn-xs ai1ec-btn-default ai1ec-btn-block"><i class="ai1ec-fa ai1ec-fa-ellipsis-h ai1ec-fa-lg"></i></li>'),
            a = "", f;
        for (f = 1; f <= 32; f++) {
            a += '<li class="color-' + f + '"></li>';
        }
        a = e(a), o.ColorPicker({
            onSubmit: function (t, n, r, s) {
                e("#tag-color-background").css("background-color", "#" + n), e("#tag-color-value").val("#" + n), e(s).ColorPickerHide(), i.remove()
            }, onBeforeShow: function () {
                i.hide(), e(document).unbind("mousedown", u);
                var t = e("#tag-color-value").val();
                t = t.length > 0 ? t : "#ffffff", e(this).ColorPickerSetColor(t)
            }
        }), a.click(function () {
            var t = e(this).css("background-color");
            t = "rgba(0, 0, 0, 0)" === t ? "" : s(t), e("#tag-color-background").css("background-color", t), e("#tag-color-value").val(t), i.remove()
        }), i.append(a).append(o), i.appendTo("body").css({
            top: n + "px",
            left: r + "px"
        }), e(document).bind("mousedown", {ls: i}, u)
    }), e("#tag-color-value-remove").click(function () {
        e("#tag-color-background").css("background-color", ""), e("#tag-color-value").val("")
    });
    var s = function (e) {
        return e = e.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/), "#" + o(e[1]) + o(e[2]) + o(e[3])
    }, o = function (e) {
        return ("0" + parseInt(e, 10).toString(16)).slice(-2)
    }, u = function (t) {
        a(t.data.ls.get(0), t.target, t.data.ls.get(0)) || (e(t.data.ls.get(0)).remove(), e(document).unbind("mousedown", u))
    }, a = function (e, t, n) {
        if (e === t) {
            return !0;
        }
        if (e.contains) {
            return e.contains(t);
        }
        if (e.compareDocumentPosition) {
            return !!(e.compareDocumentPosition(t) & 16);
        }
        var r = t.parentNode;
        while (r && r !== n) {
            if (r === e) {
                return !0;
            }
            r = r.parentNode
        }
        return !1
    }, f = function () {
        n(function () {
            e("#osec_category_image_uploader").click(i);
            var t = e("#osec_category_imag_preview").attr("src");
            t && t.length > 0 && e("#osec_category_image_url").val(t)
        })
    };
    return {start: f}
});
