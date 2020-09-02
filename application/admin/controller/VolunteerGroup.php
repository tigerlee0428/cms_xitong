<?php

namespace app\admin\controller;

use app\admin\model\Admin;
use app\common\controller\Backend;
use think\Db;
use fast\Random;
use fast\Tree;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Collection;
/**
 * 志愿者 - 志愿者团体
 *
 * @icon fa fa-circle-o
 */
class VolunteerGroup extends Backend
{

    /**
     * VolunteerGroup模型对象
     * @var \app\admin\model\VolunteerGroup
     */
    protected $model = null;
    protected $noNeedRight = ['select'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\VolunteerGroup;
        $this->searchFields = "title,master,mobile";
        $this->multiFields = 'is_check';

        $areaList = collection(\app\admin\model\Area::where([])->field("id,pid,name")->select())->toArray();
        Tree::instance()->init($areaList);
        $areaResult = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($this->auth->area_id));
        $areaData[$this->auth->area_id] = \app\admin\model\Area::where(['id'=>$this->auth->area_id])->value('name');
        foreach ($areaResult as $k => $v)
        {
            $areaData[$v['id']] = $v['name'];
        }
        $this->view->assign('areadata', $areaData);

    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     *
     * 选择
     */
    public function select()
    {
        if ($this->request->isAjax()) {
            return $this->myGroup();
        }
        return $this->view->fetch();
    }
    /**
     *
     * 查看
     */
    public function index()
    {
        $ids = intval(input("ids"));
        $is_check = intval(input("is_check",-1));
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where = [];
            if($ids){
                $group = [];
                $groups = \app\admin\model\VolunteerGroupAccess::where(['vid'=>$ids,'is_pass'=>1])->select();
                foreach($groups as $v){
                    $group[] = $v['gid'];
                }
                $where['id'] = ['in',$group];
            }
            if($is_check != -1){
                $where['is_check'] = $is_check;
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
                $list[$k]['areaName'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
                $list[$k]['number'] = \app\admin\model\VolunteerGroupAccess::where(['gid'=>$v['id']])->count();
                $volunteerList = \app\admin\model\VolunteerGroupAccess::where(['gid'=>$v['id'],'is_pass'=>1])->select();
                $volunteers = [];
                foreach($volunteerList as  $key => $val){
                    $volunteers[] = $val['vid'];
                }
                $list[$k]['jobtimeall'] = \app\admin\model\Volunteer::where(['id'=>['in',$volunteers]])->sum('jobtime');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig("ids",$ids);
        return $this->view->fetch();
    }

    public function myGroup()
    {
        $ids = intval(input("ids"));

        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            //$where = ['is_check' =>1];
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
            foreach($list as $k => $v){
                $list[$k]['areaName'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
                $list[$k]['number'] = \app\admin\model\VolunteerGroupAccess::where(['gid'=>$v['id'],'is_pass'=>1])->count();
                $volunteerList = \app\admin\model\VolunteerGroupAccess::where(['gid'=>$v['id'],'is_pass'=>1])->select();
                $volunteers = [];
                foreach($volunteerList as  $key => $val){
                    $volunteers[] = $val['vid'];
                }
                $list[$k]['jobtimeall'] = \app\admin\model\Volunteer::where(['id'=>['in',$volunteers]])->sum('jobtime');
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        $this->assignconfig("ids",$ids);
        return $this->view->fetch('index');
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
                    \app\admin\model\User::update(['is_volunteer_group'=>1],['id'=>$row['uid']]);
                    notice([
                        'sys_msg' => [
                            'title'     => $params['is_check'] == 1 ? __("Congratulations on passing the VolunteerGroup Audit") : __("I am sorry you did not pass the volunteerGroup audit"),
                            'brief'     => $params['is_check'] == 1 ? __("Congratulations on passing the VolunteerGroup Audit") : __("I am sorry you did not pass the volunteerGroup audit"),
                            'uid'       => $row['uid'],
                            'tpe'       => 0,
                        ]
                    ]);
                    if($params['is_check'] == 1){
                        $volunteerGroup = $this->model->get($ids)->toArray();
                        $volunteerGroup['action'] = 'volunteergroup';
                        \think\Hook::listen("volunteer",$volunteerGroup);
                    }
                    $this->success(__('Check Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }

        $row['addtime'] = format_time($row['addtime']);
        $row['areaName'] = \app\admin\model\Area::where(['id'=>$row['area_id']])->value("mergename");
        $this->view->assign("row", $row);
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
                $params['admin_id'] = $this->_addAdmin($params['mobile'],$params['title'],$params['area_id']);
                $params['has_admin'] = 1;
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
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
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
                if(isset($params['is_auto'])){
                    $params['has_admin'] =1;
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
                    if(isset($params['is_auto'])){
                        $pinyin =   new \fast\Pinyin();
                        $title = $pinyin->get($row['title'],true);
                        $this->_addAdmin($title,$row['title'],$row['area_id']);
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

    private function _addAdmin($username,$nickname,$area_id){
        $salt = Random::alnum();
        $password = md5(md5('volunteer123') . $salt);
        $admin_data = [
            'username' => $username,
            'nickname' => $nickname,
            'password' => $password,
            'salt'     => $salt,
            'area_id'  => $area_id,
            'avatar'   => '/assets/img/avatar.png'
        ];
        $admin =  Admin::create($admin_data);
        $access = ['uid' => $admin->id, 'group_id' => 7];
        model('AuthGroupAccess')->save($access);
        return $admin->id;
    }




    /**
     * 自动生成管理员账号
     */
    public function autoAdmin($ids = null)
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
                $params = [];
                $pinyin =   new \fast\Pinyin();
                $title = $pinyin->get($row['title'],true);
                $params['admin_id'] = $this->_addAdmin($title,$row['title'],$row['area_id']);
                $params['has_admin']  = 1;
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

    }




}
