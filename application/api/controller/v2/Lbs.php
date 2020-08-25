<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
/**
 * LBS活动阵地接口
 */
class Lbs extends ApiCommon
{
    protected $noNeedLogin = ['activity','place'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    /**
     * 最近活动
     * @param string $address 地址
     * @return array
     */
    public function activity(){
        $address = trim(input("address"));
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        //$orders = trim(input("orders", "status asc, start_time desc"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_publish' => 1,
            'is_check' => 1,
        ];
        list($x,$y) = jwd($address);
        $field = "*,power(power(x-".$x.",2) + power(y-".$y.",2),0.5)*100 as distance";
        $orders = "distance asc";
        $activity = [];
        $activityList = \app\common\model\Activity::where($where)->page($page)->limit($pagesize)->order($orders)->field($field)->select();
        $total = \app\common\model\Activity::where($where)->count();
        //$list = collection($activityList)->toArray();
        foreach ($activityList as $k => $v) {
            $activity[$k] = [
                'distance' => $v['distance'] > 1 ? round($v['distance'],3)."km": round($v['distance']*1000)."m",
                'id' => $v['id'],
                'title' => $v['title'],
                'brief' => $v['brief'],
                'images' => $v['images'],
                'thumb_images' => thumb_img($v['images']),
                'category' => $v['category'],
                'status' => $v['status'],
                'format_zm_start_time' => format_time($v['publish_time']),
                'format_zm_end_time' => format_time($v['start_time']),
                'format_start_time' => format_time($v['start_time'], "Y/m/d"),
                'format_end_time' => format_time($v['end_time'], "Y/m/d"),
                'start_time' => $v['start_time'],
                'end_time' => $v['end_time'],
                'servicetime' => $v['servicetime'],
                'address' => $v['address'],
                'group_name' => $v['group_name'],
                'contacter' => $v['contacter'],
                'phone' => $v['phone'],
                'likes' => $v['likes'],
                'click_count' => $v['click_count'],
                'x' => $v['x'],
                'y' => $v['y'],
                'person_limit' => $v['person_limit'],
                'bm_count' => $v['is_volunteer'] ? \app\common\model\ActivityBmLog::where(["aid" => $v['id'], 'tpe' => 1])->count() : ($v['is_menu'] ? \app\common\model\ActivityBmLog::where(["aid" => $v['id'], 'tpe' => 1])->count() : \app\common\model\ActivityBmLog::where(["aid" => $v['id']])->count()),
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
     * 最近阵地
     * @param string $address 地址
     * @return array
     */
    public function place(){
        $address = trim(input("address"));
        $page = intval(input("page", 1));
        $pagesize = intval(input("pagesize", 10));
        //$orders = trim(input("orders", "status asc, start_time desc"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            //'is_publish' => 1,
            //'is_check' => 1,
        ];
        list($x,$y) = jwd($address);
        $field = "*,power(power(x-".$x.",2) + power(y-".$y.",2),0.5)*100 as distance";
        $orders = "distance asc";
        $place = [];
        $placeList = \app\admin\model\Place::where($where)->page($page)->limit($pagesize)->order($orders)->field($field)->select();
        $total = \app\admin\model\Place::where($where)->count();
        //$list = collection($activityList)->toArray();
        foreach ($placeList as $k => $v) {
            $place[$k] = [
                'distance' => $v['distance'] > 1 ? round($v['distance'],3)."km": round($v['distance']*1000)."m",
                'id' => $v['id'],
                'name' => $v['name'],
                'address' => $v['address'],
                'img' => thumb_img($v['img']),
                'mobile' => $v['mobile'],
                'order_time' => $v['order_time'],
                'number' => $v['number'],
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
    
}
