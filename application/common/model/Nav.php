<?php

namespace app\common\model;

use think\Model;

/**
 * 导航模型
 */
class Nav extends Model
{
    // 表名
    protected $name = 'nav';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
