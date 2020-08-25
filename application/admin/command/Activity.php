<?php
namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class Activity extends Command
{
    protected function configure()
    {
        $this->setName('Activity')->setDescription('Here is the remark ');
        $this->addArgument('name', 1); //必传参数
    }

    protected function execute(Input $input, Output $output)
    {
        $args = $input->getArguments();
        $name = $args['name'];
        switch($name){
            case "activity"://维护活动状态
                $this->Activity();
                break;
            case "vote"://维护投票活动状态
                $this->Vote();
                break;
            case "activityRemind"://活动开始前通知
                $this->activityRemind();
                break;
            case "activityExamine"://考核活动次数、时长、参与人数
                $this->activityExamine();
                break;
            case "activityJobTime"://同步活动时长
                $this->activityJobTime();
                break;
            case "order"://同步活动时长
                $this->order();
                break;
            default:
                echo md5(\think\Env::get("database.database"));
                //echo "wx-notice-msg-list-".md5(cfg("name"));
        }
        
    }
    
    //活动状态维护
    private function Activity(){
        //已发布非结束活动
        $where = [
            "is_publish"        => 1,
            "status"            => ['in',[0,1]]
        ];
        $activityList = \app\admin\model\Activity::where($where)->select();
        if($activityList){
            $activityList = collection($activityList)->toArray();
            foreach($activityList as $k => $v){
                $cur_time = time();
                $status = 0;
                if($v['start_time'] < $cur_time){
                    $status = 1;
                }
                if($v['end_time'] < $cur_time){
                    $status = 2;
                }
                if($v['status'] == $status){
                    continue;
                }
                \app\admin\model\Activity::update(['status'=>$status], ['id'=>$v['id']]);
            }
            echo 'Complete activity status Tour!';
        }else{
            echo 'No activity needs to change state!';
        }
    
    }
    
    //投票活动状态维护
    private function Vote(){
        //已发布非结束活动
        $where = [
            "is_publish"        => 1,
            "is_check"          => 1,
            "status"            => ['in',[0,1]]
        ];
        $voteList = \app\admin\model\Vote::where($where)->select();
        if($voteList){
            $voteList = collection($voteList)->toArray();
            foreach($voteList as $k => $v){
                $cur_time = time();
                $status = 0;
                if($v['start_time'] < $cur_time){
                    $status = 1;
                }
                if($v['end_time'] < $cur_time){
                    $status = 2;
                }
                if($v['status'] == $status){
                    continue;
                }
                \app\admin\model\Vote::update(['status'=>$status], ['id'=>$v['id']]);
            }
            echo 'Complete Vote activity status Tour!';
        }else{
            echo 'No Vote activity needs to change state!';
        }
        
    }
    
    //活动开始前消息通知
    private function activityRemind(){
        $where = [
            'is_publish'        => 1,
            'status'            => 0,
            'is_notify'         => 0,
        ];
        $activityList = \app\admin\model\Activity::where($where)->select();
        if($activityList){
            $activityList = collection($activityList)->toArray();
            foreach($activityList as $k => $v){
                $cur_time = time() + 3600 * 24;
                if($cur_time > $v['start_time']){
                    $bmwhere = [
                        'aid'       => $v['id'],
                        'is_pass'   => 1
                    ];
                    $bmList = \app\admin\model\ActivityBmLog::where($bmwhere)->select();
                    if($bmList){
                        $bmList = collection($bmList)->toArray();
                        foreach($bmList as $key => $val){
                            notice([
                                'sys_msg'   => [
                                    'title'     => '您报名的活动：'.$v['title'].'马上要开始了!!',
                                    'brief'     => '您报名的活动：'.$v['title'].'马上要开始了!!活动开始时间为：'.date($v['start_time']),
                                    'uid'       => $val['uid']
                                ]
                            ]);
                        }
                        \app\admin\model\Activity::update(['is_notify'=>1],['id'=>$v['id']]);
                    }
                }                
            }
            echo 'Notify finished!';
        }else{
            echo 'No activity needs to notify!';
        }
    }
    //考核活动次数、时长、参与人数
    private function activityExamine(){
        $where = [
            'is_publish'        => 1,
            'is_check'          => 1,
            'status'            => 3,
            'is_report'         => 1,
            'is_notify'         => ['<>',99]
        ];
        $activityList = \app\admin\model\Activity::where($where)->select();
        if($activityList){
            $activityList = collection($activityList)->toArray();
            foreach($activityList as $k => $v){
                if($v['is_notify'] != 99){
                    $bmCount = \app\admin\model\ActivityBmLog::where(['aid'=>$v['id']])->count();
                    $activity_time = $v['servicetime'] ? $v['servicetime'] * 3600 : $v['end_time'] - $v['start_time'];
                    if($v['is_menu']){
                        \app\admin\model\Area::where(['id'=>$v['area_id']])->setInc("activity_join_count",$bmCount);
                        \app\admin\model\Area::where(['id'=>$v['area_id']])->setInc("activity_time",$activity_time);
                        \app\admin\model\Area::where(['id'=>$v['area_id']])->setInc("activity_num");
                    }elseif($v['is_volunteer']){
                        \app\admin\model\VolunteerGroup::where(['id'=>$v['group_id']])->setInc("activity_join_count",$bmCount);
                        \app\admin\model\VolunteerGroup::where(['id'=>$v['group_id']])->setInc("activity_time",$activity_time);
                        \app\admin\model\VolunteerGroup::where(['id'=>$v['group_id']])->setInc("activity_num");
                    }
                    \app\admin\model\Activity::update(['is_notify'=>99],['id'=>$v['id']]);
                }
            }
            echo 'Synchronous completion!';
        }else{
            echo 'No activity needs to Synchronous data!';
        }
    }
    
    //同步志愿苏州活动时长
    private function activityJobTime(){
        $where = [
            'is_publish'        => 1,
            'is_check'          => 1,
            'status'            => 3,
            'is_volunteer'      => 1,
        ];
        $activityList = \app\admin\model\Activity::where($where)->select();
        if($activityList){
            $activityList = collection($activityList)->toArray();
            foreach($activityList as $k => $v){
                $bmLog = \app\admin\model\ActivityBmLog::where(['aid'=>$v['id']])->select();
                if($bmLog){
                    $bmLog = collection($bmLog)->toArray();
                    foreach($bmLog as $key => $val){
                        if(!$val['is_report']){
                            $userInfo = \app\admin\model\User::get($val['uid'])->toArray();
                            if($userInfo){
                                if($userInfo['is_volunteer']){
                                    $data = [
                                        'releaseid'     => $v['releaseid'],
                                        'idcard'        => \app\admin\model\Volunteer::where(['id'=>$userInfo['vid']])->value("card"),
                                        'jointime'      => date("Y-m-d",$val['addtime']),
                                        'servertime'    => round(($v['end_time'] - $v['start_time'])/3600),
                                        'id'            => $val['id']
                                    ];
                                    $data['action'] = 'report';
                                    \think\Hook::listen("volunteer",$data);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    
    //百姓点单状态脚本
    private function order(){
        $where = [
            'status'    => ['in',[0,1]]
        ];
        $orderPeriod = \app\admin\model\OrderPeriod::where($where)->select();
        if($orderPeriod){
           foreach(collection($orderPeriod)->toArray() as $k => $v){
               $cur_time = time();
               $status = 0;
               if($v['start_time'] < $cur_time){
                   $status = 1;
               }
               if($v['end_time'] < $cur_time){
                   $status = 2;
               }
               if($v['status'] == $status){
                   continue;
               }
               \app\admin\model\OrderPeriod::update(['status'=>$status], ['id'=>$v['id']]);
               if($status == 2){
                   $order = \app\admin\model\Order::where(['id'=>$v['order_id']])->find();
                   if($order && $order['is_auto'] && $order['cycle']){
                       $now = time();
                       $end = $now + $order['cycle'] * 24 * 3600;
                       $data = [
                           'order_id'   => $v['order_id'],
                           'start_time' => $now,
                           'end_time'   => $end,
                           'area_id'    => $order['area_id'],
                           'status'     => 1,
                           'add_time'   => time()
                       ];
                       $orderperiod_id = \app\admin\model\OrderPeriod::insertGetId($data);
                       if($orderperiod_id){
                           \app\admin\model\Order::where(['id'=>$v['order_id']])->update(['cur_period'=>$orderperiod_id]);
                           echo 'New orderPeriod create!\n';
                       }
                   }
               }
           }
           echo 'Finish change state!';
        }else{
            echo 'No order needs to change state!';
        }
    }
}