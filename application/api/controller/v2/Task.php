<?php
namespace app\api\controller\v2;

use app\api\controller\v2\MobileOffice;
use app\common\model\TaskDo;

/**
 * 任务接口
 */
class Task extends MobileOffice
{
    protected $model = null;
    protected $domodel = null;

    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Task();
        $this->domodel = new \app\admin\model\TaskDo();

    }
    

    
    /**
     * 任务列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param int $keyword    关键词
     * @return array
     *
     */

    public function taskList(){
        $page       = intval(input("page"));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page       = max($page,1);
        $keyword    = trim(input("keyword"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        if($keyword){
            $where['title'] = ['like',"%".$keyword."%"];
        }
        $taskList = \app\common\model\Task::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Task::where($where)->count();
        $list = [];
        foreach($taskList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'title'     => $v['title'],
                'tpe'       => $v['tpe'],
                'status'    => $v['status'],
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
     * 所站任务列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param int $keyword    关键词
     * @return array
     *
     */
    public function taskDoList(){
        $page       = intval(input("page"));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page       = max($page,1);
        $keyword    = trim(input("keyword"));
        $area_id    = intval(input("area_id",$this->area_id));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where['is_team'] = 0;
        if($keyword){
            $where['title'] = ['like',"%".$keyword."%"];
        }
        if($area_id){
            $where['area_id'] = $area_id;
        }
        $taskList = \app\common\model\TaskDo::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\TaskDo::where($where)->count();
        $list = [];
        foreach($taskList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'title'     =>  \app\common\model\Task::where(['id'=>$v['tid']])->value("title"),
                'status'    => $v['status'],
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
     * 任务指派给下级
     * @param int $id 任务ID
     * @param array $area_id 区域id集合
     */

    public function appoint()
    {
        $area_ids = input('area_ids/a');
        $id = intval(input('id'));
        if(!$id || !$area_ids){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $taskDo =  new \app\common\model\TaskDo();
        $data=[];
        foreach ($area_ids as $k => $v){
            $info = $this->model
                ->where(['area_id'=>$v,'tid' =>$id])
                ->find();
            if($info){continue;}
            $data[]=[
                'area_id' => $v,
                'tid'     => $id
            ];
        }
        $result = $taskDo->allowField(true)->saveAll($data);
        $this->model->save(['status'=>1],['id'=>$id]);

        if(!$result){
            $lang = lang("appoint_fail");
            err(200, "appoint_fail", $lang['code'], $lang['message']);
        }
        ok();
    }
    /**
     * 实践所指派给下级
     * @param int $id 任务指派ID
     * @param array $area_id 区域id集合
     */
    public function appointDo()
    {
        $area_ids = input('area_ids/a');
        $id = intval(input('id'));
        if(!$id || !$area_ids){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }

        $taskDo =  new \app\common\model\TaskDo();
        $data=[];
        foreach ($area_ids as $k => $v){
            $info = $this->model
                ->where(['area_id'=>$v,'tid' =>$v['tid'] ])
                ->find();
            if($info){continue;}
            $data[]=[
                'area_id' => $v,
                'tid'     => $v['tid'],
                'pid'     => $v['id']
            ];
        }
        $result =$taskDo->allowField(true)->saveAll($data);
        $taskDo->save(['status'=>1],['id'=>$id]);
        if(!$result){
            $lang = lang("appoint_fail");
            err(200, "appoint_fail", $lang['code'], $lang['message']);
        }
        ok();
    }

    /**
     * 中心指派给工作人员
     * @param int $id 任务ID
     * @param int $do_id 工作人员ID
     */
    public function toperator(){
        $id = intval(input('id'));
        $do_id = intval(input('do_id'));
        if(!$id || !$do_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $where =[
            'tid' => $id,
            'do_id' => $do_id
        ];
        $doInfo = TaskDo::where($where)->find();
        if($doInfo){
            $lang = lang("has_appoint");
            err(200, "has_appoint", $lang['code'], $lang['message']);
        }

        $data=[
            'do_id'   => $do_id,
            'tid'     => $id,
            'area_id' => $this->area_id,
        ];
        $result =  TaskDo::save($data);
        \app\common\model\Task::update(['status'=>1],['id'=>$id]);
        if(!$result){
            $lang = lang("appoint_fail");
            err(200, "appoint_fail", $lang['code'], $lang['message']);
        }
        ok();
    }


    public function toperatorDo(){
        $id = intval(input('id'));
        $do_id = intval(input('do_id'));
        if(!$id || !$do_id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $doInfo = TaskDo::where(['do_id'=>$do_id,'id'=>$id])->find();
        if($doInfo){
            $lang = lang("has_appoint");
            err(200, "has_appoint", $lang['code'], $lang['message']);
        }
        $result = TaskDo::update(['do_id'=>$do_id,'status'=>1],['id'=>$id]);
        if(!$result){
            $lang = lang("appoint_fail");
            err(200, "appoint_fail", $lang['code'], $lang['message']);
        }
        ok();
    }


    
}
