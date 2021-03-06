<?php

namespace app\common\model;

use think\Model;

/**
 * 积分日志模型
 */
class UserIntegralLog extends Model
{
    // 表名
    protected $name = 'user_integral_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
