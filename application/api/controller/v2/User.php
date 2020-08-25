<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
/**
 * 站内信接口
 */
class User extends ApiCommon
{
    
    protected $noNeedLogin = ['getJsApiConf'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    
    /**
     * 视频学习记录
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     */
    public function studyLog(){
        
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize",10));
        $orders = trim(input("orders","add_time desc"));
        $page = max($page,1);
        $where = [
            'uid'       => $this->uid,
            'is_video'  => 1
        ];
        $studyLogList = \app\common\model\Visits::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Visits::where($where)->count();
        $list = [];
        foreach($studyLogList as $k => $v)
        {
            $list[$k]['add_time']   = format_time($v['add_time']);
            $list[$k]['subject']    = $v['subject'];
            $list[$k]['device']     = $v['device'];
            $list[$k]['duration']   = $v['duration'];
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
     * 用户积分记录
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
        $where = [
            'uid'   => $this->uid
        ];
        $creditsLog = \app\common\model\UserIntegralLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\UserIntegralLog::where($where)->count();
        
        $scores = \app\common\model\User::where(['id' => $this->uid])->value("score");
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
    
    /**
     * 获取JSAPI配置
     */
    public function getJsApiConf()
    { 
        $url = config('wx_service_url')."/getJsApiConfig"; 
        if(!cfg("appid")){
            $lang = lang("appid_error");
            err(200,"appid_error",$lang['code'],$lang['message']);
        }
        $furl = trim(input('url'));
        $data['apid'] = cfg("appid");
        $data['url'] = $furl;
        $content = myhttp($url,$data); 
        $result = json_decode($content,true);
        ret($result);
    }
    
    /**
     * 站内信列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param string $orders    排序
     * @param string $token 用户TOKEN
     * @return array
     */
    public function stationLetterList()
    {
        
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize"));
        $orders = trim(input("orders", "add_time desc"));
        $page = max(1, $page);
        $pagesize = $pagesize ? $pagesize : 10;
        
        $where = [
            'uid' => $this->uid
        ];
        $messageList = \app\common\model\Message::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Message::where($where)->count();
        foreach ($messageList as $k => $v) {
            $messageList[$k]['add_time'] = format_time($v['add_time']);
            $messageList[$k]['read_time'] = format_time($v['read_time']);
        }
    
        ok([
            "items" => $messageList,
            "page" => $page,
            "pagesize" => $pagesize,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    }
    
    /**
     * 站内信详情
     * @param int $id    消息ID
     * @param string $token 用户TOKEN
     * @return array
     */
    public function stationLetter()
    {
        
        $id = intval(input("id"));
        $where = [
            'id' => $id,
            'uid' => $this->uid
        ];
    
        $messageInfo = \app\common\model\Message::where($where)->find();
        if (!$messageInfo) {
            $lang = lang("not_message");
            err(200, "not_message", $lang['code'], $lang['message']);
        }
        $messageInfo = $messageInfo->toArray();
        \app\common\model\Message::update(['is_read' => 1, 'read_time' => time()], $where);
    
        $data = [
            'title' => $messageInfo['title'],
            'brief' => $messageInfo['brief'],
            'is_read' => $messageInfo['is_read'],
            'add_time' => format_time($messageInfo['add_time']),
            'read_time' => format_time($messageInfo['read_time']),
            'tpe' => $messageInfo['tpe'],
            'url' => $messageInfo['url'],
        ];
        ok($data);
    }
}
