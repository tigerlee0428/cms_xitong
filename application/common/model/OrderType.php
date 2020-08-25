<?php

namespace app\common\model;

use think\Model;

/**
 * 点单类型模型
 */
class OrderType extends Model
{
    // 表名
    protected $name = 'order_type';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
