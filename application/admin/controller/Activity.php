<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 活动 - 主体表（志愿者活动）
 *
 * @icon fa fa-circle-o
 */
class Activity extends Backend
{

    /**
     * Activity模型对象
     * @var \app\admin\model\Activity
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Activity;
        $this->searchFields = "title,brief,address";
        $this->multiFields = 'is_publish';
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    /**
     * 查看
     * tpe=0 志愿活动
     * tpe=1 点单活动
     */
    public function index()
    {
        //设置过滤方法
        $tpe = intval(input("tpe",-1));
        $is_check = intval(input("is_check",-1));
        $category = intval(input("category"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where = [];
            if($is_check != -1){
                $where['is_check'] = $is_check;
            }
            if($tpe != -1){
                switch($tpe){
                    case 0:
                        $where['is_volunteer'] = 1;
                        break;
                    case 1:
                        $where['is_menu'] = 1;
                        break;
                }
            }
            if($category){
                $where['category'] = $category;
            }
            if($this->auth->is_admin){
                if($this->auth->area_id){
                    $where['area_id'] = ['in',\app\common\model\Cfg::childArea($this->auth->area_id)];
                }
            }else{
                $where['admin_id'] = $this->auth->id;
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
                $list[$k]['area_name'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
                $url = config("interface_domain") . "/home/auth/wxLogin?redirect_uri=".urlencode(config("interface_domain") . "/home/index/signin?id=" . $v['id']);
                $list[$k]['qrcode'] = config("interface_domain").'/home/index/qrcode?url='.$url;
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig("tpe",$tpe);
        $this->assignconfig("category",$category);
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        $tpe = intval(input("tpe"));
        $tdid = intval(input('ids'));
        $category = intval(input("category"));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if(!isset($params['title'])){
                    $this->error(__('Invalid parameters'));
                }
                $params = $this->preExcludeFields($params);
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                $status = 0;
                $cur_time = format_time(time());
                if($params['start_time'] >= $params['end_time']){
                    $this->error(__('Start time error'));
                }
                if($params['publish_time'] >= $params['start_time']){
                    $this->error(__('Publish time error'));
                }
                $is_publish = 0;
                if($params['start_time'] < $cur_time){
                    $status = 1;
                }
                if($params['end_time'] < $cur_time){
                    $status = 2;
                }
                $params['status'] = $status;
                $params['area_id'] = $this->auth->area_id;
                $params['admin_id'] = $this->auth->id;
                $params['admin_name'] = $this->auth->nickname;
                if($params['address']){
                    list($x,$y) = jwd($params['address']);
                    $params['x'] = $x;
                    $params['y'] = $y;
                }
                if($tdid){
                    $params['tdid'] = $tdid;

                }
                if($tpe == 0){
                    $params['is_volunteer'] = 1;
                }elseif($tpe == 1){
                    $params['is_menu'] = 1;
                }

                if(isset($params['vol_num']) && $params['vol_num'] == 0){
                    $params['is_need_vol'] = 0;
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
                    if($tdid){
                        $taskDo = new \app\admin\model\TaskDo;
                        $taskDo ->save(['status'=>2],['id'=>$tdid]);
                    }

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
                    if($params['place_id']){
                        \app\admin\model\Place::where(['id'=>$params['place_id']])->setInc("activity_count");
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $tpeData = $this->model->getActivityTpe();
        $this->view->assign("category",$category);
        $this->view->assign("tpe",$tpe);
        $this->view->assign("tdid",$tdid);
        $this->view->assign('tpedata', $tpeData);
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $oldPlaceId = $row['place_id'];
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
                if(!isset($params['title'])){
                    $this->error(__('Invalid parameters'));
                }
                $params = $this->preExcludeFields($params);
                $status = 0;
                $cur_time = format_time(time());

                if($params['start_time'] >= $params['end_time']){
                    $this->error(__('Start time error'));
                }
                if($params['publish_time'] >= $params['start_time']){
                    $this->error(__('Publish time error'));
                }

                if($params['start_time'] < $cur_time){
                    $status = 1;
                }
                if($params['end_time'] < $cur_time){
                    $status = 2;
                }
                $params['status'] = $status;
                if($params['address']){
                    list($x,$y) = jwd($params['address']);
                    $params['x'] = $x;
                    $params['y'] = $y;
                }
                $params['is_check'] = 0;
                $params['is_publish'] = 0;
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
                    if($params['place_id'] && $oldPlaceId != (int)$params['place_id']){
                        \app\admin\model\Place::where(['id'=>$params['place_id']])->setInc("activity_count");
                        \app\admin\model\Place::where(['id'=>$oldPlaceId])->setDec("activity_count");
                    }
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /*
     * 审核
     */
    public function check($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['is_check'] == 1){
            $this->error(__('Has Check'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['check_time'] = time();
                $params['check_admin'] = $this->auth->id;
                if($params['is_check'] == 1){
                    $params['is_publish'] = 1;
                    //$params['publish_time'] = time();
                    unset($params['check_case']);
                }elseif($params['is_check'] == 2){
                    if(!$params['check_case']){
                        $this->error(__('No Check Case Reason'));
                    }
                }
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
                    if($row['uid']){
                        $activity = $this->model->get($ids)->toArray();
                        $activity['action'] = 'check_success';
                        $activity['uid']  = $row['uid'];
                        \think\Hook::listen("activity",$activity);
                    }
                    if($params['is_check'] == 1){
                        $activity = $this->model->get($ids)->toArray();
                        $activity['action'] = 'activity';
                        \think\Hook::listen("volunteer",$activit);
                        \app\admin\model\VolunteerGroup::where(['id'=>$row['group_id']])->setInc("activity_num");
                    }
                    $this->success(__('Check Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $row = $row->toArray();
        $row['start_time'] = format_time($row['start_time']);
        $row['end_time'] = format_time($row['end_time']);
        $row['areaName'] = \app\admin\model\Area::where(['id'=>$row['area_id']])->value("mergename");
        $row['add_name'] = \app\admin\model\Admin::where(['id'=>$row['admin_id']])->value("nickname");
        $this->view->assign("row", $row);
        return $this->view->fetch();

    }

    /*
     *  上报
     */
    public function report($ids = null){
        $row = $this->model->get($ids);

        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['is_report'] == 1){
            $this->error(__('Has report'));
        }

        if ($this->request->isPost()) {
                  $params = $this->request->post("row/a");
            if ($params) {
                if(!$params['content'] || !$params['servers']){
                    $this->error(__('Invalid parameters'));
                }

                $params = $this->preExcludeFields($params);
                $params['admin_id'] = $this->auth->id;
                $params['aid'] = $ids;
                $params['video_ids'] = isset($params['video_id']) ? $params['video_id'] : 0;
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    $ActivityReport_model = new \app\admin\model\ActivityReport;
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $ActivityReport_model->validateFailException(true)->validate($validate);
                    }
                    $result = $ActivityReport_model->allowField(true)->save($params);
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
                    $this->model->save(['is_report'=>1,'status'=>3],['id'=>$ids]);
                    $tdid = \app\admin\model\Activity::where(['id'=>$ids])->value('tdid');
                    if($tdid){
                        $taskDo = new \app\admin\model\TaskDo();
                        $taskDo ->where(['id'=>$tdid])->update(['status'=>3]);
                    }
                    $this->success(__('Report Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();

    }
    /*
     *  点评
     */
    public function comment($ids = null){
        $row = $this->model->get($ids);
        $report = \app\admin\model\ActivityReport::get(['aid'=>$ids])->toArray();
        if (!$row) {
            $this->error(__('No Results were found'));
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
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->save($params,['id'=>$ids]);
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
                    $this->model->save(['is_appraise' => 1,'status'=>4],['id'=>$ids]);
                    $tdid = \app\admin\model\Activity::where(['id'=>$ids])->value('tdid');
                    if($tdid){
                        $taskDo = new \app\admin\model\TaskDo();
                        $taskDo ->where(['id'=>$tdid])->update(['status'=>4]);
                    }
                    $this->success(__('Appraise Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $report['video_path'] = $report['video_ids'] != '' ? cfg("cst_video_public_url")."/media/video/".\app\common\model\Video::where(['id'=>$report['video_ids']])->value("third_id").".mp4" : '';
        $this->view->assign("report", $report);
        $this->view->assign("row", $row);
        $this->assignconfig("actIds",$ids);
        return $this->view->fetch();

    }
}
