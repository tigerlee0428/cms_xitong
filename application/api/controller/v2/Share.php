<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
/**
 * 服务共享接口
 */
class Share extends ApiCommon
{
    protected $noNeedLogin = ['shareList','index'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    
    /**
     * 服务详情
     * @param int $id    服务ID
     * @return array
     */
    public function index(){
        $id        = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $shareInfo = \app\common\model\Share::get($id);
        $share = [];
        if($shareInfo){
            $shareInfo = $shareInfo->toArray();
    
            $share = [
                'id'            => $shareInfo['id'],
                'title'         => $shareInfo['title'],
                'brief'         => $shareInfo['brief'],
                'status'        => $shareInfo['status'],
                'image'         => json_decode($shareInfo['image'],true),
                'format_add_time'   => format_time($shareInfo['add_time'],"Y-m-d"),
                'username'      => $shareInfo['username'],
                'mobile'        => substr($shareInfo['mobile'],0,3).'****'.substr($shareInfo['mobile'],7,4),
                'free_time'     => $shareInfo['free_time'],
                'address'       => $shareInfo['address'],
                'score'         => $shareInfo['score'],
                "help_type_name"=> \app\common\model\HelpType::where(['id'=>$shareInfo['help_type']])->value("name"),
            ];
            
            $shareLog = \app\common\model\ShareLog::where(['share_id'=>$id,'status'=>3])->select();
            if($shareLog){
                $shareLog = collection($shareLog)->toArray();
                $shareLogArr = [];
                foreach($shareLog as $k => $v){
                    $uinfo = \app\common\model\User::get($v['uid']);
                    if(!$uinfo){
                        continue;
                    }
                    $uinfo = $uinfo->toArray();
                    $shareLogArr[] = [
                        'name'          => $uinfo['nickname'],
                        'headimg'       => $uinfo['avatar'],
                        'score'         => $v['score']
                    ];
                }
                $share['sharelog'] = $shareLogArr;
            }
            
        }
        ok($share);
    }
    
    /**
     * 发布服务
     * @param string $title 服务主题
     * @param string $brief 服务内容
     * @param array $image 服务相关图片
     * @param string $help_type 服务类型（1,2,3取值share_type）
     * @param string $free_time 服务时间
     * @param string $token 用户TOKEN
     * @return array
     */
    public function post()
    {
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $title          = trim(input("title"));  
        $brief          = trim(input("brief"));
        $image          = input("image/a");
        $help_type      = trim(input("help_type"));
        $free_time      = trim(input("free_time"));
        $address        = trim(input("address"));
        
        if(!$title){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        //判断是否是志愿者身份
        if(!$this->auth->is_volunteer){
            $lang = lang("not_volunteer");
            err(200,"not_volunteer",$lang['code'],$lang['message']);
        }
 
        $data = [
            'uid'           => $this->uid,
            'area_id'       => $this->auth->area_id,
            'title'         => $title,
            'brief'         => $brief,
            'image'         => is_array($image) ? json_encode($image) : '[]',
            'help_type'     => $help_type,
            'free_time'     => $free_time,
            'address'       => $address,
            'username'      => $this->auth->realname,
            'mobile'        => $this->auth->mobile,
            'add_time'      => time(),
        ];
        $inf = \app\common\model\Share::insert($data);
        if(!$inf){
            $lang = lang("post_share_err");
            err(200,"post_share_err",$lang['code'],$lang['message']);
        }
        ok();
    }
    
    /**
     * 设置服务空闲
     * @param string $token 用户TOKEN
     * @return array 
     */
    public function setFree(){
        \app\common\model\Share::update(['status'=>0],['uid'=>$this->uid]);
        ok();
    }
    /**
     * 服务列表
     * @param string $share_type 服务类型（1,2,3取值share_type）
     * @param int $status 状态（0服务中，1帮助中，2帮助完成，3评价完，4，关闭）
     * @param string $keywords 关键词
     * @param int $area_id 区域ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @return array
     */
    public function shareList(){
        $share_type     = trim(input('share_type'));
        $status         = intval(input('status',-1));
        $keywords       = trim(input('keywords'));
        $page           = intval(input("page",1));
        $pagesize       = intval(input("pagesize",10));
        $area_id        = intval(input("area_id",$this->auth->area_id));
        $orders         = trim(input("orders","add_time desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_check'   => 1,
        ];
        
        if ($keywords) {
            $where['title|brief'] = ['like',"%$keywords%"];
        }
        if ($share_type){
            $arr = explode(",",$share_type);
            if(is_array($arr)){
                $where[] = ['exp', \think\Db::raw("FIND_IN_SET(".$share_type.",help_type)")];
            }
        }
        if ($status != -1) {
            $where['status'] = $status;
        }
        if($area_id){
            $where['area_id'] = $area_id;
        }
        $share = [];
        $shareList = \app\common\model\Share::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Share::where($where)->count();
        foreach($shareList as $k => $v)
        {
        	
            $share[$k] = [
                'id'            => $v['id'],
                'title'         => $v['title'],
                'avatar'        => \app\common\model\User::where(['id'=>$v['uid']])->value("avatar"),
                'brief'         => $v['brief'],
                'username'      => $v['username'],
                'mobile'        => substr($v['mobile'],0,7).'****',
                'image'         => is_array(json_decode($v['image'],true)) ? current(json_decode($v['image'],true)) : '',
                'format_add_time'   => format_time($v['add_time'],"Y-m-d"),
                'status'        => $v['status'],
                "score"         => $v['score'],
                "free_time"     => $v['free_time'],
                "address"       => $v['address'],
                "help_type_name"=> \app\common\model\HelpType::where(['id'=>$v['help_type']])->value("name"),
            ];
        }
        ok([
            "items"     => $share,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }
    
    
    
    
    
    /**
     * 我的服务列表
     * @param string $share_type 服务类型（1,2,3取值share_type）
     * @param string $keywords 关键词
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     * @return array
     */
    public function myshareList(){
        
        $share_type      = trim(input('share_type'));
        $keywords   = trim(input('keywords'));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page       = max($page,1);
        $pagesize   = $pagesize ? $pagesize : 10;
        $where = [
            'uid'       => $this->uid,
        ];
        if ($keywords) {
            $where['title|brief'] = array('like',"%$keywords%");
        }
        if ($share_type){
            $arr = explode(",",$share_type);
            if(is_array($arr)){
                $where[] = ['exp', \think\Db::raw("FIND_IN_SET(".$share_type.",share_type)")];
            }
        }
        
        $status = 0;
        $myshare = [];
        $myshareList = \app\common\model\Share::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Share::where($where)->count();
        foreach($myshareList as $k => $v)
        {
             
            $myshare[$k] = [
                'id'            => $v['id'],
                'title'         => $v['title'],
                'brief'         => $v['brief'],
                'username'      => $v['username'],
                'mobile'        => substr($v['mobile'],0,7).'****',
                'image'         => is_array(json_decode($v['image'],true)) ? current(json_decode($v['image'],true)) : '',
                'format_add_time'   => format_time($v['add_time'],"Y-m-d"),
                'status'        => $v['status'],
                "score"         => $v['score'],
                "free_time"     => $v['free_time'],
                "help_type_name"=> \app\common\model\HelpType::where(['id'=>$v['help_type']])->value("name"),
            ];
            $shareLogAllList = [];
            $shareLog = \app\admin\model\ShareLog::where(['share_id'=>$v['id'],'status'=>0])->select();
            $shareLogAll = \app\admin\model\ShareLog::where(['share_id'=>$v['id']])->order("status asc")->select();
            if($shareLog){
                foreach(collection($shareLog)->toArray() as $k1 => $v1){
                    $mobile = \app\common\model\User::getMobileById($v1['uid']);
                    if($shareLogAll){
                        $shareLogAllList = [];
                        $status = 0;
                        foreach(collection($shareLogAll)->toArray() as $key => $val){
                            if($val['share_id'] == $v1['share_id'] && $val['log_id'] == $v1['id']){
                                $shareLogAllList[] = [
                                    'id'                => $val['id'],
                                    'reply_content'     => $val['reply_content'],
                                    'score'             => $val['score'],
                                    'status'            => $val['status'],
                                    'format_reply_time'        => format_time($val['reply_time']),
                                    'format_add_time'          => format_time($val['add_time']),
                                    'uname'             => \app\admin\model\User::where(['id'=>$val['uid']])->value("realname"),
                                    'mobile'            => ($val['status'] == 1 || $val['status'] == 3) ? $mobile : substr($mobile,0,7).'****',
                                ];
                                $status = $val['status'];
                            }
                        }
                    }
                }
            }            
            $myshare[$k]['shareLog'] = $shareLogAllList;
            $myshare[$k]['status'] = $status;
        }
        ok([
            "items"     => $myshare,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    
   /**
    * 求助（服务预约）
    * @param int $id    服务ID
    * @param string $content 帮助描述
    * @param string $token 用户TOKEN
    * @return array 
    */
    public function seekHelp()
    {
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $id = intval(input('id'));
        $content = trim(input("content"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $shareInfo = \app\common\model\Share::get($id);
        if(!$shareInfo){
            $lang = lang("no_share_info");
            err(200,"no_share_info",$lang['code'],$lang['message']);
        }
        
        $shareInfo = $shareInfo->toArray();
        if($shareInfo['is_check'] != 1){
            $lang = lang("no_share_info");
            err(200,"no_share_info",$lang['code'],$lang['message']);
        }
        if($shareInfo['status'] == 1){
            $lang = lang("server_is_busy");
            err(200,"server_is_busy",$lang['code'],$lang['message']);
        }
        if($shareInfo['uid'] == $this->uid){
            $lang = lang("can_not_shareself");
            err(200,"can_not_shareself",$lang['code'],$lang['message']);
        }
        
        
        $shareLog = [
            'share_id'          => $id,
            'uid'               => $this->uid,
            'add_time'          => time(),
            'status'            => 0,
            'reply_time'        => time(),
            'reply_content'     => $content,
        ];
        $inf = \app\common\model\ShareLog::create($shareLog);
        if(!$inf){
            $lang = lang("seek_help_fail");
            err(200,"seek_help_fail",$lang['code'],$lang['message']);
        }
        $shareloginfo = $inf->toArray();
        \app\admin\model\ShareLog::update(['log_id'=>$shareloginfo['id']],['id'=>$shareloginfo['id']]);
        \app\admin\model\Share::update(['status'=>1],['id'=>$shareInfo['id']]);
        $share_name = \app\common\model\User::where(['id'=>$shareInfo['uid']])->value('realname');
        //通知服务人
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$shareInfo['uid']])->value('openid'),
            'temp_id'   => 2,
            'msg_data'  => [
                'first'     => __("Someone ordered your service %s",$shareInfo['title']),
                'keyword1'  => format_time(time()),
                'keyword2'  => $shareInfo['brief'],
                'keyword3'  => $share_name,
                'keyword4'  => '',
                'remark'    => '',
            ],
            'sys_msg'   => [
                'title'     => __("Someone ordered your service %s",$shareInfo['title']),
                'brief'     => __("Someone ordered your service %s",$shareInfo['title']),
                'uid'       => $shareInfo['uid'],
            ],
            'url'       => config("wx_domain").'/#/shareDetails?template=1&id='.$id,
        ]);
        //通知求助者
        
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$this->uid])->value('openid'),
            'temp_id'   => 3,
            'msg_data'  => [
                'first'     => __("You ordered %s service",$shareInfo['title']),
                'keyword1'  => $share_name,
                'keyword2'  => $shareInfo['brief'],
                'keyword3'  => $shareInfo['title'],
                'keyword4'  => format_time(time()),
                'remark'    => __("Contact")."：".$shareInfo['mobile'],
            ],
            'sys_msg'   => [
                'title'     => __("You ordered %s service",$shareInfo['title']),
                'brief'     => __("You ordered %s service",$shareInfo['title']),
                'uid'       => $this->uid,
            ],
            'url'       => config("wx_domain").'/#/shareDetails?template=1&id='.$id,
        ]);
        ok();
    }
    
    /**
     * 我的求助列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     * @return array
     */
    public function mySeekHelpList(){
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page       = max($page,1);
        $pagesize   = $pagesize ? $pagesize : 10;
        $where = [
            'uid'       => $this->uid,
            'status'    => 0,
        ];
       
        $mySeekHelp = [];
        $mySeekHelpList = \app\common\model\ShareLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\ShareLog::where($where)->count();
        foreach($mySeekHelpList as $k => $v)
        {
            $shareInfo = \app\admin\model\Share::get($v['share_id'])->toArray();
            $status = \app\common\model\ShareLog::where(['log_id' => $v['id']])->order("status desc")->value("status");
            $mySeekHelp[$k] = [
                'id'            => $v['id'],
                'share_id'      => $v['share_id'],
                'reply_content' => $v['reply_content'],
                'add_time'      => format_time($v['add_time']),
                'status'        => $status,
                'share_info'    => [
                    'title'         => $shareInfo['title'],
                    'brief'         => $shareInfo['brief'],
                    'username'      => $shareInfo['username'],
                    'score'         => $shareInfo['score'],
                    'mobile'        => ($status == 1 || $status == 3) ? $shareInfo['mobile'] : substr($shareInfo['mobile'],0,7)."****",
                ],
            ];
        }
        ok([
            "items"     => $mySeekHelp,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    
    /**
     * 服务者反馈
     * @param int $id    服务ID
     * @param int $log_id    求助记录ID
     * @param string $content 帮助描述
     * @param int $agree 是否同意，1同意，0拒绝
     * @param string $token 用户TOKEN
     * @return array  
     */
    public function feedback(){
        $id         = intval(input("id"));
        $log_id     = intval(input("log_id"));
        $agree      = intval(input("agree"));
        $content    = trim(input("content"));
        if(!$id || !$log_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $shareInfo = \app\common\model\Share::get($id);
        
        if(!$shareInfo){
            $lang = lang("no_share_info");
            err(200,"no_share_info",$lang['code'],$lang['message']);
        }
        $shareInfo = $shareInfo->toArray();
        if($shareInfo['is_check'] != 1){
            $lang = lang("no_share_info");
            err(200,"no_share_info",$lang['code'],$lang['message']);
        }
        if($this->uid != $shareInfo['uid']){
            $lang = lang("not_your_share");
            err(200,"not_your_share",$lang['code'],$lang['message']);
        }
        $thisShareId = \app\common\model\ShareLog::where(['id'=>$log_id])->value("share_id");
        if($thisShareId != $id){
            $lang = lang("no_share_info");
            err(200,"no_share_info",$lang['code'],$lang['message']);
        }
        $shareLog = \app\common\model\ShareLog::where(['log_id'=>$log_id,'status'=>['in',[1,2]],'uid'=>$this->uid])->find();
        if($shareLog){
            $lang = lang("has_feedback");
            err(200,"has_feedback",$lang['code'],$lang['message']);
        }
        $seekuid = \app\common\model\ShareLog::where(['id'=>$log_id])->value("uid");
        
        $data = [
            'status'            => $agree ? 1 : 2,
            'share_id'          => $id,
            'uid'               => $this->uid,
            'add_time'          => time(),
            'reply_time'        => time(),
            'reply_content'     => $content,
            'log_id'            => $log_id,
        ];
        $inf = \app\common\model\ShareLog::create($data);
        if(!$inf){
            $lang = lang("feedback_fail");
            err(200,"feedback_fail",$lang['code'],$lang['message']);
        }
        
        $share_name = $this->auth->realname;
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$seekuid])->value('openid'),
            'temp_id'   => 4,
            'msg_data'  => [
                'first'     => $agree ? __("Your application has been approved by the service personnel") : __("Your application was rejected by the service personnel"),
                'keyword1'  => $share_name.($agree ? $shareInfo['mobile'] : ''),
                'keyword2'  => $content,
            ],
            'sys_msg'   => [
                'title'     => $agree ? __("Your application has been approved by the service personnel") : __("Your application was rejected by the service personnel"),
                'brief'     => $content.($agree ? $shareInfo['mobile'] : ''),
                'uid'       => $seekuid,
            ],
            'url'       => config("wx_domain").'/#/shareDetails?template=1&id='.$id,
        ]);
        ok();
    }
    /**
     * 求助者评价
     * @param int $id    服务ID
     * @param int $log_id    求助ID
     * @param int $scores   评价分数
     * @param string $reply_info 评价内容
     * @param string $token 用户TOKEN
     * @return array  
     */
    public function appraise()
    {
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $id        = intval(input("id"));
        $log_id    = intval(input("log_id"));
        if(!$id || !$log_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $shareInfo = \app\common\model\Share::get($id);
        
        if(!$shareInfo){
            $lang = lang("no_share_info");
            err(200,"no_share_info",$lang['code'],$lang['message']);
        }
        
        $shareInfo = $shareInfo->toArray();
        
        if($shareInfo['is_check'] != 1){
            $lang = lang("not_share_info");
            err(200,"not_share_info",$lang['code'],$lang['message']);
        }
        $shareLogInfo = \app\common\model\ShareLog::where(['share_id'=>$id,'log_id'=>$log_id,'status'=>2])->find();
        if($shareLogInfo){
            $lang = lang("has_refuse");
            err(200,"has_refuse",$lang['code'],$lang['message']);
        }
        $shareLogInfo = \app\common\model\ShareLog::where(['share_id'=>$id,'log_id'=>$log_id,'status'=>3])->find();
        if($shareLogInfo){
            $lang = lang("has_appraise");
            err(200,"has_appraise",$lang['code'],$lang['message']);
        }
        
        $scores = intval(input('scores'));
        $reply_info = trim(input('reply_info'));
       
        $shareLog = [
            'status'            => 3,
            'share_id'          => $id,
            'uid'               => $this->uid,
            'add_time'          => time(),
            'reply_time'        => time(),
            'reply_content'     => $reply_info,
            'log_id'            => $log_id,
            'score'             => $scores,
        ];
        $inf = \app\common\model\ShareLog::create($shareLog);
        $av_score = \app\common\model\ShareLog::where(['share_id'=>$id,'status'=>3])->avg('score');
        \app\common\model\Share::update(['score'=>$av_score,'status'=>0],['id'=>$id]);
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$shareInfo['uid']])->value('openid'),
            'temp_id'   => 4,
            'msg_data'  => [
                'first'     => __("Your service has been evaluated"),
                'keyword1'  => $shareInfo['title'],
                'keyword2'  => $reply_info,
            ],
            'sys_msg'   => [
                'title'     => __("Your service has been evaluated"),
                'brief'     => $reply_info,
                'uid'       => $shareInfo['uid'],
            ],
            'url'       => config("wx_domain").'/#/shareDetails?template=1&id='.$id,
        ]);
        $integralInfo = [
            'event_code'        => 'VolunteerService',
            'uid'               => $shareInfo['uid'],
            'scores'            => $scores,
            'note'              => $shareInfo['title'],
            'area_id'           => 0,
        ];
        \think\Hook::listen("integral",$integralInfo);
        ok();
    }
}
