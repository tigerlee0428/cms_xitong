<?php

namespace app\admin\model;

use think\Model;


class Config extends Model
{





    // 表名
    protected $name = 'config';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'add_time_text',
        'check_time_text',
        'final_check_time_text',
        'publish_time_text'
    ];




    public function getArticleTpe()
    {
        return [__('Tpe1'),__('Tpe2'),__('Tpe3'),__('Tpe4'),__('Tpe5'),__('Tpe6')];
    }

    public function getAddTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['add_time']) ? $data['add_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCheckTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['check_time']) ? $data['check_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getFinalCheckTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['final_check_time']) ? $data['final_check_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPublishTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['publish_time']) ? $data['publish_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAddTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCheckTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setFinalCheckTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setPublishTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
