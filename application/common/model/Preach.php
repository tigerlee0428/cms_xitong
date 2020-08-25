<?php

namespace app\common\model;

use think\Model;

/**
 * 成员模型
 */
class Preach extends Model
{
    // 表名
    protected $name = 'preach';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';


}
