<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use app\common\model\Event as Event_mod;
/**
 * 事件接口
 */
class Event extends ApiCommon
{
    protected $noNeedLogin = ['eventList','index'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }

    /**
     * 发布事件
     * @param string $title 事件标题
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
        $img        = input("img/a");
        $tpe        = intval(input("tpe"));
        $content    = trim(input("content"));
        $is_open    = intval(input("is_open",1));
        if(!$content || !$title){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }

        $data = [
            'uid'           => $this->uid,
            'title'         => $title,
            'username'      => $username ? $username : $this->auth->nickname,
            'mobile'        => $mobile,
            'img'           => is_array($img) ? json_encode($img) : '',
            'tpe'           => $tpe,
            'is_open'       => $tpe == 1 ? 0 : $is_open,
            'content'       => $content,
            'addtime'       => time(),
            'area_id'       => $this->auth->area_id,
        ];
        $inf = Event_mod::create($data);
        if(!$inf){
            $lang = lang("post_event_err");
            err(200,"post_event_err",$lang['code'],$lang['message']);
        }
        ok();
    }
    /**
     * 事件列表
     * @param int $tpe    事件类型
     * @param int $is_open      是否公开
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @return array
     */
    public function eventList(){
        $tpe        = intval(input("tpe", 0));
        $page       = intval(input("page",1));
        $is_open    = intval(input('is_open',-1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","addtime desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;

        $where = [
         'is_deal' =>1
        ];
        if($tpe){
            $where['tpe'] = $tpe;
        }
        if($is_open !=-1){
            $where['is_open'] = $is_open;
        }

        $event = [];
        $eventList = \app\common\model\Event::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Event::where($where)->count();
        foreach($eventList as $k => $v)
        {
            $imgsArr = [];
        	$imgs = json_decode($v['img'],true);
        	if(is_array($imgs)){
        	    foreach($imgs as $val){
        	        $imgsArr[] = thumb_img($val);
        	    }
        	}
            $event[$k] = [
                'id'            => $v['id'],
                'title'         => $v['title'],
                'username'      => $v['username'],
                'content'       => $v['content'],
                'deal_content'  => $v['deal_content'],
                'img'           => is_array(json_decode($v['img'],true)) ? thumb_img(current(json_decode($v['img'],true))) : '',
                'imgs'          => $imgsArr,
                'tpe'           => $v['tpe'],
                'addtime'       => format_time_moment($v['addtime'],"Y-m-d"),
                'dealtime'      => format_time_moment($v['dealtime']),
                'likes'         => $v['likes'],
                'head_img'      => \app\common\model\User::where(['id'=>$v['uid']])->value('avatar'),
                'status'        => $v['status']
            ];
        }
        ok([
            "items"     => $event,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }

    /**
     * 我的事件列表
     * @param int $tpe 事件类型 1我发现  2我的谏言  3我解决
     * @param int $is_check 0为审核中 不传 全部
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param string $orders 排序
     * @param string $token 用户TOKEN
     * @return array
     */
    public function myEventList()
    {
        $tpe = intval(input("tpe"));
        $is_check = intval(input("is_check", -1));
        $is_solve_check = intval(input("is_solve_check", -1));
        $status = intval(input("status", -1));
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "addtime desc"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        $keyword = trim(input('keyword'));
        $where = [
            'uid' => $this->uid,
            'tpe' => $tpe
        ];
        if ($is_check != -1) {
            $where['is_check'] = $is_check;
        }
        if($keyword){
            $where['title|content'] = ['like','%'.$keyword.'%'];
        }
        if($is_check !=-1){
            $where['is_check'] = $is_check;
        }
        if($is_solve_check !=-1){
            $where['is_solve_check'] = $is_solve_check;
        }

        if($status !=-1){
            $where['status'] = $status;
        }

        $event = [];
        $eventList = \app\common\model\Event::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Event::where($where)->count();
        foreach ($eventList as $k => $v) {
            $event[$k] = [
                'id' => $v['id'],
                'title' => $v['title'],
                'username' => $v['username'],
                'content' => $v['content'],
                'is_check' => $v['is_check'],
                'address' => $v['address'],
                'address_info' => $v['address_info'],
                'deal_content' => $v['deal_content'],
                'deal_img' => json_decode($v['deal_img'], true),
                'img' => is_array(json_decode($v['img'], true)) ? thumb_img(current(json_decode($v['img'], true))) : '',
                'imgs' => json_decode($v['img'], true),
                'tpe' => $v['tpe'],
                'addtime' => format_time($v['addtime'], "Y-m-d"),
                'dealtime' => format_time($v['dealtime']),
                'likes' => $v['likes'],
                'is_order' => $v['is_order'],
                'status'   => $v['status'],
                'head_img' => \app\common\model\User::where(['id' => $v['uid']])->value('avatar'),
            ];
            if($v['is_check'] ==1){
                $expire_time = round((($v['dealtime'] + $v['expire_time'])-time())/3600,2);
                if($expire_time < 0){
                    $expire_time = 0 ;
                }
                $event[$k]['expire_time'] = $expire_time.'h';
            }
        }
        ok([
            "items" => $event,
            "pagesize" => $pagesize,
            "curpage" => $page,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    }
    /**
     * 事件详情
     * @param int $id 事件ID
     * @return array
     */
    public function index()
    {
        $id = intval(input("id"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $eventInfo = Event_mod::get($id);
        if ($eventInfo) {
            $eventInfo = $eventInfo->toArray();
            $event = [
                'title'     => $eventInfo['title'],
                'username'  => $eventInfo['username'],
                'content'   => $eventInfo['content'],
                'address'   => $eventInfo['address'],
                'address_info'  => $eventInfo['address_info'],
                'is_check'      => $eventInfo['is_check'],
                'deal_content'  => $eventInfo['deal_content'],
                'dealtime'      => format_time($eventInfo['dealtime']),
                'imgs'          => json_decode($eventInfo['img'], true),
                'deal_img'      => json_decode($eventInfo['deal_img'], true),
                'check_time'    => format_time($eventInfo['check_time']),
                'tpe'           => $eventInfo['tpe'],
                'addtime'       => format_time($eventInfo['addtime'], "Y-m-d"),
                'likes'         => $eventInfo['likes'],
                'status'        => $eventInfo['status']
            ];
            if($eventInfo['is_check'] ==1){
                $expire_time = round((($eventInfo['dealtime'] + $eventInfo['expire_time'])- time() )/3600,2);
                if($expire_time < 0){
                    $expire_time = 0 ;
                }
                $event['expire_time'] = $expire_time.'h';
            }
        }
        ok($event);
    }
    /**
     * 点赞
     * @param int $id 事件ID
     */
    public function like(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $event = Event_mod::get($id);

        $event = $event->toArray();
        $likeInfo = \app\common\model\EventLikeLog::where(['event_id'=>$id,'ua' => _ua_key(),'ip' => get_onlineip(),'daytime'=>strtotime(date("Y-m-d"))])->find();
        if(!$likeInfo){
            $data = [
                'event_id'   => $id,
                'ua'    => _ua_key(),
                'ip'    => get_onlineip(),
                'daytime'  => strtotime(date("Y-m-d")),
                'uid'   => $this->uid
            ];
            \app\common\model\EventLikeLog::create($data);
            Event_mod::update(['likes'=>$event['likes']+1],['id'=>$id]);
            if($this->uid){
                $integralInfo = [
                    'event_code'        => 'Like',
                    'uid'               => $this->uid,
                    'area_id'           => $this->auth->area_id,
                    'note'              => '点赞'.$event['title'],
                    'obj_id'            => $id,
                ];
                \think\Hook::listen("integral",$integralInfo);
            }
            ok(['likes'=>$event['likes']+1]);
        }
        $lang = lang("has_click");
        err(200, "has_click", $lang['code'], $lang['message']);
    }
}
