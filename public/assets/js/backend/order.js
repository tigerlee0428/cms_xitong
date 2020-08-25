define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    order_log_url: 'order_log/index',
                    addperiod_url: 'order_period/add',
                    viewperiod_url:'order_period/index',
                    table: 'order',
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
                        {field: 'img', title: __('Img'), events: Table.api.events.image, formatter: Table.api.formatter.images},
                        {field: 'title', title: __('Title'),align:'left',operate:'like'},
                        {field: 'content', title: __('Content')},
                        {field: 'counts', title: __('Counts')},
                        {
                        	field: 'operate',
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	buttons: [
								  {
								       name: 'creat_period',
								       text: __('CreatPeriod'),
								       title:function(row){return __('CreatPeriod')},
								       classname: 'btn btn-xs btn-success btn-dialog',
								       icon: 'fa fa-folder-o',
								       url: $.fn.bootstrapTable.defaults.extend.addperiod_url,
								  },
								  {
								       name: 'view_period',
								       text: __('ViewPeriod'),
								       title:function(row){return __('ViewPeriod')},
								       classname: 'btn btn-xs btn-success btn-dialog',
								       icon: 'fa fa-folder-o',
								       url: $.fn.bootstrapTable.defaults.extend.viewperiod_url,
								       
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