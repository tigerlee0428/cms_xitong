define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'question/index' + location.search,
                    add_url: 'question/add',
                    edit_url: 'question/edit',
                    del_url: 'question/del',
                    multi_url: 'question/multi',
                    table: 'question',
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
                        {field: 'categoryName', title: __('Cid')},
                        {field: 'type', title: __('Type'), searchList: {1:__('Wenda'),2:__('Wenjuan')},formatter: Table.api.formatter.normal},
                        {field: 'question', title: __('Question')},
                        {field: 'optionA', title: __('Optiona')},
                        {field: 'optionB', title: __('Optionb')},
                        {field: 'optionC', title: __('Optionc')},
                        {field: 'optionD', title: __('Optiond')},
                        {field: 'answer', title: __('Answer'), searchList: {"A":__('Answer a'),"B":__('Answer b'),"C":__('Answer c'),"D":__('Answer d'),"0":__('Answer 0')}, formatter: Table.api.formatter.normal},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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
                $("#c-cid").on('change',function(){
                    var id = $('#c-cid').val();
                    $.ajax({
                        type: "POST", //请求的方式，默认get请求
                        url: "question_category/index2", //请求地址，后台提供的
                        data: {id: id},//data是传给后台的字段，后台需要哪些就传入哪些
                        dataType: "json", //json格式，如果后台返回的数据为json格式的数据，那么前台会收到Object
                        success: function (result) {
                            if (result == 1) {
                                $("#answer-option").show();
                            } else if (result == 2) {
                                $("#answer-option").hide();
                                // $("#c-answer").val(0);
                            }
                        }
                    });
                });
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});