<?php

namespace app\common\model;

use think\Model;

/**
 * 访问统计模型
 */
class Visits extends Model
{
    // 表名
    protected $name = 'visits';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
