<?php

namespace app\admin\model;

use think\Model;


class ActivityComment extends Model
{

    

    

    // 表名
    protected $name = 'activity_comment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'iswinning_text',
        'addtime_text'
    ];
    

    
    public function getIswinningList()
    {
        return ['1' => __('Iswinning 1'), '0' => __('Iswinning 0')];
    }


    public function getIswinningTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['iswinning']) ? $data['iswinning'] : '');
        $list = $this->getIswinningList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAddtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['addtime']) ? $data['addtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAddtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    function get_gift(){
        //拼装奖项数组
        // 奖项id，奖品，概率
        $prize_arr = array(
            '0' => array('id'=>1,'prize'=>'平板电脑','v'=>0),
            '1' => array('id'=>2,'prize'=>'数码相机','v'=>0),
            '2' => array('id'=>3,'prize'=>'音箱设备','v'=>0),
            '3' => array('id'=>4,'prize'=>'4G优盘','v'=>5),
            '4' => array('id'=>5,'prize'=>'10Q币','v'=>0),
            '5' => array('id'=>6,'prize'=>'空奖','v'=>5),
        );
        foreach ($prize_arr as $key => $val) {
            $arr[$val['id']] = $val['v'];//概率数组
        }

        $rid = $this->get_rand($arr); //根据概率获取奖项id
        $res['yes'] = $prize_arr[$rid-1]['prize']; //中奖项
        unset($prize_arr[$rid-1]); //将中奖项从数组中剔除，剩下未中奖项
        shuffle($prize_arr); //打乱数组顺序
        for($i=0;$i<count($prize_arr);$i++){
            $pr[] = $prize_arr[$i]['prize']; //未中奖项数组
        }
        $res['no'] = $pr;
        // var_dump($res);
        if($res['yes']!='空奖'){
            $result['status']=1;
            $result['name']=$res['yes'];
        }else{
            $result['status']=-1;
            $result['msg']=$res['yes'];
        }
        //return $result;
        var_dump($result);
    }

    //计算中奖概率
    function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        // var_dump($proSum);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {

            $randNum = mt_rand(1, $proSum); //返回随机整数

            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }


}
