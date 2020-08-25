<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use app\common\model\Area as Area_mod;
use think\Collection;
/**
 * 区域接口
 */
class Area extends ApiCommon
{
    protected $noNeedLogin = ['areaList','myareaList','childArea','areaInfo','areaAll','childrenArea'];
    protected $noNeedRight = '*';
    protected $model = null;
    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\common\model\Area;
    }

    /**
     * 地区列表
     * @param int $id      地区ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @return array
     *
     */
    public function areaList(){
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize",10));
        $orders = trim(input("orders","id desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $id = intval(input("id"));
        $where = [];
        if($id){
            $where = ['pid'=>$id];
        }else{
            $where = ['pid' => \app\common\model\Admin::where(['id'=>1])->value("area_id")];
        }
        $areaList = Area_mod::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = Area_mod::where($where)->count();
        $list = [];
        foreach($areaList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'title'     => $v['name'],
                'mergename' => $v['mergename'],
                'lng'       => $v['lng'],
                'lat'       => $v['lat'],
		        'img'	    => $v['img'],

            ];
        }
        ok([
            "items"     => $list,
            "page"      => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

    /**
     * 地区列表
     * @param int $id      地区ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @return array
     *
     */
    public function myareaList(){
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize",10));
        $orders = trim(input("orders","id desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $id = intval(input("id"));
         $where = ['pid'=>$this->auth->area_id];
        $areaList = Area_mod::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = Area_mod::where($where)->count();
        $list = [];
        foreach($areaList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'title'     => $v['name'],
                'mergename' => $v['mergename'],

            ];
        }
        ok([
            "items"     => $list,
            "page"      => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    /**
     * 获取子区域
     * @param int $id 区域ID
     *
     */
    public function childArea()
    {
        $id = intval(input("id"));
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize",10));
        $orders = trim(input("orders","id desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $areaList = Area_mod::where(['id'=>['in',\app\common\model\Cfg::childArea($id)]])->page($page)->limit($pagesize)->order($orders)->select();
        $total = Area_mod::where(['id'=>['in',\app\common\model\Cfg::childArea($id)]])->count();
        $list = [];
        if($areaList){
            foreach(Collection($areaList)->toArray() as $k => $v){
                $list[$k] = [
                    'id'        => $v['id'],
                    'title'     => $v['name'],
                    'mergename' => $v['mergename'],
                    'lng'       => $v['lng'],
                    'lat'       => $v['lat'],
                ];
            }
        }
        ok([
            "items"     => $list,
            "page"      => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    /**
     * 获取区域详情
     * @param int $id 区域ID
     *
     */

    /**
     * 获取子区域
     * @param int $id 区域ID
     *
     */
    public function childrenArea()
    {
        $id = intval(input("id"));
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize",10));
        $orders = trim(input("orders","id desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        if(!$id){
            $id  =  \app\common\model\Admin::where(['id'=>1])->value("area_id");
        }
        $areaList = Area_mod::where(['pid'=>$id])->order($orders)->select();
        $total = Area_mod::where(['pid'=>$id])->count();
        $list = [];
        if($areaList){
            foreach(Collection($areaList)->toArray() as $k => $v){
                $list[$k] = [
                    'id'        => $v['id'],
                    'title'     => $v['name'],
                    'mergename' => $v['mergename'],
                    'lng'       => $v['lng'],
                    'lat'       => $v['lat'],
                ];
            }
        }
        ok([
            "items"     => $list,
            "page"      => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

    public function areaInfo(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $areaInfo = $this->model->get($id)->toArray();
        if(!$areaInfo){
            $lang = lang("not_area");
            err(200,"not_area",$lang['code'],$lang['message']);
        }
        ok($areaInfo);
    }


    /**
     * 设置用户所属区域
     * @param int $area_id 区域ID
     *
     */
    public function setArea(){
        $area_id = intval(input('area_id'));
        $inf = \app\common\model\User::update(['area_id'=>$area_id],['id'=>$this->auth->id]);
        ok();
    }


    /**
     * 地区列表
     * @param int $id      地区ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @return array
     *
     */
    public function areaAll(){
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize",10));
        $orders = trim(input("orders","id desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $id = intval(input("id"));
        $level = intval(input("level",-1));

        $where = [];
        $center_id =  \app\common\model\Admin::where(['id'=>1])->value("area_id");
        $where['id'] =['in',\app\common\model\Cfg::childArea($center_id)];
            if($level !=-1){
                $where['level'] = $level;
            }
        $areaList = Area_mod::where($where)->order($orders)->select();
        $total = Area_mod::where($where)->count();
        $list = [];
        foreach($areaList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'title'     => $v['name'],
                'mergename' => $v['mergename'],
                'lng'       => $v['lng'],
                'lat'       => $v['lat'],
                'img'	    => $v['img'],
                'level'     => $v['level']
            ];
        }
        ok([
            "items"     => $list,
            "page"      => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
}
