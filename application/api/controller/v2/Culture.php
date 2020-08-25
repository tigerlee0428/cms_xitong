<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use EasyWeChat\Kernel\Support\Collection;
/**
 * 文化礼堂接口
 */
class Culture extends ApiCommon
{
    protected $noNeedLogin = ['Culturelist','index'];
    protected $noNeedRight = '*';
    protected $model = null;
    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Culture;
    }

    /**
     * 文化礼堂详情
     * @param int $id 文章ID
     *
     */
    public function index()
    {
        $id = intval(input("id"));
        $cultureInfo = $this->model->get($id);
        if(!$cultureInfo){
            $lang = lang("not_culture");
            err(200,"not_culture",$lang['code'],$lang['message']);
        }
        $cultureInfo = $cultureInfo->toArray();
        $video_ids = explode(',',$cultureInfo['video_id']);
        $video_path =[];
        $video_path_tv = [];
        foreach ($video_ids as $k => $v){
            $liveInfo = \app\common\model\Camera::get($v);
            if($liveInfo){
                $liveInfo = $liveInfo->toArray();
                $url = cfg("cst_live_url")."act=getLiveInfo";
                $para=[
                    "client"    => "cms",
                    "id"        => encrypt($liveInfo['third_id']),
                ];
                $live = json_decode(myhttp($url,$para),true);
                $video_path[] = str_replace(cfg("cst_live_private_url"), cfg("cst_live_public_url"), $live['url_hls']);
                $video_path_tv[] = $live['url_hls'];}
        }

        $data = [
            'id'        => $cultureInfo['id'],
            'title'     => $cultureInfo['title'],
            'img'       => $cultureInfo['img'],
            'video_path'=> $video_path,
            'video_path_tv'     => $video_path_tv,
            'format_add_time'   => format_time($cultureInfo['add_time']),
            'content'           => $cultureInfo['content'],
            'contracter'        => $cultureInfo['contracter'],

        ];
        ok($data);
    }


    /**
     * 文化礼堂列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param string $device    设备标识
     * @param int $area_id    区域ID
     * @param int $keyword    关键词
     * @return array
     *
     */

    public function cultureList(){
        $cid        = intval(input("cid"));
        $page       = intval(input("page"));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page       = max($page,1);
        $keyword    = trim(input("keyword"));
        $area_id    = intval(input("area_id",$this->auth->area_id));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [];
        if($area_id){
            $where['area_id'] = ['in',\app\common\model\Cfg::childArea($area_id)];
        }
        if($keyword){
            $where['title'] = ['like',"%".$keyword."%"];
        }
        $cultureList = \app\common\model\Culture::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Culture::where($where)->count();
        $list = [];
        foreach($cultureList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'title'     => $v['title'],
                'img'       => $v['img'],
                'thumb_img' => $v['img'],
                'area_id'   => $v['area_id'],
                'add_time'  => format_time($v['add_time'],"Y-m-d"),
                'video_path'=> explode(",",$v['video_path']),
                'area_name' => \app\common\model\Area::where(['id'=>$v['area_id']])->value("name"),
                'contracter'        => $v['contracter'],
            ];
        }
        $categoryname = \app\common\model\Category::where(['id'=>$cid])->value('title');
        ok([
            "categoryname" => $categoryname,
            "items" => $list,
            "page"  => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }


}
