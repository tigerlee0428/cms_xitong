<?php

namespace app\api\controller\v2;

use think\Request;
use app\common\library\Auth;

/**
 * 移动办公接口
 */
class MobileOffice
{
    protected $request;
    protected $uid;
    protected $auth;
    protected $area_id;


    public function __construct()
    {
        $this->request = Request::instance();
        $this->auth = Auth::instance();
        $actionname = strtolower($this->request->action());
        if ($actionname != 'login') {
            $access_token = trim(input("access_token"));
            $redis = new \Redis();
            $redis->connect("127.0.0.1", 6379);
            $uid = $redis->hget($access_token, 'cadmin_id');
            if (!$uid) {
                $lang = lang('please_login_first');
                err(200, 'please_login_first', $lang['code'], $lang['message']);
            }
            $admin_info = \app\admin\model\Admin::where(['id' => $uid])->find();

            $this->uid = $uid;
            $this->area_id= $admin_info['area_id'];
        }
    }


    /**
     * 登录
     * @param string $username 用户名
     * @param string $password 密码
     */
    public function login()
    {
        $username = trim(input("username"));
        $password = trim(input("password"));
        //验参
        if (!$username || !$password) {
            $lang = lang('params_not_valid');
            err(200, 'params_not_valid', $lang['code'], $lang['message']);
        }
        $admin_info = \app\admin\model\Admin::where(['username' => $username])->find();
        if (!$admin_info) {
            $lang = lang('user_error');
            err(200, 'user_error', $lang['code'], $lang['message']);
        }
        $salt = $admin_info['salt'];
        $password = md5(md5($password) . $salt);

        if ($password == $admin_info['password']) {
            if ($admin_info['status'] != 'normal') {
                $lang = lang('account_uninitiated');
                err(200, 'account_uninitiated', $lang['code'], $lang['message']);
            }
            $data = [
                'logintime' => time(),
            ];
            $access_token = md5($admin_info['id'] . 'mobileoffice' . rand(100,999));
            \app\admin\model\Admin::update($data, ['id' => $admin_info['id']]);
            $pid = \app\admin\model\Area::where(['id' => $admin_info['area_id']])->value('pid');
            if($admin_info['area_id'] == 896){
                $auth = 'center';
            }elseif ($pid == 895) {
                $auth = 'place';
            }else{
                $auth = 'station';
            }
            $redis = new \Redis();
            $redis->connect("127.0.0.1", 6379);
            $redis->hset($access_token, 'cadmin_id', $admin_info['id']);
            ok(['access_token' => $access_token, 'info' => ['nickname' => $admin_info['nickname'], 'avatar' => $admin_info['avatar'], 'area_id' => $admin_info['area_id'],'auth'=>$auth]]);
        } else {
            $lang = lang('password_error');
            err(200, 'password_error', $lang['code'], $lang['message']);
        }
        ok();
    }

    /**
     * 活动列表
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param string $orders 排序
     */

