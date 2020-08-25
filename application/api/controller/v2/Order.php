<?php
namespace app\api\controller\v2;

use app\admin\model\WorkOrder;
use app\api\controller\v2\ApiCommon;
/**
 * 点单接口
 */
class Order extends ApiCommon
{
    protected $noNeedLogin = ['orderList','detail'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }


    /**
     * 点单详情
     * @param int $order_id 点单ID
     */
    public function detail(){
        $order_id       = intval(input("order_id"));
        $where = ['cur_period'=>['>',0]];
        $order = \app\common\model\Order::get($order_id);
        $orderLog = [];
        $curPeriod = [];
        if(!$order){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }

        $order = $order->toArray();
        $curPeriod = \app\common\model\OrderPeriod::where(['id'=>$order['cur_period']])->find()->toArray();
        $orderLog = \app\common\model\OrderPeriod::where(['order_id'=>$order['id']])->select();
        $list = [];
        foreach($orderLog as $k=>$v){
            $list[$k] = [
                'start_time'=> format_time($v['start_time'],"Y-m-d"),
                'end_time'  => format_time($v['end_time'],"Y-m-d"),
                'counts'    => $v['counts'],
            ];
        }
        ok([
            'order' => [
                'id'        => $order['id'],
                'title'     => $order['title'],
                'img'       => $order['img'],
                'content'   => $order['content'],
                'team'      => $order['team'],
                'contracter'=> $order['contracter'],
                'mobile'    => $order['mobile'],
                'tpe'       => $order['tpe'],
                'counts'    => $order['counts'],
                'tpe_name'       => \app\common\model\OrderType::where(['id'=>$order['tpe']])->value("name"),
                'cur_period'=> $order['cur_period'],
                'start_time'=> $curPeriod ? format_time($curPeriod['start_time'],"Y-m-d") : '',
                'end_time'  => $curPeriod ? format_time($curPeriod['end_time'],"Y-m-d") : '',
            ],
            'orderlog' => $list
        ]);
    }
    /**
     * 点单主题列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     */
    public function orderList(){
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","id desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = ['cur_period'=>['>',0]];
        $orderList = \app\common\model\Order::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        foreach($orderList as $k => $v){
            $list = \app\common\model\OrderPeriod::where(['id'=>$v['cur_period']])->find();
            $list['activity_time'] = format_time($list['start_time'],"Y-m-d")."-".format_time($list['end_time'],"Y-m-d");
            $orderList[$k]['periods'] = $list;
	    $orderList[$k]['activity_time']= $list['activity_time'];
            $orderList[$k]['tpe_name'] = \app\common\model\OrderType::where(['id'=>$v['tpe']])->value('name');
        }
        $total = \app\common\model\Order::where($where)->count();
        ok([
            "items"     => $orderList,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

    /**
     * 点单
     * @param int $period_id 菜单ID
     * @param int $area_id 区域ID
     * @param string $content 点单内容
     * @param string $token 用户TOKEN
     */

    public function index(){
        $period_id      = intval(input("period_id"));
        $area_id        = intval(input("area_id"));
        $content        = trim(input("content"));
        if(!$period_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $periodInfo = \app\common\model\OrderPeriod::where(['id'=>$period_id])->find();
        if(!$periodInfo){
            $lang = lang("not_activity");
            err(200,"not_activity",$lang['code'],$lang['message']);
        }
        if($periodInfo['status'] == 2){
            $lang = lang("order_has_finish");
            err(200,"order_has_finish",$lang['code'],$lang['message']);
        }
        $orderInfo = \app\common\model\Order::where(['id'=>$periodInfo['order_id']])->find();
        if(!$orderInfo){
            $lang = lang("not_activity");
            err(200,"not_activity",$lang['code'],$lang['message']);
        }
        $orderLogInfo = \app\common\model\OrderLog::where(['order_id' => $periodInfo['order_id'],'period_id'=> $period_id, 'uid' => $this->uid])->find();
        if($orderLogInfo){
            $lang = lang("has_order");
            err(200,"has_order",$lang['code'],$lang['message']);
        }
        $params = [
            'order_id'      => $periodInfo['order_id'],
            'period_id'     => $period_id,
            'uid'           => $this->uid,
            'addtime'       => time(),
            'content'       => $content,
            'area_id'       => $area_id ? $area_id : $this->auth->area_id,
            'tpe'           => $orderInfo['tpe']
        ];
        $ret = \app\common\model\OrderLog::insert($params);
        if(!$ret){
            $lang = lang("order_fail");
            err(200,"order_fail",$lang['code'],$lang['message']);
        }
        $work = [
            'resource_id' => $ret,
            'title'       => $orderInfo['title'],
            'content'     => $content,
            'area_id'     => $area_id,
            'add_time'    => time(),
            'tpe'         => 4
        ];
        WorkOrder::create($work);
        $total = \app\common\model\OrderLog::where(['order_id'=>$periodInfo['order_id']])->count();
        \app\common\model\Order::update(['counts'=>$total],['id'=>$periodInfo['order_id']]);
        $totalPeriod = \app\common\model\OrderLog::where(['order_id'=>$periodInfo['order_id'],'period_id'=>$period_id])->count();
        \app\common\model\OrderPeriod::update(['counts'=>$totalPeriod],['id'=>$period_id]);
        $integralInfo = [
            'event_code'        => 'OrderSheet',
            'uid'               => $this->uid,
            'note'              => $orderInfo['title'],
            'area_id'           => 0,
        ];
        ok(['total'=>$total]);
    }
}
