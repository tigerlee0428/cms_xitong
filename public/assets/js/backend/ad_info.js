define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ad_info/index/ids/' + Config.ids + location.search,
                    add_url: 'ad_info/add/ids/' + Config.ids,
                    edit_url: 'ad_info/edit',
                    del_url: 'ad_info/del',
                    multi_url: 'ad_info/multi',
                    table: 'ad_info',
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
                        {field: 'ad_pos', title: __('Ad_pos')},
                        {field: 'image', title: __('Image'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'title', title: __('Title'),align:'left',operate:'like'},
                        {field: 'started_at', title: __('Started_at'),formatter:started_at},
                        {field: 'modified_at', title: __('Modified_at'),formatter:Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),formatter:Table.api.formatter.toggle,searchable:false},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            function started_at(value,row,index){
            	var datetimeFormat = 'YYYY-MM-DD';
            	var st = row.started_at ? Moment(parseInt(row.started_at) * 1000).format(datetimeFormat) : __('None');
            	var ed = row.expired_in ? Moment(parseInt(row.expired_in) * 1000).format(datetimeFormat) : __('None');
            	return st + '-' + ed;
            }
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