<?php

namespace app\common\model;

use think\Model;

/**
 * 文章收藏模型
 */
class ArticleCollectionLog extends Model
{
    // 表名
    protected $name = 'collection_log';
    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';
    
    
}
