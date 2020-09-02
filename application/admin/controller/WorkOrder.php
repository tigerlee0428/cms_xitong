<?php

namespace app\admin\controller;

use app\admin\model\User;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 工单管理
 *
 * @icon fa fa-circle-o
 */
class WorkOrder extends Backend
{

    /**
     * WorkOrder模型对象
     * @var \app\admin\model\WorkOrder
     */
    protected $model = null;
    protected $noNeedRight = ['appointDo', 'toperator','designate'];


    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WorkOrder;
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

        $tpe = intval(input("tpe"));
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where =[];
            if($tpe){
                $where['tpe'] = $tpe;
            }
            if($this->auth->area_id){
                $where['area_id'] = ['in',\app\common\model\Cfg::childArea($this->auth->area_id)];
                if($this->auth->area_id !=1007){
                    $where['tpe'] = 6;
                }
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
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        if($this->auth->area_id==1007){
            $this->assignconfig('is_center',1);
        }
        $this->assignconfig('area_id',$this->auth->area_id);
        $this->assignconfig('tpe',$tpe);
        return $this->view->fetch();
    }


    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $params['area_id'] = $this->auth->area_id;
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->save($params);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }


    /**
     *
     * 指派所站
     * @param unknown $ids
     * @return string
     */
    public function appoint($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                switch ($params['type']){
                    case 1:
                        $this->appointDo($ids);
                        break;
                    case 2:
                        $this ->designate($ids);
                               break;
                    case 3:
                        $this ->toperator($ids);
                        break;
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['img'] = $row['img'] ? explode(',',$row['img']) : [];
        $orders = [];
        if(in_array($row['tpe'],[5,6])){
            $orders = \app\admin\model\Orders::get(['id'=>$row['resource_id']]);
            $this->view->assign("orders", $orders);
        }
        $this->view->assign("orders", $orders);
        $this->view->assign("row", $row);
        $this->view->assign("type",$this->model->getDoType());
        return $this->view->fetch();
    }

    public function appointDo($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $info = \app\admin\model\WorkDo::where(['area_id'=>$params['area_id'],'wid' =>$ids])->find();
                    if($info){
                        $this->error(__('has_appoint'));
                    }
                    $data=[
                        'area_id' =>$params['area_id'],
                        'wid'     => $ids,
                        'title'   => $row['title'],
                        'tpe'     => $row['tpe'],
                        'need_finish_time' => strtotime($params['need_finish_time']),
                        'style'   => 1

                    ];
                    $result = \app\admin\model\WorkDo::Create($data);
                    $row->save(['status'=>1],['id'=>$ids]);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

    }
    /**
     * 指派志愿团体
     * @param unknown $ids
     * @return string
     */
    public function designate($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $info =\app\admin\model\WorkDo::where(['group_id'=>$params['group_id'],'wid' =>$ids])->find();
                    if($info){
                        $this->error(__('has_appoint'));
                    }
                    $data=[
                        'title'    => $row['title'],
                        'group_id' => $params['group_id'],
                        'wid'     => $ids,
                        'area_id' => $this->auth->area_id,
                        'tpe'     => $row['tpe'],
                        'need_finish_time' => strtotime($params['need_finish_time']),
                        'style'   => 2
                    ];
                    $result = \app\admin\model\WorkDo::create($data);
                    $row->save(['status'=>1],['id'=>$ids]);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

    }
    /**
     * 指派操作员
     * @param unknown $ids
     * @return string
     */
    public function toperator($ids = null){
        $row = $this->model->get($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
        }

        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $where =[
                        'wid' => $ids,
                        'do_id' => $params['do_id']
                    ];
                    $doInfo = \app\admin\model\WorkDo::where($where)->find();
                    if($doInfo){
                        $this->error(__('has_appoint'));
                    }
                    $data=[
                        'do_id' => $params['do_id'],
                        'wid'     => $ids,
                        'area_id' => $this->auth->area_id,
                        'tpe'     => $row['tpe'],
                        'need_finish_time' => strtotime($params['need_finish_time']),
                        'title'  => $row['title'],
                        'style'   => 3

                    ];
                    $result = \app\admin\model\WorkDo::create($data);
                    $row->save(['status'=>1],['id'=>$ids]);
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $row['img'] = $row['img'] ? explode(',',$row['img']) : [];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    public function upRequest($ids){
      \app\admin\model\WorkOrder::update(['status' =>3,'area_id'=>1007],['id'=>$ids]);
      $this->success();
    }


    /**
     * 详情
     */
    public function info($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
