<?php 
namespace app\common\behavior;
class Activity{
    /**
     * 报名成功消息通知
     * 审核通过消息通知
     * @param unknown $params
     */
    public function run(&$params){
        if(isset($params['action'])){
            switch($params['action']){
                case 'bm_success':
                    return $this->bm_success($params);
                break;
                case 'check_success':
                    return $this->check_success($params);
                break;
            }
        }
        return ;
    }
    
    private function bm_success($params){
        \app\common\model\Activity::where(['id'=>$params['id']])->setInc("joincount");
        notice([
            'sys_msg' =>[
                'title'     => __("You have successfully participated in Activity %s",$params['title']),
                'brief'     => __("You have successfully participated in Activity %s",$params['title'])."，".__("The start time of the activity is %s",format_time($params['start_time'])),
                'uid'       => $params['uid'],
            ]            
        ]);
    }
    private function check_success($params){
        if($params['is_check'] == 1){
            notice([
                'sys_msg' =>[
                    'title'     => __("The activities %s you create have been approved",$params['title']),
                    'brief'     => __("The activities %s you create have been approved",$params['title']),
                    'uid'       => $params['uid'],
                ]
            ]);
        }elseif($params['is_check'] == 2){
            notice([
                'sys_msg' =>[
                    'title'     => __("The activity %s you created failed to be audited",[$params['title'],$params['check_case']]),
                    'brief'     => __("The activity %s you created failed to be audited",[$params['title'],$params['check_case']]),
                    'uid'       => $params['uid'],
                ]
            ]);
        }
    }
} 

?>