    public function activity()
    {
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "status asc, start_time desc"));
        $adminInfo = \app\admin\model\Admin::where(['id' => $this->uid])->find()->toArray();
        $where = [
            'is_publish' => 1,
            'is_check' => 1,
            'status' => 1,
            'area_id' => ['in', \app\common\model\Cfg::childArea($adminInfo['area_id'])],
        ];
        $activity = [];
        $activityList = \app\common\model\Activity::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Activity::where($where)->count();
        //$list = collection($activityList)->toArray();
        foreach ($activityList as $k => $v) {
            $activity[$k] = [
                'id' => $v['id'],
                'title' => $v['title'],
                'brief' => $v['brief'],
                'images' => $v['images'],
                'category' => $v['category'],
                'status' => $v['status'],
                'format_zm_start_time' => format_time($v['publish_time']),
                'format_zm_end_time' => format_time($v['start_time']),
                'format_start_time' => format_time($v['start_time'], "Y-m-d"),
                'format_end_time' => format_time($v['end_time'], "Y-m-d"),
                'start_time' => $v['start_time'],
                'end_time' => $v['end_time'],
                'servicetime' => $v['servicetime'],
                'address' => $v['address'],
                'group_name' => $v['group_name'],
                'contacter' => $v['contacter'],
                'phone' => $v['phone'],
                'likes' => $v['likes'],
                'click_count' => $v['click_count'],
                'x' => $v['x'],
                'y' => $v['y'],
                'person_limit' => $v['person_limit'],
                'bm_count' => $v['is_volunteer'] ? \app\common\model\ActivityBmLog::where(["aid" => $v['id'], 'tpe' => 2])->count() : ($v['is_menu'] ? \app\common\model\ActivityBmLog::where(["aid" => $v['id'], 'tpe' => 1])->count() : \app\common\model\ActivityBmLog::where(["aid" => $v['id']])->count()),
            ];
        }
        ok([
            "items" => $activity,
            "pagesize" => $pagesize,
            "curpage" => $page,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    }

    /**
     * 接收视频
     * @param string $access_token 登录token
     * @return array
     */
    public function receive()
    {
        $adminInfo = \app\admin\model\Admin::where(['id' => $this->uid])->find()->toArray();
        $area_id = $adminInfo['area_id'];
        $key = md5($area_id . config("authkey"));
        $redis = new \Redis();
        $redis->connect("127.0.0.1", 6379);
        $data = $redis->lpop($key);
        if ($data) {
            $info = json_decode($data, true);
            ok($info);
        }
        $lang = lang("please_wait");
        err(200, "please_wait", $lang['code'], $lang['message']);
    }


    /**
     * 开始直播
     * @param int $act_id 活动ID
     * @param string $access_token 登录token
     */
    public function startLive()
    {
        $act_id = intval(input('act_id'));

        $push_domain = config('push_url');//推流的域名
        $play_domain = config('play_url');//播放的域名
        $key = config('tencent_api_key');//推流防盗链key
        $time = date('Y-m-d H:i:s', strtotime('+5hour'));//过期时间,当前时间戳+1小时 2017-01-09 22:04:11

        if (!$act_id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }

        $activityInfo = \app\admin\model\Activity::where(['id' => $act_id])->find();
        if (!$activityInfo) {
            $lang = lang("not_activity");
            err(200, "not_activity", $lang['code'], $lang['message']);
        }

        $stream_name = config('stream_name') . $act_id;
        $push_url = getPushUrl($push_domain, $stream_name, $key, $time);
        $play_url = [
            'RTMP' => "rtmp://" . $play_domain . "/live/" . $stream_name,
            'FLV'  => "http://" . $play_domain . "/live/" . $stream_name . '.flv',
            'HLS'  => "http://" . $play_domain . "/live/" . $stream_name . '.m3u8'
        ];

        $redis = new \Redis();
        $redis->connect("127.0.0.1", 6379);
        if ($redis->exists($stream_name)) {
            $lang = lang("activity_has_live");
            err(200, "activity_has_live", $lang['code'], $lang['message']);
        }

        $redis->hset($stream_name, 'push_url', $push_url);
        $redis->hset($stream_name, 'play_url', json_encode($play_url));
        $redis->expire($stream_name, 60 * 60 * 5);//过期时间5小时


        \app\admin\model\Activity::update(['is_live' => 1], ['id' => $activityInfo['id']]);

        ok([
            'push_url' => $push_url,
            'play_url' => $play_url,
            'stream_name' => $stream_name,
        ]);
    }


    /**
     * 关闭直播
     * @param int $act_id 活动ID
     * @param string $access_token 登录token
     */
    public function stopLive()
    {
        $act_id = intval(input('act_id'));
        if (!$act_id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }

        $activityInfo = \app\admin\model\Activity::where(['id' => $act_id])->find();
        if (!$activityInfo) {
            $lang = lang("not_activity");
            err(200, "not_activity", $lang['code'], $lang['message']);
        }

        $redis = new \Redis();
        $redis->connect("127.0.0.1", 6379);
        $redis->del(config('stream_name').$act_id);

        \app\admin\model\Activity::update(['is_live' => 0], ['id' => $activityInfo['id']]);
        ok();
    }
}
