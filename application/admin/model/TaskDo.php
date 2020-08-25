<?php

namespace app\admin\model;

use think\Model;


class TaskDo extends Model
{

    

    

    // 表名
    protected $name = 'task_do';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'finish_time_text'
    ];




    public function getTaskDoStatus()
    {
        return [__('Status1'),__('Status2'),__('Status3')];
    }


    public function getFinishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['finish_time']) ? $data['finish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setFinishTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
