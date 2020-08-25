define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity/index/tpe/' + Config.tpe + (Config.category ? "/category/" + Config.category : '') + location.search,
                    add_url: 'activity/add/tpe/' + Config.tpe + (Config.category ? "/category/" + Config.category : ''),
                    edit_url: 'activity/edit',
                    del_url: 'activity/del',
                    multi_url: 'activity/multi',
                    check_url: 'activity/check',
                    report_url: 'activity/report',
                    bmlog_url: 'activity_bm_log/index/tpe/' + Config.tpe,
                    comment_url: 'activity/comment',
                    setPrize_url: 'commentprize/index',
                    commentList_url: 'activity_comment/index',
                    table: 'activity',
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
                        {field: 'images', title: __('Images'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'area_name', title: __('Area_id'),searchable:false},
                        {field: 'title', title: __('Title'),align:'left',operate:'like',width:350,cellStyle: {css: {"white-space":"normal"}},formatter:title},
                        {field: 'servicetime', title: __('Servicetime'),searchable:false,width:30},
                        {field: 'start_time', title: __('Start_time'),operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime},
                        {field: 'end_time', title: __('End_time'),operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'),formatter: Table.api.formatter.flag,searchList:{0:__('Not started'),1:__('Under way'),2:__('Has ended'),3:__('Completed'),4:__("Has Comment")},custom:{0:'success',1:'warning',2:'danger',3:'info',4:'primary'}},
                        {field: 'is_check', title: __('CheckStatus'),formatter: Table.api.formatter.flag,searchList:{0:__('No Check'),1:__('Check Pass'),2:__('Check No Pass')},custom:{0:'warning',1:'success',2:'danger'}},
                        {field: 'is_publish', title: __('Is_publish'),formatter:Table.api.formatter.toggle,searchable:false},
                        {field: 'qrcode', title: __('Qrcode'),formatter:qrcode},
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
       			                    	  if(row.is_check == 0){
       			                    		  return true;
       			                    	  }
       			                    	  return false;
       			                      }
       			                  },
       			                  {
       			                      name: 'report',
       			                      text: __('Report'),
       			                      title:function(row){return __('Report') + row.title},
       			                      classname: 'btn btn-xs btn-danger btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.report_url,
       			                      visible:function(row){
       			                    	  if(row.is_report == 0 && row.status == 2 && row.is_check == 1){
       			                    		  return true;
       			                    	  }
       			                    	  return false;
       			                      }
       			                  },
       			                  {
       			                      name: 'log',
       			                      text: __('BmLog'),
       			                      title:function(row){return row.title + __('BmLog')},
       			                      classname: 'btn btn-xs btn-info btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.bmlog_url,
       			                      visible:function(row){
    			                    	  if(row.is_check == 1){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      }
       			                  },
       			                  {
       			                      name: 'comment',
       			                      text: __('Comment'),
       			                      title:function(row){return row.title + __('Comment')},
       			                      classname: 'btn btn-xs btn-info btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.comment_url,
       			                      visible:function(row){
    			                    	  if(row.is_check == 1 && row.status == 3 && row.is_appraise == 0){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      }
       			                  },
       			                  {
       			                      name: 'setPrize',
       			                      text: __('setPrize'),
       			                      title:function(row){return row.title + __('setPrize')},
       			                      classname: 'btn btn-xs btn-info btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.setPrize_url,
       			                      visible:function(row){
    			                    	  if(row.is_need_prize == 1 && row.status == 0){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      }
       			                  },
       			                  {
       			                      name: 'commentList',
       			                      text: __('commentList'),
       			                      title:function(row){return row.title + __('commentList')},
       			                      classname: 'btn btn-xs btn-info btn-dialog',
       			                      icon: 'fa fa-folder-o',
       			                      url: $.fn.bootstrapTable.defaults.extend.commentList_url,
       			                      visible:function(row){
    			                    	  if(row.is_need_prize == 1){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      }
       			                  },
       			             ]
                        }
                    ]
                ]
            });
            function qrcode(value,row,index){
            	return "<a href='"+value+"' target='_blank'><img class='img-sm' src='"+value+"'></a>";
            }
            
            function title(value,row,index){
            	return value + (row.is_classic ? '<span class="fa fa-graduation-cap btn-success"></span>' : '');
            }
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        bmlog:function(){
        	var table1 = $("#cbmlogtable");
            table1.bootstrapTable({
                url: 'activity_bm_log/index/ids/'+Config.actIds+'?tpe=1',
                toolbar: '#cbmlog',
                sortName: 'id',
                sidePagination:'server',
                //search: false,
                //exportTypes: ['csv', 'excel'],
                columns: [
                    [
                        {field: 'id', title: 'ID',searchable:false},
                        {field: 'name', title: __('Name'),searchable:false},
                        {field: 'mobile', title: __('Mobile'),operate:'like'},
                        {field: 'is_sign', title: __('Is_sign'),searchable:false,formatter:Table.api.formatter.flag,searchList:{0:__('No Sign'),1:__('Has Sign')},custom:{0:'success',1:'danger'}},
                        {field: 'addtime', title: __('Addtime'),searchable:false,operate: 'RANGE', addclass: 'datetimerange',formatter:Table.api.formatter.datetime},
                        {field: 'score', title: __('Score'),searchable:false},
                        {field: 'total_score', title: __('TotalScore'),searchable:false},
                    ]
                ]
            });

            // 为表格1绑定事件
            Table.api.bindevent(table1);
        	
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
        report: function () {
            Controller.api.bindevent();
        },
        comment: function () {
        	//绑定事件
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
            	if($(this).attr("href") == '#bmlog'){
            		var panel = $($(this).attr("href"));
                    if (panel.size() > 0) {
                        Controller.bmlog.call(this);
                        $(this).on('click', function (e) {
                            $($(this).attr("href")).find(".btn-refresh").trigger("click");
                        });
                    }
            	}                
                //移除绑定的事件
                $(this).unbind('shown.bs.tab');
            });
            
            //必须默认触发shown.bs.tab事件
            $('ul.nav-tabs li.active a[data-toggle="tab"]').trigger("shown.bs.tab");
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