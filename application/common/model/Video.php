<?php

namespace app\common\model;

use think\Model;

/**
 * 视频模型
 */
class Video extends Model
{
    // 表名
    protected $name = 'video';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
