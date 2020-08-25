define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'event/index/tpe/'+ Config.tpe + location.search,
                    //add_url: 'event/add',
                    //edit_url: 'event/edit',
                    del_url: 'event/del',
                    multi_url: 'event/multi',
                    deal_url: 'event/deal',
                    view_url: 'event/view',
                    table: 'event',
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
                        {field: 'id', title: __('Id'),searchable:false},
                        {field: 'img', title: __('Img'),searchable:false,events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'title', title: __('Title'),operate:'like',align:'left'},
                        {field: 'username', title: __('Username'),operate:'like'},
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'is_open', title: __('Is_open'),formatter:Table.api.formatter.toggle,searchable:false},
                        {field: 'is_deal', title: __('Is_deal'),formatter: Table.api.formatter.flag,searchList:{0:__('No Deal'),1:__('Deal Done')},custom:{0:'danger',1:'success'}},
                        //{field: 'is_check', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('No Check'),1:__('Check Pass'),2:__('Check No Pass')},custom:{0:'warning',1:'success',2:'danger'}},
                        {
                        	field: 'operate',
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate, 
                        	formatter: Table.api.formatter.operate,
                        	buttons:[
             			              {
          			                      name: 'check',
          			                      text: __('Deal'),
          			                      title:function(row){return __('Deal') + row.title},
          			                      classname: 'btn btn-xs btn-success btn-dialog',
          			                      icon: 'fa fa-folder-o',
          			                      url: $.fn.bootstrapTable.defaults.extend.deal_url,
          			                      visible:function(row){
          			                    	  if(row.is_deal == 0){
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
          			                    	  if(row.is_deal == 1){
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
        deal: function () {
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