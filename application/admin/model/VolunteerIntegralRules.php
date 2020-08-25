<?php

namespace app\admin\model;

use think\Model;


class VolunteerIntegralRules extends Model
{

    

    

    // 表名
    protected $name = 'volunteer_integral_rules';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    // 定义时间戳字段名
    protected $createTime = 'create_at';
    protected $updateTime = 'modify_at';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







}
