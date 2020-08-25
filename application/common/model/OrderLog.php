<?php

namespace app\common\model;

use think\Model;

/**
 * 点单记录模型
 */
class OrderLog extends Model
{
    // 表名
    protected $name = 'order_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
