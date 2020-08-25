<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 活动 - 投票参与记录管理
 *
 * @icon fa fa-circle-o
 */
class VoteLog extends Backend
{
    
    /**
     * VoteLog模型对象
     * @var \app\admin\model\VoteLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\VoteLog;

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
        $options_id = intval(input("options_id"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where = [];
            if($ids){
                $where['tid'] = $ids;
            }
            if($options_id){
                $where['option_id'] = $options_id;
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
            foreach ($list as $k => $v){
                $list[$k]['username'] = \app\admin\model\User::where(['id'=>$v['uid']])->value('nickname');
                $list[$k]['votename'] = \app\admin\model\Vote::where(['id'=>$v['tid']])->value('title');
                $list[$k]['voteoptionname'] = \app\admin\model\VoteOptions::where(['id'=>$v['option_id']])->value('title');
            }
            
            $result = array("total" => $total, "rows" => $list);
    
            return json($result);
        }
        $this->assignconfig("ids",$ids);
        $this->assignconfig("options_id",$options_id);
        return $this->view->fetch();
    }

}
