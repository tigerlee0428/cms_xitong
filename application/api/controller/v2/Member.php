<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use EasyWeChat\Kernel\Support\Collection;
/**
 * 团队成员接口
 */
class Member extends ApiCommon
{
    protected $noNeedLogin = ['memberList','index','like','collection','collectionList'];
    protected $noNeedRight = '*';
    protected $model = null;
    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Member;
    }

    /**
     * 详情
     * @param int $id 文章ID
     *
     */
    public function index()
    {
        $id = intval(input("id"));
        $membernfo = $this->model->get($id);
        if(!$membernfo){
            $lang = lang("not_member");
            err(200,"not_member",$lang['code'],$lang['message']);
        }
        $membernfo = $membernfo->toArray();
        $data = [
            'id'         => $membernfo['id'],
            'name'       => $membernfo['name'],
            'mobile'     => $membernfo['mobile'],
            'pos'        => $membernfo['pos'],
            'sex'        => $membernfo['sex'],
            'expert'     => $membernfo['expert'],
            'img'        => $membernfo['img'],
        ];

        ok($data);
    }


    /**
     * 成员列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param int $keyword    关键词
     * @return array
     *
     */

    public function memberList(){
        $page       = intval(input("page"));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","id desc"));
        $page       = max($page,1);
        $keyword    = trim(input("keyword"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [];
        if($keyword){
            $where['title'] = ['like',"%".$keyword."%"];
        }
        $memberList = \app\common\model\Member::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Member::where($where)->count();
        $list = [];
        foreach($memberList as $k => $v)
        {

            $list[$k] = [
                'id'         => $v['id'],
                'name'       => $v['name'],
                'img'        => $v['img'],
                'thumb_img'  => $v['img'],
                'mobile'     => $v['mobile'],
                'pos'        => $v['pos'],
                'sex'        => $v['sex'],
                'expert'     => $v['expert'],
            ];
        }
        ok([
            "items" => $list,
            "page"  => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }


    /**
     * 点赞
     * @param int $id 文章ID
     */
    public function like(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $membernfo = $this->model->get($id);
        if (!$membernfo) {
            $lang = lang("not_article");
            err(200, "not_article", $lang['code'], $lang['message']);
        }
        $membernfo = $membernfo->toArray();
        $likeInfo = \app\common\model\MemberLikeLog::where(['aid'=>$id,'ua' => _ua_key(),'ip' => get_onlineip(),'daytime'=>strtotime(date("Y-m-d"))])->find();
        if(!$likeInfo){
            $data = [
                'mid'   => $id,
                'ua'    => _ua_key(),
                'ip'    => get_onlineip(),
                'daytime'  => strtotime(date("Y-m-d")),
                'uid'   => $this->uid
            ];
            \app\common\model\MembeLikeLog::create($data);
            $this->model->update(['likes'=>$membernfo['likes']+1],['id'=>$id]);
            if($this->uid){
                $integralInfo = [
                    'event_code'        => 'Like',
                    'uid'               => $this->uid,
                    'area_id'           => $this->auth->area_id,
                    'note'              => '点赞'.$membernfo['title'],
                    'obj_id'            => $id,
                ];
                \think\Hook::listen("integral",$integralInfo);
            }
            ok(['likes'=>$membernfo['likes']+1]);
        }
        $lang = lang("has_click");
        err(200, "has_click", $lang['code'], $lang['message']);
    }






}
