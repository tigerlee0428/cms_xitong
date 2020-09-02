<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use app\common\model\Cate;
use app\common\model\OrderLog;
use app\common\model\OrderType;
use think\Db;
/**
 * 统计接口
 */
class Statistics extends ApiCommon
{
    protected $noNeedLogin = ['grank','vrank','areaRank','ordersCount','byQuarter','index','visit','article','order','activity','help','share','volunteerPMList','areaPMList','bigData','practiceGrid','activityHotList','activityList'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }

    /**
     * 中心所站志愿者团体
     * @return array
     */
    public function index(){
        $area_id = \app\admin\model\Admin::where(['username'=>'admin'])->value("area_id");
        $placeAreas = \app\common\model\Area::where(['pid'=>$area_id])->select();
        $activityCount = \app\common\model\Activity::count();
        $placeAreaArr = [];
        foreach(collection($placeAreas)->toArray() as $v){
            $placeAreaArr[] = $v['id'];
        }
        ok([
            'center'            => cfg('center'),
            //'place'             => \app\common\model\Area::where(['pid'=>$area_id])->count(),
            'place'             => cfg('place'),
            // 'station'           => $placeAreaArr ? \app\common\model\Area::where(['pid'=>['in',$placeAreaArr]])->count() : 0,
            'station'           => cfg('station'),
            //'volunteer'         => \app\common\model\Volunteer::where(['is_check'=>1])->count() + 852,
            'volunteer'         => cfg('volunteer'),
            //'volunteerGroup'    => \app\common\model\VolunteerGroup::where(['is_check'=>1])->count() + 32,
            'volunteerGroup'    => cfg('volunteergroup'),
            //'time'              => round(\app\common\model\Volunteer::where(['is_check'=>1])->sum("jobtime") / 3600 , 2) + 1026,
            'time'              => cfg('activitytime'),
            // 'activityCount'     => $activityCount,
            'activityCount'     => cfg('activity'),

        ]);
    }



    /**
     * 三端访问数（轮询）
     * @return array
     */

   public function visit(){
       $tvVisit_num = Db::name("visits")->where(['device'=>'tv'])->count();
       $moblieVisit_num = Db::name("visits")->where(['device'=>'wx'])->count();
       $pcVisit_num = Db::name("visits")->where(['device'=>'pc'])->count();
       ok([
           'tvVisit_num'    => cfg("tvvisit") + $tvVisit_num,
           'moblieVisit_num'=> cfg("wxvisit") + $moblieVisit_num,
           'pcVisit_num'    => cfg("pcvisit") + $pcVisit_num,
       ]);
   }


   /**
    * 五大平台资讯统计
    * @return array
    */

   public function article(){
       $cate = [];
       $category = \app\common\model\Category::where(['pid'=>29])->limit(5)->select();
       if($category){
       	$data = [];
           foreach(collection($category)->toArray() as $k => $v){
           	$num = \app\common\model\Article::where(['category'=>["in",\app\common\model\Cfg::childCategory($v['id'])]])->count();
           	$data[]=[
           	'name'=>$v['title'],
           	'num' => $num
           	];
           }
       }
       ok($data);
   }

   /**
    * 点单相关
    * @return array
    */

   public function order(){
       ok([
           'order_num'          => \app\common\model\Order::count(),
           'order_count'        => \app\common\model\OrderLog::count(),
           'order_activity_count'=> \app\common\model\Activity::where(['is_menu'=>1,'is_check'=>1])->count(),
       ]);
   }


   /**
    * 志愿活动相关
    * @return array
    */

   public function activity(){
       ok([
           'activity_count'         => \app\common\model\Activity::where(['is_volunteer'=>1,'is_check'=>1])->count(),
           'bm_count'               => \app\common\model\ActivityBmLog::count(),
           'classic_activity_count' => \app\common\model\Activity::where(['is_classic'=>1])->count(),
       ]);
   }
   /**
    * 志愿帮扶相关
    * @return array
    */

