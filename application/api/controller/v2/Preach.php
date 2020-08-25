<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use EasyWeChat\Kernel\Support\Collection;
/**
 * 宣讲预告接口
 */
class Preach extends ApiCommon
{
    protected $noNeedLogin = ['preachList','index','like','collection','collectionList'];
    protected $noNeedRight = '*';
    protected $model = null;
    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Member;
    }

    /**
     * 预告详情
     * @param int $id 文章ID
     *
     */
    public function index()
    {
        $id = intval(input("id"));
        $preachinfo = $this->model->get($id);
        if(!$preachinfo){
            $lang = lang("not_preach");
            err(200,"not_preach",$lang['code'],$lang['message']);
        }
        $preachinfo = $preachinfo->toArray();
        $data = [
            'id'          => $preachinfo['id'],
            'title'       => $preachinfo['title'],
            'address'     => $preachinfo['address'],
            'teacher'     => $preachinfo['teacher'],
            'preach_time' => format_time($preachinfo['preach_time']),
        ];
        ok($data);
    }

    /**
     * 宣讲预告列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param int $keyword    关键词
     * @return array
     *
     */

    public function preachList(){
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
        $preachList = \app\common\model\Preach::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Preach::where($where)->count();
        $list = [];
        foreach($preachList as $k => $v)
        {

            $list[$k] = [
                'id'          => $v['id'],
                'title'       => $v['title'],
                'address'     => $v['address'],
                'teacher'     => $v['teacher'],
                'preach_time' => format_time($v['preach_time']),
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





}
