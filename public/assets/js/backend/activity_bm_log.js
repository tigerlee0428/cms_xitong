define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity_bm_log/index/ids/' + Config.ids + location.search,
                    adjust_url: 'activity_bm_log/adjust',
                    table: 'activity_bm_log',
                }
            });
			//绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                var panel = $($(this).attr("href"));
                if (panel.size() > 0) {
                    Controller.table[panel.attr("id")].call(this);
                    $(this).on('click', function (e) {
                        $($(this).attr("href")).find(".btn-refresh").trigger("click");
                    });
                }
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });
            
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");

        },
		table: {
            first: function () {
                // 表格1
                var table1 = $("#bmlogtable");
                table1.bootstrapTable({
                    url: 'activity_bm_log/index/ids/'+Config.ids+'?tpe=1',
                    toolbar: '#bmlog',
                    sortName: 'id',
                    search: false,
                    exportTypes: ['csv', 'excel'],
                    columns: [
                        [
                            {field: 'id', title: 'ID',searchable:false},
                            {field: 'name', title: __('Name'),searchable:false},
                            {field: 'mobile', title: __('Mobile'),operate:'like'},
                            {field: 'is_sign', title: __('Is_sign'),searchable:false,formatter:Table.api.formatter.flag,searchList:{0:__('No Sign'),1:__('Has Sign')},custom:{0:'success',1:'danger'}},
                            {field: 'addtime', title: __('Addtime'),searchable:false,operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime},
                            {field: 'score', title: __('Score'),searchable:false},
                            {field: 'total_score', title: __('TotalScore'),searchable:false},
                            {
                            	field: 'operate', 
                            	title: __('Operate'), 
                            	table: table1, 
                            	events: Table.api.events.operate, 
                            	formatter: Table.api.formatter.operate,
                            	buttons:[
              			              {
           			                      name: 'adjust',
           			                      text: __('Adjust'),
           			                      title:function(row){return __('Adjust') + row.title},
           			                      classname: 'btn btn-xs btn-success btn-dialog',
           			                      icon: 'fa fa-folder-o',
           			                      url: $.fn.bootstrapTable.defaults.extend.adjust_url,
           			                      visible:function(row){
           			                    	  if(row.is_sign == 1){
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

                // 为表格1绑定事件
                Table.api.bindevent(table1);
            },
            second: function () {
                // 表格2
                var table2 = $("#zmlogtable");
                table2.bootstrapTable({
                    url: 'activity_bm_log/index/ids/'+Config.ids+'?tpe=2',
                    toolbar: '#zmlog',
                    sortName: 'id',
                    exportTypes: ['csv', 'excel'],
                    search: false,
                    columns: [
                        [
                            {field: 'id', title: 'ID',searchable:false},
                            {field: 'name', title: __('Name'),searchable:false},
                            {field: 'mobile', title: __('Mobile'),operate:'like'},
                            {field: 'is_sign', title: __('Is_sign'),formatter:Table.api.formatter.flag,searchList:{0:__('No Sign'),1:__('Has Sign')},custom:{0:'success',1:'danger'},searchable:false},
							{field: 'addtime', title: __('Addtime'),operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime,searchable:false},
                        ]
                    ]
                });

                // 为表格2绑定事件
                Table.api.bindevent(table2);
            },
            
            
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        adjust: function(){
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