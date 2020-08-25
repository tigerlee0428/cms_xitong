<?php
namespace app\api\controller\v2;


use app\common\model\Area as Area_mod;
use app\api\controller\v2\MobileOffice;
/**
 * 操作员接口
 */
class Admin extends MobileOffice
{

    protected $model = null;


    protected function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Admin();

    }


    /**
     * 管理员列表
     * @param int $cid 栏目分类ID
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param int $orders 排序
     * @param string $device 设备标识
     * @param int $area_id 区域ID
     * @param int $is_show_index 是否首页显示
     * @param int $tpe 文章类型
     * @param int $keyword 关键词
     * @return array
     *
     */

    public function adminList()
    {
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "id desc"));
        $page = max($page, 1);
        $keyword = trim(input("keyword"));
        $area_id = intval(input("area_id", $this->area_id));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        if ($keyword) {
            $where['title'] = ['like', "%" . $keyword . "%"];
        }
        $where['area_id'] = $area_id;
        $adminList = \app\common\model\Admin::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Admin::where($where)->count();
        $list = [];
        foreach ($adminList as $k => $v) {
            $list[$k] = [
                'id' => $v['id'],
                'username' => $v['nickname'],
            ];
        }
        ok([
            "items" => $list,
            "page" => $page,
            "pagesize" => $pagesize,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
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
        $where = ['pid'=>$this->area_id];
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
}
