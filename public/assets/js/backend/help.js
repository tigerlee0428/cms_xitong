define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'help/index' + location.search,
                    //add_url: 'help/add',
                    //edit_url: 'help/edit',
                    del_url: 'help/del',
                    multi_url: 'help/multi',
                    check_url: 'help/check',
                    view_url: 'help/view',
                    log_url: 'help_log/index',
                    table: 'help',
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
                        {field: 'username', title: __('Username')},
                        {field: 'title', title: __('Title'),operate:'like',formatter:title,align:'left'},
                        {field: 'required_time', title: __('Required_time')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'address', title: __('Address')},                        
                        {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('Help seeking'),1:__('Help in'),2:__('Helped'),3:__('Evaluated'),4:__('Closed help')},custom:{0:'success',1:'warning',2:'danger',3:'info',4:'danger'}},
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
								       }
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
            function title(value,row,index){
            	var is_urgent = row.is_urgent ? '<span class="btn btn-xs btn-danger" style="font-size:8px;margin:0 8px 0 0;">'+__('Urgent')+'</span>' : '';
            	var is_work = row.is_work ? '<span class="btn btn-xs btn-success" style="font-size:8px;margin:0 8px 0 0;">'+__('Work')+'</span>' : '';
            	return is_urgent + is_work + value;
            }
            
            
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