define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                	index_url: 'volunteer_group/index/ids/' + Config.ids + location.search,
                    add_url: 'volunteer_group/add',
                    edit_url: 'volunteer_group/edit',
                    del_url: 'volunteer_group/del',
                    multi_url: 'volunteer_group/multi',
                    check_url: 'volunteer_group/check',
                    auto_url:'volunteer_group/autoAdmin',
                    table: 'volunteer_group',
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
                        {field: 'logo', title: __('Logo'),formatter:Table.api.formatter.image,searchable:false,events: Table.api.events.image},
                        {field: 'title', title: __('Title'),operate:'like',align:'left'},
                        {field: 'areaName', title: __('Area_id'),searchable:false},
/*
                        {field: 'master', title: __('Master')},
*/
/*
                        {field: 'mobile', title: __('Mobile')},
*/
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'number', title: __('VolunteerNumber'),searchable:false,formatter:volunteerNumber},
                        {field: 'jobtimeall', title: __('WholeJobTime'),searchable:false,formatter:function(value,row,index){return (value/3600).toFixed(2) + 'h'}},
/*
                        {field: 'is_check', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('No Check'),1:__('Check Pass'),2:__('Check No Pass')},custom:{0:'warning',1:'success',2:'danger'}},
*/
                        {
                        	field: 'operate',
                        	title: __('Operate'),
                        	table: table,
                        	events: Table.api.events.operate,
                        	formatter: Table.api.formatter.operate,
                        	buttons:[
          			          /*   {
       			                      name: 'check',
       			                      text: __('Check'),
       			                      title:function(row){return __('Check') + row.title},
       			                      classname: 'btn btn-xs btn-success btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.check_url,
       			                      visible:function(row){
       			                    	  if(row.is_check == 0){
       			                    		  return true;
       			                    	  }
       			                    	  return false;
       			                      }
       			                  },*/
                            /*    {
                                    name: 'auto',
                                    text: __('Auto'),
                                    title:function(row){return __('Auto') + row.title},
                                    classname: 'btn btn-xs btn-success btn-ajax',
                                    icon: 'fa fa-folder-o',
                                    url: $.fn.bootstrapTable.defaults.extend.auto_url,
                                    visible:function(row){
                                        if(row.has_admin == 0){
                                            return true;
                                        }
                                        return false;
                                    },
                                    success: function (data, ret){
                                        if(ret.code == 1){
                                            table.bootstrapTable('refresh');
                                        }
                                    }
                                },*/
       			             ]
                        }
                    ]
                ]
            });

            function volunteerNumber(value,row,index){
            	return "<a href='volunteer/index?gid="+row.id+"' class='btn btn-success btn-addtabs' data-text='"+__('VolunteerNumber')+"' data-title='"+__('VolunteerNumber')+"'>"+value+"</a>";
            }

            // 为表格绑定事件
            Table.api.bindevent(table);
        },


        select: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'volunteer_group/select' + location.search,
                    table: 'volunteer_group',
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
                        {field: 'title', title: __('Title'),width:330,align:'left'},
                        {field: 'addtime', title: __('Addtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'extension', title: __('Extension')},
                        {
                            field: 'operate',
                            title: __('Operate'),
                            table: table,
                            events: {
                                'click .btn-chooseone': function (e, value, row, index) {
                                    console.log(e,value,row.id,index)
                                    var multiple = Backend.api.query('multiple');
                                    multiple = multiple == 'true' ? true : false;
                                    Fast.api.close({id:row.id,title:row.title, multiple: multiple});
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
        check:function () {
        	Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"),function(data){Fast.api.refreshmenu();});
            }
        }
    };
    // 选择按钮事件
    $("#toolbar").on('click', '.btn-choose-multi', function () {
        // var url = myTable.add_url+'/tpe/'+Config.Tpe;
        // Fast.api.open(url, __('Add'), $(this).data() || {});
        var idStr = ''
        var titleStr = ''
        $("#table tbody tr.selected").each(function(index,item){
            idStr += $(item).find('td:nth-child(2)').text() + ','
            titleStr += $(item).find('td:nth-child(3)').text() + ','
        })
        console.log(idStr,titleStr)
        idStr = idStr.slice(0,-1)
        titleStr = titleStr.slice(0,-1)
        Fast.api.close({id:idStr,title:titleStr,multiple: false});
    });
    return Controller;
});
