define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'volunteer_integral_log/index/ids/' + Config.ids + location.search,
                    //add_url: 'volunteer_integral_log/add',
                    //edit_url: 'volunteer_integral_log/edit',
                    //del_url: 'volunteer_integral_log/del',
                    //multi_url: 'volunteer_integral_log/multi',
                    table: 'volunteer_integral_log',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                exportTypes: ['csv', 'excel'],
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),searchable:false},
                        {field: 'volunteer', title: __('Vid'),searchable:false},
                        {field: 'event_code', title: __('Event_code'),operate:'like'},
                        {field: 'event_note', title: __('Event_note'),operate:'like'},
                        {field: 'note', title: __('Note'),searchable:false},
                        {field: 'scores', title: __('Scores'),searchable:false},
                        {field: 'create_at', title: __('Create_at'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        /*add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },*/
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});