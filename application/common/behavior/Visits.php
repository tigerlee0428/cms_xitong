<?php 
namespace app\common\behavior;
class Visits{
    
    public function run(&$params){

        $data = [
            'device'      => $params['device'],
		    'ip'          => $params['ip'],
		    'duration'    => $params['duration'],
		    'category'    => $params['category'],
		    'tpe'         => $params['tpe'],
		    'sid'         => $params['sid'],
		    'platf'       => $params['platf'],
		    'add_time'    => time(),
            'uid'         => $params['uid'],
            'is_video'    => $params['is_video'],
        ];
        if($params['subject']){
            $data['subject'] = $params['subject'];
        }
        if($params['cardNo']){
            $data['cardno'] = $params['cardNo'];
        }
        $logInfo = \app\common\model\Visits::where($data)->find();
        if(!$logInfo){
            \app\common\model\Visits::create($data);
        }
    }
} 

?>