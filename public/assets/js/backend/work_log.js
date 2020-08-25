define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'work_log/index' + location.search,
                    del_url: 'work_log/del',
                    multi_url: 'work_log/multi',
                    table: 'work_log',
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
                        {field: 'wid', title: __('Wid')},
                        {field: 'wdid', title: __('Wdid')},
                        {field: 'reply_content', title: __('Reply_content')},
                        {field: 'reply_time', title: __('Reply_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'img', title: __('Img')},
                        {field: 'is_score', title: __('Is_score')},
                        {field: 'score_user', title: __('Score_user')},
                        {field: 'scores', title: __('Scores')},
                        {field: 'score_time', title: __('Score_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'appraise', title: __('Appraise')},
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
        appraise: function () {
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