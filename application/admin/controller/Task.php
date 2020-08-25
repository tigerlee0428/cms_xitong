<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 系统 - 任务主体管理
 *
 * @icon fa fa-circle-o
 */
class Task extends Backend
{

    /**
     * Task模型对象
     * @var \app\admin\model\Task
     */
    protected $model = null;


    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Task;
        $this->domodel = new \app\admin\model\TaskDo;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 添加
     */
    public function add()
    {
        $tpe = intval(input("tpe"));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
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
                }else{
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign("category",$this->model->getTaskTpe());
        $tpeData = $this->model->getActivityTpe();
        $this->view->assign("tpe",$tpe);
        $this->view->assign('tpedata', $tpeData);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
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
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign("category",$this->model->getTaskTpe());
        $this->view->assign("row", $row);
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
        $this->assign("category",$this->model->getTaskTpe());
        $this->assign("type",$this->model->getTaskType());
        $this->view->assign("row", $row);
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
                    $taskDo =  new \app\admin\model\TaskDo();
                    $area_ids = explode(',',$params['area_id']);
                    $data=[];
                    foreach ($area_ids as $k => $v){
                        $info = $taskDo
                            ->where(['area_id'=>$v,'tid' =>$ids])
                            ->find();
                        if($info){continue;}
                        $data[]=[
                            'area_id' => $v,
                            'tid'     => $ids,
                        ];
                    }
                    $result = $taskDo->allowField(true)->saveAll($data);
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
        $row['img'] = $row['img'] ? explode(',',$row['img']):[];
        $this->assign("category",$this->model->getTaskTpe());
        $this->assign("type",$this->model->getTaskType());
        $this->view->assign("row", $row);
        return $this->view->fetch();
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
                    //$result = $row->allowField(true)->save($params);
                    $taskDo =  new \app\admin\model\TaskDo();
                    $group_ids = explode(',',$params['group_id']);
                    $data=[];
                    foreach ($group_ids as $k => $v){
                        $info = $taskDo
                            ->where(['group_id'=>$v,'tid' =>$ids])
                            ->find();
                        if($info){continue;}
                        $data[]=[
                            'group_id' => $v,
                            'tid'     => $ids,
                            'area_id' => $this->auth->area_id,
                            'is_team'   =>1,
                        ];
                    }
                    $result = $taskDo->allowField(true)->saveAll($data);
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
        $this->assign("category",$this->model->getTaskTpe());
        $this->view->assign("row", $row);
        return $this->view->fetch();
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
                    $taskDo =  new \app\admin\model\TaskDo;
                    $where =[
                        'tid' => $ids,
                        'do_id' => $params['do_id']
                    ];
                    $doInfo = $taskDo->where($where)->find();
                    if($doInfo){
                        $this->error(__('has_appoint'));
                    }
                    $data=[
                        'do_id' => $params['do_id'],
                        'tid'     => $ids,
                        'area_id' => $this->auth->area_id,
                    ];
                    $result =  $taskDo->allowField(true)->save($data);
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
}
