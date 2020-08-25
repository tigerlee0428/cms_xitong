<?php

namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use app\common\model\ActivityBmLog;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 活动接口
 */
class Activity extends ApiCommon
{
    protected $noNeedLogin = ['activityList', 'index', 'bmLogList', 'commentlist', 'prizerecords', 'lottery', 'commentprize', 'getLiveUrl','pullComment'];
    protected $noNeedRight = '*';
    protected $model = null;

    protected function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Activity;
    }

    /**
     * 活动列表（我创建的活动）
     * @param int $category 活动分类
     * @param int $status 活动状态
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param string $orders 排序
     * @param string $search 关键词
     * @param int $area_id 区域ID
     * @param int $is_volunteer 是否是志愿活动
     * @param int $is_menu 是否是点单活动
     * @param int $is_classic 是否是经典活动
     * @param string $keyword 关键词
     * @param string $token 用户token,token传了为我创建的活动
     * @return array
     *
     */
    public function activityList()
    {
        $category = intval(input("category", -1));
        $status = intval(input("status", -1));
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "status asc, start_time desc"));
        $search = input("search");
        $area_id = intval(input("area_id", $this->auth->area_id));
        $is_volunteer = intval(input("is_volunteer"));
        $is_menu = intval(input("is_menu"));
        $is_classic = intval(input("is_classic"));
        $keyword = trim(input("keyword"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_publish' => 1,
            'is_check' => 1,
        ];

        if ($area_id) {
            $where['area_id'] = ['in', \app\common\model\Cfg::childArea($area_id)];
        }
        if ($category != -1) {
            $where['category'] = $category;
        }
        if ($status != -1) {
            $where['status'] = $status;
            if ($status == 2) {
                $where['status'] = ['in', [2, 3]];
            }
        }
        if ($keyword) {
            $where['title'] = ['like', '%' . $keyword . '%'];
        }
        if ($this->auth->id) {
            unset($where['is_publish']);
            unset($where['is_check']);
            $where['uid'] = $this->auth->id;
        }
        if (!empty($search)) {
            $where['title'] = ['like', '%' . $search . '%'];
        }
        if ($is_menu) {
            $where['is_menu'] = 1;
        }
        if ($is_volunteer) {
            $where['is_volunteer'] = 1;
        }
        if ($is_classic) {
            $where['is_classic'] = 1;
        }
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
                'thumb_images' => thumb_img($v['images']),
                'category' => $v['category'],
                'status' => $v['status'],
                'format_zm_start_time' => format_time($v['publish_time']),
                'format_zm_end_time' => format_time($v['start_time']),
                'format_start_time' => format_time($v['start_time'], "Y/m/d"),
                'format_end_time' => format_time($v['end_time'], "Y/m/d"),
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
                'bm_count' => $v['is_volunteer'] ? \app\common\model\ActivityBmLog::where(["aid" => $v['id'], 'tpe' => 1])->count() : ($v['is_menu'] ? \app\common\model\ActivityBmLog::where(["aid" => $v['id'], 'tpe' => 1])->count() : \app\common\model\ActivityBmLog::where(["aid" => $v['id']])->count()),
            ];
            if ($this->auth->id) {
                $activity[$k]['is_check'] = $v['is_check'];
                $url = config("interface_domain") . "/home/auth/wxLogin?redirect_uri=" . urlencode(config("interface_domain") . "/home/index/signin?id=" . $v['id']);
                $activity[$k]['qrcode'] = config("interface_domain") . '/home/index/qrcode?url=' . $url;
            }
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
     * 我参加的活动列表
     * @param int $status 活动状态
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param int $orders 排序
     * @param int $is_volunteer 是否是志愿活动
     * @param int $is_menu 是否是点单活动
     * @param string $token 用户TOKEN
     * @return array
     *
     */
    public function myActivityList()
    {
        $status = intval(input("status", -1));
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "add_time desc"));
        $is_volunteer = intval(input("is_volunteer"));
        $is_menu = intval(input("is_menu"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        $joinActivity = \app\common\model\ActivityBmLog::where(['uid' => $this->uid])->select();
        $aidList = [];
        $activity = [];
        $activityLog = [];
        $total = 0;
        if ($joinActivity) {
            foreach ($joinActivity as $k => $v) {
                $aidList[] = $v['aid'];
                $activityLog[$v['aid']] = $v;
            }
            $where = [];
            $where['id'] = ['in',$aidList];
            if ($status != -1) {
                $where['status'] = $status;
                if ($status == 2) {
                    $where['status'] = ['in', [2,3]];
                }
            }
            if ($is_menu) {
                $where['is_menu'] = 1;
            }
            if ($is_volunteer) {
                $where['is_volunteer'] = 1;
            }
            $activityList = \app\common\model\Activity::where($where)->page($page)->limit($pagesize)->order($orders)->select();
            $total = \app\common\model\Activity::where($where)->count();
            foreach ($activityList as $k => $v) {
                $activity[$k] = [
                    'id'            => $v['id'],
                    'title'         => $v['title'],
                    'brief'         => $v['brief'],
                    'images'        => $v['images'],
                    'thumb_images'  => thumb_img($v['images']),
                    'status'        => $v['status'],
                    'format_start_time' => format_time($v['start_time']),
                    'format_estartnd_time' => format_time($v['end_time']),
                    'end_time'      => $v['end_time'],
                    'start_time'    => $v['start_time'],
                    'bm_time'       => format_time($activityLog[$v['id']]['addtime']),
                    'is_sign'       => $activityLog[$v['id']]['is_sign'],
                    'group_name'    => $v['group_name'],
                    'address'       => $v['address']
                ];
            }
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
     * 报名记录列表
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param int $orders 排序
     * @param string $token 用户TOKEN
     * @return array
     *
     */
    public function bmLogList()
    {
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "addtime desc"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [];
        $bmlog = [];
        $bmloglist = \app\common\model\ActivityBmLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\ActivityBmLog::where($where)->count();
        foreach ($bmloglist as $k => $v) {

            $bmlog[$k] = [
                'id' => $v['id'],
                'name' => $v['name'],
                'is_sign' => $v['is_sign'],
                'addtime' => format_time($v['addtime']),
                'signtime' => format_time($v['signtime']),
                'head_img' => \app\common\model\User::where(['id' => $v['uid']])->value('avatar'),
            ];
        }
        ok([
            "items" => $bmlog,
            "pagesize" => $pagesize,
            "curpage" => $page,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    }


    /**
     * 活动详情
     * @param int $id 活动ID（必）
     * @param int $share 是否来自微信分享
     * @return array
     *
     */
    public function index()
    {
        $id = intval(input("id"));
        $share = intval(input("share"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $activityInfo = \app\common\model\Activity::where(['id' => $id])->find();
        if ($activityInfo) {
            $activity = [
                'id'            => $activityInfo['id'],
                'title'         => $activityInfo['title'],
                'brief'         => $activityInfo['brief'],
                'status'        => $activityInfo['status'],
                'format_start_time'     => format_time($activityInfo['start_time'], "Y/m/d H:i"),
                'format_end_time'       => format_time($activityInfo['end_time'], "Y/m/d H:i"),
                'format_zm_start_time'  => format_time($activityInfo['start_time']),
                'format_zm_end_time'    => format_time($activityInfo['end_time']),
                'content'       => $activityInfo['content'],
                'photos'        => $activityInfo['photos'] ? json_decode($activityInfo['photos'], true) : [],
                'contacter'     => $activityInfo['contacter'],
                'group_name'    => $activityInfo['group_name'],
                'address'       => $activityInfo['address'],
                'phone'         => $activityInfo['phone'],
                'person_limit'  => $activityInfo['person_limit'],
                'bm_count'      => $activityInfo['is_volunteer'] ? \app\common\model\ActivityBmLog::where(["aid" => $id, 'tpe' => 1])->count() : ($activityInfo['is_menu'] ? \app\common\model\ActivityBmLog::where(["aid" => $id, 'tpe' => 1])->count() : \app\common\model\ActivityBmLog::where(["aid" => $id])->count()),
                'images'        => $activityInfo['images'],
                'joiner'        => \app\common\model\ActivityBmLog::getJoiner(['aid' => $id, 'tpe' => 1]),
                'volunteers'    => \app\common\model\ActivityBmLog::getJoiner(['aid' => $id, 'tpe' => 2]),
                'likes'         => $activityInfo['likes'],
                'click_count'   => $activityInfo['click_count'],
                'is_need_vol'   => $activityInfo['is_need_vol'],
                'vol_num'       => $activityInfo['vol_num'],
                'score'         => $activityInfo['servicetime'] ? round($activityInfo['servicetime']) : round(($activityInfo['end_time'] - $activityInfo['start_time'])/3600),
            ];

            $activity['is_join'] = \app\common\model\ActivityBmLog::is_bm($id,$this->uid);
            if ($this->uid && $activity['is_join']) {
                $url = config("interface_domain") . "/home/activity/activity?aid=" . $id . "&uid=" . $this->uid;
                $activity['qrcode'] = config("interface_domain") . "/home/index/qrcode?url=" . urlencode(config("interface_domain") . "/home/auth/wxlogin?redirect_uri=" . urlencode($url));
            }
        }
        if ($share != 1) {
            \app\common\model\Activity::update(['click_count' => $activityInfo['click_count'] + 1], ['id' => $id]);
        }
        ok($activity);
    }

    /**
     * 活动点赞
     * @param int $id 活动ID（必）
     * @param string $token 用户TOKEN
     * @return array
     *
     */
    public function like()
    {
        $id = intval(input("id"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $activityInfo = \app\common\model\Activity::where(['id' => $id])->find();
        if (!$activityInfo) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $activityLog = \app\common\model\ActivityLikeLog::where(['aid' => $id, "uid" => $this->uid, 'daytime' => strtotime(date("Y-m-d")), 'tpe' => 1])->find();

        if (!$activityLog) {
            $inf = \app\common\model\ActivityLikeLog::create(['aid' => $id, "uid" => $this->uid, 'daytime' => strtotime(date("Y-m-d")), 'tpe' => 1]);
            if ($inf) {
                if ($this->uid) {
                    $integralInfo = [
                        'event_code' => 'Like',
                        'uid' => $this->uid,
                        'area_id' => $this->auth->area_id,
                        'note' => '点赞' . $activityInfo['title'],
                        'obj_id' => $id,
                    ];
                    \think\Hook::listen("integral", $integralInfo);
                }
                \app\common\model\Activity::update(['likes' => $activityInfo['likes'] + 1], ['id' => $id]);
            }
            ok(['likes' => $activityInfo['likes'] + 1]);
        }
        $lang = lang("has_likes");
        err(200, "has_likes", $lang['code'], $lang['message']);
    }


    /**
     * 报名预约
     * @param int $id 活动ID（必）
     * @param int $name 姓名
     * @param int $mobile 手机号
     * @param int $is_need_volunteer 点单招募志愿者传1
     * @param string $token 用户TOKEN
     * @return array
     *
     */
    public function join()
    {
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $id = intval(input("id"));
        $name = trim(input("name"));
        $mobile = trim(input("mobile"));
        $is_need_volunteer = intval(input("is_need_volunteer"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $activityInfo = \app\common\model\Activity::where(['id' => $id])->find();

        if (!$activityInfo) {
            $lang = lang("not_activity");
            err(200, "not_activity", $lang['code'], $lang['message']);
        }
        //是否是志愿活动或点单活动招募志愿者，此时志愿身份验证
        if($activityInfo['is_volunteer'] || $is_need_volunteer){
            if(!$this->auth->is_volunteer){
                $lang = lang("not_volunteer");
                err(200, "not_volunteer", $lang['code'], $lang['message']);
            }
        }

        //活动是否发布
        if (!$activityInfo['is_publish']) {
            $lang = lang("activity_not_publish");
            err(200, "activity_not_publish", $lang['code'], $lang['message']);
        }
        $thistime = time();
        $status = 0;
        if ($thistime < $activityInfo['start_time']) {
            $status = 0;
        }
        if ($thistime > $activityInfo['start_time'] && $thistime < $activityInfo['end_time']) {
            $status = 1;
        }
        if ($thistime > $activityInfo['end_time']) {
            $status = 2;
        }
        //活动是否结束
        if ($status == 2) {
            $lang = lang("activity_time_error");
            err(200, "activity_time_error", $lang['code'], $lang['message']);
        }

        //活动招募上限
        if ($activityInfo['person_limit']) {
            $count = $activityInfo['is_menu'] ? \app\common\model\ActivityBmLog::where(['aid' => $id, 'tpe' => 1])->count() : ($activityInfo['is_volunteer'] ? \app\common\model\ActivityBmLog::where(['aid' => $id, 'tpe' => 2])->count() : \app\common\model\ActivityBmLog::where(['aid' => $id])->count());
            if ($count >= $activityInfo['person_limit']) {
                $lang = lang("person_limit");
                err(200, "person_limit", $lang['code'], $lang['message']);
            }
        }
        //如果是点单活动招募
        if ($activityInfo['is_menu'] && $activityInfo['is_need_vol'] && $is_need_volunteer) {
            $count = \app\common\model\ActivityBmLog::where(['aid' => $id, 'tpe' => 2])->count();
            if ($count >= $activityInfo['vol_num']) {
                $lang = lang("person_limit");
                err(200, "person_limit", $lang['code'], $lang['message']);
            }
        }
        //判断是否参与该活动
        $bmLogWhere = [
            'aid' => $id,
            'uid' => $this->uid,
        ];
        if ($activityInfo['is_menu']) {
            $bmLogWhere['tpe'] = $is_need_volunteer ? 2 : 1;
        } elseif ($activityInfo['is_volunteer']) {
            $bmLogWhere['tpe'] = 1;
        }
        $bmLog = \app\common\model\ActivityBmLog::where($bmLogWhere)->find();
        if ($bmLog) {
            $lang = lang("had_join_activity");
            err(200, "had_join_activity", $lang['code'], $lang['message']);
        }

        $userInfo = \app\common\model\User::get($this->uid);
        $bmLogInfo = [
            'aid' => $id,
            'uid' => $this->uid,
            'addtime' => time(),
            'name' => $name ? $name : ($userInfo['realname'] ? $userInfo['realname'] : $userInfo['nickname']),
            'mobile' => $mobile ? $mobile : $userInfo['mobile'],
            'tpe' => ($activityInfo['is_menu'] && $is_need_volunteer) ? 2 : 1,
        ];
        if (!cfg("is_need_bm_check")) {
            $bmLogInfo['is_pass'] = 1;
        }
        $this->joinZyh($activityInfo['zid']);
        $inf = \app\common\model\ActivityBmLog::create($bmLogInfo);
        $cur_time = time() + 3600 * 24;
        if ($inf) {
            $params = \app\common\model\Activity::get($id)->toArray();
            $params['action'] = 'bm_success';
            $params['uid'] = $this->uid;
            \think\Hook::listen("activity", $params);
        }
        ok();
    }
    /**
     * 报名志愿汇
     * @param int $id 活动ID（必）
     * @param int $name 姓名
     * @param int $mobile 手机号
     * @param int $is_need_volunteer 点单招募志愿者传1
     * @param string $token 用户TOKEN
     * @return array
     *
     */
    private function joinZyh($recruitId){
        $token = trim(input('zyh_token'));
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/recruit/signup'; //访问方法
        $apiParam = array('token'=>$token,'recruitId'=>$recruitId);//访问参数
        $res = $zyh::getData($apiFun, $apiParam);
        if($res['errCode'] !='0000'){
            $lang = lang("join_activity_fail");
            err(200, "join_activity_fail", $res['errCode'],$res['message']);
        }
    }
    /**
     * 取消报名
     * @param int $id 活动ID（必）
     * @param string $access_token 用户TOKEN
     * @return array
     *
     */
    public function cancelJoin(){
        $aid = intval(input('id'));
        $activityInfo = \app\common\model\Activity::where(['id' => $aid])->find();
        $this->cancelJoinzyh($activityInfo['zid']);
        ActivityBmLog::destroy(['uid'=>$this->auth->id,'aid'=>$aid]);
        ok();
    }

    private function cancelJoinzyh($recruitId){
        $token = trim(input('zyh_token'));
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/recruit/signout'; //访问方法
        $apiParam = array('token'=>$token,'recruitId'=>$recruitId);//访问参数
        $res = $zyh::getData($apiFun, $apiParam);
        if($res['errCode'] !='0000'){
            $lang = lang("cancel_join_fail");
            err(200, "cancel_join_fail", $lang['code'], $lang['message']);
        }
    }

    /**
     * 添加志愿者活动
     * @param string $title 活动标题(必)
     * @param string $brief 活动简介
     * @param string $images 活动封面
     * @param string $publish_time 发布时间
     * @param string $start_time 开始时间(必)
     * @param string $end_time 结束时间(必)
     * @param int $person_limit 活动人数
     * @param float $servicetime 活动时长
     * @param string $contacter 联系人
     * @param string $phone 联系电话
     * @param string $address 活动地址
     * @param string $group_name 发起组织
     * @param string $content 活动内容
     * @param string $token 用户TOKEN
     */
    public function add()
    {
        $title = trim(input("title"));
        $brief = trim(input("brief"));
        $images = trim(input("images"));
        $publish_time = strtotime(input("publish_time"));
        $start_time = strtotime(input("start_time"));
        $end_time = strtotime(input("end_time"));
        $person_limit = intval(input("person_limit"));
        $servicetime = floatval(input("servicetime"));
        $contacter = trim(input("contacter"));
        $phone = trim(input("phone"));
        $address = trim(input("address"));
        $group_name = trim(input("group_name"));
        $content = trim(input("content"));
        if (!$title || !$start_time || !$end_time) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }

        $group_id = \app\common\model\VolunteerGroup::where(['uid' => $this->auth->id])->value("id");
        if (!$group_id) {
            $lang = lang("Not_Volunteer_Manage");
            err(200, "Not_Volunteer_Manage", $lang['code'], $lang['message']);
        }
        $status = 0;
        $cur_time = time();

        if ($start_time >= $end_time) {
            $lang = lang("start_time_error");
            err(200, "start_time_error", $lang['code'], $lang['message']);
        }
        if ($publish_time >= $start_time) {
            $lang = lang("publish_time_error");
            err(200, "publish_time_error", $lang['code'], $lang['message']);
        }
        $is_publish = 0;

        if ($start_time < $cur_time) {
            $status = 1;
        }
        if ($end_time < $cur_time) {
            $status = 2;
        }
        $params = [
            'title' => $title,
            'brief' => $brief,
            'images' => $images,
            'publish_time' => $publish_time,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'person_limit' => $person_limit,
            'servicetime' => $servicetime ? $servicetime : round(($end_time - $start_time) / 3600, 2),
            'contacter' => $contacter ? $contacter : \app\common\model\VolunteerGroup::where(['id' => $group_id])->value("master"),
            'phone' => $phone ? $phone : \app\common\model\VolunteerGroup::where(['id' => $group_id])->value("mobile"),
            'address' => $address,
            'group_name' => $group_name ? $group_name : \app\common\model\VolunteerGroup::where(['id' => $group_id])->value("title"),
            'content' => $content
        ];
        $params['status'] = $status;
        $params['area_id'] = $this->auth->area_id;
        if ($params['address']) {
            list($x, $y) = jwd($params['address']);
            $params['x'] = $x;
            $params['y'] = $y;
        }
        $params['is_volunteer'] = 1;
        $params['mid'] = $this->auth->id;
        $params['uid'] = $this->auth->id;
        $params['group_id'] = $group_id;
        $result = false;
        Db::startTrans();
        try {

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
            ok();
        } else {
            $lang = lang("activity_add_error");
            err(200, "activity_add_error", $lang['code'], $lang['message']);
        }
    }

    /**
     * 修改志愿者活动
     * @param int $id 活动ID
     * @param string $title 活动标题(必)
     * @param string $brief 活动简介
     * @param string $images 活动封面
     * @param string $publish_time 发布时间
     * @param string $start_time 开始时间(必)
     * @param string $end_time 结束时间(必)
     * @param int $person_limit 活动人数
     * @param float $servicetime 活动时长
     * @param string $contacter 联系人
     * @param string $phone 联系电话
     * @param string $address 活动地址
     * @param string $group_name 发起组织
     * @param string $content 活动内容
     * @param string $token 用户TOKEN
     */
    public function edit()
    {
        $id = intval(input("id"));
        $title = trim(input("title"));
        $brief = trim(input("brief"));
        $images = trim(input("images"));
        $publish_time = strtotime(input("publish_time"));
        $start_time = strtotime(input("start_time"));
        $end_time = strtotime(input("end_time"));
        $person_limit = intval(input("person_limit"));
        $servicetime = floatval(input("servicetime"));
        $contacter = trim(input("contacter"));
        $phone = trim(input("phone"));
        $address = trim(input("address"));
        $group_name = trim(input("group_name"));
        $content = trim(input("content"));
        if (!$id || !$title || !$start_time || !$end_time) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $activityInfo = \app\common\model\Activity::where(['id' => $id])->find();
        if ($activityInfo['status'] >= 2) {
            $lang = lang("activity_time_error");
            err(200, "activity_time_error", $lang['code'], $lang['message']);
        }

        $group_id = \app\common\model\VolunteerGroup::where(['uid' => $this->auth->id])->value("id");
        if (!$group_id) {
            $lang = lang("Not_Volunteer_Manage");
            err(200, "Not_Volunteer_Manage", $lang['code'], $lang['message']);
        }
        $status = 0;
        $cur_time = time();

        if ($start_time >= $end_time) {
            $lang = lang("start_time_error");
            err(200, "start_time_error", $lang['code'], $lang['message']);
        }
        if ($publish_time >= $start_time) {
            $lang = lang("publish_time_error");
            err(200, "publish_time_error", $lang['code'], $lang['message']);
        }
        $is_publish = 0;

        if ($start_time < $cur_time) {
            $status = 1;
        }
        if ($end_time < $cur_time) {
            $status = 2;
        }

        $params = [
            'title' => $title,
            'brief' => $brief,
            'images' => $images,
            'publish_time' => $publish_time,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'person_limit' => $person_limit,
            'servicetime' => $servicetime ? $servicetime : round(($end_time - $start_time) / 3600, 2),
            'contacter' => $contacter ? $contacter : \app\common\model\VolunteerGroup::where(['id' => $group_id])->value("master"),
            'phone' => $phone ? $phone : \app\common\model\VolunteerGroup::where(['id' => $group_id])->value("mobile"),
            'address' => $address,
            'group_name' => $group_name ? $group_name : \app\common\model\VolunteerGroup::where(['id' => $group_id])->value("title"),
            'content' => $content
        ];
        $params['status'] = $status;
        if ($params['address']) {
            list($x, $y) = jwd($params['address']);
            $params['x'] = $x;
            $params['y'] = $y;
        }
        $params['is_check'] = 0;
        $params['is_publish'] = 0;
        $result = false;
        Db::startTrans();
        try {

            $result = $this->model->allowField(true)->save($params, ['id' => $id]);
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
            ok();
        } else {
            $lang = lang("activity_add_error");
            err(200, "activity_add_error", $lang['code'], $lang['message']);
        }
    }

    /**
     * 志愿者活动上报
     * @param int $id 活动ID(必)
     * @param float $servers 活动时长
     * @param string $images 活动图片,多图用,分隔
     * @param string $content 活动上报内容
     * @param string $token 用户TOKEN
     */
    public function report()
    {
        $id = intval(input("id"));
        $servers = floatval(input("servers"));
        $images = trim(input("images"));
        $content = trim(input("content"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }

        $group_id = \app\common\model\VolunteerGroup::where(['uid' => $this->auth->id])->value("id");
        if (!$group_id) {
            $lang = lang("Not_Volunteer_Manage");
            err(200, "Not_Volunteer_Manage", $lang['code'], $lang['message']);
        }
        $activityInfo = \app\common\model\Activity::where(['id' => $id])->find();
        if ($activityInfo['status'] != 2) {
            $lang = lang("activity_no_end");
            err(200, "activity_no_end", $lang['code'], $lang['message']);
        }
        if ($activityInfo['is_report']) {
            $lang = lang("activity_had_report");
            err(200, "activity_had_report", $lang['code'], $lang['message']);
        }

        $params = [
            'aid' => $id,
            'servers' => $servers,
            'images' => $images,
            'content' => $content,
            'addtime' => time(),
            'group_id' => $group_id,
        ];
        $reportModel = new \app\admin\model\ActivityReport;
        $result = false;
        Db::startTrans();
        try {

            $result = $reportModel->allowField(true)->save($params);
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
            $this->model->update(['is_report' => 1, 'status' => 3], ['id' => $id]);
            ok();
        } else {
            $lang = lang("activity_report_error");
            err(200, "activity_report_error", $lang['code'], $lang['message']);
        }
    }


    /**
     *  活动评论增加
     *
     * @ApiParams (name="aid", type="int", required=true, description="活动编号")
     * @ApiParams (name="content", type="string", required=true, description="评论内容")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiReturnParams   (name="status", type="integer", required=true, sample="200 评论成功")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object",  description="{}")
     *
     * @ApiReturn   ({
    "status": 200,
    "exception": "",
    "code": 0,
    "message": "",
    "data": {}
    })
     */
    public function comment()
    {
        /* if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        } */
        $aid = intval(input("aid"));
        $content = trim(input("content"));
        $user_id = $this->auth->id;

        if (!$aid || !$content) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }

        if (sensitiveWords($content)) {
            $lang = lang("senswords");
            err(200, "senswords", $lang['code'], $lang['message']);
        }

        $params = [
            'aid' => $aid,
            'content' => $content,
            'addtime' => time(),
            'user_id' => $user_id,
        ];
        $activityCommentModel = new \app\admin\model\ActivityComment();
        $result = false;
        Db::startTrans();
        try {
            $result = $activityCommentModel->allowField(true)->save($params);
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
            $key = md5($aid.config("authkey").\think\Env::get("database.database"));
            $data = [
                'nickname'  => $this->auth->nickname,
                'head_img'  => $this->auth->avatar,
                'content'   => $content
            ];
            $redis = new \Redis();
            $redis->connect("127.0.0.1",6379);
            $redis->lpush($key,json_encode($data));
            ok();
        } else {
            $lang = lang("activity_comment_error");
            err(200, "activity_comment_error", $lang['code'], $lang['message']);
        }
    }

    /**
     *  活动评论列表
     *
     * @ApiParams (name="aid", type="int", required=true, description="活动编号")
     * @ApiParams (name="page", type="int", required=true, description="页码")
     * @ApiParams (name="pagesize", type="int", required=true, description="每页显示数量")
     * @ApiParams (name="iswinning", type="int", required=false, description="中奖状态 1为中奖，0 为不中奖")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object",  description="扩展数据返回")
     * @ApiReturnParams   (name="----data object ------", type="object", description="data的数据类型")
     *
     * @ApiReturnParams   (name="pagesize", type="int", description="每页显示条数")
     * @ApiReturnParams   (name="curpage", type="int", description="当前页码")
     * @ApiReturnParams   (name="totalpage", type="int", description="总页数")
     * @ApiReturnParams   (name="total", type="int", description="总记录")
     *
     * @ApiReturnParams   (name = "items",type="array")
     * @ApiReturnParams   (name="----items object ------", type="object", description="items的数据类型")
     * @ApiReturnParams   (name="id", type="int", description="id")
     * @ApiReturnParams   (name="aid", type="int", description="活动编号")
     * @ApiReturnParams   (name="content", type="string", description="评论内容")
     * @ApiReturnParams   (name="user_id", type="int", description="评论人编号")
     * @ApiReturnParams   (name="head_img", type="string", description="评论人头像")
     * @ApiReturnParams   (name="nickname", type="string", description="评论人昵称")
     * @ApiReturnParams   (name="mobile", type="int", description="评论人手机")
     * @ApiReturnParams   (name="addtime", type="int", description="增加时间")
     * @ApiReturnParams   (name="addtime_text", type="int", description="增加时间年月日时分秒")
     * @ApiReturn   ({
    "status": 200,
    "exception": "",
    "code": 0,
    "message": "",
    "data": {
    "items": [
    {
    "id": 61,
    "aid": 1,
    "content": "好看",
    "user_id": null,
    "iswinning": "0",
    "addtime": 1573463791,
    "head_img": null,
    "nickname": null,
    "mobile": null,
    "iswinning_text": "Iswinning 0",
    "addtime_text": "2019-11-11 17:16:31"
    }
    ],
    "pagesize": 1,
    "curpage": 1,
    "totalpage": 61,
    "total": 61
    }
    })
     */
    /* public function commentlist()
    {
        $iswinning       = intval(input("iswinning",''));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","addtime asc"));
        $aid    = intval(input("aid"));
        $addtime    = intval(input("addtime"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;


        $where = [];
        $where['aid'] = $aid;

        if ($iswinning != '') {
            $where['iswinning'] = $iswinning;
        }

        $commentList = \app\admin\model\ActivityComment::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\admin\model\ActivityComment::where($where)->count();
        foreach ($commentList as &$v) {
            $userInfo = \app\common\model\User::get($v['user_id']);
            $v['head_img'] = $userInfo['avatar'];
            $v['nickname'] = $userInfo['nickname'];
            $v['mobile'] = $userInfo['mobile'];
        }
        ok([
            "items" => $commentList,
            "pagesize" => $pagesize,
            "curpage" => $page,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    } */


    public function commentlist()
    {

        $aid    = intval(input("aid"));
        $pagesize = intval(input("pagesize",10));
        $key = md5($aid.config("authkey").\think\Env::get("database.database"));
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        $data = [];
        for($i=0;$i<$pagesize;$i++){
            $value = $redis->lpop($key);
            if($value){
                $data[] = $value;
            }else{
                break;
            }
        }
        $list = [];
        if($data){
            foreach($data as $k => $v){
                $list[] = json_decode($v,true);
                $redis->rpush($key,$v);
            }
        }
        ok([
            "items" => $list
        ]);
    }

    /**
     * 推送弹幕
     * @param int $aid 活动ID
     */
    public function pullComment(){
        $aid = intval(input("aid"));
        $where = ['aid'=>$aid];
        $commentList = \app\admin\model\ActivityComment::where($where)->select();
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        $key = md5($aid.config("authkey").$aid);
        foreach(collection($commentList)->toArray() as $v){
            $data = [
                'nickname'  => \app\common\model\User::where(['id'=>$v['user_id']])->value("nickname"),
                'head_img'  => \app\common\model\User::where(['id'=>$v['user_id']])->value("avatar"),
                'content'   => $v['content']
            ];
            $redis->lpush($key,json_encode($data));
        }
    }

    /**
     *  活动评论奖品列表
     *
     * @ApiParams (name="aid", type="int", required=true, description="活动编号")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object",  description="扩展数据返回")
     * @ApiReturnParams   (name="----data object ------", type="object", description="data的数据类型")
     * @ApiReturnParams   (name="total", type="int", description="总记录")
     *
     * @ApiReturnParams   (name = "items",type="array")
     * @ApiReturnParams   (name="----items object ------", type="object", description="items的数据类型")
     * @ApiReturnParams   (name="id", type="int", description="id")
     * @ApiReturnParams   (name="aid", type="int", description="活动编号")
     * @ApiReturnParams   (name="prize_id", type="int", description="奖品编号")
     * @ApiReturnParams   (name="prize_img", type="string", description="奖品图片")
     * @ApiReturnParams   (name="prize_name", type="string", description="奖品名称")
     * @ApiReturnParams   (name="user_ids", type="string", description="作弊人员的编号")
     * @ApiReturnParams   (name="nums", type="int", description="奖品数量")
     * @ApiReturnParams   (name="addtime", type="int", description="增加时间")
     * @ApiReturnParams   (name="addtime_text", type="int", description="增加时间年月日时分秒")
     * @ApiReturn   ({
    "status": 200,
    "exception": "",
    "code": 0,
    "message": "",
    "data": {
    "items": [
    {
    "id": 2,
    "aid": 1,
    "prize_id": 1,
    "title": "",
    "user_ids": "5",
    "nums": 1,
    "addtime": 1573444087,
    "weigh": 10,
    "prize_img": "/uploads/20190918/e65c3a6a264bf115fd0e64e1d1359539.jpg",
    "prize_name": "一等奖",
    "addtime_text": "2019-11-11 11:48:07"
    },
    {
    "id": 3,
    "aid": 1,
    "prize_id": 2,
    "title": "",
    "user_ids": "8",
    "nums": 5,
    "addtime": 1573444115,
    "weigh": 9,
    "prize_img": "/uploads/20190918/b6a1c7aa6ea54a0692f73f97c4e6a53c.jpg",
    "prize_name": "二等奖",
    "addtime_text": "2019-11-11 11:48:35"
    },
    {
    "id": 1,
    "aid": 1,
    "prize_id": 3,
    "title": "",
    "user_ids": "6",
    "nums": 20,
    "addtime": 1573443118,
    "weigh": 0,
    "prize_img": "/uploads/20190916/e20cf95d9274f7ca2a463ee60b27ab7b.jpg",
    "prize_name": "三等奖",
    "addtime_text": "2019-11-11 11:31:58"
    }
    ],
    "total": 3
    }
    })
     */
    public function commentprize()
    {
        $aid = intval(input("aid"));

        $where = [];
        $where['aid'] = $aid;

        $commentList = \app\admin\model\Prizeset::where($where)->order(['weigh' => 'DESC'])->select();
        $total = \app\admin\model\Prizeset::where($where)->count();
        foreach ($commentList as &$v) {
            $userInfo = \app\admin\model\Commentprize::get($v['prize_id'])->toArray();
            $v['prize_img'] = $userInfo['image'];
            $v['prize_name'] = $userInfo['title'];
        }
        ok([
            "items" => $commentList,
            "total" => $total
        ]);
    }

    /**
     *  活动评论抽奖
     *
     * @ApiParams (name="aid", type="int", required=true, description="活动编号")
     * @ApiParams (name="prize_id", type="int", required=true, description="奖品设置编号")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object",  description="扩展数据返回")
     * @ApiReturnParams   (name="----data object ------", type="object", description="data的数据类型")
     * @ApiReturnParams   (name="total", type="int", description="总记录")
     *
     * @ApiReturnParams   (name = "items",type="array")
     * @ApiReturnParams   (name="----items object ------", type="object", description="items的数据类型")
     * @ApiReturnParams   (name="id", type="int", description="id")
     * @ApiReturnParams   (name="aid", type="int", description="活动编号")
     * @ApiReturnParams   (name="prizeid", type="int", description="奖品编号")
     * @ApiReturnParams   (name="prize_img", type="string", description="奖品图片")
     * @ApiReturnParams   (name="prize_name", type="string", description="奖品名称")
     * @ApiReturnParams   (name="user_id", type="int", description="中奖人编号")
     * @ApiReturnParams   (name="head_img", type="string", description="中奖人头像")
     * @ApiReturnParams   (name="nickname", type="string", description="中奖人昵称")
     * @ApiReturnParams   (name="mobile", type="string", description="中奖人手机")
     * @ApiReturnParams   (name="addtime", type="int", description="抽奖时间")
     * @ApiReturnParams   (name="addtime_text", type="int", description="抽奖时间年月日时分秒")
     * @ApiReturn   ({
    "status": 200,
    "exception": "",
    "code": 0,
    "message": "",
    "data": {
    "items": [
    {
    "id": 1,
    "aid": 1,
    "prizeid": 1,
    "title": "",
    "user_id": 5,
    "status": "0",
    "addtime": 1573461510,
    "head_img": "http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTKA0d0NIVvCPibPdIcXgCBUjD3iawyI4IYyIPdHBgVvk0rB0tkefRpdiaTywxHnXiaVTxER2tibV70DfZQ/132",
    "nickname": "船上人家",
    "mobile": "15995809679",
    "prize_img": "/uploads/20190918/e65c3a6a264bf115fd0e64e1d1359539.jpg",
    "prize_name": "一等奖",
    "status_text": "Status 0",
    "addtime_text": "2019-11-11 16:38:30"
    }
    ],
    "total": 1
    }
    })
     */
    public function lottery()
    {
        $aid = intval(input("aid"));
        $prize_id = intval(input('prize_id', 0));

        $where = [];
        $where['aid'] = $aid;
        $where['prizeid'] = $prize_id;

        $prizewhere = [];
        $prizewhere['aid'] = $aid;
        $prizewhere['prize_id'] = $prize_id;

        $prizesetInfo = \app\admin\model\Prizeset::where($prizewhere)->find();

        //作弊的人员编号
        $cheatarr = explode(',', $prizesetInfo['user_ids']);

        $lotterynum = $prizesetInfo['nums'] - count($cheatarr);//抽奖数量

        $prizerecordsArr = \app\admin\model\Prizerecords::where($where)->select();
        $recordsuserArr = [];//已经中奖的人员
        foreach ($prizerecordsArr as $record) {
            array_push($recordsuserArr, $record['user_id']);
        }

        //不参与抽奖的人
//        $nojoinLotteryArr = array_merge($cheatarr, $recordsuserArr);
        $nojoinLotteryArr = $cheatarr;//array_merge($cheatarr, $recordsuserArr);

        $comentwhere = [];
        $comentwhere['aid'] = $aid;

        $commentList = \app\admin\model\ActivityComment::where($comentwhere)->order(['addtime' => 'ASC'])->select();

//        $total = \app\admin\model\Prizerecords::where($where)->count();


        $lotteryarr = [];
        foreach ($commentList as $v) {
            //已经中奖和作弊的人员的人员不再参与中奖
            if (in_array($v['user_id'], $nojoinLotteryArr)) {
                continue;
            }
            array_push($lotteryarr, $v);
        }

        if ($lotterynum > 0) {
            //除了作弊的人员，还有抽奖的人数
            $random_keys = array_rand($lotteryarr, $lotterynum);

            foreach ($random_keys as $rw) {
                $recordmodel = new \app\admin\model\Prizerecords();
                $recordmodel->aid = $aid;
                $recordmodel->prizeid = $prize_id;
                $recordmodel->title = $prizesetInfo['title'];
                $recordmodel->user_id = $lotteryarr[$rw]['user_id'];
                $recordmodel->addtime = time();
                $recordmodel->save();

                //把弹幕设置为中奖
                $ActivityComment = new \app\admin\model\ActivityComment();
                $savedata = [];
                $savedata['iswinning'] = 1;
                \app\admin\model\ActivityComment::update($savedata, ['id' => $lotteryarr[$rw]['id']]);
            }
        }

        //把作弊人员的弹幕设置为中奖
        foreach ($cheatarr as $user) {
            $cheatComment = \app\admin\model\ActivityComment::where(['user_id' => $user])->find();

            //必须要发了弹幕才能抽奖
            if (!empty($cheatComment)) {
                $recordmodel = new \app\admin\model\Prizerecords();
                $recordmodel->aid = $aid;
                $recordmodel->prizeid = $prize_id;
                $recordmodel->title = $prizesetInfo['title'];
                $recordmodel->user_id = $user;
                $recordmodel->addtime = time();
                $recordmodel->save();


                //把弹幕设置为中奖
//                $ActivityComment = new \app\admin\model\ActivityComment();
                $savedata = [];
                $savedata['iswinning'] = 1;
                \app\admin\model\ActivityComment::update($savedata, ['id' => $cheatComment['id']]);
            }
        }


        $recordwhere = [];
        $recordwhere['aid'] = $aid;
        $recordwhere['prizeid'] = $prize_id;

        $commentList = \app\admin\model\Prizerecords::where($recordwhere)->order(['addtime' => 'DESC'])->select();
        $total = \app\admin\model\Prizerecords::where($recordwhere)->count();
        foreach ($commentList as &$v) {
            $prizeInfo = \app\admin\model\Commentprize::get($v['prizeid']);
            $userInfo = \app\common\model\User::get($v['user_id']);
            $v['head_img'] = $userInfo['avatar'];
            $v['nickname'] = $userInfo['nickname'];
            $v['mobile'] = $userInfo['mobile'];

            $v['prize_img'] = $prizeInfo['image'];
            $v['prize_name'] = $prizeInfo['title'];
        }

        ok([
            "items" => $commentList,
            "total" => $total
        ]);
    }

    /**
     *  活动评论中奖名单列表
     *
     * @ApiParams (name="aid", type="int", required=true, description="活动编号")
     * @ApiParams (name="prize_id", type="int", required=true, description="奖品设置编号")
     * @ApiHeaders  (name=token, type=string, required=true, description="请求的Token")
     * @ApiReturnParams   (name="code", type="integer", required=true, sample="0")
     * @ApiReturnParams   (name="msg", type="string", required=true, sample="返回成功")
     * @ApiReturnParams   (name="data", type="object",  description="扩展数据返回")
     * @ApiReturnParams   (name="----data object ------", type="object", description="data的数据类型")
     * @ApiReturnParams   (name="total", type="int", description="总记录")
     *
     * @ApiReturnParams   (name = "items",type="array")
     * @ApiReturnParams   (name="----items object ------", type="object", description="items的数据类型")
     * @ApiReturnParams   (name="id", type="int", description="id")
     * @ApiReturnParams   (name="aid", type="int", description="活动编号")
     * @ApiReturnParams   (name="prizeid", type="int", description="奖品编号")
     * @ApiReturnParams   (name="prize_img", type="string", description="奖品图片")
     * @ApiReturnParams   (name="prize_name", type="string", description="奖品名称")
     * @ApiReturnParams   (name="user_id", type="int", description="中奖人编号")
     * @ApiReturnParams   (name="head_img", type="string", description="中奖人头像")
     * @ApiReturnParams   (name="nickname", type="string", description="中奖人昵称")
     * @ApiReturnParams   (name="mobile", type="string", description="中奖人手机")
     * @ApiReturnParams   (name="addtime", type="int", description="抽奖时间")
     * @ApiReturnParams   (name="addtime_text", type="int", description="抽奖时间年月日时分秒")
     * @ApiReturn   ({
    "status": 200,
    "exception": "",
    "code": 0,
    "message": "",
    "data": {
    "items": [
    {
    "id": 1,
    "aid": 1,
    "prizeid": 1,
    "title": "",
    "user_id": 5,
    "status": "0",
    "addtime": 1573461510,
    "head_img": "http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTKA0d0NIVvCPibPdIcXgCBUjD3iawyI4IYyIPdHBgVvk0rB0tkefRpdiaTywxHnXiaVTxER2tibV70DfZQ/132",
    "nickname": "船上人家",
    "mobile": "15995809679",
    "prize_img": "/uploads/20190918/e65c3a6a264bf115fd0e64e1d1359539.jpg",
    "prize_name": "一等奖",
    "status_text": "Status 0",
    "addtime_text": "2019-11-11 16:38:30"
    }
    ],
    "total": 1
    }
    })
     */
    public function prizerecords()
    {
        $aid = intval(input("aid"));
        $prize_id = intval(input('prize_id', 0));

        $where = [];
        $where['aid'] = $aid;
        $where['prizeid'] = $prize_id;

        $commentList = \app\admin\model\Prizerecords::where($where)->order(['addtime' => 'DESC'])->select();
        $total = \app\admin\model\Prizerecords::where($where)->count();
        foreach ($commentList as &$v) {
            $prizeInfo = \app\admin\model\Commentprize::get($v['prizeid']);
            $userInfo = \app\common\model\User::get($v['user_id']);
            $v['head_img'] = $userInfo['avatar'];
            $v['nickname'] = $userInfo['nickname'];
            $v['mobile'] = $userInfo['mobile'];

            $v['prize_img'] = $prizeInfo['image'];
            $v['prize_name'] = $prizeInfo['title'];
        }
        ok([
            "items" => $commentList,
            "total" => $total
        ]);
    }


    /**
     *  获取直播流地址
     *
     * @ApiParams (name="act_id")
     *
     **/
    public function getLiveUrl()
    {
        $act_id = intval(input('act_id'));
        if (!$act_id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }

        $redis = new \Redis();
        $redis->connect("127.0.0.1", 6379);

        $activityInfo = \app\admin\model\Activity::where(['id' => $act_id])->find();

        if (!$activityInfo) {
            $lang = lang("not_activity");
            err(200, "not_activity", $lang['code'], $lang['message']);
        }

        //如果MySQL里面没有,或者redis里面也没有
        if ($activityInfo['is_live'] != 1 || $redis->exists(config('stream_name') . $act_id) == 0) {
            $lang = lang("activity_is_not_live");
            err(200, "activity_is_not_live", $lang['code'], $lang['message']);
        }

        //如果redis记录有效,但是mysql没有,更新mysql记录
        if ($redis->exists(config('stream_name') . $act_id) == 1 && $activityInfo['is_live'] == 0) {
            \app\admin\model\Activity::update(['is_live' => 0], ['id' => $activityInfo['id']]);
        }

        $play_url = $redis->hget(config('stream_name') . $act_id, 'play_url');

        ok(['play_url' => json_decode($play_url)]);


    }


}
