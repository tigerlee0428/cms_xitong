<?php
namespace app\manage\command;

use app\admin\model\Event;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class Assist extends Command
{
    protected function configure()
    {
        $this->setName('Assist')->setDescription('Here is the remark ');
        $this->addArgument('name', 1); //必传参数
    }
    protected function execute(Input $input, Output $output)
    {
        $args = $input->getArguments();
        $name = $args['name'];
        switch($name){
            case "help":
                $this->assist();
                break;
            case "order":
                $this->help2work();
                break;
            case "case":
                $this->case2work();
                break;
            default:
                echo '';
        }
    }
   /* private function assist(){
    $helpList = \app\manage\model\Help::getHelpAll(['status'=>1]);
    if($helpList){
        foreach($helpList as $k => $v){
            $time_expire = time()-$v['hand_time'];
            $appraise_expire_time = \app\manage\model\Cfg::getByName(['name'=>'appraise_expire_time'],'config', 'value');
            if($time_expire >= $appraise_expire_time * 3600){
                \app\manage\model\Help::helpUpdate(['scores'=>5,'status'=>3], ['id'=>$v['id']]);
                if(\app\manage\model\Cfg::getByName(['name'=>'help_scores'],'config', 'value')){
                    $integralInfo = [
                        'event_code'        => 'partyHelp',
                        'uid'               => $v['hid'],
                        'scores'            => 5,
                    ];
                    \think\Hook::listen("integral",$integralInfo);
                }
            }
        }
    }
}*/


    private function help2work(){
        $helpList = \app\admin\model\Help::where(['status'=>0,"is_check"=>1,'is_work'=>0])->select();
        if($helpList){
            foreach($helpList as $k => $v){
                $time_expire = time()-$v['check_time'];
                $help_expire_time = \app\admin\model\Config::where(['name'=>'help_expire_time'])->value('value');
                $expire_time = $help_expire_time * 3600;
                if($time_expire >= $expire_time){
                    $data =[
                        "resource_id" => $v['id'],
                        "title"       => $v['title'],
                        "content"     => $v['content'],
                        "area_id"     => $v['area_id'],
                        "add_time"    => time(),
                        'img'         => $v['img'],
                        'tpe'         => 1,
                        'mobile'      => $v['mobile']
                    ];
                    \app\admin\model\WorkOrder::create($data);
                    \app\admin\model\Help::update(['is_work'=>1],['id'=>$v['id']]);
                }
            }
        }
    }

    private function case2work(){
        $eventList = \app\admin\model\Event::where(['is_order'=>0,"is_check"=>1])->select();
        if($eventList){
            foreach($eventList as $k => $v){
                    $data =[
                        "resource_id" => $v['id'],
                        "title"       => $v['title'],
                        'content'     => $v['content'],
                        'img'         => $v['img'],
                        'tpe'         => 2,
                        "add_time"    => time(),
                        'mobile'      => $v['mobile']
                    ];
                    \app\admin\model\WorkOrder::create($data);
                    Event::update(['is_order'=>1,'status'=>1],['id'=>$v['id']]);
            }
        }
    }

}
