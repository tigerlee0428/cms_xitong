define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vote_options/index/ids/' + Config.ids + location.search,
                    add_url: 'vote_options/add/ids/' + Config.ids,
                    edit_url: 'vote_options/edit',
                    del_url: 'vote_options/del',
                    multi_url: 'vote_options/multi',
                    log_url: 'vote_log/index',
                    table: 'vote_options',
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
                        //{field: 'img', title: __('Img'),events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'title', title: __('Title'),align:'left',operate:'like',width:350,cellStyle: {css: {"white-space":"normal"}}},
                        {field: 'tickets', title: __('Tickets'),searchable:false},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	buttons: [
                        	   {
							       name: 'log',
							       text: __('Log'),
							       title:function(row){return row.title + __('Log')},
							       classname: 'btn btn-xs btn-info btn-dialog',
							       icon: 'fa fa-folder-o',
							       url: function(row){return $.fn.bootstrapTable.defaults.extend.log_url + "?options_id=" + row.id},
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});