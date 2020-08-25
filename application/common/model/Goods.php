<?php

namespace app\common\model;

use think\Model;

/**
 * 积分商品模型
 */
class Goods extends Model
{
    // 表名
    protected $name = 'goods';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
