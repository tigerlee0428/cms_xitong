<?php

namespace app\admin\model;

use think\Model;


class ActivityReport extends Model
{

    

    

    // 表名
    protected $name = 'activity_report';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'addtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    


}
