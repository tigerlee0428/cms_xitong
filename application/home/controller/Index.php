<?php




namespace app\home\controller;



use app\common\controller\Api;
use app\common\model\User;
use Overtrue\Weather\Weather;

class Index extends Api{

    protected $noNeedLogin = ['vrank','grank','plogin','unbind','pclogin','outGroup','joinGroup','smallqr','myActivity','cancelJoin','join','qrcode','weather','signin','index','get','test','testlogin','volunteer'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();

    }
    public function index(){
        header('Content-type:image/png');
        $img = file_get_contents("http://file.szzyz.org/staticfiles/202002/27/0142dddc201e47cb9791ddfa59b991ff.jpg");
        echo $img;
        exit;
        //notice(['ok'=>'ok']);
    }

    //生成二维码
    public function qrcode(){
        $url = trim(input("url"));
        $size = intval(input("size",8));
        qrcode($url,$size);
    }

    /* public function weather()
    {
	    $key = '969be7016fc5b9f0d8313a402bc0fc9d';
        $weather = new Weather($key);
        $response = $weather->getLiveWeather(cfg('areaname'));//获取实时天气
        ok($response['lives']);
    } */
    public function weather(){
        $url = "http://www.weather.com.cn/weather1d/101190902.shtml";
        $data = file_get_contents($url);
        preg_match_all('%<p class="tem">([\s\S]+?)</span>%',$data,$temp);
        $temmax = intval(str_replace("<span>","",$temp[1][0]));
        $temmin = intval(str_replace("<span>","",$temp[1][1]));
        preg_match_all('%<p class="wea" title="([^>]+?)"%',$data,$wea);
        $weather = $wea[1][0];
        $data = [
            'tem_min'   => $temmin,
            'tem_max'   => $temmax,
            'weather'   => $weather,
        ];
        ok($data);
    }
    public function signin(){
        $id = intval(input("id"));

        if(!$id){
            exit("<script type='text/javascript'>alert('二维码错误！');</script>");
        }
        $activity = \app\common\model\Activity::get($id);
        if(!$activity){
            exit("<script type='text/javascript'>alert('活动不存在！');</script>");
        }
        $activity = $activity->toArray();
        if($activity['is_check'] != 1){
            exit("<script type='text/javascript'>alert('活动通过审核！');</script>");
        }
        if($activity['status'] == 0){
            exit("<script type='text/javascript'>alert('活动未开始！');</script>");
        }
        if($activity['status'] >= 2){
            exit("<script type='text/javascript'>alert('活动已结束！');</script>");
        }
        $bmlog = \app\common\model\ActivityBmLog::where(['aid'=>$id,'uid'=>$this->auth->id])->find();
        if(!$bmlog){
            exit("<script type='text/javascript'>alert('您未报名此活动！');</script>");
        }
        if($bmlog['is_sign']){
            exit("<script type='text/javascript'>alert('您已签到！');</script>");
        }
        $volunteer = \app\common\model\Volunteer::where(['uid'=>$this->auth->id])->find();
        $servicetime = $activity['servicetime'] ? $activity['servicetime'] * 3600 : $activity['end_time'] - $activity['start_time'];
        if($volunteer){
            \app\common\model\Volunteer::update(['jobtime'=>$volunteer['jobtime'] + $servicetime],['uid'=>$this->auth->id]);
        }
        $scores = ceil($servicetime / 3600);
        \app\common\model\ActivityBmLog::update(['is_sign'=>1,'signtime'=>time(),'score'=>$scores,'total_score'=>$scores],['aid'=>$id,'uid'=>$this->auth->id]);
        if($this->auth->is_volunteer){
            \app\common\model\VolunteerJobtimeLog::insert([
                'vid'           => $this->auth->vid,
                'act_id'        => $id,
                'jobtime'       => $servicetime,
                'create_at'     => time(),
                'note'          => $activity['title'],
            ]);
        }
        $integralInfo = [
            'event_code'        => 'ParticipateInActivities',
            'uid'               => $this->auth->id,
            'scores'            => $scores,
            'note'              => "参加".$activity['title'],
            'area_id'           => 0,
        ];
        \think\Hook::listen("integral",$integralInfo);
        exit("<script type='text/javascript'>alert('签到成功！');</script>");
    }

