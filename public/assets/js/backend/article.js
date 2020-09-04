define(['jquery', 'bootstrap', 'backend', 'table', 'form','editable'], function ($, undefined, Backend, Table, Form,undefined) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'article/index/category/'+ Config.category + location.search,
                    add_url: 'article/add/category/'+ Config.category,
                    edit_url: 'article/edit',
                    del_url: 'article/del',
                    multi_url: 'article/multi',
                    check_url: 'article/check',
                    cancelcheck_url: 'article/cancelcheck',
                    finalcheck_url: 'article/finalcheck',
                    cancelfinalcheck_url: 'article/cancelfinalcheck',
                    publish_url: 'article/publish',
                    cancelpublish_url: 'article/cancelpublish',
                    table: 'article',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                exportTypes: ['csv', 'excel'],
                clickToSelect: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'),searchable:false,},
                        {field: 'img', title: __('Img'),events: Table.api.events.image,formatter:Table.api.formatter.image,searchable:false},
                        {field: 'title', title: __('Title'),operate:'like',align:'left',width:380,cellStyle: {css: {"white-space":"normal"}},formatter:title},
                        {field: 'tpe', title: __('Tpe'),formatter: Table.api.formatter.flag,searchList:{1:__('Tpe1'),2:__('Tpe2'),3:__('Tpe3'),4:__('Tpe4'),5:__('Tpe5'),6:__('Tpe6')},custom:{1:'success',2:'warning',3:'info',4:'danger'},},
                        {field: 'categoryName', title: __('Category'),searchable:false},
                        {field: 'areaName', title: __('Area_id'),searchable:false},
                        {field: 'add_time', title: __('Add_time'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime,},
                        {field: 'adminName', title: __('Admin_id'),searchable:false},
                        {field: 'click_count', title: __('Click_count'),searchable:false,editable:true},
                        {field: 'is_check', title: __('CheckStatus'),formatter:checkstatus,searchable:false,},
                        {
                        	field: 'operate',
                        	title: __('Operate'),
                        	table: table,
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
    			                      name: 'cancelcheck',
    			                      text: __('CancelCheck'),
    			                      title:function(row){return __('CancelCheck') + row.title},
    			                      classname: 'btn btn-xs btn-danger btn-ajax',
    			                      icon: 'fa fa-folder-o',
    			                      url: $.fn.bootstrapTable.defaults.extend.cancelcheck_url,
    			                      visible:function(row){
    			                    	  if(row.is_check && row.is_final_check == 0 && row.is_publish == 0){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      },
    			                      success: function (data, ret){
    			                    	  if(ret.code == 1){
    			                    		  table.bootstrapTable('refresh');
    			                    	  }
    			                      }
    			                  },
    			                  {
    			                      name: 'finalcheck',
    			                      text: __('FinalCheck'),
    			                      title:function(row){return __('FinalCheck') + row.title},
    			                      classname: 'btn btn-xs btn-success btn-dialog',
    			                      icon: 'fa fa-folder-o',
    			                      url: $.fn.bootstrapTable.defaults.extend.finalcheck_url,
    			                      visible:function(row){
    			                    	  if(row.is_check == 1 && row.is_final_check == 0){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      }
    			                  },
    			                  {
    			                      name: 'cancelfinalcheck',
    			                      text: __('CancelFinalCheck'),
    			                      title:function(row){return __('CancelFinalCheck') + row.title},
    			                      classname: 'btn btn-xs btn-danger btn-ajax',
    			                      icon: 'fa fa-folder-o',
    			                      url: $.fn.bootstrapTable.defaults.extend.cancelfinalcheck_url,
    			                      visible:function(row){
    			                    	  if(row.is_check == 1 && row.is_final_check > 0){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      },
    			                      success: function (data, ret){
    			                    	  if(ret.code == 1){
    			                    		  table.bootstrapTable('refresh');
    			                    	  }
    			                      }
    			                  },
    			                  {
    			                      name: 'publish',
    			                      text: __('Publish'),
    			                      title:function(row){return __('Publish') + row.title},
    			                      classname: 'btn btn-xs btn-success btn-ajax',
    			                      icon: 'fa fa-folder-o',
    			                      url: $.fn.bootstrapTable.defaults.extend.publish_url,
    			                      visible:function(row){
    			                    	  if(row.is_publish == 0 && row.is_check == 1 && row.is_final_check == 1){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      },
    			                      success: function (data, ret){
    			                    	  if(ret.code == 1){
    			                    		  table.bootstrapTable('refresh');
    			                    	  }
    			                      }
    			                  },
    			                  {
    			                      name: 'cancelpublish',
    			                      text: __('CancelPublish'),
    			                      title:function(row){return __('CancelPublish') + row.title},
    			                      classname: 'btn btn-xs btn-danger btn-ajax',
    			                      icon: 'fa fa-folder-o',
    			                      url: $.fn.bootstrapTable.defaults.extend.cancelpublish_url,
    			                      visible:function(row){
    			                    	  if(row.is_publish == 1 && row.is_check == 1 && row.is_final_check == 1){
    			                    		  return true;
    			                    	  }
    			                    	  return false;
    			                      },
    			                      success: function (data, ret){
    			                    	  if(ret.code == 1){
    			                    		  table.bootstrapTable('refresh');
    			                    	  }
    			                      }
    			                  },
    			              ],
                        }
                    ]
                ]
            });
            function checkstatus(value,row,index){
            	if(row.is_check == 0){
            		return __('NoCheck');
            	}else if(row.is_check == 2){
            		return '<div data-toggle="tooltip" data-placement="left" title="'+row.check_case+'">'+__('CheckUnPass')+'</div>';
            	}else if(row.is_check == 1 && row.is_final_check == 0){
            		return __('CheckPass');
            	}else if(row.is_check == 1 && row.is_final_check == 2){
            		return '<div data-toggle="tooltip" data-placement="left" title="'+row.final_check_case+'">'+__('FinalCheckUnPass')+'</div>';
            	}else if(row.is_check == 1 && row.is_final_check == 1){
            		return __('FinalCheckPass');
            	}
            }

            function title(value,row,index){
            	var html = '';
            	var tv = '';
            	var like = '<span class="fa fa-heart" style="margin-left:10px;color:#18bc9c">'+row.likes+'</span>';
            	value +=like;
            	if(row.is_tv_show){
            		tv = '<a class="tvyl" target="_blank" href="/template/tv/pages/A_Detail.html?id=' + row.id + '"><span class="fa fa-tv"></span></a>'
            	}
            	var pc = '';
            	if(row.is_pc_show){
            		var pcurl = Config.pc_domain+"/#/ariticleDetail/"+row.id;
            		pc = '<a class="pcyl" href="'+pcurl+'" target="_blank">'+value+'</a>' + tv;
            	}else{
            		pc = value + tv;
            	}
            	var wx = pc;
            	if(row.is_wx_show){
            		if(!row.is_tv_show && !row.is_pc_show){
            			pc = value;
            		}
            		var wxurl = encodeURIComponent(Config.wx_domain + "/#/textAndVideo/" + row.id);
            		var qrcode = "<img class='qrcode' src='/home/index/qrcode?url=" + wxurl + "'><p>" + __("QrcodeView") + "</p>";
            		wx = '<div data-toggle="tooltip" data-placement="right" data-html=true data-trigger="focus" data-title="' + qrcode + '">' + pc + '</div>';
            	}
            	return wx;
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
        check:function () {
        	Controller.api.bindevent();
        },
        cancelcheck:function () {
        	Controller.api.bindevent();
        },
        finalcheck:function () {
        	Controller.api.bindevent();
        },
        cancelfinalcheck:function () {
        	Controller.api.bindevent();
        },
        publish:function () {
        	Controller.api.bindevent();
        },
        cancelpublish:function () {
        	Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
            	$("#c-tpe").on('change',function(){
            		var val = $(this).val();
            		if(val == 2 || val == 3){
            			$("#images-box").show();
            		}else{
            			$("#images-box").hide();
            		}
            		if(val == 4){
            			$("#video-box").show();
            		}else{
            			$("#video-box").hide();
            		}
            		if(val == 5){
            			$("#camera-box").show();
            		}else{
            			$("#camera-box").hide();
            		}
            	});

                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});
