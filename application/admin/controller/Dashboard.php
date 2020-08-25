<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\Config;

/**
 * 控制台
 *
 * @icon fa fa-dashboard
 * @remark 用于展示当前系统中的统计数据、统计报表及重要实时数据
 */
class Dashboard extends Backend
{
    protected $areaIds = [];
    /**
     * 查看
     */
    
    public function _initialize()
    {
        parent::_initialize();
        $this->areaIds = \app\common\model\Cfg::childArea($this->auth->area_id);
    }
    public function index()
    {
        $dayS = strtotime(date("Y-m-d")) - 1;
        $dayE = $dayS + 3600 * 24;
        $now = strtotime(date("Y-m-d H:i:s"));
        $nowS = $now - 3600 * 24 * 7;
        $seventtime = \fast\Date::unixtime('day', -7);
        $visitlist = [];
        for ($i = 0; $i < 31; $i++)
        {
            $day = strtotime(date("Y-m-d", $dayS - ($i * 86400)));
            $visitlist[date("Y-m-d",$day)] = \app\common\model\Visits::where(['add_time'=>['between',[$day,$day + 86400]]])->count();
        }
        //print_r($paylist);exit;
        $hooks = config('addons.hooks');
        $uploadmode = isset($hooks['upload_config_init']) && $hooks['upload_config_init'] ? implode(',', $hooks['upload_config_init']) : 'local';
        $addonComposerCfg = ROOT_PATH . '/vendor/karsonzhang/fastadmin-addons/composer.json';
        Config::parse($addonComposerCfg, "json", "composer");
        $config = Config::get("composer");
        $addonVersion = isset($config['version']) ? $config['version'] : __('Unknown');
        $this->view->assign([
            'no_check_num'      => $this->_noCheckArticle(),
            'no_final_check_num'=> $this->_noFinalCheckArticle(),
            'no_pass_num'       => $this->_noPassArticle(),
            'no_final_pass_num' => $this->_noPassFinalArticle(),
            'no_check_activity_num' => $this->_noCheckActivity(),
            'no_pass_activity_num'  => $this->_noPassActivity(),
            'no_check_volunteer_num'  => $this->_noCheckVolunteer(),
            'no_check_volunteergroup_num'  => $this->_noCheckVolunteerGroup(),
            
            'article_count'     => \app\admin\model\Article::where(['is_check'=>1,'is_final_check'=>1,'is_del'=>0])->count(),
            'article_click_count' => \app\admin\model\Article::where(['is_check'=>1,'is_final_check'=>1,'is_del'=>0])->sum("click_count"),
            'article_like_count' => \app\admin\model\Article::where(['is_check'=>1,'is_final_check'=>1,'is_del'=>0])->sum("likes"),
            'activity_count'    => \app\admin\model\Activity::where(['is_check'=>1])->count(),
            'activity_like_count'    => \app\admin\model\Activity::where(['is_check'=>1])->sum("likes"),
            'volunteer_count'   => \app\admin\model\Volunteer::where(['is_check'=>1])->count(),
            'volunteer_jobtime_count'   => round(\app\admin\model\Volunteer::where(['is_check'=>1])->sum("jobtime")/3600,2),
            'volunteergroup_count'=> \app\admin\model\VolunteerGroup::where(['is_check'=>1])->count(),
            
            'todayVisit'        => \app\common\model\Visits::where(['add_time'=>['between',[$dayS,$dayE]]])->count(),
            'todayLikes'       => \app\common\model\ArticleLikeLog::where(['daytime'=>['between',[$dayS,$dayE]]])->count() + \app\common\model\ActivityLikeLog::where(['daytime'=>['between',[$dayS,$dayE]]])->count(),
            'sevenMenus'       => \app\common\model\OrderLog::where(['addtime'=>['between',[$nowS,$now]]])->count(),
            'sevenActivityJoin'=> \app\common\model\ActivityBmLog::where(['addtime'=>['between',[$nowS,$now]]])->count(),
            'visit'         => \app\common\model\Visits::where([])->count(),
            'visitTime'     => round(\app\common\model\Visits::where([])->sum("duration")/3600,2),
            
            'artileList'    => collection(\app\admin\model\Article::where(['is_del'=>0])->order("id desc")->page(1)->limit(8)->select())->toArray(),
            'visitLog'      => collection(\app\common\model\Visits::where(['sid'=>['>',0]])->order("id desc")->page(1)->limit(10)->select())->toArray(),
            'visitlist'          => $visitlist,
            'addonversion'       => $addonVersion,
            'uploadmode'       => $uploadmode
        ]);

        return $this->view->fetch();
    }
    //未初审数
    private function _noCheckArticle(){
        $where = [
            'is_check'          => 0,
            'is_final_check'    => 0,
            'is_del'            => 0,
            'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\Article::where($where)->count();
    }
    //未终审数
    private function _noFinalCheckArticle(){
        $where = [
            'is_check'          => 1,
            'is_final_check'    => 0,
            'is_del'            => 0,
            'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\Article::where($where)->count();
    }
    //未通过初审数
    private function _noPassArticle(){
        $where = [
            'is_check'          => 2,
            'is_final_check'    => 0,
            'is_del'            => 0,
            'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\Article::where($where)->count();
    }
    //未通过终审数
    private function _noPassFinalArticle(){
        $where = [
            'is_check'          => 1,
            'is_final_check'    => 2,
            'is_del'            => 0,
            'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\Article::where($where)->count();
    }
    //未审核活动数
    private function _noCheckActivity(){
        $where = [
            'is_check'          => 0,
            'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\Activity::where($where)->count();
    }
    //未通过活动数
    private function _noPassActivity(){
        $where = [
            'is_check'          => 2,
            'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\Activity::where($where)->count();
    }
    //未审核志愿者数
    private function _noCheckVolunteer(){
        $where = [
            'is_check'          => 0,
            //'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\Volunteer::where($where)->count();
    }
    //未审核志愿团体数
    private function _noCheckVolunteerGroup(){
        $where = [
            'is_check'          => 0,
            //'area_id'           => ['in',$this->areaIds],
        ];
        return \app\admin\model\VolunteerGroup::where($where)->count();
    }
}