    public function test(){
        //require_once ROOT_PATH .'extend/fast/ZyhResource.php';
        header("Content-type: text/html; charset=utf-8");
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/recruit/list'; //访问方法
        $apiParam = array('page'=>1,'rows'=>200);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }
    public function testdetail(){
        //require_once ROOT_PATH .'extend/fast/ZyhResource.php';
        header("Content-type: text/html; charset=utf-8");
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/list'; //访问方法
        $apiParam = array('page'=>1,'rows'=>200);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }
    public function mlogin(){
        $token = trim(input('token'));
        $redirect_url =trim(input('redirect_url','http://m.kh.cst-info.cn:8000'));
        if(!$redirect_url){
            exit('redirect_uri error!!');
        }
        $openid = User::where(['id'=>$this->auth->id])->value('openid');
        $openid = 'ot80C1R3gAvuBt0lKQ3t2U8crVj8';
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
                $redirect_url = 'http://m.kh.cst-info.cn:8000?zyh_token='.$token;
                $this->redirect($redirect_url);
            }
        }else{
            $token = $result['token'];
            $redirect_url = 'http://m.kh.cst-info.cn:8000?zyh_token='.$token;
            $this->redirect($redirect_url);
        }
}

    public function pclogin(){
        $token = trim(input('token'));
        $access_token= trim(input('access_token','12312312123'));
        $redirect_url =trim(input('redirect_url','http://www.kh.cst-info.cn:8000'));
        if(!$redirect_url){
            exit('redirect_uri error!!');
        }
        $openid = User::where(['id'=>$this->auth->id])->value('openid');
        $openid = $openid?$openid:'ot80C1R3gAvuBt0lKQ3t2U8crVj8';
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
                $redirect_url = 'http://www.kh.cst-info.cn:8000?zyh_token='.$token;
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
        $result = $zyh::getData($apiFun, $apiParam);

    }
    public function unbind(){
        $token = trim(input('zyh_token','15967709688078f45379ccf474013b53537519b56bb86'));
        header("Content-type: text/html; charset=utf-8;token:".$token);
        $openid = 'ot80C1R3gAvuBt0lKQ3t2U8crVj8';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/userCenter/unbinding'; //访问方法
        $apiParam = array('openid'=>$openid,'token'=>$token);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);
    }


    public function volunteer(){
    $token = trim(input('zyh_token','15966834464015671304ee1c942b68a09907520c63b12'));
    $zyh = new \fast\ZyhResource();
    $apiFun = '/api/newage/volunteer/info'; //访问方法
    $apiParam = array('token'=>$token);//访问参数
    $result = $zyh::getData($apiFun, $apiParam);
    var_dump($result);exit;
}
    public function join(){
        $token = trim(input('zyh_token','15979907780267392cb8c1bb549f59b0fc0827ae3c771'));
        $recruitId ='1597991916354fc15d6828a2c4c44bb09d2ce989c7a5d';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/recruit/signup'; //访问方法
        $apiParam = array('token'=>$token,'recruitId'=>$recruitId);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);
    }
    public function cancelJoin(){
        $token = trim(input('zyh_token','15966834464015671304ee1c942b68a09907520c63b12'));
        $recruitId ='15961024814174dbf807f00734c738cebc846d04d4476';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/recruit/signout'; //访问方法
        $apiParam = array('token'=>$token,'recruitId'=>$recruitId);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }
    public function myActivity(){
        $token = trim(input('zyh_token','15966834464015671304ee1c942b68a09907520c63b12'));
        $page = intval(input('page',1));
        $pagesize= intval(input('pagesize',10));
        $recruitId ='15961024814174dbf807f00734c738cebc846d04d4476';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/recruit/signuplist'; //访问方法
        $apiParam = array('token'=>$token,'recruitId'=>$recruitId,'rows'=>$pagesize,'page'=>$page);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }
    //获取签到码
    public function smallqr(){
        $token = trim(input('zyh_token','15966834464015671304ee1c942b68a09907520c63b12'));
        $appid = 'wx1b55837c8b34ff49';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/home/getwxacodeunlimit'; //访问方法
        $apiParam = array('volunteerid'=>$token,'appId'=>$appid);//访问参数
        $result = $zyh::getqrData($apiFun, $apiParam);
        $qrcode = $result["qrcode"];
        echo '<img width="400" height="400" src="data:image/jpg;base64,'.$qrcode.'" />';
    }
   //加入组织
    public function joinGroup(){
        $token = trim(input('zyh_token','15979907780267392cb8c1bb549f59b0fc0827ae3c771898899'));
        $deptId ='14798133820D9BD539E28E4BE7AD2A006A069FCD2F';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/signup'; //访问方法
        $apiParam = array('token'=>$token,'deptId'=>$deptId);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }
    //退出组织
    public function outGroup(){
        $token = trim(input('zyh_token','15966834464015671304ee1c942b68a09907520c63b12'));
        $deptId ='14798133820D9BD539E28E4BE7AD2A006A069FCD2F';
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/signout.'; //访问方法
        $apiParam = array('token'=>$token,'deptId'=>$deptId);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }
    //组织排行
    public function grank(){
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/rank'; //访问方法
        $page = intval(input('page',1));
        $rows = intval(input('rows',10));
        $apiParam = array('page'=>$page,'rows'=>$rows,'type'=>3);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }

    //志愿者排行
    public function vrank(){
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/volunteer/rank'; //访问方法
        $page = intval(input('page',1));
        $rows = intval(input('rows',10));
        $apiParam = array('page'=>$page,'rows'=>$rows,'type'=>3);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        var_dump($result);exit;
    }


}
