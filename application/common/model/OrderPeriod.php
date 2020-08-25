<?php

namespace app\common\model;

use think\Model;

/**
 * 点单周期模型
 */

class OrderPeriod extends Model
{

   // 表名
    protected $name = 'order_period';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;




}
