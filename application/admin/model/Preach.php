<?php

namespace app\admin\model;

use think\Model;


class Preach extends Model
{

    

    

    // 表名
    protected $name = 'preach';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'preach_time_text',
        'add_time_text'
    ];
    

    



    public function getPreachTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['preach_time']) ? $data['preach_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getAddTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['add_time']) ? $data['add_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setPreachTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setAddTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
