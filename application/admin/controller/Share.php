<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 供需 - 记录管理
 *
 * @icon fa fa-share
 */
class Share extends Backend
{
    
    /**
     * Share模型对象
     * @var \app\admin\model\Share
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Share;

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
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
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
            foreach($list as $k => $v){
                $list[$k]['area_name'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
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
                $params['check_admin_id'] = $this->auth->id;
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
                    $this->success(__('Check Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    
        $row = $row->toArray();
        $row['format_add_time'] = format_time($row['add_time']);
        $row['image'] = json_decode($row['image'],true);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    
    }
    public function view($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row = $row->toArray();
        $row['format_add_time'] = format_time($row['add_time']);
        $row['image'] = json_decode($row['image'],true);
        $shareLog = \app\admin\model\ShareLog::where(['share_id'=>$row['id'],'status'=>0])->select();
        $shareLogAll = \app\admin\model\ShareLog::where(['share_id'=>$row['id']])->order("status asc")->select();
        $shareLogList = [];
        foreach(collection($shareLog)->toArray() as $k => $v){
            $shareLogList[$k]['id'] = $v['id'];
            $shareLogList[$k]['reply_content'] = $v['reply_content'];
            $shareLogList[$k]['format_format_time'] = format_time($v['add_time']);
            $shareLogList[$k]['uname'] = \app\admin\model\User::where(['id'=>$v['uid']])->value("nickname");
            $shareLogList[$k]['format_add_time'] = format_time($v['add_time']);
            $shareLogAllList = [];
            $status = 0;
            foreach(collection($shareLogAll)->toArray() as $key => $val){
                if($val['share_id'] == $v['share_id'] && $val['log_id'] == $v['id']){
                    $shareLogAllList[] = [
                        'reply_content'     => $val['reply_content'],
                        'score'             => $val['score'],
                        'status'            => $val['status'],
                        'format_reply_time'        => format_time($val['reply_time']),
                        'format_add_time'          => format_time($val['add_time']),
                        'uname'             => \app\admin\model\User::where(['id'=>$val['uid']])->value("nickname"),
                    ];
                    $status = $val['status'];
                }
            }
            $shareLogList[$k]['shareLog'] = $shareLogAllList;
            $shareLogList[$k]['status'] = $status;
        }
        //print_r($shareLogList);exit;
        $this->view->assign("row", $row);
        $this->view->assign("shareLog", $shareLogList);
        return $this->view->fetch();
    }

}
