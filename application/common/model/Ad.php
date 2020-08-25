<?php

namespace app\common\model;

use think\Model;

/**
 * 广告模型
 */
class Ad extends Model
{
    // 表名
    protected $name = 'ad_info';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