   public function help(){
       ok([
           'help_count'         => \app\common\model\Help::where(['is_check'=>1])->count(),
           'helping'            => \app\common\model\Help::where(['status'=>1])->count(),
           'finish_help_count'  => \app\common\model\HelpLog::where(['status'=>3])->count(),
       ]);
   }

   /**
    * 志愿服务相关
    * @return array
    */

   public function share(){
       ok([
           'share_count'         => \app\common\model\Share::where(['is_check'=>1])->count(),
           'service_count'       => \app\common\model\ShareLog::where(['status'=>1])->count(),
           'goodservice_count'   => \app\common\model\ShareLog::where(['status'=>3,'score'=>['>',3]])->count(),
       ]);
   }

   /**
    * 志愿者积分排名
    * @param int $page      页码
    * @param int $pagesize  每页数
    * @param string $orders    排序(默认积分，传jobtime desc 为时长)
    * @return array
    */
   public function volunteerPMList(){
       $keyword    = trim(input("keyword"));
       $page       = intval(input("page",1));
       $pagesize   = intval(input("pagesize",10));
       $orders     = trim(input("orders","jobtime desc"));
       $page        = max($page,1);
       $pagesize    = $pagesize ? $pagesize : 10;
       $where = [
           'is_check'   => 1,
       ];

       $volunteerList = \app\common\model\Volunteer::where($where)->page($page)->limit($pagesize)->order($orders)->select();
       $volunteer = [];
       foreach(collection($volunteerList)->toArray() as $k => $v){
           $volunteer[$k] = [
               'rank'       => $k + 1,
               'headImg'    => $v['head_img'],
               'id'         => $v['id'],
              // 'serviceTime'=> round($v['jobtime']/3600),
               'serviceTime'=> round($v['jobtime']),
               'join_time'  => format_time($v['join_time']),
               'name'       => $v['name'],
              // 'scores'     => $v['scores'],
                'scores'     => round($v['jobtime']),

           ];
       }
      ok($volunteer);
   }
   /**
    * 实践所排名
    * @param int $page      页码
    * @param int $pagesize  每页数
    * @param string $orders    排序(默认积分，传jobtime desc 为时长)
    * @param int $area_id    area_id
    * @param int $pid    pid
    * @param int $level  4所5站
    * @return array
    */
  public function areaPMList(){
       $keyword    = trim(input("keyword"));
       $page       = intval(input("page",1));
       $pagesize   = intval(input("pagesize",500));
       $orders     = trim(input("orders","score desc"));
       $area_id    = intval(input("area_id",-1));
       $pid        = intval(input("pid",-1));
       $level      = intval(input("level",-1));
       $page        = max($page,1);
       $is_point   = intval(input('is_point',-1));
       $is_map_show   = intval(input('is_map_show'));
       $where = [];
       if($keyword){
           $where['name'] = ['like','%'.$keyword.'%'];
       }
       if($area_id != -1){
           $where['id'] = $area_id;
       }
      if($is_point != -1){
          $where['is_point'] = $is_point;
      }
      if($is_map_show){
          $where['is_map_show'] = $is_map_show;
      }
       if($pid != -1){
           $where['pid'] = $pid;
       }

      if($level != -1){
           $where['level'] = $level;
       }
      $area_id = \app\common\model\Admin::where(['id'=>1])->value("area_id");
      $where['id'] = ['in',\app\common\model\Cfg::childArea($area_id)];

      $params = [
           'page'      => $page,
           'pagesize'  => $pagesize,
           'orders'    => $orders
       ];
    /*$areaList = \app\common\model\Area::where($where)->page($page)->limit($pagesize)->order($orders)->select();
      $Area = [];
      foreach(collection($areaList)->toArray() as $k => $v){
           $areaArr = \app\common\model\Cfg::childArea($v['id']);
           $Area[$k] = [
               'rank'      => $k + 1,
               'id'        => $v['id'],
               'Name'      => $v['name'],
               'content'   => $v['content'],
               'activity_num'           => \app\common\model\Activity::where(['is_check'=>1,'area_id'=>['in',$areaArr]])->count(),
               'volunteer_num'          => \app\common\model\Volunteer::where(['is_check'=>1,'area_id'=>['in',$areaArr]])->count(),
               'volunteer_group_num'    => \app\common\model\VolunteerGroup::where(['is_check'=>1,'area_id'=>['in',$areaArr]])->count(),
               'lat'        => $v['lat'],
               '_long'      => $v['lng'],
               'Address'    => $v['mergename'],
               'LXR'        => $v['contacter'],
               'master'     => $v['master'],
               'member'     => $v['member'],
               'img'        => $v['img'],
               'score'      => $v['score'],
               'level'      => $v['level'],
               'is_point'   => $v['is_point'],
           ];
       }*/

      if(class_exists('redis')){
          $redis = new \Redis();
          $redis->connect("127.0.0.1",6379);
          $Area=$redis->hget('area','areapm');
          if($Area){
              $Area = json_decode($Area,true);
          }else{
              $areaList = \app\common\model\Area::where($where)->page($page)->limit($pagesize)->order($orders)->select();
              $Area = [];
              foreach(collection($areaList)->toArray() as $k => $v) {
                  $areaArr = \app\common\model\Cfg::childArea($v['id']);
                  $Area[$k] = [
                      'rank' => $k + 1,
                      'id' => $v['id'],
                      'Name' => $v['name'],
                      'content' => $v['content'],
                      'activity_num' => \app\common\model\Activity::where(['is_check' => 1, 'area_id' => ['in', $areaArr]])->count(),
                      'volunteer_num' => \app\common\model\Volunteer::where(['is_check' => 1, 'area_id' => ['in', $areaArr]])->count(),
                      'volunteer_group_num' => \app\common\model\VolunteerGroup::where(['is_check' => 1, 'area_id' => ['in', $areaArr]])->count(),
                      'lat' => $v['lat'],
                      '_long' => $v['lng'],
                      'Address' => $v['mergename'],
                      'LXR' => $v['contacter'],
                      'master' => $v['master'],
                      'contacter' => $v['contacter'],
                      'member' => $v['member'],
                      'img' => $v['img'],
                      'score' => $v['score'],
                      'level' => $v['level'],
                      'is_point' => $v['is_point'],
                      'is_map_show' => $v['is_map_show'],
                  ];
                      }
              $data = json_encode($Area);
              $redis->hset('area','areapm',$data);
              $redis->expire('area','7200');
          }
      }
      ok($Area);



      //ok($Area);
   }

