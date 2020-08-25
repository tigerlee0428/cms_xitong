<?php

namespace app\common\model;

use think\Model;

/**
 * 监控直播模型
 */
class Camera extends Model
{
    // 表名
    protected $name = 'camera';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
