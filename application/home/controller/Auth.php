<?php

/*
 * 微信登录
 * URL: /home/auth/wxlogin
 * PARAMS: redirect_uri,登录成功后回跳URL，带上access_token
 * 下次需要登录的接口需要传参access_token
 * */


namespace app\home\controller;



use app\common\controller\Api;

use app\common\model\User;
use think\Request;
use EasyWeChat\Factory;
use think\response\Redirect;
use app\common\library\Token;

class Auth extends Api{

    protected $noNeedLogin = ['unbind','mlogin','pclogin','getQrcodeLoginToken','qrcodeLogin', 'wxlogin', 'getCardnoCode', 'getAccessToken','d','qrcode'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    //扫码登录取登录状态
    public function getQrcodeLoginToken(){
        $scankey = trim(input('scankey'));
        if(!$scankey){
            err(200,'','901','no scankey');
        }
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        if($redis->exists($scankey)){
            $status = $redis->hget($scankey,'status');
            if($status == 1){
                err(200,'','903','已扫描');
            }elseif($status == 2){
                $access_token = $redis->hget($scankey,'access_token');
                $uid = $redis->hget($access_token,'uid');
                $nick_name = $redis->hget($access_token,'nick_name');
                $cardNo = trim(input("cardNo"));
                $redis->del($scankey);
                ok(["access_token"=>$access_token]);
            }
        }
        err(200,'','902','fail');
    }

    //扫码登录
    public function qrcodeLogin(){
        $scankey = trim(input('scankey'));
        $access_token = trim(input('access_token'));
        //有access_token后手机跳转到首页
        if($access_token){
            $redis = new \Redis();
            $redis->connect("127.0.0.1",6379);
            $redis->hset($scankey,'status',2);
            $redis->hset($scankey,'access_token',$access_token);
            $redis->expire($scankey,60);
            $url = config("wx_domain")."?access_token=".$access_token;
            $this->redirect($url);
        }else{
            if(!$scankey){
                err(200,'','901','no scankey');
            }
            $redis = new \Redis();
            $redis->connect("127.0.0.1",6379);
            $redis->hset($scankey,'status',1);
            $url = config("interface_domain").'/home/auth/qrcodeLogin?scankey='.$scankey;
            $this->redirect(config("interface_domain")."/home/auth/wxlogin?redirect_uri=".urlencode($url));
        }
    }

    //微信登录
    public function wxlogin(){
        $redirect_uri = trim(input("redirect_uri"));
        if(!$redirect_uri){
            exit('redirect_uri error!!');
        }
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        $sid = _ua_key();
        if(!$redis->exists($sid)){
            $redis->set($sid,$redirect_uri);
        }
        if(cfg('is_server')){
            $this->_wx_server_login();
        }else{
            $this->_wx_local_login();
        }
    }


    private function _wx_local_login(){
        if(cfg("wxappid") && cfg("wxappsecret")){
            $wechatConfig = [
                'app_id' => cfg("wxappid"),
                'secret' => cfg("wxappsecret"),

                // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
                'response_type' => 'array',

                'log' => [
                    'level' => 'debug',
                    'file' => ROOT_PATH.'weixin/logs/wechat.log',
                ],
                'oauth' => [
                    'scopes'   => ['snsapi_userinfo'],
                    'callback' => config("interface_domain").'/home/auth/wxlogin?apid='.cfg("wxappid").'&loginback=1&redirect_uri=d',
                ],
            ];
            $this->easyWechat = Factory::officialAccount($wechatConfig);
        }
        $oauth = $this->easyWechat->oauth;
        if(input('loginback')){
            $user = $oauth->user();
            $userinfo = $user->getOriginal();
            $userinfo['nickname'] = filter_wx_nick_name($userinfo['nickname']);
            $access_token =  $this->_chk_wx_login($userinfo);
            $redis = new \Redis();
            $redis->connect("127.0.0.1",6379);
            $sid = _ua_key();
            $redirect_uri = $redis->get($sid);
            $redis->del($sid);
            if(strpos($redirect_uri,"#") > 0){
                list($a,$b) = explode("/#",$redirect_uri);
                $redirect_uri = $a."?access_token=".$access_token."/#".$b;
                $this->redirect($redirect_uri);
            }else{
                if(strpos($redirect_uri,"?") > 0){
                    $this->redirect($redirect_uri."&access_token=".$access_token);
                }else{
                    $this->redirect($redirect_uri."?access_token=".$access_token);
                }
            }
            exit;
        }else{
            $oauth->redirect()->send();
        }
    }

    private function _wx_server_login(){
        if(is_wx()){                        //是否是微信
            $code = urldecode(input('code'));
            if(!$code){
                if(input('error') == 1000){
                    err("200","",input('code'),input('msg'));
                }
                $redirect_uri = Request::instance()->url(true);
                $redirect_uri = str_replace("http://127.0.0.1:82", config("interface_domain"), $redirect_uri);
                if(!cfg("appid")){
                    err(200,"appid_error",__("appid_error")['code'],__("appid_error")['message']);
                }
                $this->redirect(config('wx_service_url')."?apid=".cfg("appid")."&backurl=".$redirect_uri);
            }else{
                if(strpos($code," ") > 0){
                    $code = str_replace(" ", "+", $code);
                }else{
                    err("200","","",$code);
                }
                $userInfo = json_decode(authcode($code,'DECODE'),true);
                $userInfo['nickname'] = filter_wx_nick_name($userInfo['nickname']);
                $access_token =  $this->_chk_wx_login($userInfo);
                $redis = new \Redis();
                $redis->connect("127.0.0.1",6379);
                $sid = _ua_key();
                $redirect_uri = $redis->get($sid);
                $redis->del($sid);
                if(strpos($redirect_uri,"#") > 0){
                    list($a,$b) = explode("/#",$redirect_uri);
                    $redirect_uri = $a."?access_token=".$access_token."/#".$b;
                    $this->redirect($redirect_uri);
                }else{
                    if(strpos($redirect_uri,"?") > 0){
                        $this->redirect($redirect_uri."&access_token=".$access_token);
                    }else{
                        $this->redirect($redirect_uri."?access_token=".$access_token);
                    }
                }

                exit;
            }
        }else{
            exit('请在微信中打开');
        }
    }



    private function _chk_wx_login($info){
        if(isset($info['openid'])){
            $access_token = $this->auth->wxopenidlogin($info);

            if($this->auth->id){
                $integralInfo = [
                    'event_code'        => 'FirstLogin',
                    'uid'               => $this->auth->id,
                    'area_id'           => 0,
                    'note'              => '初次登录',
                ];
                \think\Hook::listen("integral",$integralInfo);
                $this->_loginIntegral($this->auth->id);
            }
            return $access_token;
        }else{
            exit('未获取微信openid');
        }
    }

    public function getCardnoCode(){
        $sign = trim(input('sign'));
        $rand = intval(input('rand'));
        $smartcardno = trim(input('smartcardno'));
        if(!$sign || !$rand){
            $lang = __("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        //验证签名

        $mysign = md5($smartcardno.$rand);
        if($mysign != $sign){
            err(200,"sign_error",800,"签名错误");
        }
        $str = $rand.$smartcardno._ua_key().config("authkey");
        $code = md5($str);
        $expire = time() + 30*2400;
        if(class_exists("redis")){
            $redis = new \Redis();
            $redis->connect("127.0.0.1",6379);
            $redis->del($sign);
            $redis->hset($sign,'code',$code);
            $redis->expireAt($sign,$expire);
        }
        ok(['code' => $code]);
    }

    public function getAccessToken(){
        $code = trim(input('code'));
        $cardno = trim(input('cardno'));
        $sign = trim(input('sign'));
        if(!$sign || !$code || !$cardno){
            $lang = __("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        if(class_exists("redis")){
            $redis = new \Redis();
            $redis->connect("127.0.0.1",6379);
            if($redis->exists($sign)){
                if($code == $redis->hget($sign,'code')){
                    $access_token = $this->auth->cardnologin($cardno);
                    if(!$access_token){
                        err(200,"cardno_not_bind",801,"机顶盒未绑定");
                    }
                    ok(['access_token'=>$access_token]);
                }
                err(200,"",803,"非法请求！！");
            }else{
                err(200,"",803,"异常错误！！");
            }
        }
        err(200,"",803,"异常错误！！");
    }

    public function bindCardNo(){
        $cardno = trim(input('cardno'));
        if(!$cardno){
            $lang = __("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        \app\admin\model\User::where(['cardnum'=>$cardno])->update(['cardnum'=>'']);
        $ret = \app\admin\model\User::where(['id'=>$this->auth->id])->update(['cardnum'=>$cardno]);
        if($ret){
            ok();
        }
        err(200,'bind_cardno_fail',808,'绑定机顶盒号失败');
    }

    //登录积分规则
    private function _loginIntegral($uid){
        //连续登录次数
        $ContinuLogin =\app\common\model\Auth::userLoginLogInsert($uid);
        if($ContinuLogin <= 5){
            $this->_addLoginIntegral(3, $ContinuLogin, false,$uid);
            if($ContinuLogin == 5){
                $this->_addLoginIntegral(15, $ContinuLogin, true,$uid);
            }
        }elseif($ContinuLogin >5 && $ContinuLogin <= 30){
            $this->_addLoginIntegral(5, $ContinuLogin, false,$uid);
            if($ContinuLogin == 10){
                $this->_addLoginIntegral(20, $ContinuLogin, true,$uid);
            }
            if($ContinuLogin == 20){
                $this->_addLoginIntegral(50, $ContinuLogin, true,$uid);
            }
            if($ContinuLogin == 30){
                $this->_addLoginIntegral(100, $ContinuLogin, true,$uid);
            }
        }elseif($ContinuLogin > 30){
            $this->_addLoginIntegral(10, $ContinuLogin, false,$uid);
            if($ContinuLogin % 15 == 0){
                $this->_addLoginIntegral(100, $ContinuLogin, true,$uid);
            }
        }
    }

    private function _addLoginIntegral($scores,$continuDay,$isEW,$uid){
        $integralInfo = [
            'event_code'        => $isEW ? 'loginMore' : 'login',
            'uid'               => $uid,
            'scores'            => $scores,
            'area_id'           => 0,
            'note'              => $isEW ? '连续登录'.$continuDay.'天，额外获得'.$scores.'分！':'登录第'.$continuDay.'天，获得'.$scores.'分！',
        ];
        \think\Hook::listen("integral",$integralInfo);
    }
    //生成二维码
    public function qrcode(){
        $url = trim(input("url"));
        $size = intval(input("size",8));
        qrcode($url,$size);
    }


    public function zyhlogin(){
        $callBackUrl = 'http://cms.kh.cst-info.cn:8000/home/Auth/zyhlogin';
        $url = 'http://47.99.112.147:8080/webproject/usercenter/login?callbackurl='.urlencode($callBackUrl);
        $this->redirect($url);

    }
    public function d(){
        echo '您的网络环境异常，请联系管理员！！';
    }


    public function mlogin(){
        $token = trim(input('token'));
        $access_token = trim(input('access_token'));
        $redirect_url =trim(input('redirect_url'));
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        $sid = _ua_key();
        if(!$redis->exists($sid)){
            $redis->hset($sid,'redirect_url',$redirect_url);
            $redis->hset($sid,'access_token',$access_token);
        }else{
            $redirect_url =  $redis->hget($sid,'redirect_url');
            $access_token =  $redis->hget($sid,'access_token');
            $redis->hdel($sid);

        }
        if(!$redirect_url){
            exit('redirect_uri error!!');
        }
        $user = Token::get($access_token);
        $openid = User::where(['id'=>$user['user_id']])->value('openid');
        if($token){
            User::update(['is_volunteer'=>1],['id'=>$this->auth->id]);
            $redirect_url = $redirect_url.'?zyh_token='.$token;
            $this->redirect($redirect_url);
        }
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/loginByOpenId'; //访问方法
        $apiParam = array('openid'=>$openid);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        if($result['errCode'] == '0009'){
                $callbackurl = 'http://cms.kh.cst-info.cn:8000/home/auth/mlogin/access_token/'.$access_token;
                $url = 'http://47.99.112.147:8080/webproject/usercenter/login?callbackurl='.$callbackurl.'&openid='.$openid;
                $this->redirect($url);
        }else{
            $token = $result['token'];
            $redirect_url = $redirect_url.'?zyh_token='.$token;
            $this->redirect($redirect_url);
        }
    }



    public function pclogin(){
        $token = trim(input('token'));
        $access_token= trim(input('access_token'));
        $redirect_url =trim(input('redirect_url'));
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        $sid = _ua_key();
        if(!$redis->exists($sid)){
            $redis->hset($sid,'redirect_url',$redirect_url);
            $redis->hset($sid,'access_token',$access_token);
        }else{
            $redirect_url =  $redis->hget($sid,'redirect_url');
            $access_token =  $redis->hget($sid,'access_token');
            $redis->hdel($sid);

        }
        if(!$redirect_url){
            exit('redirect_uri error!!');
        }
        $user = Token::get($access_token);
        $openid = User::where(['id'=>$user['user_id']])->value('openid');
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/loginByOpenId'; //访问方法
        $apiParam = array('openid'=>$openid);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        if($token){
            User::update(['is_volunteer'=>1],['id'=>$user['user_id']]);
            $redirect_url = $redirect_url.'?zyh_token='.$token;
            $this->_binding($openid,$token);
            $this->redirect($redirect_url);
        }
        if($result['errCode'] == '0009'){
            $callbackurl = 'http://cms.kh.cst-info.cn:8000/home/auth/pclogin';
            $url = 'http://47.99.112.147:8080/webproject/usercenter/pc/login?callbackurl='.urlencode($callbackurl);
            $this->redirect($url);

        }else{
            $token = $result['token'];
            $redirect_url = $redirect_url.'?zyh_token='.$token;
            $this->redirect($redirect_url);
        }
    }


    private function _binding($openid,$token){
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/binding'; //访问方法
        $apiParam = array('openid'=>$openid,'token'=>$token);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);

    }
    public function unbind(){
        $token = trim(input('zyh_token'));
        header("Content-type: text/html; charset=utf-8;token:".$token);
        $openid = 'ot80C1R3gAvuBt0lKQ3t2U8crVj8';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/unbinding'; //访问方法
        $apiParam = array('openid'=>$openid,'token'=>$token);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);
    }
}
