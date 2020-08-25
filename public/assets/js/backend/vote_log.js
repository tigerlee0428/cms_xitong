define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vote_log/index/ids/' + Config.ids + location.search,
                    //add_url: 'vote_log/add',
                    //edit_url: 'vote_log/edit',
                    //del_url: 'vote_log/del',
                    //multi_url: 'vote_log/multi',
                    table: 'vote_log',
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
                        //{checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'username', title: __('Uid')},
                        {field: 'votename', title: __('Tid')},
                        {field: 'voteoptionname', title: __('Option_id')},
                        {field: 'add_time', title: __('Add_time'),operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime,searchable:false},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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