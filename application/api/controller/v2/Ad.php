<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
/**
 * 广告接口
 */
class Ad extends ApiCommon
{
    protected $noNeedLogin = ['index'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    /**
     * 广告列表
     * @param int $position 广告位ID
     */
    public function index(){
        $positions = trim(input("position"));
        if(!$positions){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $position_arr = explode(",",$positions);
        $thistime = time();
        $where = [
            'status'    => 1,
            'started_at'=> ['<=',$thistime],
            'expired_in'=> ['>=',$thistime],
            'ad_pos'    => ['in',$position_arr]
        ];
        $ad = [];
        $adList = \app\common\model\Ad::where($where)->order("seq desc")->select();
        if($adList){
            foreach(collection($adList)->toArray() as $k => &$v){
                $ad[$k]['icon_url']     = $v['icon_url'];
                $ad[$k]['description']  = $v['description'];
                $ad[$k]['title']        = $v['title'];
                $ad[$k]['url']          = $v['url'];
                $ad[$k]['is_ext']       = $v['is_ext'];
                $ad[$k]['show_title']   = $v['show_title'];
                $ad[$k]['image']        = is_array(json_decode($v['image'],true))?current(json_decode($v['image'],true)):$v['image'];
            }
        }
        ok($ad);
    }


}
