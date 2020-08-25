<?php


namespace app\api\controller\v2;
use app\api\controller\v2\ApiCommon;
/**
 * 视频连线接口
 */

class Videolink extends ApiCommon
{
    protected $noNeedLogin = ['call','receive'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    /**
     * 发送视频连接
     * @param int $room_id 房间ID
     * @param int $send_id 发起ID
     * @param int $receive_id 接收者ID
     * @return array
     */
    public function call(){
        $room_id = intval(input("room_id"));
        $send_id = intval(input("send_id"));
        $receive_id = intval(input("receive_id"));
        if(!$room_id || !$send_id || !$receive_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $data = [
            'room_id'   => $room_id,
            'send_id'   => $send_id,
            'send_name' => \app\common\model\Area::where(['id'=>$send_id])->value('name'),
            'receive_id'=> $receive_id
        ];
        $key = md5($receive_id.config("authkey"));
        $redis = new \Redis();
        $redis->connect("127.0.0.1",6379);
        $redis->lpush($key,json_encode($data));
        $redis->expire($key,60);
        ok();
    }
    
}