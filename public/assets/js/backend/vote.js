define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'vote/index/category/'+Config.category + location.search,
                    add_url: 'vote/add/category/'+Config.category,
                    edit_url: 'vote/edit',
                    del_url: 'vote/del',
                    multi_url: 'vote/multi',
                    check_url: 'vote/check',
                    options_url: 'vote_options/index',
                    log_url: 'vote_log/index',
                    table: 'vote',
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
                        {field: 'img', title: __('Img'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'title', title: __('Title'),align:'left',operate:'like',width:350,cellStyle: {css: {"white-space":"normal"}}},
                        {field: 'status', title: __('Status'),searchList: {"0": __('Not started'), "1": __('Under way'),"2":__('Has ended')}, formatter: Table.api.formatter.status,searchable:false},
                        {field: 'start_time', title: __('Start_time'), operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime},
                        {field: 'end_time', title: __('End_time'), operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime,searchable:false},
                        {field: 'joincount', title: __('Joincount'),searchable:false},
                        {field: 'is_check', title: __('Is_check'),formatter: Table.api.formatter.flag,searchList:{0:__('No Check'),1:__('Check Pass'),2:__('Check No Pass')},custom:{0:'warning',1:'success',2:'danger'}},
                        {field: 'is_publish', title: __('Is_publish'),formatter:Table.api.formatter.toggle,searchable:false},
                        {
                        	field: 'operate',
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	buttons: [
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
    			                      name: 'viewoptions',
    			                      text: __('View_vote_options'),
    			                      title:function(row){return __('View_vote_options') + row.title},
    			                      classname: 'btn btn-xs btn-success btn-addtabs',
    			                      icon: 'fa fa-folder-o',
    			                      url: $.fn.bootstrapTable.defaults.extend.options_url,
    			                  },
    			                  {
       			                      name: 'log',
       			                      text: __('Log'),
       			                      title:function(row){return row.title + __('Log')},
       			                      classname: 'btn btn-xs btn-info btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.log_url,
       			                      visible:function(row){
    			                    	  if(row.status > 0 && row.is_check == 1){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      }
       			                  },
    			              ],
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
    	check: function () {
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