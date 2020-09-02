<?php
namespace app\api\controller\v2;

use app\admin\model\WorkOrder;
use app\api\controller\v2\ApiCommon;
use app\common\model\Cate as Cate_mod;
use app\common\model\Cate;

/**
 * 实践点单以及个性化点单接口
 */
class Orders extends ApiCommon
{
    protected $noNeedLogin = ['ordersList','index','childCate','cateInfo'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Orders;

    }
    /**
     * 点单
     * @param string $title 点单标题
     * @param string $username 联系人
     * @param string $content 事件内容
     * @param string $mobile 手机号
     * @param array $img 图片
     * @param int $tpe 事件类型
     * @param int $is_open 是否公开
     * @param string $token 用户TOKEN
     * @return array
     */
    public function post()
    {
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $title      = trim(input("title"));
        $username   = trim(input("username"));
        $mobile     = trim(input("mobile"));
        $tpe        = intval(input("tpe"));
        $content    = trim(input("content"));
        $address    = trim(input("address"));
        $area_id    = intval(input('area_id'));
        $cate       = intval(input('cate'));
        if(!$content || !$title){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $data = [
            'uid'           => $this->uid,
            'title'         => $title,
            'name'      => $username ? $username : $this->auth->nickname,
            'mobile'        => $this->auth->mobile,
            'tpe'           => $tpe,
            'content'       => $content,
            'add_time'       => time(),
            'area_id'       => $area_id,
            'address'       => $address,
            'cate'          => $cate
        ];
        $orders =  \app\admin\model\Orders::Create($data);
        if(!$orders){
            $lang = lang("order_fail");
            err(200,"order_fail",$lang['code'],$lang['message']);
        }
        if($tpe == 1){

            $work = [
                'title'         => $title,
                'content'       => $content,
                'address'       => $address,
                'area_id'       => $area_id,
                'mobile'        => $this->auth->mobile,
                'tpe'           => 5,
                'resource_id'   => $orders->id,
                'username'      => $this->auth->realname ? $this->auth->realname : $this->auth->nickname,
                'add_time'      => time()
            ];
            WorkOrder::create($work);
            \app\common\model\Orders::update(['status'=> 1],['id'=>$orders->id]);
        }
        ok();
    }
    /**
     * 点单列表
     * @param int $tpe    1实践菜单2个性化点单
     * @param int $is_open      是否公开
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @return array
     */
    public function OrdersList(){
        $tpe        = intval(input("tpe", 0));
        $cate        = intval(input("cate", 0));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","id desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [];
          if($cate){
            $where['cate'] =$cate;
        }
        if($tpe){
            $where['tpe'] =$tpe;
        }
        $order = [];
        $ordersList = \app\common\model\Orders::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Orders::where($where)->count();
        foreach($ordersList as $k => $v)
        {

            $order[$k] = [
                'id'            => $v['id'],
                'title'         => $v['title'],
                'username'      => $v['name'],
                'content'       => $v['content'],
                'mobile'        => $v['mobile'],
                'address'       => $v['address'],
                'tpe'           => $v['tpe'],
                'status'        => $v['status'],
                'add_time'       => format_time_moment($v['add_time'],"Y-m-d"),
                'head_img'      => \app\common\model\User::where(['id'=>$v['uid']])->value('avatar'),
                'cate_title'    => Cate::where(['id'=>$v['cate']])->value('title'),
                'area_title'    => \app\common\model\Area::where(['id'=>$v['area_id']])->value('name')
            ];
        }
        ok([
            "items"     => $order,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }
    /**
     * 我的点单列表
     * @param int $tpe    事件类型
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     * @return array
     */
    public function myOrdersList(){

        $tpe        = intval(input("tpe"));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'uid'       => $this->uid,
        ];
        if($tpe){
            $where['tpe'] = $tpe;
        }
        $order = [];
        $ordersList = \app\common\model\Orders::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Orders::where($where)->count();
        foreach($ordersList as $k => $v){
            $order[$k] = [
                'id'            => $v['id'],
                'title'         => $v['title'],
                'username'      => $v['name'],
                'content'       => $v['content'],
                'mobile'        => $v['mobile'],
                'address'       => $v['address'],
                'tpe'           => $v['tpe'],
                'status'        => $v['status'],
                'add_time'      => format_time_moment($v['add_time'],"Y-m-d"),
                'head_img'      => \app\common\model\User::where(['id'=>$v['uid']])->value('avatar'),
                'cate_title'    => Cate::where(['id'=>$v['cate']])->value('title'),
                'area_title'    => \app\common\model\Area::where(['id'=>$v['area_id']])->value('name')
            ];
        }
        ok([
            "items"     => $order,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

    /**
     * 点单详情
     * @param int $id    事件ID
     * @return array
     */
    public function index(){
        $id        = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $ordersInfo = \app\common\model\Orders::get($id);
        $orders=[];
        if($ordersInfo){
            $ordersInfo = $ordersInfo->toArray();
            $orders = [
                'title'         => $ordersInfo['title'],
                'username'      => $ordersInfo['name'],
                'content'       => $ordersInfo['content'],
                'mobile'        => $ordersInfo['mobile'],
                'address'       => $ordersInfo['address'],
                'tpe'           => $ordersInfo['tpe'],
                'status'        => $ordersInfo['status'],
                'add_time'      => format_time($ordersInfo['add_time'],"Y-m-d"),
                'cate_title'    => Cate::where(['id'=>$ordersInfo['cate']])->value('title'),
                'area_title'    => \app\common\model\Area::where(['id'=>$ordersInfo['area_id']])->value('name')
            ];
        }
        ok($orders);
    }
    /**
     * 获取子栏目
     * @param int $id 栏目ID
     *
     */
    public function childCate()
    {
        $id = intval(input("id"));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","addtime desc"));
        $cateList = Cate_mod::where(['pid'=>$id,'status'=>1])->page($page)->limit($pagesize)->order("weight desc")->select();
        $total = \app\common\model\Cate::where(['pid'=>$id,'status'=>1])->count();
        $list = [];
        if($cateList){
            foreach(collection($cateList)->toArray() as $k => $v){
                $list[$k] = [
                    'id'            => $v['id'],
                    'title'         => $v['title'],
                    'add_time'      => format_time($v['updatetime']),
                    'order_num' => \app\common\model\Orders::where(['cate'=>$id])->count()
                ];
            }
        }
        ok([
            "items"     => $list,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

    /**
     * 获取栏目详情
     * @param int $id 栏目ID
     *
     */
    public function cateInfo(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $cateInfo = Cate_mod::get($id);
        if(!$cateInfo){
            $lang = lang("not_category");
            err(200,"not_category",$lang['code'],$lang['message']);
        }
        $cateInfo = $cateInfo->toArray();
        ok([
            'id'        => $cateInfo['id'],
            'pid'       => $cateInfo['pid'],
            'title'     => $cateInfo['title'],
            'team'      => $cateInfo['team'],
            'mobile'    => $cateInfo['mobile'],
            'contracter'=> $cateInfo['contracter'],
            'content'   => $cateInfo['content'],
            'order_num' => \app\common\model\Orders::where(['cate'=>$id])->count()
        ]);
    }

    /**
     * 面包屑
     * @param int $id 栏目ID
     *
     */
    public function bread(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $cateInfo = Category_mod::get($id);
        if(!$cateInfo){
            $lang = lang("not_category");
            err(200,"not_category",$lang['code'],$lang['message']);
        }
        $cateInfo = $cateInfo->toArray();
        $title = $this->parentsName($cateInfo['pid']) ."&nbsp;&nbsp;>&nbsp;&nbsp;". $cateInfo['title'];
        ok(['title'=>$title]);
    }

    private function parentsName($id){
        $title = '';
        $cate = Category_mod::get($id);
        if($cate){
            $cate = $cate->toArray();
            $title .= $cate['title'];
            if($cate['pid'] != 0){
                return $this->parentsName($cate['pid'])."&nbsp;&nbsp;>&nbsp;&nbsp;".$title;
            }
        }
        return $title;
    }


}
