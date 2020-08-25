<?php

namespace app\common\model;

use think\Model;

/**
 * 成员点赞模型
 */
class MemberLikeLog extends Model
{
    // 表名
    protected $name = 'member_like_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';


}
