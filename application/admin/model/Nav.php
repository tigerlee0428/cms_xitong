<?php

namespace app\admin\model;

use think\Model;


class Nav extends Model
{

    

    

    // 表名
    protected $name = 'nav';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'addtime';

    // 定义时间戳字段名
    protected $createTime = 'addtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'addtime_text'
    ];
    

    



    public function getAddtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['addtime']) ? $data['addtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAddtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
