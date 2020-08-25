<?php

namespace app\common\model;

use think\Model;

/**
 * 点单模型
 */
class Order extends Model
{
    // 表名
    protected $name = 'order';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
