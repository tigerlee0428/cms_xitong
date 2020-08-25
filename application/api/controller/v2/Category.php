<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use app\common\model\Category as Category_mod;
/**
 * 栏目接口
 */
class Category extends ApiCommon
{
    protected $noNeedLogin = ['childCategory','categoryInfo','bread'];
    protected $noNeedRight = '*';
    protected function _initialize(){
        parent::_initialize();
    }
    /**
     * 获取子栏目
     * @param int $id 栏目ID
     *
     */
    public function childCategory()
    {
        $id = intval(input("id"));
        $categoryList = Category_mod::where(['pid'=>$id,'status'=>1])->order("weight desc")->select();
        $list = [];
        if($categoryList){
            foreach(collection($categoryList)->toArray() as $k => $v){
                $list[$k] = [
                    'id'        => $v['id'],
                    'title'     => $v['title'],
                    'image'     => $v['image'],
                    'content'   => $v['content'],
                ];
            }
        }
        ok($list);
    }

    /**
     * 获取栏目详情
     * @param int $id 栏目ID
     *
     */
    public function categoryInfo(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $cateInfo = Category_mod::get($id);
        if(!$cateInfo){
            $lang = lang("not_category");
            err(200,"not_category",$lang['code'],$lang['message']);
        }
        $cateInfo = $cateInfo->toArray();
        ok([
            'id'        => $cateInfo['id'],
            'pid'       => $cateInfo['pid'],
            'title'     => $cateInfo['title'],
            'description'   => $cateInfo['description'],
            'image'       => $cateInfo['image'],
        ]);
    }

    /**
     * 面包屑
     * @param int $id 栏目ID
     *
     */
    public function bread(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $cateInfo = Category_mod::get($id);
        if(!$cateInfo){
            $lang = lang("not_category");
            err(200,"not_category",$lang['code'],$lang['message']);
        }
        $cateInfo = $cateInfo->toArray();
        $title = $this->parentsName($cateInfo['pid']) ."&nbsp;&nbsp;>&nbsp;&nbsp;". $cateInfo['title'];
        ok(['title'=>$title]);
    }

    private function parentsName($id){
        $title = '';
        $cate = Category_mod::get($id);
        if($cate){
            $cate = $cate->toArray();
            $title .= $cate['title'];
            if($cate['pid'] != 0){
                return $this->parentsName($cate['pid'])."&nbsp;&nbsp;>&nbsp;&nbsp;".$title;
            }
        }
        return $title;
    }

}
