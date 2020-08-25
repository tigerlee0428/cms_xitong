<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use EasyWeChat\Kernel\Support\Collection;
/**
 * 导航接口
 */
class Nav extends ApiCommon
{
    protected $noNeedLogin = ['childNav','index','brotherNav'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    
    /**
     * 获取所有导航
     * @param int $tpe 导航类型，1微信，2PC，3TV
     * @return array
     */
    public function index(){
        $tpe = intval(input("tpe",1));
        $where = [
            'tpe'   => $tpe,
            'status'=> 1
        ];
        $nav = \app\common\model\Nav::where($where)->order("display desc")->select();
        if($nav){
            $nav = collection($nav)->toArray();
            ok(children($nav));
        }
        ok();
    } 
    /**
     * 获取子导航
     * @param int $id 导航ID
     * @return array
     */
    
    public function childNav()
    {
        $id = intval(input("id"));
        $navList = \app\common\model\Nav::where(['pid'=>$id,'status'=>1])->order("display desc")->select();
        $list = [];
        if($navList){
            foreach($navList as $k => $v){
                $list[$k] = [
                    'id'        => $v['id'],
                    'name'      => $v['name'],
                    'link'      => $v['link'],
                    'img'       => $v['img'],
                    'data_tpe'  => $v['data_tpe'],
                    'data_id'   => $v['data_id'],
                    'styles'    => $v['styles'],
                    'position'  => $v['ad_position'],
                ];
            }
        }
        ok($list);
    }
    
    /**
     * 获取兄弟导航
     * @param int $id 导航ID
     * @return array
     */
    public function brotherNav()
    {
        $id = intval(input("id"));
        $nav = \app\common\model\Nav::get($id);
        $list = [];
        if($nav){
            $nav = $nav->toArray();
            $pid = $nav['pid'];
            $navList = \app\common\model\Nav::where(['pid'=>$pid,'status'=>1,'tpe'=>$nav['tpe']])->order("display desc")->select();
            if($navList){
                foreach($navList as $k => $v){
                    $list[$k] = [
                        'id'        => $v['id'],
                        'name'      => $v['name'],
                        'link'      => $v['link'],
                        'img'       => $v['img'],
                        'data_tpe'  => $v['data_tpe'],
                        'data_id'   => $v['data_id'],
                        'styles'    => $v['styles'],
                        'position'  => $v['ad_position'],
                    ];
                }
            }
        }
        ok($list);
    }
}
