<?php

namespace app\common\model;

use think\Model;

/**
 * 志愿者团体模型
 */
class VolunteerGroup extends Model
{
    // 表名
    protected $name = 'volunteer_group';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
