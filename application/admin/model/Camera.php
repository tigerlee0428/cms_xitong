<?php

namespace app\admin\model;

use think\Model;


class Camera extends Model
{

    

    

    // 表名
    protected $name = 'camera';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'add_time_text',
        'status_text',
        'is_record_text',
        'domain_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }


    public function getIsRecordList()
    {
        return ['0' => __('Is_record 0'), '1' => __('Is_record 1')];
    }

    public function getDomainList()
    {
        return ['1' => __('Domain 1'), '2' => __('Domain 2'), '3' => __('Domain 3')];
    }


    public function getAddTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['add_time']) ? $data['add_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }



    public function getIsRecordTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_record']) ? $data['is_record'] : '');
        $list = $this->getIsRecordList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getDomainTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['domain']) ? $data['domain'] : '');
        $list = $this->getDomainList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setAddTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
