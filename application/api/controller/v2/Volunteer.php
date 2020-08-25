<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use app\common\model\Volunteer as Volunteer_mod;
use app\common\model\VolunteerGroup;

/**
 * 志愿者接口
 */
class Volunteer extends ApiCommon
{
    protected $noNeedLogin = ['volunteerList','volunteer','volunteerGroupList','volunteerGroup'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }

    /**
     * 志愿者列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param int $group_id     团体ID
     * @param string $keyword   关键词
     * @return array
     */
    public function volunteerList(){
        $group_id   = intval(input("group_id"));
        $keyword    = trim(input("keyword"));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","jobtime desc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_check'   => 1,
        ];
        if($group_id){
            $volunteerAccess = \app\common\model\VolunteerGroupAccess::where(['gid'=>$group_id])->select();
            $volunteers = [];
            if($volunteerAccess){
                foreach(collection($volunteerAccess)->toArray() as $k => $v){
                    $volunteers[] = $v['vid'];
                }
                $where['id'] = ['in',$volunteers];
            }
        }
        if($keyword){
            $where['name'] = ['like','%'.$keyword.'%'];
        }
        $orders = "jobtime desc“";
        $volunteerList = \app\common\model\Volunteer::where($where)->page($page)->limit($pagesize)->order("jobtime desc“")->select();
        $total = \app\common\model\Volunteer::where($where)->count();
        $volunteer = [];
        foreach($volunteerList as $k => $v){
            $volunteer[$k] = [
                'head_img'  => $v['head_img'],
                'id'        => $v['id'],
                'jobtime'   => round($v['jobtime'],2),
                'join_time' => format_time($v['join_time']),
                'name'      => $v['name'],
                'scores'    => $v['scores'],
                'work'      => $v['work'],
                'serial_number' => $v['serial_number'],
                'honor_time' => $v['honor_time'],
                'rank'       => $v['rank'],
            ];
        }
        ok([
            "items"     => $volunteer,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }


    /**
     * 志愿者详情
     * @param int $id      志愿者ID
     * @return array
     */
    public function volunteer(){
        $id        = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $volunteerInfo = Volunteer_mod::get($id);
        if(!$volunteerInfo){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $volunteerInfo = $volunteerInfo->toArray();
        $volunteerGroupAccess = \app\common\model\VolunteerGroupAccess::where(['vid'=>$id])->select();
        $group_titles =[];
        if($volunteerGroupAccess){
            foreach (collection($volunteerGroupAccess)->toArray() as $k => $v) {
                $group_titles[] = \app\common\model\VolunteerGroup::where(['id'=>$v['gid']])->value("title");
            }
        }
        $volunteer = [
            'head_img'  => $volunteerInfo['head_img'],
            'id'        => $volunteerInfo['id'],
            'jobtime'   => round($volunteerInfo['jobtime']/3600,2),
            'join_time' => format_time($volunteerInfo['join_time']),
            'skill'     => $volunteerInfo['skill'],
            'name'      => $volunteerInfo['name'],
            'groups'    => $group_titles,
            'scores'    => $volunteerInfo['scores'],
            'serial_number' => $volunteerInfo['serial_number'],
            'honor_time' => $volunteerInfo['honor_time'],
            'rank'       => $volunteerInfo['rank'],
        ];
        ok($volunteer);
    }


    /**
     * 志愿者团体列表
     * @param int $area_id   区域ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param int $group_id     团体ID
     * @param string $keyword   关键词
     * @return array
     */
    public function volunteerGroupList(){
        $keyword    = trim(input("keyword"));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10000));
        $orders     = trim(input("orders","id asc"));
        $area_id    = intval(input("area_id",$this->auth->area_id));
        $where = ['is_check'=>1];
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        if($area_id){
            $where['area_id'] = ['in',\app\common\model\Cfg::childArea($area_id)];
        }
        if($keyword){
            $where['title'] = ['like','%'.$keyword.'%'];
        }
        $volunteerGroup = [];
        $volunteerGroupList = \app\common\model\VolunteerGroup::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\VolunteerGroup::where($where)->count();
        foreach($volunteerGroupList as $k => $v){
            $volunteerGroup[$k] = [
                'id'        => $v['id'],
                'title'     => $v['title'],
                'condition' => $v['condition'],
                'address'   => $v['address'],
                'work_unit' => $v['work_unit'],
                'mobile'    => $v['mobile'],
                'master'    => $v['master'],
                'addtime'   => format_time($v['addtime']),
                'content'   => $v['content'],
                'logo'      => $v['logo'],
                'volunteers'=> \app\common\model\VolunteerGroupAccess::where(['gid'=>$v['id'],'is_pass'=>1])->count(),
                //'activitys' => \app\common\model\Activity::where(['uid'=>$v['uid'],'is_volunteer'=>1,'is_check'=>1])->count(),
                'activitys'     => $v['activity_num'],
                'credit_hours'  => $v['credit_hours'],
                'group_rank'    => $v['group_rank'],
                'vol_num'       => $v['vol_num'],
            ];
        }
        ok([
            "items"     => $volunteerGroup,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }


    /**
     * 志愿者团体详情
     * @param int $id      志愿者团体ID
     * @return array
     */
    public function volunteerGroup(){
        $id        = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $volunteer = [];
        $volunteerGroupInfo = \app\common\model\VolunteerGroup::get($id);
        if($volunteerGroupInfo){
            $volunteerGroupInfo = $volunteerGroupInfo->toArray();
            $volunteer = [
                'title'     => $volunteerGroupInfo['title'],
                'id'        => $volunteerGroupInfo['id'],
                'master'    => $volunteerGroupInfo['master'],
                'addtime'   => format_time($volunteerGroupInfo['addtime']),
                'content'   => $volunteerGroupInfo['content'],
                'logo'      => $volunteerGroupInfo['logo'],
                'condition' => $volunteerGroupInfo['condition'],
                'address'   => $volunteerGroupInfo['address'],
                'work_unit' => $volunteerGroupInfo['work_unit'],
                'mobile'    => $volunteerGroupInfo['mobile'],
                'credit_hours'  => $volunteerGroupInfo['credit_hours'],
                'group_rank'    => $volunteerGroupInfo['group_rank'],
                'vol_num'       => $volunteerGroupInfo['vol_num'],
            ];
        }

        ok($volunteer);
    }

    /**
     * 申请志愿者
     * @param string $name      志愿者名称（必）
     * @param string $avatar      志愿者头象（必）
     * @param string $skill      志愿者技能
     * @param string $brief      志愿者简介
     * @param string $card      志愿者身份证号（必）
     * @param string $mobile      志愿者手机号（必）
     * @param string $work      志愿者工作单位
     * @param array $group_id      加入的团体
     * @param string $token   用户TOKEN
     * @return array
     */
    public function becomeVolunteer(){
        if(!$this->is_realname){
            $lang = lang("please_real_name_authentication");
            err(200, "please_real_name_authentication", $lang['code'], $lang['message']);
        }
        $name = trim(input("name"));
        $avatar = trim(input("avatar"));
        $skill = trim(input("skill"));
        $card = trim(input("card"));
        $mobile = trim(input("mobile"));
        $brief = trim(input("brief"));
        $work = trim(input("work"));
        $group_id = input("group_id/a");
        if(!$card){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $volunteerInfo = Volunteer_mod::get(['card'=>$card]);
        if($volunteerInfo){
            $volunteerInfo = $volunteerInfo->toArray();
            $vol_id = $volunteerInfo['id'];
            Volunteer_mod::update(['uid'=>$this->uid], ['card'=>$card]);
        }else{
            $data = [
                'name'      => $name ? $name : $this->auth->realname,
                'skill'     => $skill,
                'head_img'  => $avatar ? $avatar : $this->auth->avatar,
                'card'      => $card,
                'work'      => $work,
                'brief'     => $brief,
                'area_id'   => $this->auth->area_id,
                'mobile'    => $mobile ? $mobile : $this->auth->mobile,
                'uid'       => $this->uid,
                'join_time' => time(),
            ];
            $vol_id = Volunteer_mod::create($data);
        }
        if($vol_id){
            if(is_array($group_id)){
                foreach ($group_id as $vo) {
                    $result = \app\common\model\VolunteerGroupAccess::create([
                        'gid'   => $vo,
                        'vid'   => $vol_id
                    ]);
                }
                $data = ['vid'=>$vol_id];
                if($volunteerInfo){
                    $data['is_volunteer'] = 1;
                }
                \app\common\model\User::update($data,['id'=>$this->uid]);
            }
        }
        ok();
    }


    /**
     * 加入志愿团体(多团体)
     * @param array $group_id      加入的团体
     * @param string $token   用户TOKEN
     * @return array
     *
     */
    public function joinGroup(){

        $uid = $this->uid;
        $group_id = input("group_id/a");
        $volunteerInfo = Volunteer_mod::get(['uid'=>$uid]);
        if($volunteerInfo){
            $volunteerInfo = $volunteerInfo->toArray();
            $vol_id = $volunteerInfo['id'];
            if(is_array($group_id)){
                foreach ($group_id as $vo) {
                    $data['gid'] = $vo;
                    $data['vid'] = $vol_id;
                    $volunteergroupaccess = \app\common\model\VolunteerGroupAccess::where($data)->find();
                    if($volunteergroupaccess){
                        $lang = lang("has_join_group");
                        err(200,"has_join_group",$lang['code'],$lang['message']);
                    }
                    $result = \app\common\model\VolunteerGroupAccess::create($data);
                    $dep_id = VolunteerGroup::where(['id' => $vo])->value('dep_id');
                    if($dep_id){
                        $this->joinzyhGroup($dep_id);
                    }
                    if(!$result){
                        $lang = lang("group_join_fail");
                        err(200,"group_join_fail",$lang['code'],$lang['message']);
                    }
                }
            }else{
                $data['gid'] = $group_id;
                $data['vid'] = $vol_id;
                $volunteergroupaccess = \app\common\model\VolunteerGroupAccess::where($data)->find();
                if($volunteergroupaccess){
                    $lang = lang("has_join_group");
                    err(200,"has_join_group",$lang['code'],$lang['message']);
                }
                $result = \app\common\model\VolunteerGroupAccess::create($data);
                if(!$result){
                    $lang = lang("group_join_fail");
                    err(200,"group_join_fail",$lang['code'],$lang['message']);
                }
            }
        }else{
            $lang = lang("not_volunteer");
            err(200,"not_volunteer",$lang['code'],$lang['message']);
        }

       ok();
    }


    //加入组织
    private function joinzyhGroup($deptId){
        $token = trim(input('zyh_token'));
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/signup'; //访问方法
        $apiParam = array('token'=>$token,'deptId'=>$deptId);//访问参数
        $res = $zyh::getData($apiFun, $apiParam);
        if($res['errCode'] !='0000'){
            $lang = lang("group_join_fail");
            err(200,"group_join_fail",$res['errCode'],$res['message']);
        }
    }

    //组织排行
    public function grank(){
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/rank.'; //访问方法
        $page = intval(input('page',1));
        $rows = intval(input('rows',10));
        $params = ['page'=>$page,'rows'=>$rows];
        $apiParam = array('page'=>$page,'rows'=>$rows);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
    }

    /**
     * 退出志愿团体
     * @param int $group_id      团体ID
     * @param string $token   用户TOKEN
     * @return array
     *
     */
    public function cancelGroup(){
        $uid = $this->uid;
        $group_id = intval(input("group_id"));
        $volunteerInfo = Volunteer_mod::get(['uid'=>$uid]);
        if($volunteerInfo){
            $volunteerInfo = $volunteerInfo->toArray();
            $vid = $volunteerInfo['id'];
            $result = \app\common\model\VolunteerGroupAccess::destroy(['vid'=>$vid,'gid'=>$group_id]);
            if($volunteerInfo['dep_id']){
                $this->outGroup($volunteerInfo['dep_id']);
            }
        }
        if(!$result){
            $lang = lang("group_cancel_fail");
            err(200,"group_cancel_fail",$lang['code'],$lang['message']);
        }
        ok();
    }

    //退出组织
    private function outGroup($deptId){
        $token = trim(input('zyh_token'));
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/signout.'; //访问方法
        $apiParam = array('token'=>$token,'deptId'=>$deptId);//访问参数
        $res = $zyh::getData($apiFun, $apiParam);
        if($res['errCode'] !='0000'){
            $lang = lang("group_cancel_fail");
            err(200,"group_cancel_fail",$lang['code'],$lang['message']);
        }
    }

    /**
     * 修改志愿团体信息
     * @param int $group_id      团体ID
     * @param string $token   用户TOKEN
     * @param string $title      团体名称（必）
     * @param string $logo      团体logo
     * @param string $condition   加入条件
     * @param string $services  服务内容
     * @param string $address   地址
     * @param string $work_unit   工作单位
     * @param string $content   团体内容
     * @return array
     *
     */
    public function editGroup(){
        $uid = $this->uid;
        $group_id = intval(input("group_id"));
        $title          = trim(input("title"));
        $logo           = trim(input("logo"));
        $services       = trim(input("services"));
        $condition      = trim(input("condition"));
        $address        = trim(input("address"));
        $work_unit      = trim(input("work_unit"));
        $content      = trim(input("content"));
        if(!$title){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        if(!$this->auth->is_volunteer){
            $lang = lang("not_volunteer");
            err(200,"not_volunteer",$lang['code'],$lang['message']);
        }
        $params = [
            'title'          => $title,
            'logo'           => $logo,
            'services'       => $services,
            'condition'      => $condition,
            'address'        => $address,
            'work_unit'      => $work_unit,
            'content'        => $content
        ];
        if($address){
            list($x,$y) = jwd($address);
            $params['x'] = $x;
            $params['y'] = $y;
        }
        $inf = \app\common\model\VolunteerGroup::update($params,['id'=>$group_id,'uid'=>$this->uid]);
        if(!$inf){
            $lang = lang("edit_group_error");
            err(200,"edit_group_error",$lang['code'],$lang['message']);
        }
        ok();
    }

    /**
     * 申请团体
     * @param int $area_id      区域ID
     * @param string $title      团体名称（必）
     * @param string $logo      团体logo
     * @param string $condition   加入条件
     * @param string $services  服务内容
     * @param string $address   地址
     * @param string $work_unit   工作单位
     * @param string $content   团体内容
     * @param string $token   用户TOKEN
     * @return array
     *
     */
    public function applicantGroup(){
        $area_id        = intval(input("area_id"));
        $title          = trim(input("title"));
        $logo           = trim(input("logo"));
        $services       = trim(input("services"));
        $condition      = trim(input("condition"));
        $address        = trim(input("address"));
        $work_unit      = trim(input("work_unit"));
        $content      = trim(input("content"));
        if(!$title){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        if(!$this->auth->is_volunteer){
            $lang = lang("not_volunteer");
            err(200,"not_volunteer",$lang['code'],$lang['message']);
        }
        $volunteer = \app\common\model\Volunteer::where(['uid'=>$this->uid])->find();
        $params = [
            'area_id'        => $area_id,
            'title'          => $title,
            'logo'           => $logo,
            'mobile'         => $volunteer['mobile'],
            'master'         => $volunteer['name'],
            'idcard'         => $volunteer['card'],
            'services'       => $services,
            'condition'      => $condition,
            'address'        => $address,
            'work_unit'      => $work_unit,
            'uid'            => $this->uid,
            'content'        => $content
        ];
        if($address){
            list($x,$y) = jwd($address);
            $params['x'] = $x;
            $params['y'] = $y;
        }
        $inf = \app\common\model\VolunteerGroup::create($params);
        if(!$inf){
            $lang = lang("applicant_group_error");
            err(200,"applicant_group_error",$lang['code'],$lang['message']);
        }
        ok();
    }


    /**
     * 团体的志愿者列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $keyword   关键词
     * @param string $token 用户TOKEN
     * @return array
     */
    public function myVolunteerList(){
        $keyword    = trim(input("keyword"));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","id asc"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_check'   => 1,
        ];
        if(!$this->auth->is_volunteer_group){
            $lang = lang("not_volunteer_group_master");
            err(200,"not_volunteer_group_master",$lang['code'],$lang['message']);
        }
        $group_id = \app\common\model\VolunteerGroup::where(['uid'=>$this->uid])->value('id');
        $is_pass = [];
        if($group_id){
            $volunteerAccess = \app\common\model\VolunteerGroupAccess::where(['gid'=>$group_id])->select();
            $volunteers = [];
            if($volunteerAccess){
                foreach(collection($volunteerAccess)->toArray() as $k => $v){
                    $volunteers[] = $v['vid'];
                    $is_pass[$v['vid']] = $v['is_pass'];
                }
                $where['id'] = ['in',$volunteers];
            }else{
                $where['id'] = 0 ;
            }
        }
        if($keyword){
            $where['name'] = ['like','%'.$keyword.'%'];
        }
        $volunteerList = \app\common\model\Volunteer::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Volunteer::where($where)->count();
        $volunteer = [];
        foreach($volunteerList as $k => $v){
            $volunteer[$k] = [
                'head_img'  => $v['head_img'],
                'id'        => $v['id'],
                'jobtime'   => round($v['jobtime']/3600,2),
                'join_time' => format_time($v['join_time']),
                'name'      => $v['name'],
                'scores'    => $v['scores'],
                'work'      => $v['work'],
                'is_pass'   => !empty($is_pass[$v['id']]) ? $is_pass[$v['id']] : 0,
            ];
        }
        ok([
            "items"     => $volunteer,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

    /**
     * 通过申请加入志愿团体
     * @param int $volunteer_id      志愿者ID
     * @param string $token   用户TOKEN
     * @return array
     *
     */
    public function passVolunteer(){
        $volunteer_id = intval(input("volunteer_id"));
        if(!$this->auth->is_volunteer_group){
            $lang = lang("not_volunteer_group_master");
            err(200,"not_volunteer_group_master",$lang['code'],$lang['message']);
        }
        $group_id = \app\common\model\VolunteerGroup::where(['uid'=>$this->uid])->value('id');
        $where = [
            'gid'       => $group_id,
            'vid'       => $volunteer_id,
        ];
        $group_name = \app\common\model\VolunteerGroup::where(['uid'=>$this->uid])->value('title');
        $inf = \app\common\model\VolunteerGroupAccess::update(['is_pass'=>1],$where);
        if($inf){
            notice([
                'sys_msg'   => [
                    'title'     => __("Group %s has passed your application",$group_name),
                    'brief'     => __("Group %s has passed your application",$group_name),
                    'uid'       => \app\common\model\Volunteer::where(['id'=>$volunteer_id])->value("uid"),
                ],
            ]);
        }
        ok();
    }

    /**
     * 团体移除志愿者
     * @param int $volunteer_id      志愿者ID
     * @param string $token   用户TOKEN
     * @return array
     *
     */
    public function cancelVolunteer(){
        $volunteer_id = intval(input("volunteer_id"));
        if(!$this->auth->is_volunteer_group){
            $lang = lang("not_volunteer_group_master");
            err(200,"not_volunteer_group_master",$lang['code'],$lang['message']);
        }
        $group_id = \app\common\model\VolunteerGroup::where(['uid'=>$this->uid])->value('id');
        $where = [
            'gid'       => $group_id,
            'vid'       => $volunteer_id,
        ];
        $group_name = \app\common\model\VolunteerGroup::where(['uid'=>$this->uid])->value('title');
        $inf = \app\common\model\VolunteerGroupAccess::where($where)->delete();
        if($inf){
            notice([
                'sys_msg'   => [
                    'title'     => __("You have been removed from group %s",$group_name),
                    'brief'     => __("You have been removed from group %s",$group_name),
                    'uid'       => \app\common\model\Volunteer::where(['id'=>$volunteer_id])->value("uid"),
                ],
            ]);
        }
        ok();
    }

    /**
     * 志愿者积分记录
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     */
    public function integralLog()
    {
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize"));
        $orders = trim(input("orders", "create_at desc"));
        $page = max(1, $page);
        $pagesize = $pagesize ? $pagesize : 10;
        $vid = \app\common\model\Volunteer::where(['uid'=>$this->uid])->value("id");
        if(!$vid){
            $lang = lang("not_volunteer");
            err(200,"not_volunteer",$lang['code'],$lang['message']);
        }
        $where = [
            'vid'   => $vid
        ];
        $creditsLog = \app\common\model\VolunteerIntegralLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\VolunteerIntegralLog::where($where)->count();

        $scores = \app\common\model\Volunteer::where(['uid' => $this->uid])->value("scores");
        foreach ($creditsLog as $k => $v) {
            $creditsLog[$k]['format_create_at'] = format_time($v['create_at']);
        }

        ok([
            "items" => $creditsLog,
            "page" => $page,
            "pagesize" => $pagesize,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total,
            "scores" => $scores,
        ]);
    }
}
