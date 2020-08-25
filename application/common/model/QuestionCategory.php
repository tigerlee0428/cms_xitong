<?php

namespace app\common\model;

use think\Model;

/**
 * 题目类型模型
 */
class QuestionCategory extends Model
{
    // 表名
    protected $name = 'question_category';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    
}
