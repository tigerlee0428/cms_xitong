<?php
namespace app\common\model;

use think\Db;
use think\Model;
class Credits extends Model
{
    protected $name = 'user_integral_rules';
    
    //处理用户行为
    public static function deal_user_event($data){
        $uid        = $data['uid'];
        $area_id    = $data['area_id'];
        $event_code = $data['event_code'];
        $obj_id     = $data['obj_id'];
        $credit_amount = intval($data['scores']);
        $device_id  = isset($data['device_id']) ? $data['device_id'] : '';
        $device_ip  = isset($data['device_ip']) ? $data['device_ip'] : '';
        $cur_time   = isset($data['create_at']) &&  $data['create_at'] ? $data['create_at'] : time();
        $cur_day    = strtotime(date('Y-m-d',$cur_time));
        $sql = "select id,area_id,event_code,event_note,openid_limit,day_limit,grand_type,credit_amount,is_publish,is_delete
            from ".config("database.prefix")."user_integral_rules
            where event_code = ? and is_delete = ?";
        $cur_rule = Db::query($sql, array($event_code,0));
        if($cur_rule){
            $cur_rule = $cur_rule[0];
        }else{
            return false;
        }
        //规则未发布或已删除跳过
        if(!$cur_rule['is_publish'] || $cur_rule['is_delete']){
            return false;
        }
        //行为积分且积分为0跳过
        if(!$cur_rule['grand_type'] && $cur_rule['credit_amount'] == 0){
            return false;
        }
        //自定义积分规则
        if($cur_rule['grand_type'] && $credit_amount == 0){
            return false;
        }
        //自定义积分的分值为传入
        if($cur_rule['grand_type']){
            if($cur_rule['day_limit']){
                $sql = "select count(*) as num from ".config("database.prefix")."user_integral_log where uid = ? and event_code = ?  and create_at >= '".$cur_day."' and create_at < '".($cur_day+86400)."'";
                $cur_log = Db::query($sql, array($uid,$event_code));
                if($cur_log[0]['num'] >= $cur_rule['openid_limit']){
                    return false;
                }
            }
            //积分大于规则积分
            if($cur_rule['credit_amount'] < $credit_amount){
                return false;
            }
            $sql = "select sum(scores) as scores from ".config("database.prefix")."user_integral_log where grand_type = 1 and uid = ? and event_code = ?";
            $sc = Db::query($sql,[$uid,$event_code]);
            if($sc && $sc[0]){                
                if($credit_amount > 0){
                    $max_sc = $cur_rule['credit_amount'] - $sc[0]['scores'];
                    if($max_sc < $credit_amount){
                        //加分总分大于最高分
                        return false;
                    }
                }elseif($credit_amount < 0){
                    $max_sc = 0 - $sc[0]['scores'] - $cur_rule['credit_amount'];
                    if(abs($max_sc) < abs($credit_amount)){
                        //减分大于当前拥有分
                        return false;
                    }
                }               
            }
        }
        //行为积分赋值
        if(!$cur_rule['grand_type']){
            $credit_amount = $cur_rule['credit_amount'];
        }
        //如果有计分次数
        if($cur_rule['openid_limit']){
            $sql = "select count(*) as num from ".config("database.prefix")."user_integral_log where uid = ? and event_code = ?";
            if($cur_rule['day_limit']){
                $sql .= " and create_at >= '".$cur_day."' and create_at < '".($cur_day+86400)."'";
            }
            $cur_log = Db::query($sql, array($uid,$event_code));
            if($cur_log[0]['num'] >= $cur_rule['openid_limit']){
                return false;
            }
        }
        
        $params = array(
            'uid'        => $uid,
            'area_id'    => $area_id,
            'event_code' => $event_code,
            'event_note' => $cur_rule['event_note'],
            'obj_id'     => $obj_id,
            'scores'     => $credit_amount,
            'note'       => trim($data['note']),
            'device_id'  => $device_id,
            'device_ip'  => $device_ip,
            'create_at'  => $cur_time,
            'grand_type' => $cur_rule['grand_type']
        );
        return self::change_user_scores($params);
    }  
    
    
    //修改用户积分
    public static function change_user_scores($params){
        $params = [
            'uid'        => trim($params['uid']),
            'area_id'    => intval($params['area_id']),
            'event_code' => trim($params['event_code']),
            'event_note' => trim($params['event_note']),
            'obj_id'     => trim($params['obj_id']),
            'scores'     => intval($params['scores']),
            'note'       => trim($params['note']),
            'device_id'  => trim($params['device_id']),
            'device_ip'  => trim($params['device_ip']),
            'create_at'  => intval($params['create_at']),
            'grand_type' => intval($params['grand_type']),
        ];
        /* 插入积分更改日志 */
        Db::startTrans();  //启用事务
        try{ 
            $log_data = [
                'uid'       => $params['uid'],
                'area_id'   => $params['area_id'],
                'event_code'=> $params['event_code'],
                'event_note'=> $params['event_note'],
                'obj_id'    => $params['obj_id'],
                'scores'    => $params['scores'],
                'note'      => $params['note'],
                'device_id' => $params['device_id'],
                'device_ip' => $params['device_ip'],
                'grand_type'=> $params['grand_type'],
                'create_at' => time(),
            ];
            Db::execute("set sql_mode=''");
            Db::name('user_integral_log')->insert($log_data);
            $cur_daytime = strtotime(date('Y-m-d',$params['create_at']));
        
            $sql = "INSERT INTO ".config('database.prefix')."user_integral (uid,scores)
    				VALUES ('".$params['uid']."','".$params['scores']."') ON DUPLICATE KEY UPDATE scores = scores +".$params['scores'];
            $result = Db::execute($sql);
            $scores = Db::name("user_integral")->where(['uid'=>$params['uid']])->value("scores");
            $sql = "update ".config("database.prefix")."user set score = ".$scores." where id= ?";
            $result1 = Db::execute($sql,[$params['uid']]);
            if($result && $result1){
                notice([
                    'sys_msg' => [
                        'title'     => $params['event_note'] . __("获得%s积分",$params['scores']),
                        'brief'     => $params['event_note'] . __("获得%s积分",$params['scores']),
                        'uid'       => $params['uid'],
                        'tpe'       => 0,
                    ]
                ]);
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            // 回滚事务
            //print_r($e);exit;
            Db::rollback();
            return false;
        }
    }
    
    //处理志愿者行为
    public static function deal_volunteer_event($data){
        $vid        = $data['vid'];
        $area_id    = $data['area_id'];
        $event_code = $data['event_code'];
        $obj_id     = $data['obj_id'];
        $credit_amount = intval($data['scores']);
        $device_id  = isset($data['device_id']) ? $data['device_id'] : '';
        $device_ip  = isset($data['device_ip']) ? $data['device_ip'] : '';
        $cur_time   = isset($data['create_at']) &&  $data['create_at'] ? $data['create_at'] : time();
        $cur_day    = strtotime(date('Y-m-d',$cur_time));
        $sql = "select id,area_id,event_code,event_note,openid_limit,day_limit,grand_type,credit_amount,is_publish,is_delete
            from ".config("database.prefix")."volunteer_integral_rules
            where event_code = ? and is_delete = ?";
        $cur_rule = Db::query($sql, array($event_code,0));
        if($cur_rule){
            $cur_rule = $cur_rule[0];
        }else{
            return false;
        }
        //规则未发布或已删除跳过
        if(!$cur_rule['is_publish'] || $cur_rule['is_delete']){
            return false;          
        }
        //行为积分且积分为0跳过
        if(!$cur_rule['grand_type'] && $cur_rule['credit_amount'] == 0){
            return false;         
        }
        //自定义积分规则
        if($cur_rule['grand_type'] && $credit_amount == 0){
            return false;
        }
        //自定义积分的分值为传入
        if($cur_rule['grand_type']){
            //积分大于规则积分
            if($cur_rule['credit_amount'] < $credit_amount){
                return false;           
            }
            $sql = "select sum(scores) as scores from ".config("database.prefix")."volunteer_integral_log where vid = ? and event_code = ?";
            $sc = Db::query($sql,[$vid,$event_code]);
            if($sc && $sc[0]){                
                if($credit_amount > 0){
                    $max_sc = $cur_rule['credit_amount'] - $sc[0]['scores'];
                    if($max_sc < $credit_amount){
                        //加分总分大于最高分
                        return false;
                    }
                }elseif($credit_amount < 0){
                    $max_sc = 0 - $sc[0]['scores'] - $cur_rule['credit_amount'];
                    if(abs($max_sc) < abs($credit_amount)){
                        //减分大于当前拥有分
                        return false;
                    }
                }               
            }
        }
        //行为积分赋值
        if(!$cur_rule['grand_type']){
            $credit_amount = $cur_rule['credit_amount'];
        }
        //如果有计分次数
        if($cur_rule['openid_limit']){
            $sql = "select count(*) as num from ".config("database.prefix")."volunteer_integral_log where vid = ? and event_code = ?";
            if($cur_rule['day_limit']){
                $sql .= " and create_at >= '".$cur_day."' and create_at < '".($cur_day+86400)."'";
            }
            /* elseif($cur_rule['month_limit']){
                $BeginDate = date('Y-m-01', strtotime(date("Y-m-d")));
                $EndDate = date('Y-m-d', strtotime("$BeginDate +1 month"));
                $sql .= " and create_at >= '".strtotime($BeginDate)."' and create_at < '".(strtotime($EndDate) - 1)."'";
            } */
            $cur_log = Db::query($sql, array($vid,$event_code));
            if($cur_log[0]['num'] >= $cur_rule['openid_limit']){
                return false;
            }
            
        }
        $params = array(
            'vid'        => $vid,
            'area_id'    => $area_id,
            'event_code' => $event_code,
            'event_note' => $cur_rule['event_note'],
            'obj_id'     => $obj_id,
            'scores'     => $credit_amount,
            'note'       => trim($data['note']),
            'device_id'  => $device_id,
            'device_ip'  => $device_ip,
            'create_at'  => $cur_time,
            'grand_type' => $cur_rule['grand_type'],
        );
        return self::change_volunteer_scores($params);
    }
    
    
    //修改志愿者积分
    public static function change_volunteer_scores($params){
        $params = [
            'vid'        => trim($params['vid']),
            'area_id'    => intval($params['area_id']),
            'event_code' => trim($params['event_code']),
            'event_note' => trim($params['event_note']),
            'obj_id'     => trim($params['obj_id']),
            'scores'     => intval($params['scores']),
            'note'       => trim($params['note']),
            'device_id'  => trim($params['device_id']),
            'device_ip'  => trim($params['device_ip']),
            'create_at'  => intval($params['create_at']),
            'grand_type' => intval($params['grand_type']),
        ];
        /* 插入积分更改日志 */
        Db::startTrans();  //启用事务
        try{
            $log_data = [
                'vid'       => $params['vid'],
                'area_id'   => $params['area_id'],
                'event_code'=> $params['event_code'],
                'event_note'=> $params['event_note'],
                'obj_id'    => $params['obj_id'],
                'scores'    => $params['scores'],
                'note'      => $params['note'],
                'device_id' => $params['device_id'],
                'device_ip' => $params['device_ip'],
                'grand_type'=> $params['grand_type'],
                'create_at' => time(),
            ];
            Db::execute("set sql_mode=''");
            Db::name('volunteer_integral_log')->insert($log_data);
            $cur_daytime = strtotime(date('Y-m-d',$params['create_at']));
            $season = date('Y').'-'.ceil((date('n'))/3);
            $sql = "INSERT INTO ".config('database.prefix')."volunteer_integral (vid,scores)
    				VALUES ('".$params['vid']."','".(cfg("volunteer_score") + $params['scores'])."') ON DUPLICATE KEY UPDATE scores = scores +".$params['scores'];
            $result = Db::execute($sql);
            $newScore = Db::name("volunteer_integral")->where(['vid'=>$params['vid']])->value("scores");
            $sql = "update ".config("database.prefix")."volunteer set scores = ".$newScore." where id= ?";
            $result1 = Db::execute($sql,[$params['vid']]);
            if($result && $result1){
                notice([
                    'sys_msg' => [
                        'title'     => $params['event_note'] . __("获得%s积分",$params['scores']),
                        'brief'     => $params['event_note'] . __("获得%s积分",$params['scores']),
                        'uid'       => \app\admin\model\Volunteer::where(['id'=>$params['vid']])->value("uid"),
                        'tpe'       => 0,
                    ]
                ]);
            }
            Db::commit();
            return 0;
        } catch (\Exception $e) {
            // 回滚事务
            //print_r($e);exit;
            Db::rollback();
            return false;
        }
    }
}