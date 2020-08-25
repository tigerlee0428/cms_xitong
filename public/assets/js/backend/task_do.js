define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: window.location.pathname + location.search,
                    appraise_url: 'task_do/appraise',
                    info_url: 'task_do/info',
                    multi_url: 'task_do/multi',
                    table: 'task_do',
                }
            });
            Controller.table['first'].call(this);
        },
        mytask:function(){
            Table.api.init({
                extend: {
                    index_url: window.location.pathname + location.search,
                    multi_url: 'task_do/multi',
                    table: 'task_do',
                    appoint_url: 'task_do/appoint',
                    task_do_url: 'task_do/index/tpe/1',
                    to_url: 'task_do/toperator',
                    designate_url:'task_do/designate',
                    info_url: 'task_do/info',
                    add_activity_url:'activity/add',
                }
            });
            Controller.table['second'].call(this);
        },
        table:{
            first:function(){
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
                            {field: 'area_name', title: __('Area_name')},
                            {field: 'status', title: __('Status'),formatter:status,searchable:false},
                            {field: 'need_finish_time', title: __('Need_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'operate',
                                title: __('Operate'),
                                table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons:[
                              /*      {
                                        name: 'appraise',
                                        text: __('Appraise'),
                                        title:function(row){return __('Appoint') + row.title},
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-folder-o',
                                        url: $.fn.bootstrapTable.defaults.extend.appraise_url,
                                        visible:function(row){
                                            if(row.status == 3){
                                                return true;
                                            }
                                            return false;
                                        }
                                    },*/
                                    {
                                        name: 'info',
                                        text: __('Info'),
                                        title:function(row){return __('Info') + row.title},
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-folder-o',
                                        url: $.fn.bootstrapTable.defaults.extend.info_url,
                                        visible:function(row){
                                            return true;
                                        }
                                    },

                                ],
                            }
                        ]
                    ]
                });

                function status(value,row,index){
                    if(row.status == 0){
                        return __('Status1');
                    }else if(row.status == 1){
                        return __('Status2');
                    }else if(row.status == 2){
                        return __('Status3');
                    }else if(row.status == 3){
                        return __('Status4');
                    }else {
                        return __('Status5');
                    }
                }
                // 为表格绑定事件
                Table.api.bindevent(table);
            },
            second:function(){
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
                            {field: 'status', title: __('Status'),formatter:status,searchable:false},
                            {field: 'area_name', title: __('Area_name')},
                            {field: 'need_finish_time', title: __('Need_finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                            {field: 'operate',
                                title: __('Operate'),
                                table: table,
                                events: Table.api.events.operate,
                                formatter: Table.api.formatter.operate,
                                buttons:[
                                    {
                                        name: 'appoint',
                                        text: __('Appoint'),
                                        title:function(row){return __('Appoint') + row.title},
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-folder-o',
                                        url: $.fn.bootstrapTable.defaults.extend.appoint_url,
                                        visible:function(row){
                                            if(row.status ==0 ){
                                                return true;
                                            }
                                            return false
                                        }
                                    },
                                    {
                                        name:'toperator',
                                        text: __('Toperator'),
                                        title:function(row){return __('Toperator') + row.title},
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-folder-o',
                                        url: $.fn.bootstrapTable.defaults.extend.to_url,
                                        visible:function(row){
                                            if(row.status ==0){
                                                return true;
                                            }
                                            return false
                                        }
                                    },
                                    {
                                        name:'designate',
                                        text: __('Designate'),
                                        title:function(row){return __('Designate') + row.title},
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-folder-o',
                                        url: $.fn.bootstrapTable.defaults.extend.designate_url,
                                        visible:function(row){
                                            if(row.status ==0 ){
                                                return true;
                                            }
                                            return false
                                        }
                                    },
                                   {
                                        name: 'add_activity',
                                        text: __('AddActivity'),
                                        title:function(row){return __('AddActivity') + row.title},
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-folder-o',
                                        url: $.fn.bootstrapTable.defaults.extend.add_activity_url,
                                        visible:function(row){
                                            if(row.status >0){
                                                return false;
                                            }
                                                return true;
                                        }
                                    },
                                    {
                                        name: 'info',
                                        text: __('Info'),
                                        title:function(row){return __('Info') + row.title},
                                        classname: 'btn btn-xs btn-success btn-dialog',
                                        icon: 'fa fa-folder-o',
                                        url: $.fn.bootstrapTable.defaults.extend.info_url,
                                        visible:function(row){
                                            return true;
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
                                            return true;
                                        }
                                    },

                                ],
                            }
                        ]
                    ]
                });

                function status(value,row,index){
                    if(row.status == 0){
                        return __('Status1');
                    }else if(row.status == 1){
                        return __('Status2');
                    }else if(row.status == 2){
                        return __('Status3');
                    }else if(row.status == 3){
                        return __('Status4');
                    }else {
                        return __('Status5');
                    }
                }

                // 为表格绑定事件
                Table.api.bindevent(table);
            }
        },

        appoint: function () {
            Controller.api.bindevent();
        },
        designate: function () {
            Controller.api.bindevent();
        },
        appraise: function () {
            Controller.api.bindevent();
        },

        add_activity: function () {
            Controller.api.bindevent();
        },
        toperator:function () {
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
