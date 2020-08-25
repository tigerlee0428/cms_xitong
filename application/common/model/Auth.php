<?php
namespace app\common\model;

use think\Db;
use think\Model;

class Auth extends Model
{
   protected $name = 'user';
   
   public static function userLoginLogInsert($uid){
       $yesterday_time = strtotime(date("Y-m-d",strtotime("-1 day")));
       $userLoginInfo = Db::name("user_login_log")->where(['uid'=>$uid])->find();
       $continuDay = 0;
       if(!$userLoginInfo || $yesterday_time == $userLoginInfo['day_time']){
           $day_time = strtotime(date("Y-m-d"));
           $sql = "INSERT INTO ".config('database.prefix')."user_login_log (uid,day_time,continuLogin)
        				VALUES ('".$uid."','".$day_time."',1) ON DUPLICATE KEY UPDATE continuLogin = continuLogin + 1,day_time = ".$day_time;
           Db::execute($sql);
           $continuDay = Db::name("user_login_log")->where(['uid'=>$uid])->value("continuLogin");
       }else{
           $day_time = strtotime(date("Y-m-d"));
           Db::name("user_login_log")->where(['uid'=>$uid])->update(['day_time'=>$day_time,'continuLogin'=>1]);
           $continuDay = 1;
       }
      return $continuDay; 
   }
   
   
   
}