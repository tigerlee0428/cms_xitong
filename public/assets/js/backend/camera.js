define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'camera/index' + location.search,
                    add_url: 'camera/add',
                    edit_url: 'camera/edit',
                    del_url: 'camera/del',
                    multi_url: 'camera/multi',
                    live_play_url: 'camera/play',
                    table: 'camera',
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
                        {field: 'img', title: __('Img'),events: Table.api.events.image,formatter:Table.api.formatter.image,searchable:false},
                        {field: 'url', title: __('Url'), formatter: Table.api.formatter.url},
                        {field: 'address', title: __('Address')},
                        {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'areaName', title: __('Area_id')},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'third_id', title: __('Third_id')},
                        {field: 'is_up', title: __('Is_up'),formatter:Table.api.formatter.toggle,searchable:false},
                        {field: 'is_record', title: __('Is_record'), searchList: {"0":__('Is_record 0'),"1":__('Is_record 1')}, formatter: Table.api.formatter.normal},
                        {field: 'domain', title: __('Domain'), searchList: {"1":__('Domain 1'),"2":__('Domain 2'),"3":__('Domain 3')}, formatter: Table.api.formatter.normal},
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
                                    // classname: 'btn btn-xs btn-primary btn-dialog',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon: 'fa fa-youtube-play',
                                    url: $.fn.bootstrapTable.defaults.extend.live_play_url,
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
                    index_url: 'camera/select' + location.search,
                    table: 'camera',
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
                        {field: 'name', title: __('Name'),width:330,align:'left'},
                        {field: 'addtime', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'address', title: __('Address')},
                        {
                        	field: 'operate',
                        	title: __('Operate'),
                        	table: table,
                        	events: {
                                'click .btn-chooseone': function (e, value, row, index) {
                                	console.log(e,value,row,index)
                                    var multiple = Backend.api.query('multiple');
                                    multiple = multiple == 'true' ? true : false;
                                    Fast.api.close({id:row.id,name: row.name,multiple: multiple});
                                },
                        	},
                        	formatter: function () {
                                return '<a href="javascript:;" class="btn btn-danger btn-chooseone btn-xs"><i class="fa fa-check"></i> ' + __('Choose') + '</a>';
                            }
                        }
                    ]
                ]
            });

            // 选中多个
            $(document).on("click", ".btn-choose-multi", function () {
                var urlArr = new Array();
                var nameArr = new Array();
                $.each(table.bootstrapTable("getAllSelections"), function (i, j) {
                    console.log(j)
                    urlArr.push(j.url);
                    nameArr.push(j.name);
                });
                var multiple = Backend.api.query('multiple');
                multiple = multiple == 'true' ? true : false;
                console.log(urlArr)
                Fast.api.close({url: urlArr.join(","),name:nameArr.join(","), multiple: multiple});
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
