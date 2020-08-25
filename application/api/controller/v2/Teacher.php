<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use EasyWeChat\Kernel\Support\Collection;
/**
 * 导师成员接口
 */
class Teacher extends ApiCommon
{
    protected $noNeedLogin = ['teacherList','index','like','collection','collectionList'];
    protected $noNeedRight = '*';
    protected $model = null;
    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Teacher;
    }

    /**
     * 导师详情
     * @param int $id 文章ID
     *
     */
    public function index()
    {
        $id = intval(input("id"));
        $teacherinfo = $this->model->get($id);
        if(!$teacherinfo){
            $lang = lang("not_teacher");
            err(200,"not_teacher",$lang['code'],$lang['message']);
        }
        $teacherinfo = $teacherinfo->toArray();
        $data = [
            'id'         => $teacherinfo['id'],
            'name'       => $teacherinfo['name'],
            'pos'        => $teacherinfo['pos'],
            'img'        => $teacherinfo['img'],
        ];

        ok($data);
    }

    /**
     * 导师列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param int $keyword    关键词
     * @return array
     *
     */

    public function teacherList(){
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
        $teacherList = \app\common\model\Teacher::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Teacher::where($where)->count();
        $list = [];
        foreach($teacherList as $k => $v)
        {

            $list[$k] = [
                'id'         => $v['id'],
                'name'       => $v['name'],
                'img'        => $v['img'],
                'thumb_img'  => $v['img'],
                'pos'        => $v['pos'],
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
