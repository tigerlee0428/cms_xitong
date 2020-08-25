define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order_period/index/ids/'+Config.ids + location.search,
                    add_url: 'order_period/add/ids/'+Config.ids,
                    //edit_url: 'order_period/edit',
                    del_url: 'order_period/del',
                    multi_url: 'order_period/multi',
                    order_log_url: 'order_log/index',
                    table: 'order_period',
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
                        {field: 'order_name', title: __('Order_id')},
                        {field: 'start_time', title: __('Start_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,datetimeFormat:'YYYY-MM-DD'},
                        {field: 'end_time', title: __('End_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,datetimeFormat:'YYYY-MM-DD'},
                        {field: 'counts', title: __('Counts'),searchable:false},
                        {field: 'status', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('Not started'),1:__('Under way'),2:__('Has ended'),3:__('Completed')},custom:{0:'success',1:'warning',2:'danger',3:'info'}},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	buttons: [
      								{
      								       name: 'order_log',
      								       text: __('OrderLog'),
      								       title:function(row){return __('OrderLog')},
      								       classname: 'btn btn-xs btn-success btn-dialog',
      								       icon: 'fa fa-folder-o',
      								       url: function(row){return $.fn.bootstrapTable.defaults.extend.order_log_url + "?order_id="+row.order_id + "&period_id="+row.id},
      								       visible:function(row){
      								     	  if(row.counts > 0){
      								     		  return true;
      								     	  }
      								     	  return false;
      								       }
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