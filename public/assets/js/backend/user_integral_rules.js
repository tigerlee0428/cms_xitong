define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user_integral_rules/index' + location.search,
                    add_url: 'user_integral_rules/add',
                    edit_url: 'user_integral_rules/edit',
                    del_url: 'user_integral_rules/del',
                    multi_url: 'user_integral_rules/multi',
                    table: 'user_integral_rules',
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
                        {field: 'id', title: __('Id')},
                        {field: 'event_code', title: __('Event_code'),operate:'like'},
                        {field: 'event_note', title: __('Event_note'),operate:'like'},
                        {field: 'openid_limit', title: __('Openid_limit')},
                        {field: 'day_limit', title: __('Day_limit'),formatter: Table.api.formatter.status,searchList:{0:__('No'),1:__('Yes')}},
                        {field: 'grand_type', title: __('Grand_type'),formatter: Table.api.formatter.status,searchList:{0:__('Behavioral Integral'),1:__('Custom Integral')}},
                        {field: 'credit_amount', title: __('Credit_amount')},
                        {field: 'is_publish', title: __('Is_publish'),formatter:Table.api.formatter.toggle,searchable:false},
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