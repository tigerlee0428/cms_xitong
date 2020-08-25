<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Collection;
/**
 * 系统 - 志愿者管理
 *
 * @icon fa fa-circle-o
 */
class Volunteer extends Backend
{
    
    /**
     * Volunteer模型对象
     * @var \app\admin\model\Volunteer
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Volunteer;
        $this->searchFields = "name,brief,card,mobile";
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
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        $is_check = intval(input("is_check",-1));
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $gid = intval(input("gid",0));
            $Mywhere = [];
            if($gid){
                $volunteerList = \app\admin\model\VolunteerGroupAccess::where(['gid'=>$gid])->select();
                $volunteers = [];
                foreach($volunteerList as  $key => $val){
                    $volunteers[] = $val['vid'];
                }
                $Mywhere['id'] = ['in',$volunteers];  
            }
            if($is_check != -1){
                $Mywhere['is_check'] = $is_check;
            }
            $this->where = initWhere($Mywhere);
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
            }
            $result = array("total" => $total, "rows" => $list);
    
            return json($result);
        }
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
                    \app\admin\model\User::update(['is_volunteer'=>1,'vid'=>$ids],['id'=>$row['uid']]);
                    notice([
                        'sys_msg' => [
                            'title'     => $params['is_check'] == 1 ? __("Congratulations on passing the Volunteer Audit") : __("I am sorry you did not pass the volunteer audit"),
                            'brief'     => $params['is_check'] == 1 ? __("Congratulations on passing the Volunteer Audit") : __("I am sorry you did not pass the volunteer audit"),
                            'uid'       => $row['uid'],
                            'tpe'       => 0,
                        ]
                    ]);
                    if($params['is_check'] == 1){
                        $volunteer = $this->model->get($ids)->toArray();
                        $volunteer['action'] = 'volunteer';
                        \think\Hook::listen("volunteer",$volunteer);
                    }
                    $this->success(__('Check Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        
        $row['join_time'] = format_time($row['join_time']);
        $row['areaName'] = \app\admin\model\Area::where(['id'=>$row['area_id']])->value("mergename");
        $this->view->assign("row", $row);
        return $this->view->fetch();
    
    }
    
    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            $pk = $this->model->getPk();
            $adminIds = $this->getDataLimitAdminIds();
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $list = $this->model->where($pk, 'in', $ids)->select();
    
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $count += $v->delete();
                }
                Db::commit();
            } catch (PDOException $e) {
                Db::rollback();
                $this->error($e->getMessage());
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
            if ($count) {
                \app\admin\model\VolunteerGroupAccess::where(['vid'=>['in',$ids]])->delete();
                \app\admin\model\User::update(['is_volunteer'=>0,'is_volunteer_group'=>0,'vid'=>0],['vid'=>['in',$ids]]);
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
    
    public function import(){
        return parent::import();
    }
}
