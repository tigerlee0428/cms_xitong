define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'record/index' + location.search,
                    //add_url: 'record/add',
                    //edit_url: 'record/edit',
                    del_url: 'record/del',
                    //multi_url: 'record/multi',
                    get_url: 'record/get',
                    table: 'record',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                dblClickToEdit: false,
                exportTypes: ['csv', 'excel'],
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'goods_name', title: __('Goods_name'),operate:'like',align:'left'},
                        {field: 'name', title: __('Name'),operate:'like'},
                        {field: 'mobile', title: __('Mobile'),operate:'like'},
                        {field: 'integral', title: __('Integral'),searchable:false},
                        {field: 'num', title: __('Num'),searchable:false},
                        {field: 'add_time', title: __('Add_time'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'provide_time', title: __('Provide_time'),operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'sts', title: __('Sts'),formatter: Table.api.formatter.flag,searchList:{1:__('No get'),2:__('Has get')},custom:{1:'success',2:'danger'}},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	buttons:[
          			                  {
       			                      name: 'get',
       			                      text: __('Get'),
       			                      title:function(row){return __('Get')},
       			                      classname: 'btn btn-xs btn-danger btn-ajax',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.get_url,
       			                      visible:function(row){
       			                    	  if(row.sts == 1){
       			                    		  return true;
       			                    	  }
       			                    	  return false;
       			                      },
	       			                  success: function (data, ret){
	 			                    	  if(ret.code == 1){
	 			                    		  table.bootstrapTable('refresh');
	 			                    	  }
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