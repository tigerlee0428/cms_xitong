<?php

namespace app\admin\model;

use think\Model;


class WorkLog extends Model
{

    

    

    // 表名
    protected $name = 'work_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'reply_time_text',
        'score_time_text'
    ];
    

    



    public function getReplyTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['reply_time']) ? $data['reply_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getScoreTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['score_time']) ? $data['score_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setReplyTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setScoreTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
