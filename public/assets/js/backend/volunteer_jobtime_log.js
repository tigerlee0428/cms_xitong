define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'volunteer_jobtime_log/index/ids/' + Config.ids + location.search,
                    //add_url: 'volunteer_jobtime_log/add',
                    //edit_url: 'volunteer_jobtime_log/edit',
                    //del_url: 'volunteer_jobtime_log/del',
                    //multi_url: 'volunteer_jobtime_log/multi',
                    table: 'volunteer_jobtime_log',
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
                        {field: 'id', title: __('Id'),searchable:false},
                        {field: 'note', title: __('Act_id'),searchable:false},
                        {field: 'jobtime', title: __('Jobtime'),searchable:false,formatter:function(value,row,index){return (value/3600).toFixed(2) + 'h'}},
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