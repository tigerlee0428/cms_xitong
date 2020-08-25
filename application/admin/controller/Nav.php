<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 导航管理
 *
 * @icon fa fa-circle-o
 */
class Nav extends Backend
{
    
    /**
     * Nav模型对象
     * @var \app\admin\model\Nav
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Nav;

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index(){
        //设置过滤方法
        $tpe = intval(input("tpe"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            $Mywhere = [];
            if($tpe){
                $Mywhere['tpe'] = $tpe;
            }
            $this->where = initWhere($Mywhere);
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = $this->model
            ->where($where)
            ->order('display', 'desc')
            ->limit($offset, $limit)
            ->select();
            $list = collection($list)->toArray();
        
            return json($list);
        }
        $this->assignconfig("Tpe",$tpe);
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        $pid = intval(input("pid"));
        $level = intval(input("level"));
        $tpe = intval(input("tpe"));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params['status'] = 1;
            if(!isset($params['tpe'])){
                $navInfo = $this->model->get($pid)->toArray();
                $params['tpe'] = $navInfo['tpe'];
            }
            if(isset($params['ad_position']) && $params['ad_position']){
                $adPos = \app\admin\model\AdPos::where(['id'=>$params['ad_position']])->find();
                if($adPos){
                    $params['ad_position'] = $adPos['position'];
                }
            }
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
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign('pid',$pid);
        $this->view->assign('level',$level);
        $this->view->assign("tpe",$tpe);
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
                if(isset($params['ad_position']) && $params['ad_position']){
                    $adPos = \app\admin\model\AdPos::where(['id'=>$params['ad_position']])->find();
                    if($adPos){
                        $params['ad_position'] = $adPos['position'];
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
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $adPos = \app\admin\model\AdPos::where(['position'=>$row['ad_position']])->find();
        if($adPos){
            $row['ad_position'] = $adPos['id'];
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
}
