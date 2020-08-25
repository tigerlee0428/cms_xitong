<?php

namespace app\common\model;

use think\Model;

/**
 * 答题记录模型
 */
class QuestionLog extends Model
{
    // 表名
    protected $name = 'question_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'add_time';
    protected $updateTime = '';
    
    
}
