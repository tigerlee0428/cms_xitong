<?php

namespace app\common\model;

use think\Model;

/**
 * 活动上报模型
 */
class ActivityReport extends Model
{
    // 表名
    protected $name = 'activity_report';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
