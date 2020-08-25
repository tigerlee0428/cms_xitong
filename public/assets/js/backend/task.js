define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'task/index' + location.search,
                    add_url: 'task/add',
                    edit_url: 'task/edit',
                    appoint_url: 'task/appoint',
                    task_do_url: 'task_do/index',
                    designate_url:'task/designate',
                    to_url: 'task/toperator',
                    del_url: 'task/del',
                    multi_url: 'task/multi',
                    table: 'task',
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
                        {field: 'title', title: __('Title'),width:450},
                        {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'appoint',
                                    text: __('Appoint'),
                                    title:function(row){return __('Appoint') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.appoint_url,
                                    visible:function(row) {
                                        if (row.is_appoint == 0) {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'completion',
                                    text: __('Completion'),
                                    title:function(row){return __('Completion') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.task_do_url,
                                    visible:function(row){
                                    	if (row.is_appoint == 1) {
                                            return true;
                                        }
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
        appoint: function () {
            Controller.api.bindevent();
        },
        completion: function () {
            Controller.api.bindevent();
        },
        designate: function () {
            Controller.api.bindevent();
        },
        toperator:function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                $("#c-type").on('change',function(){
                    var val = $(this).val();
                    if(val == 1){
                        $("#area-box").show();
                        $("#group-box").hide();
                        $("#admin-box").hide();
                    }else if(val == 2){
                        $("#area-box").hide();
                        $("#group-box").show();
                        $("#admin-box").hide();
                    }else if(val == 3){
                        $("#area-box").hide();
                        $("#group-box").hide();
                        $("#admin-box").show();
                    }
                });
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});