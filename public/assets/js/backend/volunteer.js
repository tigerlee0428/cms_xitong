define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'volunteer/index' + location.search,
                    add_url: 'volunteer/add',
                    edit_url: 'volunteer/edit',
                    del_url: 'volunteer/del',
                    multi_url: 'volunteer/multi',
                    check_url: 'volunteer/check',
                    import_url: 'volunteer/import',
                    integrallog_url: 'volunteer_integral_log/index',
                    jobtimelog_url: 'volunteer_jobtime_log/index',
                    volunteergroup_url: 'volunteer_group/index',
                    table: 'volunteer',
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
                        {field: 'head_img', title: __('Head_img'),events: Table.api.events.image,formatter:Table.api.formatter.image,searchable:false},
                        {field: 'uid', title: __('Uid')},
                        {field: 'name', title: __('Name'),operate:'like'},
                        {field: 'areaName', title: __('Area_id')},
                        {field: 'card', title: __('Card')},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'join_time', title: __('Join_time'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'jobtime', title: __('Jobtime'),searchable:false,formatter:function(value,row,index){return (value/3600).toFixed(2) + 'h'}},
                        {field: 'scores', title: __('Scores'),searchable:false},
                        {field: 'is_check', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('No Check'),1:__('Check Pass'),2:__('Check No Pass')},custom:{0:'warning',1:'success',2:'danger'}},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	align: 'left',
                        	buttons:[
          			             {
       			                      name: 'check',
       			                      text: __('Check'),
       			                      title:function(row){return __('Check') + row.name},
       			                      classname: 'btn btn-xs btn-danger btn-dialog',
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
       			                      name: 'integrallog',
       			                      text: __('IntegralLog'),
       			                      title:function(row){return row.name + __('IntegralLog')},
       			                      classname: 'btn btn-xs btn-success btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.integrallog_url,
       			                  },
       			                  {
       			                      name: 'jobtimelog',
       			                      text: __('JobTimeLog'),
       			                      title:function(row){return row.name + __('JobTimeLog')},
       			                      classname: 'btn btn-xs btn-info btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.jobtimelog_url,
       			                  },
       			                  {
       			                      name: 'volunteerGroup',
       			                      text: __('ViewGroup'),
       			                      title:function(row){return row.name + __('ViewGroup')},
       			                      classname: 'btn btn-xs btn-warning btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.volunteergroup_url,
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
        check:function () {
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