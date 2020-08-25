<?php

namespace app\common\model;

use think\Model;

/**
 * 成员模型
 */
class Member extends Model
{
    // 表名
    protected $name = 'member';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';


}
