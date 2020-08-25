define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity_comment/index/ids/' + Config.ids + location.search,
                    //add_url: 'activity_comment/add',
                    //edit_url: 'activity_comment/edit',
                    del_url: 'activity_comment/del',
                    //multi_url: 'activity_comment/multi',
                    table: 'activity_comment',
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
                        {field: 'nickname', title: __('Name')},
                        {field: 'content', title: __('Content')},
                        {field: 'iswinning', title: __('Iswinning'), searchList: {"1":__('Iswinning 1'),"0":__('Iswinning 0')}, formatter: Table.api.formatter.normal},
                        {field: 'addtime', title: __('Addtime'),operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime},
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