<?php

namespace app\admin\controller;

use app\admin\model\Admin;
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
class WorkDo extends Backend
{

    /**
     * WorkDo模型对象
     * @var \app\admin\model\WorkDo
     */
    protected $model = null;
    protected $noNeedRight = ['appointDo', 'toperator','designate'];


    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\WorkDo;

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
        $ids = intval(input('ids'));
        $style = intval(input('style'));
        $wid = intval(input('wid'));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where=[];
            if($ids){
                $where['pid'] =  $ids;
            }elseif($wid){
                $where['wid'] =  $wid;
            }
            if($this->auth->area_id){
                $where['area_id'] = ['in',\app\common\model\Cfg::childArea($this->auth->area_id)];
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
                if($v['group_id']){
                    $list[$k]['object'] = \app\admin\model\VolunteerGroup::where(['id' => $v['group_id']])->value('title');
                }else{
                    $list[$k]['object'] = \app\admin\model\Area::where(['id' => $v['area_id']])->value('name');
                }
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }

        $this->assignconfig('area_id',$this->auth->area_id);
        $this->assignconfig("style",$style);
        $this->assignconfig("ids",$ids);
        $this->assign("ids",$ids);
        return $this->view->fetch();
    }
    public function mywork()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $volunteerGroup = \app\admin\model\VolunteerGroup::where(['admin_id'=>$this->auth->id])->find();
            if($volunteerGroup){
                $where['group_id'] = $volunteerGroup['id'];
            }else{
                $is_admin =\app\admin\model\Admin::where(['id'=>$this->auth->id])->value('is_admin');
                if(!$is_admin){
                    $where['do_id'] = $this->auth->id;
                }else{
                    $where['area_id'] = $this->auth->area_id;
                }
                $where['group_id'] = 0;
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
                if($v['group_id']){
                    $list[$k]['object'] = \app\admin\model\VolunteerGroup::where(['id'=>$v['group_id']])->value('title');
                }else{
                    $list[$k]['object'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     *
     * 指派选择
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
        $work = \app\admin\model\WorkOrder::get($row['wid']);
        $work['img'] = $work['img'] ? explode(',',$work['img']) : [];
        $this->view->assign("row", $work);
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
                     $info = $this->model
                            ->where(['area_id'=>$params['area_id'],'wid' =>$row['wid'] ])
                            ->find();
                    if($info){
                        $this->error(__('has_appoint'));
                    }
                        $data=[
                            'area_id' => $params['area_id'],
                            'wid'     => $row['wid'],
                            'pid'     => $ids,
                            'title'   => $row['title'],
                            'need_finish_time' => $row['need_finish_time'],
                            'style'   => 1,

                        ];
                    $result = $this->model->allowField(true)->save($data);
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
                    $doInfo = $this->model
                        ->where(['do_id'=>$params['do_id'],'wid'=>$row['wid']])
                        ->find();
                    if($doInfo){
                        $this->error(__('has_appoint'));
                    }
                    $data=[
                        'area_id' => $this->auth->area_id,
                        'wid'     => $row['wid'],
                        'pid'     => $ids,
                        'title'   => $row['title'],
                        'need_finish_time' => $row['need_finish_time'],
                        'style'   => 3,
                        'do_id'   => $params['do_id']
                    ];
                    $result = $this->model->allowField(true)->save($data);
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
                        $info = $this->model
                            ->where(['group_id'=>$params['group_id'],'wid' =>$row['wid'] ])
                            ->find();
                    if($info){
                        $this->error(__('has_appoint'));
                    }
                        $data=[
                            'group_id'  => $params['group_id'],
                            'wid'       => $row['wid'],
                            'pid'       => $row['id'],
                            'area_id'   => $this->auth->area_id,
                            'title'     => $row['title'],
                            'need_finish_time' => $row['need_finish_time'],
                            'style'   => 2,
                        ];
                    $result = $this->model->save($data);
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
                } catch (PDOException $e){
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
        $workInfo = \app\admin\model\WorkOrder::where(['id'=>$row['wid']])->find();
        $log = \app\admin\model\WorkLog::where(['wdid'=> $ids])->find();
        $log['img'] = $log['img'] ? explode(',',$log['img']) : '';
        $row['img'] = $workInfo['img'] ? explode(',',$workInfo['img']) : '';
        $row['title'] = $workInfo['title'];
        $row['content'] = $workInfo['content'];
        $row['mobile'] = $workInfo['mobile'];
        $row['address'] = $workInfo['address'];
        $this->view->assign("row",$row);
        $this->view->assign("log",$log);
        return $this->view->fetch();
    }


}
