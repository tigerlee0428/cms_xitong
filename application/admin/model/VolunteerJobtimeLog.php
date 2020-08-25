<?php

namespace app\admin\model;

use think\Model;


class VolunteerJobtimeLog extends Model
{

    

    

    // 表名
    protected $name = 'volunteer_jobtime_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'jobtime_text'
    ];
    

    



    public function getJobtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['jobtime']) ? $data['jobtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setJobtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
