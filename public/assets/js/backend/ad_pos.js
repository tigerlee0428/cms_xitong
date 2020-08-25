define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ad_pos/index' + location.search,
                    add_url: 'ad_pos/add',
                    edit_url: 'ad_pos/edit',
                    del_url: 'ad_pos/del',
                    multi_url: 'ad_pos/multi',
                    ad_url: 'ad_info/index',
                    table: 'ad_pos',
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
                        {field: 'title', title: __('Title'),align:'left',operate:'like'},
                        {field: 'position', title: __('Position')},
                        {field: 'tpe', title: __('Tpe'),formatter: Table.api.formatter.flag,searchList:{0:__('Img'),1:__('Article'),2:__('Img And Article')},custom:{0:'warning',1:'success',2:'danger'}},
                        {field: 'modified_at', title: __('Modified_at'),formatter:Table.api.formatter.datetime},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: Table.api.events.operate,
                        	formatter: Table.api.formatter.operate,
                        	buttons:[
             			              {
          			                      name: 'ViewAd',
          			                      text: __('ViewAd'),
          			                      title:function(row){return __('ViewAd') + row.title},
          			                      classname: 'btn btn-xs btn-success btn-dialog',
          			                      icon: 'fa fa-folder-o',
          			                      url: $.fn.bootstrapTable.defaults.extend.ad_url,
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