define(['jquery', 'bootstrap', 'backend','bootstrap-table-1.15.4', 'bootstrap-table-treegrid-1.15.4', 'form','jquery-treegrid'], function ($, undefined, Backend, Table, TableTree,Form) {
	/*
	 * 树型列表渲染实例，bootstrap-table,版本1.15.4
	 * 其它效果得自己实现，与FA封装的require-table不能混用。
	 */
	var Controller = {
		init:function(){
			var myTable = {
					index_url: 'nav/index/tpe/'+Config.Tpe,
					add_url: 'nav/add',
					edit_url: 'nav/edit',
					del_url: 'nav/del',
					multi_url: 'nav/multi'
			}
			var $table = $('#table');
		    $table.bootstrapTable({
	            idField: 'id',
	            dataType:'json',
	            url:myTable.index_url,
	            pagination: false,
	            dblClickToEdit: true, 
	            columns: [
	                { field: 'check',  checkbox: true},
	                { field: 'name',  title: __('Name') ,formatter:name},
	                { field: 'tpe', title: __('Tpe'),align: 'center',formatter:tpe},
	                { field: 'status',  title: __('IsOn'),align: 'center',formatter:status},
	                { field: 'display',  title: __('Display'),align: 'center'},
	                { field: 'operate', title: __('Operate'), align: 'right', formatter: operator},
	            ],
	            // bootstrap-table-treegrid.js 插件配置 -- start
	            //在哪一列展开树形
	            treeShowField: 'name',
	            //指定父id列
	            parentIdField: 'pid',
	            
	            onResetView: function(data) {
	                //console.log('load');
	                $table.treegrid({
	                    //initialState: 'collapsed',// 所有节点都折叠
	                    initialState: 'expanded',// 所有节点都展开，默认展开
	                    treeColumn: 1,
	                    expanderExpandedClass: 'glyphicon  fa la fa-caret-down',  //图标样式
	                    expanderCollapsedClass: 'glyphicon fa la fa-caret-right',
	                    onChange: function() {
	                        $table.bootstrapTable('resetWidth');
	                    }
	                });
	            },
	        });
		    
		    //导航类型
		    function tpe(value, row, index){
    			switch(value){
		    		case 1:
		    			return __('Tpe1');
		    			break;
		    		case 2:
		    			return __('Tpe2');
		    			break;
		    		case 3:
		    			return __('Tpe3');
		    			break;
		    	}
    		}		    
		    
		    //format 标题
		    function name(value, row, index){
		    	var name = '';
		    	if(row.level == 0){
		    		name = '<span style="color:#a35d26;font-weight:bold">' + value + '</span>';
		    	}else if(row.level == 1){
		    		name = '<span style="color:#a35d26;font-weight:normal">' + value + '</span>';
		    	}else{
		    		name = '<span style="color:#444444;font-weight:normal">' + value + '</span>';
		    	}
		    	
		    	var html = name+'&nbsp;&nbsp;<span style="font-size:12px;color:#347ef1">[ID:'+row.id+']&nbsp;</span>';
		    	return html
		    }
		    //状态
		    function status(value, row, index){
                var url = myTable.multi_url;                
                return "<a href='javascript:;' data-toggle='tooltip' title='" + __('Click to toggle') + "' class='btn-change' data-id='"
                    + row.id + "' " + (url ? "data-url='" + url + "'" : "") + " data-params='status=" + (value == 1 ? 0 : 1) + "'><i class='fa fa-toggle-on " + (value == 1 ? 'text-success' : 'fa-flip-horizontal text-gray') + " fa-2x'></i></a>";
		    }
		    //format 操作
		    function operator(value, row, index){
		    	var str = '';
		    	var add = '<a href="'+myTable.add_url+'/pid/' + row.id + '/level/'+(row.level + 1)+'" class="btn btn-xs btn-success btn-add btn-dialog" data-toggle="tooltip" title="" data-table-id="table" data-row-index="'+index+'" data-button-index="1" data-original-title="' + __('AddChildNav') + '"><i class="fa fa-plus"></i>&nbsp;' + __('AddChildNav') + '</a>&nbsp;&nbsp;';
		    	var edit = '<a href="'+myTable.edit_url+'/ids/' + row.id + '" class="btn btn-xs btn-success btn-edit btn-dialog" data-toggle="tooltip" title="" data-table-id="table" data-row-index="'+index+'" data-button-index="2" data-original-title="' + __('Edit') + '"><i class="fa fa-pencil"></i></a>&nbsp;&nbsp;';
		    	var del = '<a href="javascript:;" data-url="'+myTable.del_url+'" data-ids="'+row.id+'" class="btn btn-xs btn-danger btn-delone" data-toggle="tooltip" title="" data-table-id="table" data-row-index="'+index+'" data-button-index="3" data-original-title="' + __('Del') + '"><i class="fa fa-trash"></i></a>';
		    	if($table.data('operate-add')){
		    		str += add;
		    	}
		    	if($table.data('operate-edit')){
		    		str += edit;
		    	}
		    	if($table.data('operate-del')){
		    		str += del;
		    	}
		    	return str;
		    }
			 // 刷新按钮事件
		    $("#toolbar").on('click', '.btn-refresh', function () {
		    	$table.bootstrapTable('refresh');
		    });
		    // 添加按钮事件
		    $("#toolbar").on('click', '.btn-add', function () {
		        var url = myTable.add_url+'/tpe/'+Config.Tpe;
		        Fast.api.open(url, __('Add'), $(this).data() || {});
		    });
		    
		    //删除按扭的事件
		    $table.on("click", ".btn-delone", function (e) {
                e.preventDefault();
                var id = $(this).data("id");
                var that = this;
                Layer.confirm(
                    __('Are you sure you want to delete this item?'),
                    {icon: 3, title: __('Warning'), shadeClose: true},
                    function (index) {
                    	e.preventDefault();
                        var element = that;
                        var data = element ? $(element).data() : {};
                    	var options = {url: data.url, data: {ids: data.ids}};
                        Fast.api.ajax(options, function (data, ret) {
                            var success = $(element).data("success") || $.noop;
                            if (typeof success === 'function') {
                                if (false === success.call(element, data, ret)) {
                                    return false;
                                }
                            }
                            $table.bootstrapTable('refresh');
                        }, function (data, ret) {
                            var error = $(element).data("error") || $.noop;
                            if (typeof error === 'function') {
                                if (false === error.call(element, data, ret)) {
                                    return false;
                                }
                            }
                        });
                        Layer.close(index);
                    }
                );
            });
		    
		    //状态的事件，滑动开关的启停用
		    $table.on("click", "[data-id].btn-change", function (e) {
                e.preventDefault();
                var element = this;
                var data = element ? $(element).data() : {};
                var ids = data.id;
                var url = data.url;
                var params = typeof data.params !== "undefined" ? (typeof data.params == 'object' ? $.param(data.params) : data.params) : '';
                var options = {url: url, data: {action: data.action, ids: ids, params: params}};
                Fast.api.ajax(options, function (data, ret) {
                    var success = $(element).data("success") || $.noop;
                    if (typeof success === 'function') {
                        if (false === success.call(element, data, ret)) {
                            return false;
                        }
                    }
                    $table.bootstrapTable('refresh');
                }, function (data, ret) {
                    var error = $(element).data("error") || $.noop;
                    if (typeof error === 'function') {
                        if (false === error.call(element, data, ret)) {
                            return false;
                        }
                    }
                });
            });
		    //该行会影响添加修改组件渲染，必须加上
		    Form.api.bindevent($("form[role=form]"));
	    }
	};
		
	Controller.init();
	return Controller;  
});