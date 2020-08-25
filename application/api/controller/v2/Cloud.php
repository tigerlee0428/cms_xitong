<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\Db;
/**
 * 云数据统计接口
 */
class Cloud extends ApiCommon
{
    protected $noNeedLogin = ['workOrderPMList','workOrderTpe','workOrderList','workOrder','server','shareList','helpList','realTimeHelp','volunteerGroupPMList','volunteer','volunteerPMList','orderList','realTimeOrderList','orderCount','placeList','practicalField','articlePMList','likeArticle','monthArticleCount','index','visit','hotArticle','hotActivity','articleCount','article','order','activity','help','share','volunteerPMList','areaPMList','bigData','practiceGrid','activityHotList'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }

    /**
     * 所站志愿者活动数据
     * @ApiReturnParams   (name="center", type="int", description="中心数")
     * @ApiReturnParams   (name="place", type="int", description="实践所数")
     * @ApiReturnParams   (name="station", type="int", description="实践站数")
     * @ApiReturnParams   (name="spot", type="int", description="实践点数")
     * @ApiReturnParams   (name="volunteer", type="int", description="志愿者数")
     * @ApiReturnParams   (name="volunteerGroup", type="int", description="志愿团体数")
     * @ApiReturnParams   (name="volunteerTime", type="int", description="志愿总时长")
     * @ApiReturnParams   (name="activity", type="int", description="活动数")
     * @ApiReturnParams   (name="activityJoin", type="int", description="活动参与数")
     * @ApiReturnParams   (name="activityTime", type="int", description="活动总时长")
     * @return array
     */
    public function index(){
        $area_id = \app\admin\model\Admin::where(['username'=>'admin'])->value("area_id");
        $placeAreas = \app\common\model\Area::where(['pid'=>$area_id])->select();
        $placeAreaArr = [];
        foreach(collection($placeAreas)->toArray() as $v){
            $placeAreaArr[] = $v['id'];
        }
        $activityTime = \app\common\model\Activity::where(['is_check'=>1])->field("sum(end_time - start_time) as activity_time")->find();
        ok([
            'center'            => cfg("center"),
            'place'             => cfg("place") + \app\common\model\Area::where(['pid'=>$area_id])->count(),
            'station'           => $placeAreaArr ? (cfg("station") + \app\common\model\Area::where(['pid'=>['in',$placeAreaArr]])->count()) : cfg("station"),
            'spot'              => cfg("spot"),
            'volunteer'         => cfg("volunteer") + \app\common\model\Volunteer::where(['is_check'=>1])->count(),
            'volunteerGroup'    => cfg("volunteergroup") + \app\common\model\VolunteerGroup::where(['is_check'=>1])->count(),
            'volunteerTime'     => round(\app\common\model\Volunteer::where(['is_check'=>1])->sum("jobtime") / 3600 , 2),
            'activity'          => cfg("activity") + \app\common\model\Activity::where(['is_check'=>1])->count(),
            'activityJoin'      => cfg("activityjoin") + \app\common\model\ActivityBmLog::where(['is_pass'=>1])->count(),
            'activityTime'      => cfg("activitytime") + round($activityTime['activity_time'] / 3600 ,2),
            'order'             =>  \app\common\model\OrderLog::count(),

        ]);
    }



    /**
     * 三端访问数（轮询）
     * @ApiReturnParams   (name="tvVisit_num", type="int", description="电视端访问数")
     * @ApiReturnParams   (name="moblieVisit_num", type="int", description="移动端访问数")
     * @ApiReturnParams   (name="pcVisit_num", type="int", description="PC端访问数")
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
    * 热门点击资讯
    * @param int $pagesize 每页数
    * @param int $is_img 是否只图
    * @return array
    */
   public function hotArticle(){
       $pagesize = intval(input("pagesize", 10));
       $is_img = intval(input("is_img"));
       $page = 1;
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [
           'is_check' => 1,
       ];
       if($is_img){
           $where['tpe'] = ['in',[2,3,4,5]];
       }

       $article = [];
       $articleList = \app\common\model\Article::where($where)->field("id,title,brief,area_id,img,add_time,likes+click_count as hot")->page($page)->limit($pagesize)->order("hot desc")->select();
       $total = \app\common\model\Article::where($where)->count();
       //$list = collection($articleList)->toArray();
       foreach ($articleList as $k => $v) {
           $article[$k] = [
               'rank'   => $k + 1,
               'id'     => $v['id'],
               'title'  => $v['title'],
               'brief'  => $v['brief'],
               'img'    => $v['img'],
               'format_add_time' => format_time_moment($v['add_time'],"Y-m-d"),
               'hot'    => $v['hot'] + 300,
               'area_info' => \app\common\model\Area::where(['id'=>$v['area_id']])->field("id,name,lng,lat")->find(),
           ];
       }
       ok([
           "items" => $article,
           "pagesize" => $pagesize,
           "curpage" => $page,
           "totalpage" => ceil($total / $pagesize),
           "total" => $total
       ]);
   }

