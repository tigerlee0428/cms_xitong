define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'work_do/index/style/' + Config.style + location.search,
                    appraise_url: 'work_log/appraise',
                    del_url: 'work_do/del',
                    info_url: 'work_do/info',
                    multi_url: 'work_do/multi',
                    table: 'work_do',
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
                        {field: 'title', title: __('Title')},
                        {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'object', title: __('Object'),searchable:false},
                        {field: 'status', title: __('Status'),formatter:status,searchable:false},
                        {field: 'tpe', title: __('Tpe'),formatter:tpe,searchable:false},
                        {field: 'style', title: __('Style'),visible:false,searchList:{1:__('Style1'),2:__('Style2'),3:__('Style3')},},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                                {
                                    name: 'appraise',
                                    text: __('Appraise'),
                                    title:function(row){return __('Appraise') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.appraise_url,
                                    visible:function(row) {
                                        if (row.status == 2 && Config.area_id == row.area_id ) {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'info',
                                    text: __('Info'),
                                    title:function(row){return __('Info') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.info_url,
                                    visible:function(row) {
                                            return true;
                                    }
                                },
                                {
                                    name: 'completion',
                                    text: __('Completion'),
                                    title:function(row){return __('Completion') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.index_url,
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
                switch(value){
                    case 0:
                        return __('Status1');
                    case 1:
                        return __('Status2');
                    case 2:
                        return __('Status3');
                    case 3:
                        return __('Status4');
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
                    default :
                        return __('Tpe4');

                }
            }

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        mywork:function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'work_do/mywork' + location.search,
                    appoint_url: 'work_do/appoint',
                    info_url: 'work_do/info',
                    multi_url: 'work_do/multi',
                    table: 'work_do',
                    feed_url: 'work_log/add',
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
                        {field: 'title', title: __('Title')},
                        {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'object', title: __('Object'),searchable:false},
                        {field: 'status', title: __('Status'),formatter:status,searchable:false},
                        {field: 'tpe', title: __('Tpe'),formatter:tpe,searchable:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate,
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
                                        if (row.status == 0) {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'feed',
                                    text: __('Feed'),
                                    title:function(row){return __('Feed') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.feed_url,
                                    visible:function(row) {
                                        if (row.status == 0) {
                                            return true;
                                        }
                                    }
                                },
                                {
                                    name: 'info',
                                    text: __('Info'),
                                    title:function(row){return __('Info') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.info_url,
                                    visible:function(row) {
                                            return true;
                                    }
                                },
                            ]
                        }

                    ]
                ]
            });
            function status(value,row,index) {
                switch(value){
                    case 0:
                        return __('Status1');
                    case 1:
                        return __('Status2');
                    case 2:
                        return __('Status3');
                    case 3:
                        return __('Status4');
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
                    default :
                        return __('Tpe4');

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
        info: function () {
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