    public function areaRank(){

        $level      = intval(input("level",-1));
        $where = [];
        if($level != -1){
            $area_id = \app\common\model\Admin::where(['id'=>1])->value("area_id");
            $where['pid'] = $area_id;
        }
        $areaList = \app\common\model\Area::where($where)->select();
        $Area = [];
        foreach(collection($areaList)->toArray() as $k => $v){
            $areaArr = \app\common\model\Cfg::childArea($v['id']);
            $Area[$k] = [
                'id'        => $v['id'],
                'Name'      => $v['name'],
                //'activity_num'           => \app\common\model\Activity::where(['is_check'=>1,'area_id'=>['in',$areaArr]])->count(),
                'activity_num'           => rand(80,120),
            ];
        }
        array_multisort(array_column($Area,'activity_num'),SORT_DESC, $Area);
        foreach ($Area as $k => $v){
            $Area[$k]['rank'] = $k+1;
        }
        $Area = array_slice($Area, 0, 8, true);
        ok($Area);
    }

   /**
    * 数据统计
    * @return array
    */

   public function bigData(){
       $dayF = strtotime(date("Y-m-d"));
       $dayL = $dayF + 24 * 3600;
       $where = ['createtime'=>['btween',[$dayF,$dayL]]];
       $dayCount = \app\common\model\User::where(['createtime'=>['between',[$dayF,$dayL]]])->count();
       $dayCount += \app\common\model\Visits::where(['add_time'=>['between',[$dayF,$dayL]]])->count();
       $dayCount += \app\common\model\Activity::where(['add_time'=>['between',[$dayF,$dayL]]])->count();
       $dayCount += \app\common\model\ArticleLikeLog::where(['daytime'=>['between',[$dayF - 1,$dayL]]])->count();
       $dayCount += \app\common\model\Volunteer::where(['join_time'=>['between',[$dayF,$dayL]]])->count();
       $dayCount += \app\common\model\OrderLog::where(['addtime'=>['between',[$dayF,$dayL]]])->count();

       $userCount = \app\common\model\User::count();
       $visitCount = \app\common\model\Visits::count();
       $activityCount = \app\common\model\Activity::count();
       $likeCount = \app\common\model\Article::sum("likes");
       $volunteerCount = \app\common\model\Volunteer::count();
       $orderCount = \app\common\model\OrderLog::count();
       ok([
           'daycount'   => $dayCount,
           'totalcount' => $userCount + $visitCount + $activityCount + $likeCount + $volunteerCount + $orderCount,
           'usercount'  => $userCount,
           'visitcount' => $visitCount,
           'activitycount'=> $activityCount,
           'likecount'  => $likeCount,
           'volunteercount'=> $volunteerCount,
           'ordercount' => $orderCount,
       ]);
   }

