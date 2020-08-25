<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\model\Area as Area_mod;
use fast\Tree;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 地区管理
 *
 * @icon fa fa-circle-o
 */
class Area extends Backend
{

    /**
     * Area模型对象
     * @var \app\admin\model\Area
     */
    protected $model = null;
    protected $noNeedRight = ['selectpage','select'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Area;
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 选择
     */
    public function select()
    {

        if ($this->request->isAjax()) {
            return $this->areaList();
        }
        return $this->view->fetch();
    }

    public function index(){
        if ($this->request->isAjax()) {
            $areaList = collection(Area_mod::where(['id'=>["in",\app\common\model\Cfg::childArea($this->auth->area_id)]])->field("id,pid,name,level,master,contacter")->select())->toArray();
            foreach($areaList as $k => $v){
                //$areaList[$k]['article_count'] = \app\admin\model\Article::where(['area_id'=>['in',\app\common\model\Cfg::childArea($v['id'])]])->count();
                $areaList[$k]['article_count'] = \app\admin\model\Article::where(['area_id'=>$v['id']])->count();
                //$areaList[$k]['activity_count'] = \app\admin\model\Activity::where(['area_id'=>['in',\app\common\model\Cfg::childArea($v['id'])]])->count();
                $areaList[$k]['activity_count'] = \app\admin\model\Activity::where(['area_id'=>$v['id']])->count();
            }
            //print_r($areaList);exit;
            Tree::instance()->init($areaList);
            $areaResult = Tree::instance()->getTreeList(Tree::instance()->getTreeArray($this->auth->area_id));
            $curArea = Area_mod::where(['id'=>$this->auth->area_id])->field("id,pid,name,level")->find();
            $curArea['pid'] = 0;
            $areaResult[] = $curArea;
            return json($areaResult);
        }
        return $this->view->fetch();
    }
    public function selectpage(){
        //设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);

        //搜索关键词,客户端输入以空格分开,这里接收为数组
        $word = (array)$this->request->request("q_word/a");
        //当前页
        $page = $this->request->request("pageNumber");
        //分页大小
        $pagesize = $this->request->request("pageSize");
        //搜索条件
        $andor = $this->request->request("andOr", "and", "strtoupper");
        //排序方式
        $orderby = (array)$this->request->request("orderBy/a");
        //显示的字段
        $field = $this->request->request("showField");
        //主键
        $primarykey = $this->request->request("keyField");
        //主键值
        $primaryvalue = $this->request->request("keyValue");
        //搜索字段
        $searchfield = (array)$this->request->request("searchField/a");
        //自定义搜索条件
        $custom = (array)$this->request->request("custom/a");
        //是否返回树形结构
        $istree = $this->request->request("isTree", 0);
        $ishtml = $this->request->request("isHtml", 0);
        if ($istree) {
            $word = [];
            $pagesize = 99999;
        }
        $order = [];
        foreach ($orderby as $k => $v) {
            $order[$v[0]] = $v[1];
        }
        $field = $field ? $field : 'name';

        //如果有primaryvalue,说明当前是初始化传值
        if ($primaryvalue !== null) {
            $where = [$primarykey => ['in', $primaryvalue]];
        } else {
            $where = function ($query) use ($word, $andor, $field, $searchfield, $custom) {
                $logic = $andor == 'AND' ? '&' : '|';
                $searchfield = is_array($searchfield) ? implode($logic, $searchfield) : $searchfield;
                foreach ($word as $k => $v) {
                    $query->where(str_replace(',', $logic, $searchfield), "like", "%{$v}%");
                }
                if($this->auth->area_id){
                    $custom['id'] = ['in',\app\common\model\Cfg::childArea($this->auth->area_id)];
                }
                if ($custom && is_array($custom)) {
                    foreach ($custom as $k => $v) {
                        if (is_array($v) && 2 == count($v)) {
                            $query->where($k, trim($v[0]), $v[1]);
                        } else {
                            $query->where($k, '=', $v);
                        }
                    }
                }
            };
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        $list = [];
        $total = $this->model->where($where)->count();
        if ($total > 0) {
            if (is_array($adminIds)) {
                $this->model->where($this->dataLimitField, 'in', $adminIds);
            }
            $datalist = $this->model->where($where)
                ->order($order)
                ->page($page, $pagesize)
                ->field($this->selectpageFields)
                ->select();

            $baseAreaId = \app\admin\model\Admin::where(['id'=>1])->value("area_id");
            foreach ($datalist as $index => $item) {
                unset($item['password'], $item['salt']);
                $list[] = [
                    $primarykey => isset($item[$primarykey]) ? $item[$primarykey] : '',
                    $field      => isset($item[$field]) ? $item[$field] : '',
                    'pid'       => $baseAreaId == $item['id'] ? 0 : (isset($item['pid']) ? $item['pid'] : 0)
                ];
            }
            if ($istree) {
                $tree = Tree::instance();
                $tree->init(collection($list)->toArray(), 'pid');
                $list = $tree->getTreeList($tree->getTreeArray(0), $field);
                if (!$ishtml) {
                    foreach ($list as &$item) {
                        $item = str_replace('&nbsp;', ' ', $item);
                    }
                    unset($item);
                }
            }
        }
        //这里一定要返回有list这个字段,total是可选的,如果total<=list的数量,则会隐藏分页按钮
        return json(['list' => $list, 'total' => $total]);
    }
    public function areaList(){

        if ($this->request->isAjax()) {
            $areaList = collection(Area_mod::where(['pid'=>$this->auth->area_id])->field("id,pid,name,level")->select())->toArray();
            return json($areaList);
        }
        return $this->view->fetch('index');
    }

    /**
     * 添加
     */
    public function add()
    {
        $pid = intval(input("pid"));
        $level = intval(input("level"));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");

            if ($params) {
                $params = $this->preExcludeFields($params);
                $params['is_point'] = isset($params['is_point']) ? $params['is_point'] : 0;
                $params['is_map_show'] = isset($params['is_map_show']) ? $params['is_map_show'] : 0;
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if(isset($params['lat']) && isset($params['lng']) && $params['lat'] && $params['lng']){

                }else{
                    if($params['mergename']){
                        list($x,$y) = jwd($params['mergename']);
                        $params['lng'] = $x;
                        $params['lat'] = $y;
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
                $params['is_point'] = isset($params['is_point']) ? $params['is_point'] : 0;
                $params['is_map_show'] = isset($params['is_map_show']) ? $params['is_map_show'] : 0;

                $result = false;
                if(isset($params['lat']) && isset($params['lng']) && $params['lat'] && $params['lng']){

                }else{
                    if($params['mergename']){
                        list($x,$y) = jwd($params['mergename']);
                        $params['lng'] = $x;
                        $params['lat'] = $y;
                    }
                }
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
}
