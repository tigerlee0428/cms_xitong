define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // $(".btn-preview").data("area",["180%","180%"]);
            // $(".btn-add").data("area",["180%","180%"]);

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/index' + location.search,
                    add_url: 'video/add',
                    //edit_url: 'video/edit',
                    del_url: 'video/del',
                    multi_url: 'video/multi',
                    preview_url: 'video/preview',
                    table: 'video',
                }
            });

            var table = $("#table");
            table.on('post-body.bs.table',function (e,settings,json,xhr) {
                $(".btn-preview").data("area",["55%","100%"]);
            })

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
                        {field: 'video_img', title: __('Video_img'),events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'title', title: __('Title'),width:330,align:'left'},
                        {field: 'status', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('Transcoding'),1:__('Transcoding completed')},custom:{0:'warning',1:'success'}},
                        {field: 'address', title: __('Address'),formatter: Table.api.formatter.url},
                        {field: 'p_url', title: __('P_url'),formatter: Table.api.formatter.url},
                        {field: 'm_url', title: __('M_url'),formatter: Table.api.formatter.url},
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'extension', title: __('Extension')},
                        {field: 'third_id', title: __('Third_id')},
                        // {field: 'third_source', title: __('Third_source')},
                        {field: 'duration', title: __('Duration')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons: [
                                {
                                    name: 'preview',
                                    text: __('Preview'),
                                    title: __('Preview'),
                                    classname: 'btn btn-xs btn-primary btn-dialog btn-preview',
                                    icon: 'fa fa-youtube-play',
                                    url: $.fn.bootstrapTable.defaults.extend.preview_url,
                                }
                            ],
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        select: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'video/select' + location.search,
                    table: 'video',
                }
            });
            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                showToggle: false,
                showExport: false,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'video_img', title: __('Video_img'),events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'title', title: __('Title'),width:330,align:'left'},
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'extension', title: __('Extension')},
                        {
                        	field: 'operate', 
                        	title: __('Operate'), 
                        	table: table, 
                        	events: {
                                'click .btn-chooseone': function (e, value, row, index) {
                                	console.log(e,value,row,index)
                                    var multiple = Backend.api.query('multiple');
                                    multiple = multiple == 'true' ? true : false;
                                    Fast.api.close({id:row.id,title: row.title,address:row.address, multiple: multiple});
                                },
                        	},
                        	formatter: function () {
                                return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                            }
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
        publish:function () {
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
