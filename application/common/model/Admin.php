<?php

namespace app\common\model;

use think\Model;

/**
 * 文章模型
 */
class Admin extends Model
{
    // 表名
    protected $name = 'admin';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
