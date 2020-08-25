<?php
namespace app\api\controller\skt;
use think\Db;
/**
 * 统计接口
 */
class Statistics
{
   
    /**
     * 三端访问数（轮询）
     * @return array
     */
    
   public function visit(){
       $tvVisit_num = Db::name("visits")->where(['device'=>'tv'])->count();
       $moblieVisit_num = Db::name("visits")->where(['device'=>'wx'])->count();
       $pcVisit_num = Db::name("visits")->where(['device'=>'pc'])->count();
       return sktok([
           'tvVisit_num'    => cfg("tvvisit") + $tvVisit_num,
           'moblieVisit_num'=> cfg("wxvisit") + $moblieVisit_num,
           'pcVisit_num'    => cfg("pcvisit") + $pcVisit_num,
       ]);
   }
    
   
}
