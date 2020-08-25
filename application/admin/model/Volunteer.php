<?php

namespace app\admin\model;

use think\Model;


class Volunteer extends Model
{

    

    

    // 表名
    protected $name = 'volunteer';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = 'join_time';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'join_time_text',
        'jobtime_text',
        'check_time_text'
    ];
    

    



    public function getJoinTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['join_time']) ? $data['join_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getJobtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['jobtime']) ? $data['jobtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCheckTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['check_time']) ? $data['check_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setJoinTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setJobtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCheckTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
