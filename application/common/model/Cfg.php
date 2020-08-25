<?php
namespace app\common\model;

use think\Model;
use think\model\Collection;
class Cfg extends Model
{
    protected $name = 'config';



    //读取该区域所有子区域
    public static function childArea($id){
        $areaAll = Collection(\app\common\model\Area::all())->toArray();
        $children = children($areaAll,$id);
        $ids = [];
        foreach($children as $k => $v){
            array_push($ids,$v['id']);
            if(isset($v['child'])){
                $ids = array_merge($ids,self::getArea($v['child']));
            }
        }
        array_push($ids,$id);
        array_push($ids,0);
        return $ids;
    }

    public static function childrenArea($id){
        $areaAll = Collection(\app\common\model\Area::all())->toArray();
        $children = children($areaAll,$id);
        $ids = [];
        foreach($children as $k => $v){
            array_push($ids,$v['id']);
            if(isset($v['child'])){
                $ids = array_merge($ids,self::getArea($v['child']));
            }
        }
        array_push($ids,0);
        return $ids;
    }

 //取当前所有子栏目
    public static function childCategory($id){
        $areaAll = Collection(\app\common\model\Category::all())->toArray();
        $children = children($areaAll,$id);
        $ids = [];
        foreach($children as $k => $v){
            array_push($ids,$v['id']);
            if(isset($v['child'])){
                $ids = array_merge($ids,self::getArea($v['child']));
            }
        }
        array_push($ids,$id);
        return $ids;
    }

    //取当前所有子栏目
    public static function childCate($id){
        $areaAll = Collection(\app\common\model\Cate::all())->toArray();
        $children = children($areaAll,$id);
        $ids = [];
        foreach($children as $k => $v){
            array_push($ids,$v['id']);
            if(isset($v['child'])){
                $ids = array_merge($ids,self::getArea($v['child']));
            }
        }
        array_push($ids,$id);
        return $ids;
    }


    private static function getArea($arr){
        $ids = [];
        foreach($arr as $k => $v){
            array_push($ids,$v['id']);
            if(isset($v['child'])){
                $ids = array_merge($ids,self::getArea($v['child']));
            }
        }
        return $ids;
    }

}
