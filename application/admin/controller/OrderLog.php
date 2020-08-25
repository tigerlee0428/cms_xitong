<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 点单日志管理
 *
 * @icon fa fa-circle-o
 */
class OrderLog extends Backend
{
    
    /**
     * OrderLog模型对象
     * @var \app\admin\model\OrderLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderLog;
    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $ids = intval(input("ids"));
        $order_id = intval(input("order_id"));
        $period_id = intval(input("period_id"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where = [];
            if($order_id){
                $where['order_id'] = $order_id;
            }
            if($period_id){
                $where['period_id'] = $period_id;
            }
            if($ids){
                $where['order_id'] = $ids;
            }
            $this->where = initWhere($where);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
            ->where($where)
            ->order($sort, $order)
            ->count();
    
            $list = $this->model
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->select();
    
            $list = collection($list)->toArray();
            foreach($list as $k => $v){
                $list[$k]['uidName'] = \app\admin\model\User::where(['id'=>$v['uid']])->value('nickname');
            }
            $result = array("total" => $total, "rows" => $list);
    
            return json($result);
        }
        $this->assignconfig("ids",$ids);
        return $this->view->fetch();
    }
    /**
     * 统计
     */
    public function statistics()
    {
        //设置过滤方法
        $ids = intval(input("ids"));
        $order_id = intval(input("order_id"));
        $period_id = intval(input("period_id"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where = [];
            if($order_id){
                $where['order_id'] = $order_id;
            }
            if($period_id){
                $where['period_id'] = $period_id;
            }
            if($ids){
                $where['order_id'] = $ids;
            }
            $this->where = initWhere($where);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
            ->field("id,area_id,order_id,period_id,count(*) as sum")
            ->where($where)
            ->order($sort, $order)
            ->group("area_id")
            ->count();
    
            $list = $this->model
            ->field("id,area_id,order_id,period_id,count(*) as sum")
            ->where($where)
            ->order($sort, $order)
            ->limit($offset, $limit)
            ->group("area_id")
            ->select();
    
            $list = collection($list)->toArray();
            foreach($list as $k => $v){
                $list[$k]['areaName'] = $v['area_id'] ? \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name') : __("no");
            }
            $result = array("total" => $total, "rows" => $list);
    
            return json($result);
        }
        $this->assignconfig("ids",$ids);
        return $this->view->fetch();
    }
}
