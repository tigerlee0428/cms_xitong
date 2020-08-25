<?php

namespace app\common\model;

use think\Model;

/**
 * 活动报名记录模型
 */
class ActivityBmLog extends Model
{
    // 表名
    protected $name = 'activity_bm_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    public static function getJoiner($where){
       $data = [];
       $result = self::where($where)->select();
       if($result){
           $result = collection($result)->toArray();
           foreach($result as $k => $v){
               $userInfo = \app\common\model\User::where(['id'=>$v['uid']])->find();
               $data[$k] = [
                   'uid'    => $v['uid'],
                   'format_addtime' => format_time($v['addtime']),
                   'nickname'   => $userInfo['nickname'],
                   'head_img'   => $userInfo['avatar']
               ];
           }
       }
       return $data;
    }
    
    public static function is_bm($id,$uid){
        $result = self::where(['aid'=>$id,'uid'=>$uid])->find();
        if($result){
            return true;
        }
        return false;
    }
}
