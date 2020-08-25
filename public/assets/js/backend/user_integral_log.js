define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_integral_log/index/ids/' + Config.ids + location.search,
                    add_url: 'user_integral_log/add',
                    edit_url: 'user_integral_log/edit',
                    del_url: 'user_integral_log/del',
                    multi_url: 'user_integral_log/multi',
                    table: 'user_integral_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'event_code', title: __('Event_code')},
                        {field: 'event_note', title: __('Event_note')},
                        {field: 'scores', title: __('Scores')},
                        {field: 'note', title: __('Note')},
                        {field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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