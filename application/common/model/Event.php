<?php

namespace app\common\model;

use think\Model;

/**
 * 事件模型
 */
class Event extends Model
{
    // 表名
    protected $name = 'event';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
