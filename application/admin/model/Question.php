<?php

namespace app\admin\model;

use think\Model;


class Question extends Model
{

    

    

    // 表名
    protected $name = 'question';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'answer_text'
    ];
    

    public function getAnswerList()
    {
        return ['A' => __('Answer a'), 'B' => __('Answer b'), 'C' => __('Answer c'), 'D' => __('Answer d')];
    }


    public function getAnswerTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['answer']) ? $data['answer'] : '');
        $list = $this->getAnswerList();
        return isset($list[$value]) ? $list[$value] : '';
    }


}