   /**
    * 热门点击活动
    * @param int $pagesize 每页数
    * @param int $is_classic 是否是经典志愿活动
    * @return array
    */
   public function hotActivity(){
       $is_classic = intval(input("is_classic"));
       $pagesize = intval(input("pagesize", 10));
       $page = 1;
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [
           'is_check' => 1,
           'is_publish' => 1
       ];

       if($is_classic){
           $where['is_classic'] = 1;
           $where['is_volunteer'] = 1;
       }
       $activity = [];
       $activityList = \app\common\model\Activity::where($where)->field("id,title,brief,images,status,add_time,likes+click_count as hot")->page($page)->limit($pagesize)->order("hot desc")->select();
       $total = \app\common\model\Activity::where($where)->count();
       //$list = collection($activityList)->toArray();
       foreach ($activityList as $k => $v) {
           $activity[$k] = [
               'rank'   => $k + 1,
               'id'     => $v['id'],
               'title'  => $v['title'],
               'brief'  => $v['brief'],
               'images'    => $v['images'],
               'status'    => $v['status'],
               'joincount' => $v['joincount'],
               'likes'     => $v['likes'],
               'address'   => $v['address'],
               'x'         => $v['x'],
               'y'         => $v['y'],
               'area'      => \app\common\model\Area::where(['id'=>$v['area_id']])->value("name"),
               'format_add_time' => format_time_moment($v['add_time'],"Y-m-d"),
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
    * 资讯累计发布量
    * @ApiReturnParams   (name="totalCount", type="int", description="资讯总发布量")
    * @ApiReturnParams   (name="monthCount", type="int", description="当月发布量")
    * @ApiReturnParams   (name="dayCount", type="int", description="当天发布量")
    * @ApiReturnParams   (name="totalViewCount", type="int", description="资讯总点击数")
    * @ApiReturnParams   (name="monthViewCount", type="int", description="资讯当月点击数")
    * @return array
    */
   public function articleCount(){
        $beginDate = date('Y-m-01', strtotime(date("Y-m-d")));
        $endDate = date('Y-m-d 23:59:59', strtotime("$beginDate +1 month -1 day"));
        $first = strtotime(date("Y-m-d"));
        $last = $first + 3600 * 24;

        ok([
            'totalCount'    => \app\common\model\Article::where(['is_check'=>1])->count(),
            'monthCount'    => \app\common\model\Article::where(['is_check'=>1,'add_time'=>['between',[strtotime($beginDate),strtotime($endDate)]]])->count(),
            'dayCount'      => \app\common\model\Article::where(['is_check'=>1,'add_time'=>['between',[$first,$last]]])->count(),
            'totalViewCount'=> \app\common\model\Article::where(['is_check'=>1])->sum("click_count"),
            'monthViewCount'=> \app\common\model\Article::where(['is_check'=>1,'add_time'=>['between',[strtotime($beginDate),strtotime($endDate)]]])->sum("click_count"),
        ]);
   }

   /**
    * 每月发布量
    * @return array
    */
   public function monthArticleCount(){
       $month = date("m");
       $monthArr = [1,2,3,4,5,6,7,8,9,10,11,12];
       $cutArr = array_slice($monthArr,0, $month);
       $cutArrOther = array_slice($monthArr,$month);
       $monthArr = array_merge($cutArrOther,$cutArr);
       $data = [];
       foreach ($monthArr as $k => $v){
           if($v > $month){
               $year = date("Y") - 1;
           }else{
               $year = date("Y");
           }
           $firstDay = date('Y-m-01', strtotime(date("$year-$v-d")));
           $latDay = date('Y-m-d 23:59:59', strtotime("$firstDay +1 month -1 day"));
           $data[] = [
               //'fisrt'  => $firstDay,
               //'last'   => $latDay,
               'month'  => $v,
               'count'  => \app\common\model\Article::where(['is_check'=>1,'add_time'=>['between',[strtotime($firstDay),strtotime($latDay)]]])->count(),
           ];
       }
       ok($data);
   }


   /**
    * 五大平台资讯统计
    * @return array
    */

   public function article(){
       $cate = [];
       $category = \app\common\model\Category::where(['pid'=>0])->limit(5)->select();
       if($category){
           foreach(collection($category)->toArray() as $k => $v){
               $cate[$v['title']] = \app\common\model\Article::where(['category'=>["in",\app\common\model\Cfg::childCategory($v['id'])]])->count();
           }
       }
       ok($cate);
   }


   /**
    * 五大平台资讯点赞统计
    * @return array
    */

   public function likeArticle(){
       $cate = [];
       $category = \app\common\model\Category::where(['pid'=>0])->limit(5)->select();
       if($category){
           foreach(collection($category)->toArray() as $k => $v){
               $cate[$v['title']] = \app\common\model\Article::where(['category'=>["in",\app\common\model\Cfg::childCategory($v['id'])]])->sum("likes");
           }
       }
       ok($cate);
   }


   /**
    * 所站发布排行榜
    * @param int $is_station 是否站排行
    * @return array
    */
   public function articlePMList(){
       $is_station = intval(input("is_station"));
       $area_id = \app\admin\model\Admin::where(['username'=>'admin'])->value("area_id");
       $stats = \app\common\model\Area::where(['pid'=>$area_id])->select();

       if($is_station){
           $placeAreaArr = [];
           foreach(collection($stats)->toArray() as $v){
               $placeAreaArr[] = $v['id'];
           }
           $stats = \app\common\model\Area::where(['pid'=>['in',$placeAreaArr]])->select();
       }
       $data = [];
       foreach($stats as $k => $v){
           $data[] = [
               'title'  => $v['name'],
               'count'  => \app\common\model\Article::where(['is_check'=>1,'area_id'=>['in',\app\common\model\Cfg::childArea($v['id'])]])->count(),
           ];
       }
       $finalData = array_sort($data, 'count');
       foreach($finalData as $k => &$v){
           $v['rank'] = $k + 1;
       }
       ok($finalData);
   }


   /**
    * 实践阵地数据统计
    * @ApiReturnParams   (name="totalField", type="int", description="阵地总数")
    * @ApiReturnParams   (name="availableField", type="int", description="可使用阵地")
    * @ApiReturnParams   (name="buildingField", type="int", description="将投入阵地")
    * @ApiReturnParams   (name="orderCount", type="int", description="阵地总预约次数")
    * @return array
    */

   public function practicalField(){
       $totalField = \app\admin\model\Place::count();
       $orderCount = \app\admin\model\Place::sum("activity_count");
       ok([
           'totalField'     => cfg("totalfield") + $totalField,
           'availableField' => cfg("availablefield"),
           'buildingField'  => cfg("buildingfield"),
           'orderCount'     => $orderCount,
       ]);
   }

   /**
    * 实践阵地列表
    * @param int $page 页码
    * @param int $pagesize 每页数
    * @param string $orders 排序
    * @return array
    */


   public function placeList(){
       $page = intval(input("page", 1));
       $pagesize = intval(input("pagesize", 10));
       $orders = trim(input("orders", "id desc"));
       $page = max($page, 1);
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [];


       $place = [];
       $placeList = \app\admin\model\Place::where($where)->page($page)->limit($pagesize)->order($orders)->select();
       $total = \app\admin\model\Place::where($where)->count();
       //$list = collection($placeList)->toArray();
       foreach ($placeList as $k => $v) {
           $place[$k] = [
               'rank'       => $k + 1,
               'id'         => $v['id'],
               'name'       => $v['name'],
               'address'    => $v['address'],
               'img'        => $v['img'],
               'area_id'    => $v['area_id'],
               'activity_count'  => $v['activity_count'],
               'number'     => $v['number'],
               'order_time' => $v['order_time'],
               'x'          => $v['x'],
               'y'          => $v['y'],
           ];
       }
       ok([
           "items" => $place,
           "pagesize" => $pagesize,
           "curpage" => $page,
           "totalpage" => ceil($total / $pagesize),
           "total" => $total
       ]);
   }



   /**
    * 五大平台点单统计
    * @param int $is_month 是否是本月
    * @return array
    */

   public function orderCount(){
       $is_month = intval(input("is_month"));
       $cate = [];
       $total = 0;
       $where = [];
       $beginDate = date('Y-m-01', strtotime(date("Y-m-d")));
       $endDate = date('Y-m-d 23:59:59', strtotime("$beginDate +1 month -1 day"));
       if($is_month){
           $where['addtime'] = ['between',[strtotime($beginDate),strtotime($endDate)]];
       }
       $category = \app\common\model\OrderType::limit(5)->order("display asc")->select();
       if($category){
           foreach(collection($category)->toArray() as $k => $v){
               $orderIds = [];
               $orderList = \app\common\model\Order::where(['tpe'=>$v['id']])->select();
               foreach($orderList as $val){
                   $orderIds[] = $val['id'];
               }
               $where['order_id'] = ["in",$orderIds];
               $count = \app\common\model\OrderLog::where($where)->count();
               $cate[$v['name']] = $count;
               $total += $count;
           }
       }
       ok(['items'=>$cate,'total'=>$total]);
   }




   /**
    * 每月点单量
    * @return array
    */
   public function monthOrderLogCount(){
       $month = date("m");
       $monthArr = [1,2,3,4,5,6,7,8,9,10,11,12];
       $cutArr = array_slice($monthArr,0, $month);
       $cutArrOther = array_slice($monthArr,$month);
       $monthArr = array_merge($cutArrOther,$cutArr);
       $data = [];
       foreach ($monthArr as $k => $v){
           if($v > $month){
               $year = date("Y") - 1;
           }else{
               $year = date("Y");
           }
           $firstDay = date('Y-m-01', strtotime(date("$year-$v-d")));
           $latDay = date('Y-m-d 23:59:59', strtotime("$firstDay +1 month -1 day"));
           $data[] = [
               //'fisrt'  => $firstDay,
               //'last'   => $latDay,
               'month'  => $v,
               'count'  => \app\common\model\OrderLog::where(['addtime'=>['between',[strtotime($firstDay),strtotime($latDay)]]])->count(),
           ];
       }
       ok($data);
   }

   /**
    * 实时点单记录
    * @param int $page 页码
    * @param int $pagesize 每页数
    * @param string $orders 排序
    */
   public function realTimeOrderList()
   {
       $page = intval(input("page", 1));
       $pagesize = intval(input("pagesize", 10));
       $orders = trim(input("orders", "addtime desc"));
       $page = max($page, 1);
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [];


       $order = [];
       $orderList = \app\common\model\OrderLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
       $total = \app\common\model\OrderLog::where($where)->count();
       foreach ($orderList as $k => $v) {
           $order[$k] = [
               'id'     => $v['id'],
               'name'   => \app\common\model\User::where(['id'=>$v['uid']])->value("realname"),
               'format_addtime'  => format_time_moment($v['addtime'],"Y-m-d"),
               'title'  => \app\common\model\Order::where(['id'=>$v['order_id']])->value("title"),
           ];
       }
       ok([
           "items" => $order,
           "pagesize" => $pagesize,
           "curpage" => $page,
           "totalpage" => ceil($total / $pagesize),
           "total" => $total
       ]);
   }
   /**
    * 点单统计
    * @param int $page 页码
    * @param int $pagesize 每页数
    * @param string $orders 排序
    */
   public function orderList()
   {
       $page = intval(input("page", 1));
       $pagesize = intval(input("pagesize", 10));
       $orders = trim(input("orders", "id desc"));
       $page = max($page, 1);
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [];


       $order = [];
       $orderList = \app\common\model\Order::where($where)->page($page)->limit($pagesize)->order($orders)->select();
       $total = \app\common\model\Order::where($where)->count();
       foreach ($orderList as $k => $v) {
           $order[$k] = [
               'id'     => $v['id'],
               'img'    => $v['img'],
               'title'  => $v['title'],
               'counts' => $v['counts'],
           ];
       }
       ok([
           "items" => $order,
           "pagesize" => $pagesize,
           "curpage" => $page,
           "totalpage" => ceil($total / $pagesize),
           "total" => $total
       ]);
   }


   /**
    * 所站活动排行榜
    * @param int $is_menu 是否点单活动
    * @param int $is_volunteer 是否志愿活动
    * @return array
    */
   public function orderActivityPMList(){
       $is_menu = intval(input("is_menu"));
       $is_volunteer = intval(input("is_volunteer"));
       $area_id = \app\admin\model\Admin::where(['username'=>'admin'])->value("area_id");
       $stats = \app\common\model\Area::where(['pid'=>$area_id])->select();
       $where = [];
       if($is_menu){
           $where['is_menu'] = 1;
       }elseif($is_volunteer){
           $where['is_volunteer'] = 1;
       }
       /* if($is_station){
           $placeAreaArr = [];
           foreach(collection($stats)->toArray() as $v){
               $placeAreaArr[] = $v['id'];
           }
           $stats = \app\common\model\Area::where(['pid'=>['in',$placeAreaArr]])->select();
       } */
       $data = [];
       foreach($stats as $k => $v){
           $where['is_check'] = 1;
           $where['is_publish'] = 1;
           $where['area_id'] = ['in',\app\common\model\Cfg::childArea($v['id'])];
           $data[] = [
               'title'  => $v['name'],
               'count'  => \app\common\model\Activity::where($where)->count(),
           ];
       }
       $finalData = array_sort($data, 'count');
       foreach($finalData as $k => &$v){
           $v['rank'] = $k + 1;
       }
       ok($finalData);
   }

   /**
    * 点单相关
    * @ApiReturnParams   (name="order_num", type="int", description="点单数")
    * @ApiReturnParams   (name="order_count", type="int", description="点单量")
    * @ApiReturnParams   (name="order_activity_count", type="int", description="点单活动数")
    * @ApiReturnParams   (name="order_activityBm_count", type="int", description="点单活动参与数")
    * @ApiReturnParams   (name="order_activityClassic_count", type="int", description="经典点单活动数")
    * @return array
    */

   public function order(){
       ok([
           'order_num'          => \app\common\model\Order::count(),
           'order_count'        => \app\common\model\OrderLog::count(),
           'order_activity_count'=> \app\common\model\Activity::where(['is_menu'=>1,'is_check'=>1])->count(),
           'order_activityBm_count'=> \app\common\model\Activity::where(['is_menu'=>1,'is_check'=>1])->sum("joincount"),
           'order_activityClassic_count'=> \app\common\model\Activity::where(['is_menu'=>1,'is_check'=>1,'is_classic'=>1])->count(),

       ]);
   }


   /**
    * 志愿活动相关
    * @ApiReturnParams   (name="volunteer", type="int", description="志愿者人数")
    * @ApiReturnParams   (name="volunteerGroup", type="int", description="志愿团体数")
    * @ApiReturnParams   (name="volunteerTime", type="int", description="志愿总时长")
    * @ApiReturnParams   (name="activityCount", type="int", description="志愿活动数")
    * @ApiReturnParams   (name="joinCount", type="int", description="参与数")
    * @ApiReturnParams   (name="classicActivityCount", type="int", description="经典活动数")
    * @ApiReturnParams   (name="activityTime", type="int", description="活动总时长")
    * @return array
    */

   public function activity(){
       $activityTime = \app\common\model\Activity::where(['is_check'=>1])->field("sum(end_time - start_time) as activity_time")->find();
       ok([
           'volunteer'         => cfg("volunteer") + \app\common\model\Volunteer::where(['is_check'=>1])->count(),
           'volunteerGroup'    => cfg("volunteergroup") + \app\common\model\VolunteerGroup::where(['is_check'=>1])->count(),
           'volunteerTime'     => round(\app\common\model\Volunteer::where(['is_check'=>1])->sum("jobtime") / 3600 , 2),
           'activityCount'     => \app\common\model\Activity::where(['is_volunteer'=>1,'is_check'=>1])->count(),
           'joinCount'         => \app\common\model\Activity::where(['is_volunteer'=>1,'is_check'=>1])->sum("joincount"),
           'classicActivityCount' => \app\common\model\Activity::where(['is_volunteer'=>1,'is_check'=>1,'is_classic'=>1])->count(),
           'activityTime'      => round($activityTime['activity_time'] / 3600 ,2),

       ]);
   }

   /**
    * 志愿者画像分析
    * @return array
    */
   public function volunteer(){
       $volunteerList = \app\common\model\Volunteer::where(['is_check'=>1])->select();
       $F = 0;
       $M = 0;
       foreach($volunteerList as $v){
           $sexa=substr($v['card'],-2,1);
           if($sexa % 2 == 0){
               $M++;
           }else{
               $F++;
           }
       }
       ok([
           'm'  => $M / ($M + $F),
           'f'  => $F / ($M + $F)
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
       $page       = intval(input("page",1));
       $pagesize   = intval(input("pagesize",10));
       $orders     = trim(input("orders","scores desc"));
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
               'serviceTime'=> round($v['jobtime']/3600),
               'join_time'  => format_time($v['join_time'],"Y-m-d"),
               'name'       => $v['name'],
               'scores'     => $v['scores'],
           ];
       }
       ok($volunteer);
   }



   /**
    * 志愿者团体积分排名
    * @param int $page      页码
    * @param int $pagesize  每页数
    * @param string $orders    排序(默认积分，传jobtime desc 为时长)
    * @return array
    */
   public function volunteerGroupPMList(){
       $page       = intval(input("page",1));
       $pagesize   = intval(input("pagesize",10));
       $orders     = trim(input("orders","activty_time desc"));
       $page        = max($page,1);
       $pagesize    = $pagesize ? $pagesize : 10;
       $where = [
           'is_check'   => 1,
       ];

       $volunteerGroupList = \app\common\model\VolunteerGroup::where($where)->page($page)->limit($pagesize)->order($orders)->select();
       $volunteerGroup = [];
       foreach(collection($volunteerGroupList)->toArray() as $k => $v){
           $volunteerGroup[$k] = [
               'rank'       => $k + 1,
               'id'         => $v['id'],
               'title'       => $v['title'],
               'activty_time'     => $v['activty_time'],
           ];
       }
       ok($volunteerGroup);
   }


   /**
    * 志愿帮扶相关
    * @ApiReturnParams   (name="help_count", type="int", description="累计求助数")
    * @ApiReturnParams   (name="helping", type="int", description="处理中")
    * @ApiReturnParams   (name="finish_help_count", type="int", description="已完结")
    * @ApiReturnParams   (name="satisfaction", type="int", description="满意度")
    * @return array
    */

   public function help(){
       $satisfaction = \app\common\model\Help::where(['is_check'=>1])->avg("scores");
       ok([
           'help_count'         => \app\common\model\Help::where(['is_check'=>1])->count(),
           'helping'            => \app\common\model\Help::where(['status'=>1])->count(),
           'finish_help_count'  => \app\common\model\HelpLog::where(['status'=>3])->count(),
           'satisfaction'       => round($satisfaction,2),
       ]);
   }

   /**
    * 实时求助数据（近30天）
    * @ApiReturnParams   (name="appeal", type="int", description="未接单、求助中")
    * @ApiReturnParams   (name="helped", type="int", description="已接单，帮助中、完成")
    * @ApiReturnParams   (name="helping", type="int", description="帮助中")
    * @ApiReturnParams   (name="finish", type="int", description="已完成")
    * @ApiReturnParams   (name="totalHelp", type="int", description="总数")
    * @return array
    */

   public function realTimeHelp(){
       $end = time();
       $start = $end + 30 * 24 * 3600;
       $where = [];
       $where['add_time'] = ['between',[$start,$end]];
       $appeal = \app\common\model\Help::where(array_merge(['status'=>0],$where))->count();
       $help = \app\common\model\Help::where(array_merge(['status'=>['in',[1,2]]],$where))->count();
       $finish = \app\common\model\Help::where(array_merge(['status'=>3],$where))->count();
       $totalHelp = \app\common\model\Help::where($where)->count();
       ok([
           'appeal' => $appeal,
           'helped' => $help + $finish,
           'helping'=> $help,
           'finish' => $finish,
           'totalHelp' => $totalHelp
       ]);
   }

   /**
    * 最新求助列表
    * @param int $page 页码
    * @param int $pagesize 每页数
    * @param string $orders 排序
    */
   public function helpList()
   {
       $page = intval(input("page", 1));
       $pagesize = intval(input("pagesize", 10));
       $orders = trim(input("orders", "id desc"));
       $page = max($page, 1);
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [];


       $help = [];
       $helpList = \app\common\model\Help::where($where)->page($page)->limit($pagesize)->order($orders)->select();
       $total = \app\common\model\Help::where($where)->count();
       foreach ($helpList as $k => $v) {
           $help[$k] = [
               'id'     => $v['id'],
               'status' => $v['status'],
               'title'  => $v['title'],
               'format_addtime' => format_time_moment($v['add_time'],"Y-m-d"),
           ];
       }
       ok([
           "items" => $help,
           "pagesize" => $pagesize,
           "curpage" => $page,
           "totalpage" => ceil($total / $pagesize),
           "total" => $total
       ]);
   }

   /**
    * 志愿服务相关
    * @ApiReturnParams   (name="share_count", type="int", description="服务数")
    * @ApiReturnParams   (name="service_count", type="int", description="服务次数")
    * @ApiReturnParams   (name="goodservice_count", type="int", description="服务好评数")
    * @return array
    */

   public function share(){
       ok([
           'share_count'         => \app\common\model\Share::where(['is_check'=>1])->count(),
           'service_count'       => \app\common\model\ShareLog::where(['is_check'=>1,'status'=>1])->count(),
           'goodservice_count'   => \app\common\model\ShareLog::where(['is_check'=>1,'status'=>3,'score'=>['>',3]])->count(),
       ]);
   }

   /**
    * 共享服务列表
    * @param int $page 页码
    * @param int $pagesize 每页数
    * @param string $orders 排序
    */
   public function shareList()
   {
       $page = intval(input("page", 1));
       $pagesize = intval(input("pagesize", 10));
       $orders = trim(input("orders", "score desc"));
       $page = max($page, 1);
       $pagesize = $pagesize ? $pagesize : 10;
       $where = [
           'is_check' => 1
       ];


       $share = [];
       $shareList = \app\common\model\Share::where($where)->page($page)->limit($pagesize)->order($orders)->select();
       $total = \app\common\model\Share::where($where)->count();
       foreach ($shareList as $k => $v) {
           $share[$k] = [
               'id'     => $v['id'],
               'name'   => $v['username'],
               'title'  => $v['title'],
               'score'  => $v['score'],
               'tpe'    => \app\common\model\HelpType::where(['id'=>$v['help_type']])->value("name")
           ];
       }
       ok([
           "items" => $share,
           "pagesize" => $pagesize,
           "curpage" => $page,
           "totalpage" => ceil($total / $pagesize),
           "total" => $total
       ]);
   }


   /**
    * 服务评星统计
     * @return array
    */
   public function server(){
       $server = [];
       $total = 0;
       $data = \app\common\model\Share::where(['is_check'=>1])->group("score")->field("count(*) as count,score")->select();
       foreach($data as $k => $v){
           $total += $v['count'];
           $server[$v['score']] = $v['count'];
       }
       $thisServer = [];
       for($i=1;$i<6;$i++){
           $thisServer[$i] = isset($server[$i]) ? round($server[$i] / $total ,2) : 0;
       }
       ok($thisServer);
   }

   /**
    * 工单数据统计（近30天）
    * @ApiReturnParams   (name="pending", type="int", description="待处理数")
    * @ApiReturnParams   (name="processing", type="int", description="处理中数")
    * @ApiReturnParams   (name="solved", type="int", description="已解决数")
    * @ApiReturnParams   (name="total", type="int", description="总数")
    * @return array
    */
    public function workOrder(){
        $end = time();
        $start = $end + 30 * 24 * 3600;
        $where = [];
        $where['add_time'] = ['between',[$start,$end]];

        $pending = \app\admin\model\WorkOrder::where(array_merge(['status'=>0],$where))->count();
        $processing = \app\admin\model\WorkOrder::where(array_merge(['status'=>1],$where))->count();
        $solved = \app\admin\model\WorkOrder::where(array_merge(['status'=>2],$where))->count();
        ok([
           'pending'    => $pending,
           'processing' => $processing,
           'solved'     => $solved,
           'total'      => $pending + $processing + $solved
        ]);
    }

    /**
     * 事件工单列表
     * @param int $status 事件状态（ 0未处理1已指派2已完结）
     * @param int $page 页码
     * @param int $pagesize 每页数
     * @param string $orders 排序
     */
    public function workOrderList()
    {
        $status = intval(input("status",-1));
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "add_time desc"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [];
        if($status != -1){
            $where['status'] = $status;
        }

        $workOrder = [];
        $workOrderList = \app\admin\model\WorkOrder::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\admin\model\WorkOrder::where($where)->count();
        foreach ($workOrderList as $k => $v) {
            $workOrder[$k] = [
                'id'     => $v['id'],
                'status' => $v['status'],
                'title'  => $v['title'],
                'address'=> $v['address'],
                'x'      => $v['x'],
                'y'      => $v['y'],
                'format_addtime' => format_time_moment($v['add_time'],"Y-m-d"),
            ];
        }
        ok([
            "items" => $workOrder,
            "pagesize" => $pagesize,
            "curpage" => $page,
            "totalpage" => ceil($total / $pagesize),
            "total" => $total
        ]);
    }

    /**
     * 工单统计
     * @ApiReturnParams   (name="total", type="int", description="工单总数")
     * @ApiReturnParams   (name="helpOrder", type="int", description="求助工单数")
     * @ApiReturnParams   (name="eventOrder", type="int", description="事件工单数")
     * @ApiReturnParams   (name="completionRate", type="int", description="完成率")
     * @return array
     */
    public function workOrderTpe(){
        $total = \app\admin\model\WorkOrder::count();
        $solved = \app\admin\model\WorkOrder::where(['status'=>2])->count();
        $helpOrder = \app\admin\model\WorkOrder::where(['tpe'=>1])->count();
        $eventOrder = \app\admin\model\WorkOrder::where(['tpe'=>2])->count();
        ok([
            'total'         => $total,
            'helpOrder'     => $helpOrder,
            'eventOrder'    => $eventOrder,
            'completionRate'=> $total == 0 ? 0 : round($solved / $total,2) * 100,
        ]);
    }

    /**
     * 所站工单处理排行榜
     * @return array
     */
    public function workOrderPMList(){
        $area_id = \app\admin\model\Admin::where(['username'=>'admin'])->value("area_id");
        $stats = \app\common\model\Area::where(['pid'=>$area_id])->select();
        $where = [];

        /* if($is_station){
         $placeAreaArr = [];
         foreach(collection($stats)->toArray() as $v){
         $placeAreaArr[] = $v['id'];
         }
         $stats = \app\common\model\Area::where(['pid'=>['in',$placeAreaArr]])->select();
         } */
        $data = [];
        foreach($stats as $k => $v){
            $where['area_id'] = ['in',\app\common\model\Cfg::childArea($v['id'])];
            $data[] = [
                'title'  => $v['name'],
                'count'  => \app\admin\model\WorkDo::where($where)->count(),
            ];
        }
        $finalData = array_sort($data, 'count');
        foreach($finalData as $k => &$v){
            $v['rank'] = $k + 1;
        }
        ok($finalData);
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
       $pagesize   = intval(input("pagesize",10));
       $orders     = trim(input("orders","score desc"));
       $area_id    = intval(input("area_id",-1));
       $pid        = intval(input("pid",-1));
       $level      = intval(input("level"));
       $page        = max($page,1);
       $pagesize    = $pagesize ? $pagesize : 10;
       $where = [];
       if($keyword){
           $where['name'] = ['like','%'.$keyword.'%'];
       }
       if($area_id != -1){
           $where['id'] = $area_id;
       }
       if($pid != -1){
           $where['pid'] = $pid;
       }
       if($level){
           $area_id = \app\common\model\Admin::where(['id'=>1])->value("area_id");
           $where['level'] = $level;
           $where['id'] = ['in',\app\common\model\Cfg::childArea($area_id)];
       }

       $params = [
           'page'      => $page,
           'pagesize'  => $pagesize,
           'orders'    => $orders
       ];
       $areaList = \app\common\model\Area::where($where)->page($page)->limit($pagesize)->order($orders)->select();
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
           ];
       }

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
}
