<?php

namespace app\common\model;

use think\Model;

/**
 * 事件点赞模型
 */
class EventLikeLog extends Model
{
    // 表名
    protected $name = 'event_like_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
