define([], function () {
    require([], function () {
    //绑定data-toggle=addresspicker属性点击事件

    $(document).on('click', "[data-toggle='addresspicker']", function () {
        var that = this;
        var callback = $(that).data('callback');
        var input_id = $(that).data("input-id") ? $(that).data("input-id") : "";
        var lat_id = $(that).data("lat-id") ? $(that).data("lat-id") : "";
        var lng_id = $(that).data("lng-id") ? $(that).data("lng-id") : "";
        var lat = lat_id ? $("#" + lat_id).val() : '';
        var lng = lng_id ? $("#" + lng_id).val() : '';
        var url = "/addons/address/index/select";
        url += (lat && lng) ? '?lat=' + lat + '&lng=' + lng : '';
        Fast.api.open(url, '位置选择', {
            callback: function (res) {
                input_id && $("#" + input_id).val(res.address);
                lat_id && $("#" + lat_id).val(res.lat);
                lng_id && $("#" + lng_id).val(res.lng);
                try {
                    //执行回调函数
                    if (typeof callback === 'function') {
                        callback.call(that, res);
                    }
                } catch (e) {

                }
            }
        });
    });
});

require(['../addons/bootstrapcontextmenu/js/bootstrap-contextmenu'], function (undefined) {
    if (Config.controllername == 'index' && Config.actionname == 'index') {
        $("body").append(
            '<div id="context-menu">' +
            '<ul class="dropdown-menu" role="menu">' +
            '<li><a tabindex="-1" data-operate="refresh"><i class="fa fa-refresh fa-fw"></i>刷新</a></li>' +
            '<li><a tabindex="-1" data-operate="refreshTable"><i class="fa fa-table fa-fw"></i>刷新表格</a></li>' +
            '<li><a tabindex="-1" data-operate="close"><i class="fa fa-close fa-fw"></i>关闭</a></li>' +
            '<li><a tabindex="-1" data-operate="closeOther"><i class="fa fa-window-close-o fa-fw"></i>关闭其他</a></li>' +
            '<li class="divider"></li>' +
            '<li><a tabindex="-1" data-operate="closeAll"><i class="fa fa-power-off fa-fw"></i>关闭全部</a></li>' +
            '</ul>' +
            '</div>');

        $(".nav-addtabs").contextmenu({
            target: "#context-menu",
            scopes: 'li[role=presentation]',
            onItem: function (e, event) {
                var $element = $(event.target);
                var tab_id = e.attr('id');
                var id = tab_id.substr('tab_'.length);
                var con_id = 'con_' + id;
                switch ($element.data('operate')) {
                    case 'refresh':
                        $("#" + con_id + " iframe").attr('src', function (i, val) {
                            return val;
                        });
                        break;
                    case 'refreshTable':
                        try {
                            if ($("#" + con_id + " iframe").contents().find(".btn-refresh").size() > 0) {
                                $("#" + con_id + " iframe")[0].contentWindow.$(".btn-refresh").trigger("click");
                            }
                        } catch (e) {

                        }
                        break;
                    case 'close':
                        if (e.find(".close-tab").length > 0) {
                            e.find(".close-tab").click();
                        }
                        break;
                    case 'closeOther':
                        e.parent().find("li[role='presentation']").each(function () {
                            if ($(this).attr('id') == tab_id) {
                                return;
                            }
                            if ($(this).find(".close-tab").length > 0) {
                                $(this).find(".close-tab").click();
                            }
                        });
                        break;
                    case 'closeAll':
                        e.parent().find("li[role='presentation']").each(function () {
                            if ($(this).find(".close-tab").length > 0) {
                                $(this).find(".close-tab").click();
                            }
                        });
                        break;
                    default:
                        break;
                }
            }
        });
    }
    $(document).on('click', function () { // iframe内点击 隐藏菜单
        try {
            top.window.$(".nav-addtabs").contextmenu("closemenu");
        } catch (e) {
        }
    });

});
if ($('.cropper', $('form[role="form"]')).length > 0) {
    var allowAttr = [
        'aspectRatio', 'autoCropArea', 'cropBoxMovable', 'cropBoxResizable', 'minCropBoxWidth', 'minCropBoxHeight', 'minContainerWidth', 'minContainerHeight',
        'minCanvasHeight', 'minCanvasWidth', 'croppedWidth', 'croppedHeight', 'croppedMinWidth', 'croppedMinHeight', 'croppedMaxWidth', 'croppedMaxHeight', 'fillColor'
    ];
    String.prototype.toLineCase = function () {
        return this.replace(/[A-Z]/g, function (match) {
            return "-" + match.toLowerCase();
        });
    };

    var btnAttr = [];
    $.each(allowAttr, function (i, j) {
        btnAttr.push('data-' + j.toLineCase() + '="<%=data.' + j + '%>"');
    });
    var btn = '<button class="btn btn-success btn-cropper btn-xs" data-input-id="<%=data.inputId%>" ' + btnAttr.join(" ") + ' style="position:absolute;top:10px;right:15px;">裁剪</button>';
    require(['upload'], function (Upload) {
        //图片裁剪
        $(document).on('click', '.btn-cropper', function () {
            var image = $(this).closest("li").find('.thumbnail').data('url');
            var input = $("#" + $(this).data("input-id"));
            var url = image;
            var data = $(this).data();
            var params = [];
            $.each(allowAttr, function (i, j) {
                if (typeof data[j] !== 'undefined' && data[j] !== '') {
                    params.push(j + '=' + data[j]);
                }
            });
            (parent ? parent : window).Fast.api.open('/addons/cropper/index/cropper?url=' + image + (params.length > 0 ? '&' + params.join('&') : ''), '裁剪', {
                callback: function (data) {
                    if (typeof data !== 'undefined') {
                        var arr = data.dataURI.split(','), mime = arr[0].match(/:(.*?);/)[1],
                            bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
                        while (n--) {
                            u8arr[n] = bstr.charCodeAt(n);
                        }
                        var urlArr = url.split('.');
                        var suffix = 'png';
                        url = urlArr.join('');
                        var filename = url.substr(url.lastIndexOf('/') + 1);
                        var exp = new RegExp("\\." + suffix + "$", "i");
                        filename = exp.test(filename) ? filename : filename + "." + suffix;
                        var file = new File([u8arr], filename, {type: mime});
                        Upload.api.send(file, function (data) {
                            input.val(input.val().replace(image, data.url)).trigger("change");
                        }, function (data) {
                        });
                    }
                },
                area: ["880px", "520px"],
            });
            return false;
        });

        var insertBtn = function () {
            return arguments[0].replace(arguments[2], btn + arguments[2]);
        };
        Upload.config.previewtpl = Upload.config.previewtpl.replace(/<li(.*?)>(.*?)<\/li>/, insertBtn);
        $(".cropper").each(function () {
            var preview = $("#" + $(this).data("preview-id"));
            if (preview.size() > 0 && preview.data("template")) {
                var tpl = $("#" + preview.data("template"));
                tpl.text(tpl.text().replace(/<li(.*?)>(.*?)<\/li>/, insertBtn));
            }
        });
    });
}
require.config({
    paths: {
        'editable': '../libs/bootstrap-table/dist/extensions/editable/bootstrap-table-editable.min',
        'x-editable': '../addons/editable/js/bootstrap-editable.min',
    },
    shim: {
        'editable': {
            deps: ['x-editable', 'bootstrap-table']
        },
        "x-editable": {
            deps: ["css!../addons/editable/css/bootstrap-editable.css"],
        }
    }
});
if ($("table.table").size() > 0) {
    require(['editable', 'table'], function (Editable, Table) {
        $.fn.bootstrapTable.defaults.onEditableSave = function (field, row, oldValue, $el) {
            var data = {};
            //data["row[" + field + "]"] = row[field];
            data["params"] = field + "=" + row[field];
            Fast.api.ajax({
                url: this.extend.multi_url + "/ids/" + row[this.pk],
                data: data
            });
        };
    });
}
});