<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 点单 - 点单期管理
 *
 * @icon fa fa-circle-o
 */
class OrderPeriod extends Backend
{
    
    /**
     * OrderPeriod模型对象
     * @var \app\admin\model\OrderPeriod
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\OrderPeriod;

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
        $ids = intval(input("ids"));
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            $where = [];
            if($ids){
                $where['order_id'] = $ids;
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
            foreach($list as &$v){
                $v['order_name'] = \app\admin\model\Order::where(['id'=>$v['order_id']])->value("title");
            }
            $result = array("total" => $total, "rows" => $list);
    
            return json($result);
        }
        $this->assignconfig("ids",$ids);
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        $ids = intval(input("ids"));
        $row = \app\admin\model\Order::get($ids);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
    
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if($row['cur_period']){
                    $period = \app\admin\model\OrderPeriod::get($row['cur_period']);
                    if($period){
                        if($period['status'] != 2){
                            $this->error(__('Order not finish'));
                        }
                    }
                }
                if($ids){
                    $params['order_id'] = $ids;
                }
                $cur_time = date("Y-m-d H:i:s");
                $status = 0;
                if($params['start_time'] < $cur_time){
                    $status = 1;
                }
                if($params['end_time'] < $cur_time){
                    $status = 2;
                }
                $params['status'] = $status;
                $params['add_time'] = strtotime($cur_time);
                $params['start_time'] = strtotime($params['start_time']);
                $params['end_time'] = strtotime($params['end_time']);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $result = $this->model->allowField(true)->insertGetId($params);
                    \app\admin\model\Order::update(['cur_period'=>$result],['id'=>$ids]);
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
        $this->assign("endtime",format_time(time() + $row['cycle'] * 24 * 3600));
        $this->assignconfig("ids",$ids);
        return $this->view->fetch();
    }
    

}
