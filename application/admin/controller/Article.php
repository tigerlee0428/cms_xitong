<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Collection;
/**
 * 系统 - 文章管理
 *
 * @icon fa fa-circle-o
 */
class Article extends Backend
{
    
    /**
     * Article模型对象
     * @var \app\admin\model\Article
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Article;
        $this->searchFields = "title";
        $this->multiFields = 'is_publish,is_del,click_count';
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
        $category = intval(input("category"));
        $is_check = intval(input("is_check",-1));
        $is_final_check = intval(input("is_final_check",-1));
        $is_publish = intval(input("is_publish",-1));
        $is_del = intval(input("is_del",-1));
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
            if($is_final_check != -1){
                $where['is_final_check'] = $is_final_check;
            }
            if($is_publish != -1){
                $where['is_publish'] = $is_publish;
            }
            if($is_del != -1){
                $where['is_del'] = $is_del;
            }
            if($category){
                $where['category'] = ['in',\app\common\model\Cfg::childCategory($category)];
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
                $list[$k]['categoryName'] = \app\admin\model\Category::where(['id'=>$v['category']])->value('title');
                $list[$k]['adminName'] = $v['uid'] ? \app\admin\model\User::where(['id'=>$v['uid']])->value('nickname') : \app\admin\model\Admin::where(['id'=>$v['admin_id']])->value('nickname');
                $list[$k]['areaName'] = \app\admin\model\Area::where(['id'=>$v['area_id']])->value('name');
            }
            $result = array("total" => $total, "rows" => $list);
             return json($result);
        }
        $this->assignconfig("category",$category);
        $this->assignconfig("wx_domain",config("wx_domain"));
        $this->assignconfig("pc_domain",config("pc_domain"));
        $this->assign("category",$category);
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        $category = intval(input("category"));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if(!isset($params['title'])){
                    $this->error(__('Invalid parameters'));
                }
                if(!$params['category']){
                    $this->error(__('No Select Category'));
                }
                $params['is_show'] = isset($params['is_show']) ? $params['is_show'] : 0;
                $params['is_tv_show'] = isset($params['is_tv_show']) ? $params['is_tv_show'] : 0;
                $params['is_wx_show'] = isset($params['is_wx_show']) ? $params['is_wx_show'] : 0;
                $params['is_pc_show'] = isset($params['is_pc_show']) ? $params['is_pc_show'] : 0;
                $params['is_show_index'] = isset($params['is_show_index']) ? $params['is_show_index'] : 0;
                if(cfg('is_auto_img')){
                    if(!$params['img']){
                        $params['img'] = autoImg($params['title']);
                    }
                }
                if($params['tpe'] == 2 || $params['tpe'] == 3){
                    if(!$params['img']){
                        $this->error(__('Imgs Require'));
                    }
                }
                if($params['tpe'] == 4 || $params['tpe'] == 5){
                    if(!isset($params['video_id'])){
                        $this->error(__('No Select Video'));
                    }
                    if($params['tpe'] == 4){
                        if(!$params['img']){
                            $params['img'] = \app\admin\model\Video::where(['id'=>$params['video_id']])->value("video_img");
                        }
                    }
                    if($params['tpe'] == 5){
                        if(!$params['img']){
                            $params['img'] = \app\admin\model\Camera::where(['id'=>$params['video_id']])->value("img");
                        }
                    }
                }
                $params = $this->preExcludeFields($params);                
                $this->dataLimit = true;
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                    $params['area_id'] = $this->auth->area_id;
                    $params['article_model'] = \app\admin\model\Category::where(['id'=>$params['category']])->value('model_type');
                }
                if(!$params['add_time']){
                    $params['add_time'] = time();
                }else{
                    $params['add_time'] = strtotime($params['add_time']);
                }
                if(cfg("check_switch") == 2){
                    $params['is_check'] = 0;
                    $params['is_final_check'] = 0;
                    if($this->auth->check('article/check',$this->auth->id)){
                        $params['is_check'] = 1;
                    }
                    if($this->auth->check('article/finalcheck',$this->auth->id)){
                        $params['is_check'] = 1;
                        $params['is_final_check'] = 1;
                    }
                }elseif(cfg("check_switch") == 1){
                    $params['is_final_check'] = 0;
                    if($this->auth->check('article/finalcheck',$this->auth->id)){
                        $params['is_final_check'] = 1;
                    }
                }
                if(cfg("publish_check") == 1){
                    $params['is_publish'] = 0;
                }
                $extendParams = [];
                $extendTable = \app\admin\model\CategoryModule::where(['code'=>$params['article_model']])->value('table');
                if(Db::query('SHOW TABLES LIKE '."'".config("database.prefix")."article_".$extendTable."'")){
                    $fields_sql = "SHOW COLUMNS FROM ".config("database.prefix")."article_".$extendTable;
                    $fields_data = Db::query($fields_sql);
                    foreach($fields_data as $k => $v){
                        if($v['Field'] == 'id'){
                            continue;
                        }
                        $extendParams[$v['Field']] = $params[$v['Field']];
                        unset($params[$v['Field']]);
                    }
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
                    $result = $this->model->allowField(true)->insertGetId($params);
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
                    $extendParams['id'] = $result;
                    Db::name("article_".$extendTable)->insert($extendParams);
                    $this->success();
                } else {
                    $this->error(__('No rows were inserted'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->assign("articletpe",$this->model->getArticleTpe());
        $article_model = \app\admin\model\Category::where(['id'=>$category])->value('model_type');
        $this->assign("articlemodel",$article_model);
        $this->assignconfig("articlemodelc",$article_model);
        $cateList = Collection(\app\admin\model\Category::select())->toArray();
        $cateStr = format_option(children($cateList),$category);
        $this->view->assign("catestr",$cateStr);
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
                if(!isset($params['title'])){
                    $this->error(__('Invalid parameters'));
                }
                if(!$params['category']){
                    $this->error(__('No Select Category'));
                }
                $params['is_show'] = isset($params['is_show']) ? $params['is_show'] : 0;
                $params['is_tv_show'] = isset($params['is_tv_show']) ? $params['is_tv_show'] : 0;
                $params['is_wx_show'] = isset($params['is_wx_show']) ? $params['is_wx_show'] : 0;
                $params['is_pc_show'] = isset($params['is_pc_show']) ? $params['is_pc_show'] : 0;
                $params['is_show_index'] = isset($params['is_show_index']) ? $params['is_show_index'] : 0;
                if(cfg('is_auto_img')){
                    if(!$params['img']){
                        $params['img'] = autoImg($params['title']);
                    }
                }
                if($params['tpe'] == 2 || $params['tpe'] == 3){
                    if(!$params['img']){
                        $this->error(__('Imgs Require'));
                    }
                }
                if($params['tpe'] == 4 || $params['tpe'] == 5){
                    if(!isset($params['video_id'])){
                        $this->error(__('No Select Video'));
                    }
                    if($params['tpe'] == 4){
                        if(!$params['img']){
                            $params['img'] = \app\admin\model\Video::where(['id'=>$params['video_id']])->value("video_img");
                        }
                    }
                    if($params['tpe'] == 5){
                        if(!$params['img']){
                            $params['img'] = \app\admin\model\Camera::where(['id'=>$params['video_id']])->value("img");
                        }
                    }
                }
                $params = $this->preExcludeFields($params);
                if(!isset($params['add_time'])){
                    unset($params['add_time']);
                }else{
                    $params['add_time'] = strtotime($params['add_time']);
                }
                if(cfg("check_switch") == 2){
                    $params['is_check'] = 0;
                    $params['is_final_check'] = 0;
                    if($this->auth->check('article/check',$this->auth->id)){
                        $params['is_check'] = 1;
                    }
                    if($this->auth->check('article/finalcheck',$this->auth->id)){
                        $params['is_check'] = 1;
                        $params['is_final_check'] = 1;
                    }
                }elseif(cfg("check_switch") == 1){
                    $params['is_final_check'] = 0;
                    if($this->auth->check('article/finalcheck',$this->auth->id)){
                        $params['is_final_check'] = 1;
                    }
                }
                if(cfg("publish_check") == 1){
                    $params['is_publish'] = 0;
                }
                $extendParamsWhere = [];
                $extendTable = \app\admin\model\CategoryModule::where(['code'=>$row['article_model']])->value('table');
                if(Db::query('SHOW TABLES LIKE '."'".config("database.prefix")."article_".$extendTable."'")){
                    $fields_sql = "SHOW COLUMNS FROM ".config("database.prefix")."article_".$extendTable;
                    $fields_data = Db::query($fields_sql);
                    foreach($fields_data as $k => $v){
                        if($v['Field'] == 'id'){
                            continue;
                        }
                        $extendParams[$v['Field']] = $params[$v['Field']];
                        unset($params[$v['Field']]);
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
                    $extendParamsWhere['id'] = $ids;
                    Db::name("article_".$extendTable)->where($extendParamsWhere)->update($extendParams);
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $extendParams = [];
        $extendTable = \app\admin\model\CategoryModule::where(['code'=>$row['article_model']])->value('table');
        $extentRow = Db::name("article_".$extendTable)->where(['id'=>$ids])->find();
        if($row['tpe'] == 4){
            $row['video_name'] = \app\admin\model\Video::where(['id'=>$row['video_id']])->value('title');
        }elseif($row['tpe'] == 5){
            $row['video_name'] = \app\admin\model\Camera::where(['id'=>$row['video_id']])->value('name');
        }else{
            $row['video_name'] = '';
        }
        $row = array_merge($row->toArray(),$extentRow);
        $this->view->assign("row", $row);
        $this->assign("articletpe",$this->model->getArticleTpe());
        $article_model = \app\admin\model\Category::where(['id'=>$row['category']])->value('model_type');
        $this->assign("articlemodel",$article_model);
        $this->assignconfig("articlemodelc",$article_model);
        $cateList = Collection(\app\admin\model\Category::select())->toArray();
        $cateStr = format_option(children($cateList),$row['category']);
        $this->view->assign("catestr",$cateStr);
        return $this->view->fetch();
    }
    
    /*
     * 初审
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
                    $this->success(__('Check Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $extendTable = \app\admin\model\CategoryModule::where(['code'=>$row['article_model']])->value('table');
        $extentRow = Db::name("article_".$extendTable)->where(['id'=>$ids])->find();      
        $row = array_merge($row->toArray(),$extentRow);
        $row['images'] = $row['images'] ? explode(',',$row['images']) : [];
        $row['add_time'] = format_time($row['add_time']);
        $row['areaName'] = \app\admin\model\Area::where(['id'=>$row['area_id']])->value("mergename");
        $row['adminName'] = \app\admin\model\Admin::where(['id'=>$row['admin_id']])->value("nickname");
        $row['categoryName'] = \app\admin\model\Category::where(['id'=>$row['category']])->value("title");
        if($row['tpe'] == 4){
            $row['video_path'] = cfg("cst_video_public_url")."/media/video/".\app\common\model\Video::where(['id'=>$row['video_id']])->value("third_id").".mp4";
        }elseif($row['tpe'] == 5){
            $video_path = '';
            $liveInfo = \app\common\model\Camera::get($row['video_id']);
            if($liveInfo){
                $liveInfo = $liveInfo->toArray();
                $url = cfg("cst_live_url")."act=getLiveInfo";
                $para=[
                    "client"    => "cms",
                    "id"        => encrypt($liveInfo['third_id']),
                ];
                $live = json_decode(myhttp($url,$para),true);
                $video_path = str_replace(cfg("cst_live_private_url"), cfg("cst_live_public_url"), $live['url_hls']);
            }
            $row['video_path'] = $video_path;
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
        
    }
    /*
     * 取消初审
     */
    public function cancelcheck($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $params = [
            'id'                => $ids,
            'is_check'          => 0,
            'is_final_check'    => 0,
            'is_publish'        => 0
        ];
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
            $this->success(__('Cancel Check Success'));
        } else {
            $this->error(__('No rows were updated'));
        }
    }
    
    /*
     * 终审
     */
    public function finalcheck($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['is_final_check'] == 1){
            $this->error(__('Has Check'));
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['final_check_time'] = time();
                $params['final_check_admin'] = $this->auth->id;
                if($params['is_final_check'] == 1){
                    unset($params['final_check_case']);
                }elseif($params['is_final_check'] == 2){
                    if(!$params['final_check_case']){
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
                    if(cfg("no_publish")){
                    $this->model->update(['is_publish'=>1,'publish_time'=>time()],['id'=>$ids]);
                    }
                    $this->success(__('Final Check Success'));
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $extendTable = \app\admin\model\CategoryModule::where(['code'=>$row['article_model']])->value('table');
        $extentRow = Db::name("article_".$extendTable)->where(['id'=>$ids])->find();
        $row = array_merge($row->toArray(),$extentRow);
        $row['images'] = $row['images'] ? explode(',',$row['images']) : [];
        $row['add_time'] = format_time($row['add_time']);
        $row['areaName'] = \app\admin\model\Area::where(['id'=>$row['area_id']])->value("mergename");
        $row['adminName'] = \app\admin\model\Admin::where(['id'=>$row['admin_id']])->value("nickname");
        $row['categoryName'] = \app\admin\model\Category::where(['id'=>$row['category']])->value("title");
        if($row['tpe'] == 4){
            $row['video_path'] = cfg("cst_video_public_url")."/media/video/".\app\common\model\Video::where(['id'=>$row['video_id']])->value("third_id").".mp4";
        }elseif($row['tpe'] == 5){
            $video_path = '';
            $liveInfo = \app\common\model\Camera::get($row['video_id']);
            if($liveInfo){
                $liveInfo = $liveInfo->toArray();
                $url = cfg("cst_live_url")."act=getLiveInfo";
                $para=[
                    "client"    => "cms",
                    "id"        => encrypt($liveInfo['third_id']),
                ];
                $live = json_decode(myhttp($url,$para),true);
                $video_path = str_replace(cfg("cst_live_private_url"), cfg("cst_live_public_url"), $live['url_hls']);
            }
            $row['video_path'] = $video_path;
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    
    }
    /*
     * 取消终审
     */
    public function cancelfinalcheck($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $params = [
            'id'                => $ids,
            'is_final_check'    => 0,
            'is_publish'        => 0
        ];
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
            $this->success(__('Cancel Final Check Success'));
        } else {
            $this->error(__('No rows were updated'));
        }
    }
    
    
    /*
     * 发布
     */
    public function publish($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        if($row['is_check'] != 1 || $row['is_final_check'] != 1){
            $this->error(__('No Check No Publish'));
        }
        $params = [
            'id'                => $ids,
            'is_publish'        => 1
        ];
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
            $this->success(__('Publish Success'));
        } else {
            $this->error(__('No rows were updated'));
        }
    }
    /*
     * 取消发布
     */
    public function cancelpublish($ids = null){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $params = [
            'id'                => $ids,
            'is_publish'        => 0
        ];
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
            $this->success(__('Cancel Publish'));
        } else {
            $this->error(__('No rows were updated'));
        }
    }
}
