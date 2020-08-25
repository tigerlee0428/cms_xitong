<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }


    /**
     * 会员登录
     *
     * @param string $account  账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile  手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $mobile   手机号
     */
    public function register()
    {
        $username = $this->request->request('username');
        $password = $this->request->request('password');
        //$email = $this->request->request('email');
        $mobile = $this->request->request('mobile');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 会员个人信息
     *
     */
    public function getProfile()
    {
        $user = $this->auth->getUser();
        if($user['status'] != 'normal'){
            $this->error("Account is locked",[],402);
        }
        $uinfo = [
            'id'                => $user['id'],
            'nickname'          => $user['realname'] ? $user['realname'] : $user['nickname'],
            //'realname'          => $user['realname'],
            'mobile'            => $user['mobile'],
            'avatar'            => $user['avatar'],
            'score'             => $user['score'],
            'is_volunteer'      => $user['is_volunteer'],
            'is_volunteer_group'=> $user['is_volunteer_group'],
            'area_id'           => $this->auth->area_id,
            'area_name'         => $user['area_id'] ? \app\common\model\Area::where(['id'=>$user['area_id']])->value('name') : '',
            'msg_count'         => \app\common\model\Message::where(['uid'=>$this->auth->id,'is_read'=>0])->count(),
        ];
        $volunteer = \app\common\model\Volunteer::where(['uid'=>$uinfo['id']])->find();
        if($uinfo['is_volunteer']){
            $myGroup = [];

            $uinfo['volunteer'] = [
                'name'          => $volunteer['name'],
                'head_img'      => $volunteer['head_img'],
                'brief'         => $volunteer['brief'],
                'join_time'     => format_time($volunteer['join_time']),
                'jobtime'       => round($volunteer['jobtime']/3600,2),
                'card'          => $volunteer['card'],
                'mobile'        => $volunteer['mobile'],
                'skill'         => $volunteer['skill'],
            ];

            $groups = \app\common\model\VolunteerGroupAccess::where(['vid'=>$volunteer['id']])->select();
            if($groups){
                $groupsArr = [];
                foreach(collection($groups)->toArray() as $k => $v){
                    $groupsArr[] = $v['gid'];
                }
                $myVolunteerGroup = \app\common\model\VolunteerGroup::where(['id'=>['in',$groupsArr]])->select();
                if($myVolunteerGroup){
                    foreach(collection($myVolunteerGroup)->toArray() as $k => $v){
                        $myGroup[$k] = [
                            'id'    => $v['id'],
                            'name'  => $v['title'],
                            'logo'  => $v['logo']
                        ];
                    }
                }
            }
            $uinfo['myvolunteergroup'] = $myGroup;
        }elseif($volunteer){
            $uinfo['volunteer_check'] = 1;
        }
        $volunteergroup = \app\common\model\VolunteerGroup::where(['uid'=>$uinfo['id']])->find();

        if($uinfo['is_volunteer_group']){
            $uinfo['volunteergroup'] = [
                'id'             => $volunteergroup['id'],
                'area_name'      => \app\common\model\Area::where(['id'=>$volunteergroup['area_id']])->value('name'),
                'title'          => $volunteergroup['title'],
                'condition'      => $volunteergroup['condition'],
                'content'        => $volunteergroup['content'],
                'logo'           => $volunteergroup['logo'],
                'master'         => $volunteergroup['master'],
                'address'        => $volunteergroup['address'],
                'volunteer_num'     => \app\common\model\VolunteerGroupAccess::where(['gid'=>$volunteergroup['id']])->count(),
                'activity_time'     => \app\common\model\Activity::where(['uid'=>$this->auth->id])->sum('servicetime'),
                'activity_num'      => \app\common\model\Activity::where(['uid'=>$this->auth->id])->count(),
            ];
        }elseif($volunteergroup){
            $uinfo['volunteer_group_check'] = 1;
        }
        $uinfo['is_bind_mobile'] = $user['mobile'] ? 1 : 0;
        $this->success('',$uinfo);
    }


    /**
     * 修改会员个人信息
     *
     * @param string $avatar   头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio      个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->request('username');
        $nickname = $this->request->request('nickname');
        $bio = $this->request->request('bio');
        $avatar = $this->request->request('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        $user->nickname = $nickname;
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }



    /**
     * 修改手机号
     *
     * @param string $email   手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }



    /**
     * 重置密码
     *
     * @param string $mobile      手机号
     * @param string $newpassword 新密码
     * @param string $captcha     验证码
     */
    public function resetpwd()
    {
        $type = $this->request->request("type");
        $mobile = $this->request->request("mobile");
        $email = $this->request->request("email");
        $newpassword = $this->request->request("newpassword");
        $captcha = $this->request->request("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 绑定手机号（实名认证）
     *
     * @param string $mobile      手机号
     * @param string $realname    真实姓名
     * @param string $code        验证码
     * @param string $area_id     用户区域ID
     * @param string $token       用户TOKEN
     */
    public function bindMoblie()
    {
        $mobile         = trim(input("mobile"));
        $realname       = trim(input("realname"));
        $code           = trim(input("code"));
        $area_id        = intval(input("area_id"));
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';

        if(!$mobile || !$code){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        //判断验证码
        if(!Sms::check($mobile, $code,$event)){
            $lang = lang("verify_code_error");
            err(200,"verify_code_error",$lang['code'],$lang['message']);
        }

        //判断手机号身份是否存在
        $mobileInfo = \app\common\model\User::get(['mobile'=>$mobile]);

        if($mobileInfo){                  //该手机号已注册过用户
            //判断该用户是否绑定过微信身份
            $lang = lang("mobile_has_bind");
            err(200,"mobile_has_bind",$lang['code'],$lang['message']);
        }else{                          //新用户
            $salt = rand(1000,9999);
            $password = substr($mobile,-6);
            $info = [
                'salt'          => $salt,
                'password'      => $this->auth->getEncryptPassword($password, $salt),
                'realname'      => $realname,
                'mobile'        => $mobile,
                'bindtime'      => time(),
                'area_id'       => $area_id,
            ];

            //用户在自动登录时已创建过用户，在此更新用户的信息
            \app\common\model\User::where(['id'=>$this->auth->id])->update($info);
        }
        ok();
    }

    /**
     * 志愿汇手机登陆
     * @param string $access_token       用户TOKEN
     */
    public function zymlogin(){
        $token = trim(input('token'));
        $redirect_url =trim(input('redirect_url'));
        if(!$redirect_url){
            exit('redirect_uri error!!');
        }
        $openid = User::where(['id'=>$this->auth->id])->value('openid');
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/loginByOpenId'; //访问方法
        $apiParam = array('openid'=>$openid);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        if($result['errCode'] == '0009'){
            if(!$token){
                $callbackurl = 'http://cms.kh.cst-info.cn:8000/home/index/mlogin';
                $url = 'http://47.99.112.147:8080/webproject/usercenter/login?callbackurl='.$callbackurl.'&openid='.$openid;
                $this->redirect($url);
            }else{
                $redirect_url = $redirect_url.'?zyh_token='.$token;
                $this->redirect($redirect_url);
            }
        }else{
            $token = $result['token'];
            $redirect_url = $redirect_url.'?zyh_token='.$token;
            $this->redirect($redirect_url);
        }
    }

    /**
     * 志愿汇pc登陆
     * @param string $access_token       用户TOKEN
     */
    public function zypclogin(){
        $token = trim(input('token'));
        $access_token= trim(input('access_token'));
        $redirect_url =trim(input('redirect_url'));
        if(!$redirect_url){
            exit('redirect_uri error!!');
        }
        $openid = User::where(['id'=>$this->auth->id])->value('openid');
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/loginByOpenId'; //访问方法
        $apiParam = array('openid'=>$openid);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        if($result['errCode'] == '0009'){
            if(!$token){
                $callbackurl = 'http://cms.kh.cst-info.cn:8000/home/index/pclogin/access_token/'.$access_token;
                $url = 'http://47.99.112.147:8080/webproject/usercenter/pc/login?callbackurl='.urlencode($callbackurl);
                $this->redirect($url);
            }else{
                $redirect_url = $redirect_url.'?zyh_token='.$token;
                $this->_binding($openid,$token);
                $this->redirect($redirect_url);
            }
        }else{
            $token = $result['token'];
            $redirect_url = 'http://www.kh.cst-info.cn:8000?zyh_token='.$token;
            $this->redirect($redirect_url);
        }
    }
    private function _binding($openid,$token){
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/binding'; //访问方法
        $apiParam = array('openid'=>$openid,'token'=>$token);//访问参数
        $zyh::getData($apiFun, $apiParam);
    }
    public function unbind(){
        $token = trim(input('zyh_token','15967709688078f45379ccf474013b53537519b56bb86'));
        header("Content-type: text/html; charset=utf-8;token:".$token);
        $openid = User::where(['id'=>$this->auth->id])->value('openid');
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/unbinding'; //访问方法
        $apiParam = array('openid'=>$openid,'token'=>$token);//访问参数
        $zyh::getData($apiFun, $apiParam);
    }

}
