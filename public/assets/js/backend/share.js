define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'share/index' + location.search,
                    //add_url: 'share/add',
                    //edit_url: 'share/edit',
                    del_url: 'share/del',
                    multi_url: 'share/multi',
                    check_url: 'share/check',
                    view_url: 'share/view',
                    log_url: 'share_log/index',
                    table: 'share',
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
                        {field: 'username', title: __('Username')},
                        {field: 'title', title: __('Title'),operate:'like',align:'left'},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'area_name', title: __('Area_id')},
                        {field: 'add_time', title: __('Add_time'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('Free'),1:__('Busy')},custom:{0:'success',1:'danger'}},
                        {field: 'is_check', title: __('Is_check'),formatter: Table.api.formatter.flag,searchList:{0:__('No Check'),1:__('Check Pass'),2:__('Check No Pass')},custom:{0:'warning',1:'success',2:'danger'}},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	buttons:[
     								{
     								       name: 'check',
     								       text: __('Check'),
     								       title:function(row){return __('Check') + row.title},
     								       classname: 'btn btn-xs btn-success btn-dialog',
     								       icon: 'fa fa-folder-o',
     								       url: $.fn.bootstrapTable.defaults.extend.check_url,
     								       visible:function(row){
     								     	  if(row.is_check == 0){
     								     		  return true;
     								     	  }
     								     	  return false;
     								       },
     								  },
     								  {
     							          name: 'view',
     							             text: __('View'),
     							             title:function(row){return __('View') + row.title},
     							             classname: 'btn btn-xs btn-success btn-dialog',
     							             icon: 'fa fa-folder-o',
     							             url: $.fn.bootstrapTable.defaults.extend.view_url,
     							             visible:function(row){
     							           	  if(row.is_check == 1){
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
        check: function () {
            Controller.api.bindevent();
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function(data){Fast.api.refreshmenu();});
            }
        }
    };
    return Controller;
});