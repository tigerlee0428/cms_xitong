<?php

namespace app\admin\model;

use think\Model;


class WorkDo extends Model
{





    // 表名
    protected $name = 'work_do';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'add_time_text',
        'finish_time_text'
    ];






    public function getAddTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['add_time']) ? $data['add_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }
    public function getDoType()
    {
        return [__('Type1'),__('Type2'),__('Type3')];
    }

    public function getFinishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['finish_time']) ? $data['finish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAddTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setFinishTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
