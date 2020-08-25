<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 *
 *
 * @icon fa fa-circle-o
 */
class WorkLog extends Backend
{

    /**
     * WorkLog模型对象
     * @var \app\admin\model\WorkLog
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WorkLog;

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
        $ids = intval(input('ids'));
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
                    $params['wdid'] = $ids;
                    $result = $this->model->allowField(true)->save($params);
                    \app\admin\model\WorkDo::update(['finish_time' => $params['finish_time'],'status'=>2],['id'=>$params['wdid']]);
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

        $workDo = \app\admin\model\WorkDo::get($ids);
        $work = \app\admin\model\WorkOrder::get($workDo['wid']);
         if(in_array($work['tpe'],[4,5])){
             \app\admin\model\Orders::update(['status'=>2],['id'=>$work['resource_id']]);
         }
        $this->view->assign('row',$workDo);
        return $this->view->fetch();
    }

    /**
     * 评价
     */
    public function  appraise()
    {
        $ids = intval(input('ids'));
        $id = $this->model->where(['wdid'=>$ids])->value('id');
        $row = $this->model->get($id);
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
                    \app\admin\model\WorkDo::update(['status'=>3],['id'=>$ids]);
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
        $workDo = \app\admin\model\WorkDo::get($ids);
        $work   = \app\admin\model\WorkOrder::get($workDo['wid']);
        if(in_array($work['tpe'],[4,5])){
            \app\admin\model\Orders::update(['status'=>3],['id'=>$work['resource_id']]);
        }
        $finish_time = \app\admin\model\WorkDo::where(['id'=>$ids])->value('finish_time');
        $row['finish_time'] = $finish_time;
        $row['img'] = $row['img'] ? explode(',',$row['img']) : [];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }



}
