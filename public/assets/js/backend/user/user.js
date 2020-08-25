define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/user/index',
                    add_url: 'user/user/add',
                    edit_url: 'user/user/edit',
                    del_url: 'user/user/del',
                    multi_url: 'user/user/multi',
                    integrallog_url: 'user_integral_log/index',
                    adjust_url: 'user/user/adjust',
                    table: 'user',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'user.id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'avatar', title: __('Avatar'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},

                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'email', title: __('Email'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        {field: 'score', title: __('Score'), operate: 'BETWEEN', sortable: true},
                        {field: 'logintime', title: __('Logintime'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'loginip', title: __('Loginip'), formatter: Table.api.formatter.search},
                        {field: 'status', title: __('Status'), formatter: Table.api.formatter.status, searchList: {normal: __('Normal'), hidden: __('Hidden')}},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate,
                        	formatter: Table.api.formatter.operate,
                        	buttons:[
           			                  {
           			                      name: 'integrallog',
           			                      text: __('IntegralLog'),
           			                      title:function(row){return row.realname + __('IntegralLog')},
           			                      classname: 'btn btn-xs btn-success btn-dialog',
           			                      icon: 'fa fa-folder-o',
           			                      url: $.fn.bootstrapTable.defaults.extend.integrallog_url,
           			                  },
           			               {
           			                      name: 'integrallog',
           			                      text: __('Adjust'),
           			                      title:function(row){return row.realname + __('Adjust')},
           			                      classname: 'btn btn-xs btn-success btn-dialog',
           			                      icon: 'fa fa-folder-o',
           			                      url: $.fn.bootstrapTable.defaults.extend.adjust_url,
           			                  },
   			                  ]
                        }
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
        adjust: function () {
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