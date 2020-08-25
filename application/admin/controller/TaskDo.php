<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 系统 - 任务指派管理
 *
 * @icon fa fa-circle-o
 */
class TaskDo extends Backend
{

    /**
     * TaskDo模型对象
     * @var \app\admin\model\TaskDo
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\TaskDo;


    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $is_team = intval(input('is_team',-1));
        $status = intval(input('status',-1));
        $ids = input('ids');
        $tpe = intval(input('tpe'));
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where =[];
            if($is_team !=-1){
                $where['is_team'] = $is_team;
                if($is_team){
                    $where['area_id'] =$this->auth->area_id;
                }else{
                    $where['area_id'] =['in',\app\common\model\Cfg::childrenArea($this->auth->area_id)];;
                }
            }
            if($ids){
                if($tpe){
                    $where['pid'] =$ids;
                }else{
                    $where['tid'] =$ids;
                }
            }
            if($status !=-1){
                $where['status'] = $status;
                if($status == 3){
                    $where['status'] = ['in','3,4'];
                    $where['area_id'] =['in',\app\common\model\Cfg::childArea($this->auth->area_id)];;
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

            foreach ($list as $k => $v){
                $list[$k]['title'] =\app\admin\model\Task::where(['id'=>$v['tid']])->value('title');
                $list[$k]['need_finish_time'] = \app\admin\model\Task::where(['id'=>$v['tid']])->value('finish_time');
                if($this->auth->is_admin){
                    $list[$k]['is_admin'] = 1;
                }
                if($v['is_team']){
                    $list[$k]['area_name'] = \app\admin\model\VolunteerGroup::where(['id'=>$v['group_id']])->value('title');
                }else{
                    $list[$k]['area_name'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 我的任务
     * @return \think\response\Json|string
     */
    public function mytask()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);

        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            $admin_group_access = new \app\admin\model\AuthGroupAccess;
            $group_access_list = $admin_group_access
                ->where('uid','=',$this->auth->id)
                ->select();
            $group_ids =[];
            foreach ($group_access_list as $k => $v){
                $group_ids[] = $v['group_id'];
            }
            if(array_intersect([6,7,8],$group_ids)){
                $where['do_id'] = $this->auth->id;
            }
            $where['is_team'] = 0;
            $where['area_id'] = $this->auth->area_id;
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
            $admin = Admin::where(['id'=>$this->auth->admin_id]);
            foreach ($list as $k => $v){
                $list[$k]['title'] = \app\admin\model\Task::where(['id'=>$v['tid']])->value('title');
                if($this->auth->is_admin){
                    $list[$k]['is_admin'] = 1;
                }
                $list[$k]['need_finish_time'] = \app\admin\model\Task::where(['id'=>$v['tid']])->value('finish_time');
                if($v['is_team']){
                    $list[$k]['area_name'] = \app\admin\model\VolunteerGroup::where(['id'=>$v['group_id']])->value('title');
                }else{
                    $list[$k]['area_name'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }

    /**
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
        $task =  new \app\admin\model\Task();
        $taskInfo = $task
            ->where(['id'=>$row['tid']])
            ->find();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if(!$row['status']){
                    $params['status'] = 1;
                }
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
                    $area_ids = explode(',',$params['area_id']);
                    $data=[];
                    foreach ($area_ids as $k => $v){
                        $info = $this->model
                            ->where(['area_id'=>$v,'tid' =>$row['tid'] ])
                            ->find();
                        if($info){continue;}
                        $data[]=[
                            'area_id' => $v,
                            'tid'     => $taskInfo['id'],
                            'pid'     => $row['id']
                        ];
                    }
                    $result = $this->model->allowField(true)->saveAll($data);
                    $row->allowField(true)->save(['status'=>1],['id'=>$ids]);
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

        $row['img'] = $taskInfo['img'] ? explode(',',$taskInfo['img']) : [];
        $row['title'] = $taskInfo['title'];
        $row['content'] = $taskInfo['content'];
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
        $task =  new \app\admin\model\Task();
        $taskInfo = $task
        ->where(['id'=>$row['tid']])
        ->find();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if(!$row['status']){
                    $params['status'] = 1;
                }
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

                    $doInfo = $this->model
                        ->where(['do_id'=>$params['do_id'],'tid'=>$row['tid']])
                        ->find();
                    if($doInfo){
                        $this->error(__('has_appoint'));
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
        $task =  new \app\admin\model\Task();
        $taskInfo = $task
            ->where(['id'=>$row['tid']])
            ->find();

        $row['img'] = $taskInfo['img'] ? explode(',',$taskInfo['img']) : [];
        $row['title'] = $taskInfo['title'];
        $row['content'] = $taskInfo['content'];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /*
     * 指派团体
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
        $task =  new \app\admin\model\Task();
        $taskInfo = $task
            ->where(['id'=>$row['tid']])
            ->find();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if(!$row['status']){
                    $params['status'] = 1;
                }
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
                        $info = $this->model
                            ->where(['group_id'=>$v,'tid' =>$row['tid'] ])
                            ->find();
                        if($info){continue;}
                        $data[]=[
                            'group_id'  => $v,
                            'tid'       => $row['tid'],
                            'pid'       => $row['id'],
                            'area_id'   => $this->auth->area_id,
                            'is_team'   => 1
                        ];
                    }
                    $result = $this->model->allowField(true)->saveAll($data);
                    $row->allowField(true)->save(['status'=>1],['id'=>$ids]);
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

        $row['img'] = $taskInfo['img'] ? explode(',',$taskInfo['img']) : [];
        $row['title'] = $taskInfo['title'];
        $row['content'] = $taskInfo['content'];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 任务评价
     * @param unknown $ids
     * @return string
     */
    public function appraise($ids = null)
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
                $params['status'] = 4;
                $params['finish_time'] = time();
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
        $aid =  \app\admin\model\Activity::where(['tdid'=>$ids])->value('id');
        $report =  new \app\admin\model\ActivityReport();
        $reportInfo = $report
            ->where(['aid'=> $aid])
            ->find();

        $task =  new \app\admin\model\Task();
        $taskInfo = $task
            ->where(['id'=>$row['tid']])
            ->find();
        $row['img'] = $taskInfo['img'] ? explode(',',$taskInfo['img']) : [];
        $reportInfo['images'] = $reportInfo['images'] ? explode(',',$reportInfo['images']) : [];
        $row['title'] = $taskInfo['title'];
        $row['content'] = $taskInfo['content'];
        $this->view->assign("row", $row);
        $this->view->assign("report", $reportInfo);
        return $this->view->fetch();
    }
    /**
     * 查看任务详情
     * @param unknown $ids
     * @return string
     */
    public function info($ids = null){
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
        $task =  new \app\admin\model\Task();
        $taskInfo = $task
            ->where(['id'=>$row['tid']])
            ->find();

        $aid =  \app\admin\model\Activity::where(['tdid'=>$ids])->value('id');
        $activity = \app\admin\model\Activity::where(['tdid'=>$ids])->find();
        $report =  new \app\admin\model\ActivityReport();
        $reportInfo = $report
            ->where(['aid'=> $aid])
            ->find();
     if($reportInfo){
         $reportInfo['images'] = $reportInfo['images'] ? explode(',',$reportInfo['images']) : [];

     }
        $row['img'] = $taskInfo['img'] ? explode(',',$taskInfo['img']) : [];
        $row['title'] = $taskInfo['title'];
        $row['content'] = $taskInfo['content'];
        $this->view->assign("row", $row);
        $this->view->assign("report", $reportInfo);
        $this->view->assign("activity", $activity);
        return $this->view->fetch();
    }

}
