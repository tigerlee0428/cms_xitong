<?php

namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;

/**
 * 投票接口
 */
class Vote extends ApiCommon
{
    protected $noNeedLogin = ['voteList','index'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }
    
    /**
     * 投票列表
     * @param int $category  抽票栏目
     * @param int $status    活动状态
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param string $search    关键词
     * @return array
     */
    public function voteList(){
        $category   = intval(input("category"));
        $status     = intval(input("status",-1));
        $page       = intval(input("page",1));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","status asc, start_time desc"));
        $search     = input("search");
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [
            'is_check'   => 1,
            'is_publish' => 1
        ];
        if($status != -1){
            $where['status'] = $status;
        }
        if(!empty($search))
        {
            $where['title'] = ['like','%'.$search.'%'];
        }
        if($category){
            $where['category'] = $category;
        }
        $vote = [];
        $voteList = \app\common\model\Vote::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Vote::where($where)->count();
        $list = collection($voteList)->toArray();
        foreach($list as $k => $v){
            $vote[$k] = [
                'id'            => $v['id'],
                'title'         => $v['title'],
                'brief'         => $v['brief'],
                'img'           => $v['img'],
                'status'        => $v['status'],
                'format_start_time'       => format_time($v['start_time'],"Y-m-d"),
                'format_end_time'         => format_time($v['end_time'],"Y-m-d"),
                'start_time'    => format_time($v['start_time'],"Y-m-d H:00"),
                'end_time'      => format_time($v['end_time'],"H:00"),
                'joincount'     => $v['joincount'],
            ];
        }
        ok([
            "items"     => $vote,
            "pagesize"  => $pagesize,
            "curpage"   => $page,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }
    
    
    /**
     * 投票详情
     *
     * @param string $id  投票ID
     * @return array
     */
    public function index(){
        $id        = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $voteInfo = \app\common\model\Vote::get($id);
        if(!$voteInfo){
            $lang = lang("Vote_not_valid");
            err(200,"Vote_not_valid",$lang['code'],$lang['message']);
        }
        $voteInfo = $voteInfo->toArray();
        $vote = [
            'id'                => $voteInfo['id'],
            'title'             => $voteInfo['title'],
            'can_use_tickets'   => $voteInfo['can_use_tickets'],
            'brief'             => $voteInfo['brief'],
            'img'               => $voteInfo['img'],
            'status'            => $voteInfo['status'],
            'format_start_time' => format_time($voteInfo['start_time'],"Y-m-d"),
            'format_end_time'   => format_time($voteInfo['end_time'],"Y-m-d"),
            'start_time'        => format_time($voteInfo['start_time'],"Y-m-d H:00"),
            'end_time'          => format_time($voteInfo['end_time'],"H:00"),
            'joincount'         => $voteInfo['joincount'],
        ];
        $options = collection(\app\common\model\VoteOptions::all(['tid'=>$voteInfo['id']]))->toArray();
        
        foreach($options as $k => $v){
            if(isset($v['video_id']) && $v['video_id']) {
                $videoInfo = \app\common\model\Video::get($v['video_id'])->toArray();
                $options[$k]['video_path'] = cfg("cst_video_public_url").'/media/video/'.$videoInfo['third_id'].".mp4";
                $options[$k]['video_path_tv'] = cfg("cst_video_private_url").'/media/video/'.$videoInfo['third_id'].".mp4";
            }
        }
        $this->success('',[
            'vote'      => $vote,
            'options'   => $options,
        ]);
    }
    
    /**
     * 投票
     * @ApiMethod (POST)
     * @param string $token     用户token
     * @param int $id        投票ID
     * @param int $oid       选项ID
     * @param string $token 用户TOKEN
     * @return array
     */
    public function voteDo(){
        $id        = intval(input("id"));
        $oid       = intval(input("oid"));
        
        if(!$id || !$oid){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $voteInfo = \app\common\model\Vote::get($id);
        if(!$voteInfo){
            $lang = lang("Vote_not_valid");
            err(200,"Vote_not_valid",$lang['code'],$lang['message']);
        }
        $voteInfo = $voteInfo->toArray();
        $voteOptions = \app\common\model\VoteOptions::get(['id'=>$oid,'tid'=>$id]);
        if(!$voteOptions){
            $lang = lang("not_vote_options");
            err(200,"not_vote_options",$lang['code'],$lang['message']);
        }
        $thistime = time();
        $status = 0;
        if($thistime < $voteInfo['start_time']){
            $status = 0;
        }
        if($thistime > $voteInfo['start_time'] && $thistime < $voteInfo['end_time']){
            $status = 1;
        }
        if($thistime > $voteInfo['end_time']){
            $status = 2;
        }
        if($status != 1){
            $lang = lang("activity_time_error");
            err(200,"activity_time_error",$lang['code'],$lang['message']);
        }
        if($voteInfo['can_use_tickets']){
            $data = [
                'uid'       => $this->auth->id,
                'tid'       => $id,
            ];
            $costed = \app\common\model\VoteLog::where($data)->count();
            if($costed >= $voteInfo['can_use_tickets']){
                $lang = lang("no_tickets");
                err(200,"no_tickets",$lang['code'],$lang['message']);
            }
        }
        if($voteInfo['day_limit']){
            $today_start_time = strtotime(date("Y-m-d"));
            $data = [
                'uid'       => $this->auth->id,
                'tid'       => $id,
                'add_time'  => [['>',$today_start_time],['<',$today_start_time + 3600 * 24]],
            ];
            $costed = \app\common\model\VoteLog::where($data)->count();
            if($costed >= $voteInfo['day_limit']){
                $lang = lang("today_no_tickets");
                err(200,"today_no_tickets",$lang['code'],$lang['message']);
            }
        }
        if($voteInfo['options_limit']){
            $data = [
                'uid'       => $this->auth->id,
                'tid'       => $id,
                'option_id' => $oid,
            ];
            $costed = \app\common\model\VoteLog::where($data)->count();
            if($costed >= $voteInfo['options_limit']){
                $lang = lang("options_no_tickets");
                err(200,"options_no_tickets",$lang['code'],$lang['message']);
            }
        }
        $vote_log = [
            'uid'       => $this->auth->id,
            'tid'       => $id,
            'option_id' => $oid,
            'add_time'  => time(),
        ];
        $inf = \app\common\model\VoteLog::create($vote_log);
        if($inf){
            $voteOptions =  $voteOptions->toArray();
            \app\common\model\VoteOptions::update(['tickets'=>$voteOptions['tickets'] + 1],['id'=>$oid]);
            \app\common\model\Vote::update(['joincount'=>\app\common\model\VoteLog::where(['tid'=>$id])->group("uid")->count()],['id'=>$id]);
        }
        ok();
    }
        
}
