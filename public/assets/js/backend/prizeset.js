define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'prizeset/index' + location.search,
                    add_url: 'prizeset/add',
                    edit_url: 'prizeset/edit',
                    del_url: 'prizeset/del',
                    multi_url: 'prizeset/multi',
                    table: 'prizeset',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'weigh',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'activity_name', title: __('Aid')},
                        {field: 'prize_name', title: __('Prize_id')},
                        {field: 'title', title: __('Title'),visible:false},
                        {field: 'userids_name', title: __('User_ids')},
                        {field: 'nums', title: __('Nums')},
                        {field: 'weigh', title: __('Weigh')},
                        {field: 'addtime', title: __('Addtime'),formatter:Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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