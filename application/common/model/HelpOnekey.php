<?php

namespace app\common\model;

use think\Model;

/**
 * 一键求助模型（TV）
 */
class HelpOnekey extends Model
{
    // 表名
    protected $name = 'help_onekey';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
