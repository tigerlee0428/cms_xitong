define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order_log/index/ids/' + Config.ids + location.search,
                    //add_url: 'order_log/add',
                    //edit_url: 'order_log/edit',
                    //del_url: 'order_log/del',
                    //multi_url: 'order_log/multi',
                    table: 'order_log',
                }
            });
          //绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });
            
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
            var table = $("#table");

        },
        table: {
            first: function () {
                // 表格1
                var table1 = $("#orderlogtable");
                table1.bootstrapTable({
                    url: 'order_log/index/ids/' + Config.ids + location.search,
                    toolbar: '#orderlog',
                    sortName: 'id',
                    search: false,
                    exportTypes: ['csv', 'excel'],
                    columns: [
                        [
							{field: 'id', title: __('Id')},
							{field: 'uidName', title: __('Uid'),searchable:false},
							{field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
							{field: 'content',title: __('Content'),align: 'left'}
                        ]
                    ]
                });

                // 为表格1绑定事件
                Table.api.bindevent(table1);
            },
            second: function () {
                // 表格2
                var table2 = $("#orderstatisticstable");
                table2.bootstrapTable({
                    url: 'order_log/statistics/ids/' + Config.ids + location.search,
                    toolbar: '#orderstatistics',
                    sortName: 'id',
                    exportTypes: ['csv', 'excel'],
                    search: false,
                    columns: [
                        [
							{field: 'id', title: __('Id')},
							{field: 'areaName', title: __('Area'),searchable:false},
							{field: 'sum', title: __('Sum')},
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);
            }
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});