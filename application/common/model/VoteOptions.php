<?php

namespace app\common\model;

use think\Model;

/**
 * 投票选项模型
 */
class VoteOptions Extends Model
{

    // 表名
    protected $name = 'vote_options';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    // 追加属性
    protected $append = [
    ];
}
