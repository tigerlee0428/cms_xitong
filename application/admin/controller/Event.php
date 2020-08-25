<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 流程 - 事件主体管理
 *
 * @icon fa fa-circle-o
 */
class Event extends Backend
{
    
    /**
     * Event模型对象
     * @var \app\admin\model\Event
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Event;
        $this->searchFields = 'title,username';
        $this->multiFields = 'is_open';
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
        $tpe = intval(input("tpe"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            if($tpe){
                $this->where = initWhere(['tpe'=>$tpe]);
            }
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
            foreach($list as $k=>$v){
                $list[$k]['img'] = is_array(json_decode($v['img'],true)) ? current(json_decode($v['img'],true)) : '';
            }
            $result = array("total" => $total, "rows" => $list);
    
            return json($result);
        }
        $this->assignconfig("tpe",$tpe);
        return $this->view->fetch();
    }
    
    /*
     * 处理
     */
    public function deal($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['is_deal'] == 1){
            $this->error(__('Has Deal'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['dealtime'] = time();
                $params['admin_id'] = $this->auth->id;
                $params['is_deal'] = 1;
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
                    $this->success(__('Deal Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    
        $row = $row->toArray();
        $row['addtime'] = format_time($row['addtime']);
        $row['dealtime'] = format_time($row['dealtime']);
        $row['areaName'] = \app\admin\model\Area::where(['id'=>$row['area_id']])->value("mergename");
        $row['imgs'] = is_array(json_decode($row['img'],true)) ? json_decode($row['img'],true) : [];
        $this->view->assign("row", $row);
        return $this->view->fetch();
    
    }
    
    /*
     * 处理
     */
    public function view($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row = $row->toArray();
        $row['addtime'] = format_time($row['addtime']);
        $row['dealtime'] = format_time($row['dealtime']);
        $row['imgs'] = is_array(json_decode($row['img'],true)) ? json_decode($row['img'],true) : [];
        $row['areaName'] = \app\admin\model\Area::where(['id'=>$row['area_id']])->value("mergename");
        $this->view->assign("row", $row);
        return $this->view->fetch();
    
    }
}
