<?php

namespace app\common\model;

use think\Model;

/**
 * 文章点赞模型
 */
class ArticleLikeLog extends Model
{
    // 表名
    protected $name = 'article_like_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
