define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'orders/index' + location.search,
                    add_url: 'orders/add',
                    edit_url: 'orders/edit',
                    del_url: 'orders/del',
                    info_url: 'orders/info',
                    check_url: 'orders/check',
                    multi_url: 'orders/multi',
                    table: 'orders',
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
                        {field: 'name', title: __('Name')},
                        {field: 'area_name', title: __('Area')},
                        {field: 'cate_title', title: __('Cate')},
                        {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'tpe', title: __('Tpe'),formatter: Table.api.formatter.flag,searchList:{1:__('Tpe1'),2:__('Tpe2')},custom:{1:'success',2:'warning'},},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            align: 'left',
                            events: Table.api.events.operate,
                            formatter: Table.api.formatter.operate,
                            buttons:[
                           {
                                    name: 'check',
                                    text: __('Check'),
                                    title:function(row){return __('Check') + row.title},
                                    classname: 'btn btn-xs btn-success btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.check_url,
                                    visible:function(row){
                                        if(row.tpe == 2 && row.is_check == 0){
                                            return true;
                                        }
                                        return false;
                                    }
                                },
                                {
                                    name: 'info',
                                    text: __('Info'),
                                    title:function(row){return __('Info') + row.title},
                                    classname: 'btn btn-xs btn-danger btn-dialog',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.info_url,
                                    visible:function(row){
                                               return true;
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
        check: function () {
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
