<?php


namespace app\api\controller\v2;
use app\api\controller\v2\ApiCommon;
use app\common\model\Goods as Goods_mod;
/**
 * 积分商品接口
 */

class Goods extends ApiCommon
{
    protected $noNeedLogin = ['index','goodsList'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    /**
     * 兑换商品详情
     * @param int $id 商品ID
     * @return array
     */
    public function index()
    {
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $goodsinfo = Goods_mod::get($id);
        if(!$goodsinfo){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $goodsinfo->toArray();
        ok([
            'id'        => $goodsinfo['id'],
            'name'      => $goodsinfo['name'],
            'integral'  => $goodsinfo['integral'],
            'num'       => $goodsinfo['num'],
            'img'       => $goodsinfo['img'],
            'address'   => $goodsinfo['address']

        ]);

    }


    /**
     * 商品列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @return array
     *
     */
    public function goodsList(){
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize",10));
        $orders = trim(input("orders","record_num desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = ['is_del'=>0];
        $goodsList = Goods_mod::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = Goods_mod::where($where)->count();
        $list = [];
        foreach($goodsList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'name'      => $v['name'],
                'integral'  => $v['integral'],
                'num'       => $v['num'],
                'img'       => $v['img'],
                'record_num'=> $v['record_num'],
                'address'   => $v['address']
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
