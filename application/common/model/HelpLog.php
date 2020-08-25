<?php

namespace app\common\model;

use think\Model;

/**
 * 求助记录模型
 */
class HelpLog extends Model
{
    // 表名
    protected $name = 'help_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
