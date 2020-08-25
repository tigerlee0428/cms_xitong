<?php


namespace app\api\controller\v2;
/**
 * 积分兑换接口
 */
use app\api\controller\v2\ApiCommon;
use app\common\model\Goods;
use app\common\model\Record as Record_mod;


class Record extends ApiCommon
{
    protected $noNeedLogin = ['recordList'];
    protected $noNeedRight = '*';
    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 兑换记录
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param int sts        是否领取，1未领取，2领取
     */
    
    public function recordList(){
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize"));
        $orders = trim(input("orders","add_time desc"));
        $page = max(1,$page);
        $pagesize = $pagesize ? $pagesize : 10;
        $sts = intval(input("sts"));
        $where = [];
        if($sts){
            $where['sts'] = $sts;
        }
        $recordLog = Record_mod::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = Record_mod::where($where)->count();
        $list = [];
        foreach($recordLog as $k => $v){
            $list[$k]['sts'] = $v['sts'];
            $list[$k]['name'] = $v['name'];
            $list[$k]['integral'] = $v['integral'];
            $list[$k]['goodsimg'] = Goods::where(['id'=>$v['goodsid']])->value("img");
            $list[$k]['goodsname'] = Goods::where(['id'=>$v['goodsid']])->value("name");
            $list[$k]['format_add_time'] = format_time($v['add_time']);
        }
    
        ok([
            "items" => $list,
            "page"  => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    
    }
    
    /**
     * 积分兑换
     * @param int $goods_id 商品ID
     * @param string $token 用户TOKEN
     */
    public function index()
    {
        $goods_id = input("goods_id");
        if(!$goods_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $goodsInfo = Goods::get($goods_id);
        if(!$goodsInfo){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $jf = 0;
        $goodsInfo = $goodsInfo->toArray();
        $jf = \app\common\model\User::where(['id'=>$this->uid])->value("score");
        if($jf < $goodsInfo['integral']){
            $lang = lang('less_scores');
            err(200,"less_scores",$lang['code'],$lang['message']);
        }

        $userInfo = \app\common\model\User::get($this->uid)->toArray();
        $data = [
            'integral'  => $goodsInfo['integral'],
            'num'       => 1,
            'uid'       => $this->uid,
            'goodsid'   => $goods_id,
            'add_time'  => time(),
            'sts'       => 1,
            'mobile'    => $userInfo['mobile'],
            'name'      => $userInfo['nickname'],
            'goods_name'=> $goodsInfo['name']
        ];  
        $result = Record_mod::create($data);
        if(!$result){
            $lang = lang("exchange_fail");
            err(200,"exchange_fail",$lang['code'],$lang['message']);
        }
        $counts = Record_mod::where(['goodsid'=>$goods_id])->count();
        Goods::update(['record_num'=>$counts],['id'=>$goods_id]);
        //购物
        $buygoods = [
            'event_code'        => 'shop',
            'uid'               => $this->uid,
            'scores'            => 0 - $goodsInfo['integral'],
            'area_id'           => session("uarea_id"),
            'note'              => '兑换'.$goodsInfo['name']
        ];
        \think\Hook::listen("integral",$buygoods);
        //初次兑换
        $integralInfo = [
            'event_code'        => 'RecordGoods',
            'uid'               => $this->uid,
            'area_id'           => session("uarea_id"),
            'note'              => '初次兑换'
        ];
        \think\Hook::listen("integral",$integralInfo);
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$this->uid])->value("openid"),
            'temp_id'   => 1,
            'msg_data'  => [
                'first'     => '您使用：'.$goodsInfo['integral'].'积分兑换了礼品：'.$goodsInfo['name'].'快到个人中心查看吧！',
                'keyword1'  => '您使用：'.$goodsInfo['integral'].'积分兑换了礼品：'.$goodsInfo['name'].'快到个人中心查看吧！',
                'keyword2'  => '',
                'remark'    => '',
            ],

            'sys_msg'   => [
                'title'     => '您使用：'.$goodsInfo['integral'].'积分兑换了礼品：'.$goodsInfo['name'].'快到个人中心查看吧！',
                'breif'     => '您使用：'.$goodsInfo['integral'].'积分兑换了礼品：'.$goodsInfo['name'].'快到个人中心查看吧！',
                'uid'       => $this->uid,
                'is_read'   => 0,
                'add_time'  => time(),
                'tpe'       => 0,
                //'url'       => config("wx_domain") ."/#/volunteerDetails/",
            ]
        ]);
        ok([],"兑换成功！");
    }
    
    /**
     * 我的兑换记录
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param string $token 用户TOKEN
     */
    
    public function myRecordList(){
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize"));
        $orders = trim(input("orders","add_time desc"));
        $page = max(1,$page);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = ['uid'=>$this->uid];
        $recordLog = Record_mod::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = Record_mod::where($where)->count();
        $list = [];
        foreach($recordLog as $k => $v){
            $list[$k]['sts'] = $v['sts'];
            $list[$k]['integral'] = $v['integral'];
            $list[$k]['goodsimg'] = Goods::where(['id'=>$v['goodsid']])->value("img");
            $list[$k]['goodsname'] = Goods::where(['id'=>$v['goodsid']])->value("name");
            $list[$k]['format_add_time'] = format_time($v['add_time']);
        }
        
        ok([
            "items" => $list,
            "page"  => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
        
    }
}