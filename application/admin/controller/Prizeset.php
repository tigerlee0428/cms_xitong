<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 活动 - 活动评论表奖品设置管理
 *
 * @icon fa fa-circle-o
 */
class Prizeset extends Backend
{
    
    /**
     * Prizeset模型对象
     * @var \app\admin\model\Prizeset
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Prizeset;

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
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
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

            $list = collection($list)->toArray();
            foreach($list as $k => $v){
                $list[$k]['prize_name'] = \app\admin\model\Commentprize::where(['id'=>$v['prize_id']])->value('title');
                $list[$k]['activity_name'] = \app\admin\model\Activity::where(['id'=>$v['aid']])->value('title');
                $list[$k]['userids_name'] = $this->getUsername($v['user_ids']);
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    public function getUsername($user_ids)
    {
        $users = \app\admin\model\User::where(['id'=>['in',$user_ids]])->select();
        $userstr = '';

        if(!empty($users)) {
            foreach ($users as $user) {
                $userstr .= $user['nickname'] . ',';
            }

            $userstr = substr($userstr,0,-1);
        }

        return $userstr;
    }

}