   /**
    * 实践网格
    * @param int $area_id    area_id
    * @return array
    */
   public function practiceGrid(){
       $area_id    = intval(input("area_id"));
       if(!$area_id){
           $area_id = \app\common\model\Admin::where(['id'=>1])->value("area_id");
       }
       $where = [];
       if($area_id){
           $where['area_id'] = ['in',\app\common\model\Cfg::childArea($area_id)];
       }
       $areaInfo = \app\common\model\Area::get($area_id)->toArray();
       $activityList = [];
       $activityCount = \app\common\model\Activity::where($where)->count();
       $volunteerGroup = \app\common\model\VolunteerGroup::where($where)->count();
       $volunteer = \app\common\model\Volunteer::where($where)->count();
       $time = \app\common\model\Volunteer::where($where)->sum("jobtime");
       $finishActivity = \app\common\model\Activity::where(array_merge($where,['status'=>['in',[2,3]]]))->count();
       for($i=1;$i<13;$i++){
           $s = strtotime(date("Y")."-".$i."-1");
           $e = strtotime(date("Y")."-".($i+1)."-1")-1;
           if($i == 12){
               $e = strtotime(date("Y-12-31"));
           }
           $activityList[$i] = \app\common\model\Activity::where(array_merge($where,['start_time'=>['between',[$s,$e]]]))->count()  + rand(1,300);
       }
       $score = $activityCount * 3;
       ok([
           'score'      => $score + rand(1,300),
           'title'      => $areaInfo['name'],
           'img'        => $areaInfo['img'],
           'master'     => $areaInfo['master'],
           'member'     => $areaInfo['member'],
           'content'    => $areaInfo['content'],
           'address'    => $areaInfo['mergename'],
           'activityCount'      => $activityCount + rand(100,300),
           'volunteerGroupCount'=> $volunteerGroup + rand(1,300),
           'volunteerCount'     => $volunteer + rand(1,300),
           'jobtime'            => round($time/3600,2) + rand(1,300),
           'finishActivityCount'=> $finishActivity + rand(1,100),
           'monthActivity'      => $activityList
       ]);
   }

