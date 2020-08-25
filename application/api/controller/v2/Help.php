<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
/**
 * 求助接口
 */
class Help extends ApiCommon
{
    protected $noNeedLogin = ['appealList','helpList','index','helpWall','helptype'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    
    /**
     * 求助详情
     * @param int $id    求助ID
     * @return array
     */
    public function index(){
        $id        = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $helpInfo = \app\common\model\Help::get($id);
        $help = [];
        if($helpInfo){
            $helpInfo = $helpInfo->toArray();
    
            $help = [
                'id'            => $helpInfo['id'],
                'title'         => $helpInfo['title'],
                'content'       => $helpInfo['content'],
                'username'      => $helpInfo['username'],
                'mobile'        => substr($helpInfo['mobile'],0,3).'****'.substr($helpInfo['mobile'],7,4),
                'address'       => $helpInfo['address'],
                'img'           => json_decode($helpInfo['img'],true),
                'format_add_time'   => format_time($helpInfo['add_time'],"Y-m-d"),
                'format_hand_time'  => format_time($helpInfo['hand_time']),
                'status'        => $helpInfo['status'],
                'required_time' => $helpInfo['required_time'],
                'is_urgent'     => $helpInfo['is_urgent'],   
                "is_self"       => ($helpInfo['uid'] == $this->uid) ? 1 : 0,
                "help_type"     => \app\common\model\HelpType::where(['id'=>$helpInfo['help_type']])->value("name"),
            ];
            if($helpInfo['status'] == 1){
                $help["hid"] = $helpInfo['hid'];
                $help["help_name"] = \app\common\model\User::where(["id"=>$helpInfo['hid']])->value("realname");
            }
            $helps = \app\common\model\HelpLog::where(['help_id'=>$id,'status'=>1])->select();
            if($helps){
                foreach($helps as $k => $v){
                    $help['helps'][$k] = [
                        'is_help'   => $v['is_help'],
                        'name'      => $v['name'],
                        'avatar'    => \app\common\model\User::where(['id'=>$v['uid']])->value("avatar")
                    ];
                }
            }
            
            $helpLog = \app\common\model\HelpLog::where(['help_id'=>$id,'is_help'=>1])->select();
            if($helpLog){
                $helpLog = collection($helpLog)->toArray();
                $helpLogArr = [];
                foreach($helpLog as $k => $v){
                    $helpLogArr[$k] = [
                        'avatar'        => \app\common\model\User::where(['id'=>$v['uid']])->value("avatar"),
                        'format_add_time' => format_time($v['add_time']),
                        'content'       => $v['content'],
                        'name'          => $v['name'],
                        'scores'        => $v['scores'],
                        'status'        => $v['status'],
                        'is_help'       => $v['is_help'],
                    ];
                }
                $help['helplog'] = $helpLogArr;
            }
            if($helpInfo['status'] == 3){
                $help["reply_info"]    = $helpInfo['reply_info'];
                $help["scores"]        = $helpInfo['scores'];
                $help["format_reply_time"] = format_time($helpInfo['reply_time']);
            }
        }
        ok($help);
    }
    
    /**
     * 发布求助
     * @param string $title 求助主题
     * @param string $content 求助内容
     * @param array $img 求助相关图片
     * @param string $required_time 所需要时间
     * @param string $help_type 求助类型（1,2,3取值help_type）
     * @param int $is_urgent 是否紧急（1是，0否）
     * @param int $is_open 是否公开
     * @param string $username 求助人
     * @param string $mobile 手机号
     * @param string $address 地址
     * @param string $token 用户TOKEN
     * @return array
     */
    public function appeal()
    {
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $title          = trim(input("title"));  
        $content        = trim(input("content"));
        $img            = input("img/a");
        $required_time  = trim(input("required_time"));
        $help_type      = trim(input("help_type"));
        $is_urgent      = intval(input("is_urgent"));
        $username       = trim(input("username"));
        $mobile         = trim(input("mobile"));
        $address        = trim(input("address"));
        if(!$title){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $data = [
            'uid'           => $this->uid,
            'area_id'       => $this->auth->area_id,
            'title'         => $title,
            'content'       => $content,
            'img'           => is_array($img) ? json_encode($img) : '[]',
            'required_time' => $required_time,
            'help_type'     => $help_type,
            'is_urgent'     => $is_urgent,
            'username'      => $username ? $username : $this->auth->realname,
            'mobile'        => $mobile ? $mobile : $this->auth->mobile,
            'address'       => $address,
            'add_time'      => time(),
            'status'        => 0
        ];
        $inf = \app\common\model\Help::insert($data);
        if(!$inf){
            $lang = lang("post_help_err");
            err(200,"post_help_err",$lang['code'],$lang['message']);
        }
        ok();
    }
    
    /**
     * 关闭求助
     * @param int $id    求助ID
     * @param string $token 用户TOKEN
     * @return array
     */
    public function close()
    {
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $id = intval(input('id'));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $helpInfo = \app\common\model\Help::get($id);
        if(!$helpInfo){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
            
        $helpInfo = $helpInfo->toArray();
        
        if($helpInfo['uid'] != $this->uid){
            $lang = lang("not_your_help");
            err(200,"not_your_help",$lang['code'],$lang['message']);
        }
        $data = [
            'status'        => 4,
        ];
        $inf = \app\common\model\Help::update($data, ['id'=>$id]);
        if(!$inf){
            $lang = lang("close_fail");
            err(200,"close_fail",$lang['code'],$lang['message']);
        }
        
        ok();
    }
    
    /**
     * 求助列表
     * @param string $help_type 求助类型（1,2,3取值help_type）
     * @param int $status 状态（0求助中，1帮助中，2帮助完成，3评价完，4，关闭）
     * @param string $keywords 关键词
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @return array
     */
    public function appealList(){
        $help_type      = trim(input('help_type'));
        $status         = intval(input('status',-1));
        $keywords       = trim(input('keywords'));
        $page           = intval(input("page",1));
        $pagesize       = intval(input("pagesize",10));
        $orders         = trim(input("orders","add_time desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_check'   => 1,
        ];
        /* if($this->auth->area_id){
            $where['area_id']= ['in',\app\common\model\Cfg::childArea($this->auth->area_id)];
        } */
        if ($keywords) {
            $where['title|content'] = ['like',"%$keywords%"];
        }
        if ($help_type){
            $arr = explode(",",$help_type);
            if(is_array($arr)){
                $where[] = ['exp', \think\Db::raw("FIND_IN_SET(".$help_type.",help_type)")];
            }
        }
        if ($status != -1) {
            $where['status'] = $status;
        }
        
        $appeals = [];
        $appealList = \app\common\model\Help::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Help::where($where)->count();
        foreach($appealList as $k => $v)
        {
        	
            $appeals[$k] = [
                'avatar'        => \app\common\model\User::where(['id'=>$v['uid']])->value("avatar"),
                'id'            => $v['id'],
                'title'         => $v['title'],
                'content'       => $v['content'],
                'username'      => $v['username'],
                'mobile'        => substr($v['mobile'],0,7).'****',
                'address'       => $v['address'],
                'img'           => is_array(json_decode($v['img'],true)) ? current(json_decode($v['img'],true)) : '',
                'required_time' => $v['required_time'],
                'is_urgent'     => $v['is_urgent'],
                'format_add_time'   => format_time($v['add_time'],"Y-m-d"),
                'format_hand_time'  => format_time($v['hand_time']),
                'status'        => $v['status'],
                "hid"           => $v['hid'],
                "reply_info"    => $v['reply_info'],
                "scores"        => $v['scores'],
                "help_type"     => \app\common\model\HelpType::where(['id'=>$v['help_type']])->value("name"),
            ];
        }
        ok([
            "items"     => $appeals,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }
    
    
    
    
    
    /**
     * 我求助的列表
     * @param string $help_type 求助类型（1,2,3取值help_type）
     * @param int $status 状态（0求助中，1帮助中，2帮助完成，3评价完，4，关闭）
     * @param string $keywords 关键词
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     * @return array
     */
    public function myappealList(){
        
        $help_type      = trim(input('help_type'));
        $status         = intval(input('status',-1));
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
            $where['title|content|mobile'] = array('like',"%$keywords%");
        }
        if ($help_type){
            $arr = explode(",",$help_type);
            if(is_array($arr)){
                $where[] = ['exp', \think\Db::raw("FIND_IN_SET(".$help_type.",help_type)")];
            }
        }
        if ($status != -1) {
            $where['status'] = $status;
        }
        
        $myappeal = [];
        $myappealList = \app\common\model\Help::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Help::where($where)->count();
        foreach($myappealList as $k => $v)
        {
             
            $myappeal[$k] = [
                'avatar'        => \app\common\model\User::where(['id'=>$v['uid']])->value("avatar"),
                'id'            => $v['id'],
                'title'         => $v['title'],
                'content'       => $v['content'],
                'username'      => $v['username'],
                'mobile'        => substr($v['mobile'],0,7).'****',
                'address'       => $v['address'],
                'img'           => is_array(json_decode($v['img'],true)) ? current(json_decode($v['img'],true)) : '',
                'required_time' => $v['required_time'],
                'is_urgent'     => $v['is_urgent'],
                'format_add_time'   => format_time($v['add_time'],"Y-m-d"),
                'format_hand_time'  => format_time($v['hand_time']),
                'status'        => $v['status'],
                "hid"           => $v['hid'],
                "reply_info"    => $v['reply_info'],
                "is_check"      => $v['is_check'],
                "check_case"    => $v['check_case'],
                "scores"        => $v['scores'],
                "help_type"     => \app\common\model\HelpType::where(['id'=>$v['help_type']])->value("name"),
            ];
        }
        ok([
            "items"     => $myappeal,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    
   /**
    * 帮助
    * @param int $id    求助ID
    * @param string $content 帮助描述
    * @param string $token 用户TOKEN
    * @return array 
    */
    public function help()
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
        $helpInfo = \app\common\model\Help::get($id);
        if(!$helpInfo){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        //判断是否是志愿者身份
        if(!$this->auth->is_volunteer){
            $lang = lang("not_volunteer");
            err(200,"not_volunteer",$lang['code'],$lang['message']);
        }
        $volunteer = \app\common\model\Volunteer::where(['uid'=>$this->uid])->find();
        /* $volunteer = \app\common\model\Volunteer::where(['uid'=>$this->uid])->find();
        if($volunteer){
            if($volunteer['is_check'] != 1){
                $lang = lang("is_volunteer_error");
                err(200,"is_volunteer_error",$lang['code'],$lang['message']);
            }            
        }else{
            $lang = lang("is_volunteer_error");
            err(200,"is_volunteer_error",$lang['code'],$lang['message']);
        } */
        
        $helpInfo = $helpInfo->toArray();
        if($helpInfo['is_check'] != 1){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        if($helpInfo['status'] == 1){
            $lang = lang("has_somebody_help");
            err(200,"has_somebody_help",$lang['code'],$lang['message']);
        }elseif($helpInfo['status'] > 1){
            $lang = lang("has_finished_help");
            err(200,"has_finished_help",$lang['code'],$lang['message']);
        }
        if($helpInfo['uid'] == $this->uid){
            $lang = lang("can_not_helpself");
            err(200,"can_not_helpself",$lang['code'],$lang['message']);
        }
        $helpLog = \app\common\model\HelpLog::where(['help_id'=>$id,'uid'=>$this->uid])->find();
        if($helpLog){
            $lang = lang("has_help");
            err(200,"has_help",$lang['code'],$lang['message']);
        }
        $help_name = $volunteer['name'];
        $appeal_name = $helpInfo['username'];
        
        $helpLog = [
            'help_id'       => $id,
            'uid'           => $this->uid,
            'add_time'      => time(),
            'status'        => 1,
            'is_help'       => 0,
            'content'       => $content,
            'name'          => $help_name,
        ];
        $inf = \app\common\model\HelpLog::create($helpLog);
        if(!$inf){
            $lang = lang("help_fail");
            err(200,"help_fail",$lang['code'],$lang['message']);
        }
        //通知求助人
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$helpInfo['uid']])->value('openid'),
            'temp_id'   => 2,
            'msg_data'  => [
                'first'     => __("Your request for help has been received %s",$helpInfo['title']),
                'keyword1'  => format_time(time()),
                'keyword2'  => $helpInfo['content'],
                'keyword3'  => $appeal_name,
                'keyword4'  => '',
                'remark'    => '',
            ],
            'sys_msg'   => [
                'title'     => __("Your request for help has been received %s",$helpInfo['title']),
                'brief'     => __("Your request for help has been received %s",$helpInfo['title']),
                'uid'       => $helpInfo['uid'],
            ],
            'url'       => config("wx_domain").'/#/helpDetails?template=1&id='.$id,
        ]);
        //通知帮助人
        
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$this->uid])->value('openid'),
            'temp_id'   => 3,
            'msg_data'  => [
                'first'     => __("You have received a new application for help"),
                'keyword1'  => $help_name,
                'keyword2'  => $helpInfo['content'],
                'keyword3'  => $helpInfo['title'],
                'keyword4'  => format_time(time()),
                'remark'    => __("Contact")."：".$helpInfo['mobile'],
            ],
            'sys_msg'   => [
                'title'     => __("You have received a new application for help"),
                'brief'     => __("You have received a new application for help"),
                'uid'       => $this->uid,
            ],
            'url'       => config("wx_domain").'/#/helpDetails?template=1&id='.$id,
        ]);
        ok();
    }
    
    /**
     * 我帮助的列表
     * @param string $help_type 求助类型（1,2,3取值help_type）
     * @param int $status 状态（0求助中，1帮助中，2帮助完成，3评价完，4，关闭）
     * @param string $keywords 关键词
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     * @return array
     */
    public function myhelpList(){
    
        $help_type      = trim(input('help_type'));
        $status         = intval(input('status',-1));
        $keywords   = trim(input('keywords'));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_check'   => 1,
        ];
        $helpLog  = \app\common\model\HelpLog::where(['uid'=>$this->uid,'status'=>1])->select();
        $helpLodIds = [];
        $isHelpArr = [];
        if($helpLog){
            foreach(collection($helpLog)->toArray() as $k => $v){
                $helpLodIds[] = $v['help_id'];
                $isHelpArr[$v['help_id']] = $v['is_help']; 
            }
        }
        if($helpLodIds){
            $where['id'] = ['in',$helpLodIds];
        }
        if ($keywords) {
            $where['title|content|mobile'] = array('like',"%$keywords%");
        }
        if ($help_type){
            $arr = explode(",",$help_type);
            if(is_array($arr)){
                $where[] = ['exp', \think\Db::raw("FIND_IN_SET(".$help_type.",help_type)")];
            }
        }
        if ($status != -1) {
            $where['status'] = $status;
        }
    
        $myhelp = [];
        $myhelpList = \app\common\model\Help::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Help::where($where)->count();
        foreach($myhelpList as $k => $v)
        {
            $_thisHelpLog = [];
            $thisWhere = $v['status'] == 0 ? ['help_id'=>$v['id'],"uid" => $this->uid] : ['help_id'=>$v['id'],"is_help" => 1];
            $thisHelpLog = \app\common\model\HelpLog::where($thisWhere)->select();
            if($thisHelpLog){
                foreach(collection($thisHelpLog)->toArray() as $key => $val){
                    $_thisHelpLog[] = [
                        'avatar'        => \app\common\model\User::where(['id'=>$val['uid']])->value("avatar"),
                        'format_add_time' => format_time($val['add_time']),
                        'content'       => $val['content'],
                        'name'          => $val['name'],
                        'scores'        => $val['scores'],
                        'status'        => $val['status'],
                        'is_help'       => $val['is_help'],
                    ];
                }
            }
            $is_help = isset($isHelpArr[$v['id']]) ? $isHelpArr[$v['id']] : 0;
            $myhelp[$k] = [
                'id'            => $v['id'],
                'avatar'        => \app\common\model\User::where(['id'=>$v['uid']])->value("avatar"),
                'title'         => $v['title'],
                'content'       => $v['content'],
                'username'      => $v['username'],
                'mobile'        => $is_help ? $v['mobile'] : substr($v['mobile'],0,7).'****',
                'address'       => $v['address'],
                'img'           => is_array(json_decode($v['img'],true)) ? current(json_decode($v['img'],true)) : '',
                'required_time' => $v['required_time'],
                'is_urgent'     => $v['is_urgent'],
                'format_add_time'   => format_time($v['add_time'],"Y-m-d"),
                'format_hand_time'  => format_time($v['hand_time']),
                'status'        => $v['status'],
                "hid"           => $v['hid'],
                "reply_info"    => $v['reply_info'],
                "scores"        => $v['scores'],
                "helplog"       => $_thisHelpLog,
                "is_help"       => $is_help,
                "help_type"     => \app\common\model\HelpType::where(['id'=>$v['help_type']])->value("name"),
            ];
        }
        ok([
            "items"     => $myhelp,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    /**
     * 帮助者列表
     * @param int $id    求助ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @return array 
     */
    
    public function helpList(){
        $page           = intval(input("page",1));
        $pagesize       = intval(input("pagesize",10));
        $orders         = trim(input("orders","add_time desc"));
        $id             = intval(input("id"));
        $page       = max($page,1);
        $pagesize   = $pagesize ? $pagesize : 10;
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $where = [
            'status'    => 1,
            'help_id'   => $id,
        ];
        
        
        $helps = [];
        $helpList = \app\common\model\HelpLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\HelpLog::where($where)->count();
        foreach($helpList as $k => $v)
        {
             
            $helps[$k] = [
                'id'            => $v['id'],
                'uid'           => $v['uid'],
                'avatar'        => \app\common\model\User::where(['id'=>$v['uid']])->value("avatar"),
                'volunteer'     => \app\common\model\Volunteer::where(['uid'=>$v['uid']])->field("brief,card,id,jobtime,name,skill,composite_score")->find(),
                'name'          => $v['name'],
                'content'       => $v['content'],
                'is_help'       => $v['is_help'],
                'format_add_time'      => format_time($v['add_time']),
            ];
        }
        ok([
            "items"     => $helps,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    /**
     * 选择帮助者
     * @param int $id    求助ID
     * @param int $log_id 帮助记录ID
     * @param string $content 帮助描述
     * @param string $token 用户TOKEN
     * @return array 
     */
    public function screen(){
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $id        = intval(input("id"));
        $log_id        = intval(input("log_id"));
        if(!$id || !$log_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $helpInfo = \app\common\model\Help::get($id);
        
        if(!$helpInfo){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        $helpInfo = $helpInfo->toArray();
        if($helpInfo['is_check'] != 1){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        if($helpInfo['status'] != 0){
            $lang = lang("status_error");
            err(200,"status_error",$lang['code'],$lang['message']);
        }
        $log = \app\common\model\HelpLog::get($log_id);
        if(!$log){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        if($log['help_id'] != $id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $data = [
            'status'        => 1,
            'hid'           => $log['uid'],
            'hand_time'     => time(),
        ];
        $inf = \app\common\model\Help::update($data, ['id'=>$id]);
        if(!$inf){
            $lang = lang("screen_helper_fail");
            err(200,"screen_helper_fail",$lang['code'],$lang['message']);
        }
        \app\common\model\HelpLog::update(['is_help' => 1],['id'=>$log_id]);
        $help_name = \app\common\model\Volunteer::where(['uid'=>$log['uid']])->value("name");
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$helpInfo['uid']])->value('openid'),
            'temp_id'   => 4,
            'msg_data'  => [
                'first'     => __("The helper chose you to help him %s",$helpInfo['title']),
                'keyword1'  => $help_name,
                'keyword2'  => $helpInfo['content'],
            ],
            'sys_msg'   => [
                'title'     => __("The helper chose you to help him %s",$helpInfo['title']),
                'brief'     => __("The helper chose you to help him %s",$helpInfo['title']),
                'uid'       => $log['uid'],
            ],
            'url'       => config("wx_domain").'/#/helpDetails?template=1&id='.$id,
        ]);
        ok();
    }
    /**
     * 帮助者反馈
     * @param int $id    求助ID
     * @param string $content 帮助描述
     * @param string $token 用户TOKEN
     * @return array  
     */
    public function reply(){
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $id         = intval(input("id"));
        $content    = trim(input("content"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $helpInfo = \app\common\model\Help::get($id);
        
        if(!$helpInfo){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        $helpInfo = $helpInfo->toArray();
        if($helpInfo['is_check'] != 1){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        if($helpInfo['status'] < 1 || $helpInfo['status'] >2){
            $lang = lang("status_error");
            err(200,"status_error",$lang['code'],$lang['message']);
        }
        $data = [
            'status'        => 2,
        ];
        $inf = \app\common\model\Help::update($data, ['id'=>$id]);
        if(!$inf){
            $lang = lang("reply_fail");
            err(200,"reply_fail",$lang['code'],$lang['message']);
        }
        $helpLog = [
            'help_id'       => $id,
            'uid'           => $this->uid,
            'add_time'      => time(),
            'status'        => 2,
            'is_help'       => 1,
            'content'       => $content,
            'name'          => $this->auth->realname,
        ];
        $inf = \app\common\model\HelpLog::create($helpLog);
        $help_name = $this->auth->realname;
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$helpInfo['uid']])->value('openid'),
            'temp_id'   => 4,
            'msg_data'  => [
                'first'     => __("Feedback has been received please comment in time"),
                'keyword1'  => $help_name,
                'keyword2'  => $content,
            ],
            'sys_msg'   => [
                'title'     => __("Feedback has been received please comment in time"),
                'brief'     => $content,
                'uid'       => $helpInfo['uid'],
            ],
            'url'       => config("wx_domain").'/#/helpDetails?template=1&id='.$id,
        ]);
        ok();
    }
    /**
     * 评价
     * @param int $id    求助ID
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
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $helpInfo = \app\common\model\Help::get($id);
        
        if(!$helpInfo){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        if($helpInfo['uid'] != $this->uid){
            $lang = lang("not_your_help");
            err(200,"not_your_help",$lang['code'],$lang['message']);
        }
        $helpInfo = $helpInfo->toArray();
        if($helpInfo['is_check'] != 1){
            $lang = lang("not_help_info");
            err(200,"not_help_info",$lang['code'],$lang['message']);
        }
        if($helpInfo['status'] == 3){
            $lang = lang("has_appraise");
            err(200,"has_appraise",$lang['code'],$lang['message']);
        }
        if($helpInfo['status'] < 1 || $helpInfo['status'] > 2){
            $lang = lang("status_error");
            err(200,"status_error",$lang['code'],$lang['message']);
        }
        
        $scores = intval(input('scores'));
        $reply_info = trim(input('reply_info'));
        $data = [
            'scores'        => $scores,
            'status'        => 3,
            'reply_time'    => time(),
            'reply_info'    => $reply_info
        ];
        $inf = \app\common\model\Help::update($data, ['id'=>$id]);
        if(!$inf){
            $lang = lang("appraise_fail");
            err(200,"appraise_fail",$lang['code'],$lang['message']);
        }
        $helpLog = [
            'help_id'       => $id,
            'uid'           => $this->uid,
            'add_time'      => time(),
            'status'        => 3,
            'is_help'       => 1,
            'content'       => $reply_info,
            'name'          => $this->auth->realname,
        ];
        $inf = \app\common\model\HelpLog::create($helpLog);
        notice([
            'openid'    => \app\common\model\User::where(['id'=>$helpInfo['hid']])->value('openid'),
            'temp_id'   => 4,
            'msg_data'  => [
                'first'     => __("He commented on you %s",$helpInfo['title']),
                'keyword1'  => $helpInfo['title'],
                'keyword2'  => $reply_info,
            ],
            'sys_msg'   => [
                'title'     => __("He commented on you %s",$helpInfo['title']),
                'brief'     => $reply_info,
                'uid'       => $helpInfo['hid'],
            ],
            'url'       => config("wx_domain").'/#/helpDetails?template=1&id='.$id,
        ]);
        $integralInfo = [
            'event_code'        => 'VolunteerHelp',
            'uid'               => $helpInfo['hid'],
            'scores'            => $scores,
            'note'              => $helpInfo['title'],
            'area_id'           => 0,
        ];
        \think\Hook::listen("integral",$integralInfo);
        ok();
    }
    
    
    /**
     * 帮扶墙
     * @param string $keywords 关键词
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @return array
     */
    public function helpWall(){
        $keywords       = trim(input('keywords'));
        $page           = intval(input("page",1));
        $pagesize       = intval(input("pagesize",10));
        $orders         = trim(input("orders","add_time desc"));
        $where = [
            'is_help'   => 1,
            'status'    => ['in',[1,3]],
        ];
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        
        $helpWall = [];
        $helpWallList = \app\common\model\HelpLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\HelpLog::where($where)->count();
        foreach($helpWallList as $k => $v)
        {
            $content = '';
            
            $helpInfo = \app\common\model\Help::where(['id'=>$v['help_id']])->find()->toArray();
            $helpName = \app\common\model\User::where(['id'=>$helpInfo['hid']])->value("nickname");
            $appealName = $helpInfo['username'];
            if($v['status'] == 1){
                $content = "<span class='helper'>".$helpName."</span>".__("Help %s","<span class='appeal'>".$appealName."</span>");
            }elseif($v['status'] == 3){
                $content = "<span class='helper'>".$helpName."</span>".__("Help %s","<span class='appeal'>".$appealName."</span>").__("Get scores %s","<span class='score'>".$v['scores']."</sapn>");
            }
            $helpWall[$k] = [
                'content'            => $content,
            ];
        }
        ok([
            "items"     => $helpWall,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    
    /**
     * 求助类型
     * @param string $keywords 关键词
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @return array
     */
    public function helptype(){
        $keywords       = trim(input('keywords'));
        $page           = intval(input("page",1));
        $pagesize       = intval(input("pagesize",20));
        $orders         = trim(input("orders","display desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [];
        $helptype = [];
        $helptypeList = \app\common\model\HelpType::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\HelpType::where($where)->count();
        foreach($helptypeList as $k => $v)
        {
            
            $helptype[$k] = [
                'id'            => $v['id'],
                'name'          => $v['name'],
            ];
        }
        ok([
            "items"     => $helptype,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
   
    /**
     * 一键求助（TV）
     * @param string $cardNo 机顶盒号
     * @param string $token 用户TOKEN 
     * 
     */
    public function onkey(){
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $cardNo = trim(input("cardNo"));
        $data = [
            'name'      => $this->auth->realname,
            'addtime'   => time(),
            'uid'       => $this->auth->id,
            'mobile'    => $this->auth->mobile,
            'cardNo'    => $cardNo
        ];
        \app\common\model\HelpOnekey::insert($data);
        ok();
    }
}
