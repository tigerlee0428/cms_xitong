<?php

namespace app\admin\model;

use think\Model;


class AdPos extends Model
{

    

    

    // 表名
    protected $name = 'ad_pos';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    public function getAdPosTpe()
    {
        return [__('Style0'),__('Style1'),__('Style2'),__('Style3'),__('Style4'),__('Style5')];
    }







}
