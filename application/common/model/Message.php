<?php

namespace app\common\model;

use think\Model;

/**
 * 站内信模型
 */
class Message extends Model
{
    // 表名
    protected $name = 'message';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
