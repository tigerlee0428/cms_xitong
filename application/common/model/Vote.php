<?php

namespace app\common\model;

use think\Model;

/**
 * 投票模型
 */
class Vote Extends Model
{

    // 表名
    protected $name = 'vote';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
    ];
}
