<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 系统 - 推流管理
 *
 * @icon fa fa-camera
 */
class Camera extends Backend
{
    
    /**
     * Camera模型对象
     * @var \app\admin\model\Camera
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Camera;
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("isRecordList", $this->model->getIsRecordList());
        $this->view->assign("domainList", $this->model->getDomainList());
        $this->multiFields = 'is_up';
        $this->searchFields = "name,address";
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

    /**
     * 添加直播流
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {

                if (!$params['name'] || !$params['url'] || !$params['domain']) {
                    $this->error(__('Invalid parameters'));
                }

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                $url =  cfg('cst_live_url') . "act=addLiveSource";

                $post_data = [
                    "client" => "cms",
                    "name" => encrypt($params['name']),
                    "url" => encrypt($params['url']),
                    "domain" => encrypt($params['domain']),
                    "status" => encrypt($params['status']),
                    "server_id" => encrypt(1)
                ];
                $data = myhttp($url, $post_data);
                if (!$data) {
                    $this->error(__('get_live_server_failed'));
                }

                $data = json_decode($data, true);

                if (!isset($data['status']) || $data['status'] != 0) {
                    $this->error(__('add_live_failed'));
                }

                $params['third_id'] = $data['source_id'];

                $params = $this->preExcludeFields($params);

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
            $url =  cfg('cst_live_url')."?act=deleteLiveSource";
            $count = 0;
            Db::startTrans();
            try {
                foreach ($list as $k => $v) {
                    $post_data=[
                        "client"    => "cms",
                        "id"      => encrypt($v['third_id']),
                    ];

                    $data = myhttp($url, $post_data);
                    if (!$data) {
                        $this->error(__('get_live_server_failed'));
                    }

                    $data = json_decode($data, true);
                    if (!isset($data['status'])) {
                        $this->error(__('delete_live_failed'));
                    }
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
                $this->success();
            } else {
                $this->error(__('No rows were deleted'));
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
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

                if (!$params['name'] || !$params['url'] || !$params['domain']) {
                    $this->error(__('Invalid parameters'));
                }

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                $url =  cfg('cst_live_url') . "act=modifyLiveSource";

                $post_data = [
                    "client" => "cms",
                    "name" => encrypt($params['name']),
                    "url" => encrypt($params['url']),
                    "domain" => encrypt($params['domain']),
                    "status" => encrypt($params['status']),
                    "server_id" => encrypt(1)
                ];
                $data = myhttp($url, $post_data);
                if (!$data) {
                    $this->error(__('get_live_server_failed'));
                }

                $data = json_decode($data, true);

                if (!isset($data['status']) || $data['status'] != 0) {
                    $this->error(__('modify_live_failed'));
                }

                $params['third_id'] = $data['source_id'];
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
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 批量更新
     */
    public function multi($ids = "")
    {
        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
                if ($values || $this->auth->isSuperAdmin()) {
                    $adminIds = $this->getDataLimitAdminIds();
                    if (is_array($adminIds)) {
                        $this->model->where($this->dataLimitField, 'in', $adminIds);
                    }
                    $count = 0;
                    Db::startTrans();
                    try {
                        $list = $this->model->where($this->model->getPk(), 'in', $ids)->select();
                        $start_live_url =  cfg('cst_live_url')."?act=startLive";
                        $stop_live_url =  cfg('cst_live_url')."act=stopLive";
                        foreach ($list as $index => $item) {
                            $post_data = [
                                "client" => "cms",
                                "id" => encrypt($item["third_id"]),
                            ];

                            $data = myhttp($item['is_up'] == 1 ? $stop_live_url : $start_live_url, $post_data);
                            if (!$data) {
                                $this->error(__('get_live_server_failed'));
                            }

                            $data = json_decode($data, true);
                            if (!isset($data['status'])) {
                                $this->error($item['is_up'] == 1 ?__('stop_live_failed'):__('start_live_failed'));
                            }

                            $count += $item->allowField(true)->isUpdate(true)->save($values);
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
                        $this->success();
                    } else {
                        $this->error(__('No rows were updated'));
                    }
                } else {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 预览直播流
     *
     */
    public function play($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row = $row->toArray();
        $url = cfg('cst_live_url')."act=getLiveInfo";

        $para = [
            "client" => "admin",//admin可以取大勇直播的rtmp流
            "id" => encrypt($row['third_id']),
        ];
        $liveInfo = myhttp($url, $para);
        $liveInfo = json_decode($liveInfo, true);
        $row['live_url'] = str_replace(cfg("cst_live_private_url"), cfg("cst_live_public_url"), $liveInfo['url_hls']);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    /**
     * 选择
     */
    public function select()
    {
        if ($this->request->isAjax()) {
            return $this->index();
        }
        return $this->view->fetch();
    }
}
