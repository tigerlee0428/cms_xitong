<?php 
namespace app\common\behavior;
class Integral{
    
    public function run(&$params){
        if(isset($params['is_only_user']) && $params['is_only_user']){
            return $this->user($params);
        }elseif(isset($params['is_only_volunteer']) && $params['is_only_volunteer']){
            return $this->volunteer($params);
        }else{
            $this->user($params);
            $this->volunteer($params);
        }
        
        return;
    }
    
    private function volunteer($params){
        $vid = \app\common\model\Volunteer::where(['uid'=>$params['uid']])->value('id');
        if(!$vid){return;}
        $data = [
            'vid'           => $vid,
            'scores'        => isset($params['scores']) ? $params['scores'] : "",
            'event_code'    => $params['event_code'],
            'area_id'       => isset($params['area_id']) ? $params['area_id'] :(session("uarea_id") ? session('uarea_id') : session('area_id')),
            'note'          => trim($params['note']),
            'obj_id'        => isset($params['obj_id']) ? $params['obj_id'] : '',
            'imgs'          => isset($params['imgs']) ? $params['imgs'] : '',
        ];
        return \app\common\model\Credits::deal_volunteer_event($data);
    }
    
    
    private function user($params){
        $data = [
            'uid'           => $params['uid'],
            'scores'        => isset($params['scores']) ? $params['scores'] : 0,
            'event_code'    => $params['event_code'],
            'area_id'       => isset($params['area_id']) ? $params['area_id'] :(session("uarea_id") ? session('uarea_id') : session('area_id')),
            'note'          => trim($params['note']),
            'obj_id'        => isset($params['obj_id']) ? $params['obj_id'] : 0,
        ];
        return \app\common\model\Credits::deal_user_event($data);
    }
} 

?>