define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'work_order/index/tpe/' + Config.tpe + location.search,
                    add_url: 'work_order/add',
                    edit_url: 'work_order/edit',
                    appoint_url: 'work_order/appoint',
                    completion_url: 'work_do/index',
                    del_url: 'work_order/del',
                    up_url: 'work_order/upRequest',
                    multi_url: 'work_order/multi',
                    table: 'work_order',
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
                        {field: 'title', title: __('Title'),align:'left'},
                        {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,sortable:true,datetimeFormat:'YYYY-MM-DD'},
                        {field: 'status', title: __('Status'),formatter:status,searchable:false},
                        {field: 'tpe', title: __('Tpe'),formatter:tpe,searchable:false},
                        {field: 'operate', title: __('Operate'),
                            table: table, events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'edit',
                                    text: __('Edit'),
                                    title:function(row){return __('Edit') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.edit_url,
                                    visible:function(row) {
                                        if (row.tpe == 3 && row.status == 0) {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'appoint',
                                    text: __('Appoint'),
                                    title:function(row){return __('Appoint') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.appoint_url,
                                    visible:function(row) {
                                        if(row.tpe == 6){
                                            if ((row.status == 0 && Config.area_id == row.area_id) || (Config.is_center==1 && row.status ==3) ) {
                                                return true;
                                            }
                                        }else{
                                            if(row.status == 0) {
                                                return true;
                                            }
                                        }

                                    }
                                },
                                {
                                    name: 'up',
                                    text: __('Up'),
                                    title:function(row){return __('Up') + row.title},
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.up_url,
                                    visible:function(row) {
                                        if (row.status == 0) {
                                            return true;
                                        }
                                    },
                                    success: function (data, ret){
                                        if(ret.code == 1){
                                            table.bootstrapTable('refresh');
                                        }
                                    }
                                },
                                {
                                    name: 'completion',
                                    text: __('Completion'),
                                    title:function(row){return __('Completion') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url:function(row){
                                        return $.fn.bootstrapTable.defaults.extend.completion_url+"?wid="+row.id;
                                    },
                                    visible:function(row) {
                                        if (row.status == 1) {
                                            return true;
                                        }
                                    }
                                },
                        ]

                        }
                    ]
                ]
            });

            function status(value,row,index) {
                if (row.status == 0) {
                    return __('Status1');
                } else if (row.status == 1) {
                    return __('Status2');

                }
            }
            function tpe(value,row,index){
                switch(value){
                    case 1:
                        return __('Tpe1');
                    case 2:
                        return __('Tpe2');
                    case 3:
                        return __('Tpe3');
                    case 4:
                        return __('Tpe4');
                    case 5:
                        return __('Tpe5');
                    default :
                        return __('Tpe6');

                }
            }
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
        api: {
            bindevent: function () {
                    $("#c-type").on('change',function(){
                        var val = $(this).val();
                        console.log(val)
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
                            $("#grou-box").hide();
                            $("#admin-box").show();
                        }
                    });
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
