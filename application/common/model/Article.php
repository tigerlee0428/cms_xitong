<?php

namespace app\common\model;

use think\Model;

/**
 * 文章模型
 */
class Article extends Model
{
    // 表名
    protected $name = 'article';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