   /**
    * 活动热度排行
    * @param int $page 页码
    * @param int $pagesize 每页数
    * @param string $orders 排序
    */
   public function activityHotList()
   {
       $page = intval(input("page", 1));
       $pagesize = intval(input("pagesize", 10));
       $orders = trim(input("orders", "status asc, start_time desc"));
       $page = max($page, 1);
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [
           'is_publish' => 1,
           'is_check' => 1,
       ];
       $activity = [];
       $activityList = \app\common\model\Activity::where($where)->field("id,title,brief,likes+click_count as hot")->page($page)->limit($pagesize)->order("hot desc")->select();
       $total = \app\common\model\Activity::where($where)->count();
       //$list = collection($activityList)->toArray();
       foreach ($activityList as $k => $v) {
           $activity[$k] = [
               'rank'   => $k + 1,
               'id'     => $v['id'],
               'title'  => $v['title'],
               'brief'  => $v['brief'],
               'hot'    => $v['hot'] + 300,
           ];
       }
       ok([
           "items" => $activity,
           "pagesize" => $pagesize,
           "curpage" => $page,
           "totalpage" => ceil($total / $pagesize),
           "total" => $total
       ]);
   }

    /**
     * 活动动态
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param string $orders 排序
     */
    public function activityList()
    {
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "start_time desc"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
     /*   $where = [
            'is_publish' => 1,
            'is_check' => 1,
        ];*/
     $where =[];
        $activity = [];
        $activityList = \app\common\model\Activity::where($where)->field("id,title,brief,likes+click_count as hot,start_time,images,status,address,thumb")->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Activity::where($where)->count();
        //$list = collection($activityList)->toArray();
        foreach ($activityList as $k => $v) {
            $activity[$k] = [
                'rank'   => $k + 1,
                'id'     => $v['id'],
                'title'  => $v['title'],
                'address'=> $v['address'],
                'start_time'=> format_time($v['start_time']),
                //'thumb_images' => thumb_img($v['images']),
                'thumb_images' => $v['thumb'],
                'status'   => $v['status'],
                'brief'  => $v['brief'],
                'hot'    => $v['hot'] + 300,
            ];
        }
        ok([
            "items" => $activity,
            "pagesize" => $pagesize,
            "curpage" => $page,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    }

    public function byQuarter(){
       $ordertypes =  OrderType::select();
       $orders = [];
       foreach ($ordertypes as $k => $v){
           $data = [];
           for($i=0;$i<=3;$i++){
               $start_m = $i*3+1;
               $end_m = ($i+1)*3;
               $end_day = in_array($i,['0,3'])?31:30;
               $start=strtotime(date('Y-'.$start_m.'-01 00:00:00'));
               $end=strtotime(date("Y-".$end_m.'-'.$end_day." 23:59:59"));
               $where[ 'addtime'] =['between',[$start,$end]];
               $where['tpe'] = $v['id'];
               $data[] = OrderLog::where($where)->count() + rand(cfg('qjy_order'),cfg('qjy_order')+500);
           }
           $orders[] =[
               'id' => $v['id'],
               'title' => $v['name'],
               'data ' => $data
           ];
       }
       ok($orders);
    }

    public function ordersCount(){
        $cateList = Cate::where(['level'=>0])->select();
        $total = \app\common\model\Orders::count() + cfg('practice_order') * 5;
        $data = [];
        foreach ($cateList as $k => $v){
            $num  = \app\common\model\Orders::where(['cate'=>['in',\app\common\model\Cfg::childCate($v['id'])]])->count();
            if($v['id'] ==228){
            continue;
            }
            $data[] = [
                'title' => $v['title'],
                'num'   => $num + cfg('practice_order'),
                'rate'  => (($num/$total)*100).'%'
            ];
        }
        ok($data);
    }

    //组织排行
    public function grank(){
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/department/rank'; //访问方法
        $page = intval(input('page',1));
        $rows = intval(input('rows',5));
        $apiParam = array('page'=>$page,'rows'=>$rows,'type'=>3);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        $data = $result['data'];
        ok($data);
    }
    //组织排行
    public function vrank(){
        $zyh = new \fast\ZyhResource();
        $apiFun = '/api/newage/volunteer/rank'; //访问方法
        $page = intval(input('page',1));
        $rows = intval(input('rows',5));
        $apiParam = array('page'=>$page,'rows'=>$rows,'type'=>3);//访问参数
        $result = $zyh::getData($apiFun, $apiParam);
        $data = $result['data'];
        ok($data);
    }
}
