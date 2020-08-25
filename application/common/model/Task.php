<?php

namespace app\common\model;

use think\Model;

/**
 * 任务模型
 */
class Task extends Model
{
    // 表名
    protected $name = 'task';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
