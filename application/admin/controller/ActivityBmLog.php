<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 活动 - 报名记录管理
 *
 * @icon fa fa-circle-o
 */
class ActivityBmLog extends Backend
{

    /**
     * ActivityBmLog模型对象
     * @var \app\admin\model\ActivityBmLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\ActivityBmLog;
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
        $tpe = intval(input("tpe",-1));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where = [];

            if($tpe != -1){
                $where['tpe'] = $tpe;
            }
            if($ids){
                $where['aid'] = $ids;
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
                $list[$k]['name'] = \app\admin\model\User::where(['id'=>$v['uid']])->value('nickname');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assign("tpe",$tpe);
        $this->assignconfig("ids",$ids);
        return $this->view->fetch();
    }
    /**
     * 积分调节
     */
    public function adjust($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if ($this->request->isPost()) {
            $score = intval(input("score"));
            $cutScore = $score - $row->score;
            $params['score'] = $score;
            $result = false;
            Db::startTrans();
            try {
                //是否采用模型验证
                if ($this->modelValidate) {
                    $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                    $row->validateFailException(true)->validate($validate);
                }
                $result = $row->allowField(true)->save($params);
                Db::commit();
            } catch (ValidateException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false){
                if($cutScore != 0){
                    $adjustScore = [
                        'is_only_volunteer' => 1,
                        'event_code'        => 'SysAdjust',
                        'uid'               => $row->uid,
                        'scores'            => $cutScore,
                        'area_id'           => 0,
                        'note'              => __("SysAdjust").$cutScore.__("Fen")
                    ];
                    \think\Hook::listen("integral",$adjustScore);
                }
                $this->success(__('Adjust Success'));
            } else {
                $this->error(__('No rows were updated'));
            }

        }

        $row = $row->toArray();
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
