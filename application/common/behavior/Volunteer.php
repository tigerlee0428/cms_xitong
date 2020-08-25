<?php 
namespace app\common\behavior;

class Volunteer{
    protected $inteface_url     = 'http://test.szzyz.org/zyzOpenPlatform/api?';
    protected $szzy_app_key     = '201900805323454360168';
    protected $szzy_secret_key  = 'dsfdsf4543srfsd345sdfr45ddy76576';
    public function run(&$params){
        if(isset($params['action'])){
            switch($params['action']){
                case 'volunteer':
                    return $this->volunteer($params);
                break;
                case 'volunteergroup':
                    return $this->volunteer_group($params);
                break;
                case 'activity':
                    return $this->activity($params);
                break;
                case 'report':
                    return $this->report($params);
                break;
            }
        }
        return ;
    }
    
    /*
     * 同步注册志愿者，在志愿者申请过后，后台审核通过后调用该钩子
     */
    private function volunteer($params){
         $log_str = json_encode($params);
         $params = [
            'method' => 'registerUsers',
            'xml' => [
                'users' => [
                    'info' => [
                        'idcode'        => $params['card'],
                        'name'          => $params['name'],
                        'phone'         => $params['mobile'],
                        'nativeplace'   => '',
                        'school'        => '',
                        'technology'    => '',
                        'company'       => isset($params['work']) ? $params['work'] : '',
                        'companyaddress' => '',
                        'title'         => isset($params['brief']) ? $params['brief'] : '',
                        'duties'        => '',
                        'email'         => '',
                        'zipcode'       => '',
                    ]
                ]
            ]
        ];
        $logFile = LOG_PATH.DS."volunteer_tb.log";
        $fp = fopen($logFile, 'a+');
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        $log = myhttp($url);
        fwrite($fp, $log.$log_str."\n");
        fclose($fp);
        return json_decode($log,true);
    }
    
    /*
     * 同步注册志愿者组织，在志愿者申请组织过后，后台审核通过后调用该钩子
     */
    
    private function volunteer_group($params){
        $log_str = json_encode($params);
        $groupId = $params['id'];
        $volunteer = \app\common\model\Volunteer::where(['uid'=>$params['uid']])->find();
        if(!$this->checkVolunteer(['idcard' => $params['idcard']])){
            $info = $this->volunteer([
                'card'      => $volunteer['card'],
                'name'      => $volunteer['name'],
                'mobile'    => $volunteer['mobile'],
            ]);
        }
        
        $params = [
            'method' => 'registerOrgs',
            'xml' => [
                'orgs' => [
                    'info' => [
                        'orgname'       => $params['title'],
                        'idcode'        => $volunteer['card'],
                        'name'          => $volunteer['name'],
                        'area'          => '吴中区',
                        'descs'         => $params['content'],
                        'address'       => $params['address'],
                        'createdate'    => date("Y-m-d",$params['addtime']),
                        'condition'     => $params['condition'],
                        'linkname'      => $volunteer['name'],
                        'linkphone'     => $volunteer['mobile'],
                    ]
                ]
            ]
        ];
        $logFile = LOG_PATH.DS."volunteerGroup_tb.log";
        $fp = fopen($logFile, 'a+');
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        //fwrite($fp, $url."\n");
        $log = myhttp($url);
        fwrite($fp, $log.$log_str."\n");
        fclose($fp);
        if($log){
            $logArr = json_decode($log,true);
            if($logArr['orgs'][0]['code'] == 'SUCCESS'){
                \app\admin\model\VolunteerGroup::update(['third_id'=>$logArr['orgs'][0]['orgid']],['id'=>$groupId]);
            }
        }
        
    }
    
    private function adfa(){
        
        $params = [
            'method' => 'queryServiceTypes',
            'xml' => [
                'query' => [
                    'id' => 0
                ]
            ]
        ];
        
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        $log = myhttp($url);
        echo $log;exit;
    }
    
    /*
     * 同步创建志愿活动，在活动申请通过后调用该钩子，并发布招募活动
     */
    
    private function activity($params){
        //$this->adfa();
        $actId = $params['id'];
        $oldParams = $params;
        $log_str = json_encode($params);
        $params = [
            'method' => 'synchronizeAct',
            'xml' => [
                'acts' => [
                    'info' => [
                        'name'          => $params['title'],
                        'servicetype'   => 145,
                        'area'          => 6,
                        'descs'         => $params['brief'] ? trim($params['brief']): ($params['content'] ? trim($params['content']) : $params['title']),
                        'adrress'       => $params['address'],
                        'startDate'     => date("Y-m-d",$params['start_time']),
                        'endDate'       => date("Y-m-d",$params['end_time']),
                        'linkName'      => $params['contacter'],
                        'phone'         => $params['phone'],
                        'uniqueCode'    => 'sb'.$params['id'],
                        'recordway'     => 0,
                        'servicetime'   => round(($params['end_time'] - $params['start_time'])/3600),
                        'orgid'         => \app\admin\model\VolunteerGroup::where(['id'=>$params['group_id']])->value("third_id"),
                    ]
                ]
            ]
        ];
        //print_r($params);exit;
        $logFile = LOG_PATH.DS."activity_tb.log";
        $fp = fopen($logFile, 'a+');
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        $log = myhttp($url);
        if($log){
            $logArr = json_decode($log,true);
            if($logArr['outActList'][0]['code'] == 'SUCCESS'){
                \app\admin\model\Activity::update(['third_id'=>$logArr['outActList'][0]['actid']],['id'=>$actId]);
                $this->pubActivity($oldParams, $logArr['outActList'][0]['actid']);
            }
        }
        fwrite($fp, $log.$log_str."\n");
        fclose($fp);
        return json_decode($log,true);
    }
    
    
    private function pubActivity($params,$actid){
        $log_str = json_encode($params);
        $oldParams = $params;
        $params = [
            'method' => 'syncActRelease',
            'xml' => [
                'rels' => [
                    'info' => [
                        'actid'         => $actid,
                        'activitydate'  => date("Y-m-d",$params['start_time']),
                        'starttime'     => date("H:i",$params['start_time']),
                        'endtime'       => date("H:i",$params['end_time']),
                        'uniquecode'    => 'fb'.$params['id'],
                    ]
                ]
            ]
        ];
        $logFile = LOG_PATH.DS."activity_tb.log";
        $fp = fopen($logFile, 'a+');
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        $log = myhttp($url);
        if($log){
            $logArr = json_decode($log,true);
            if($logArr['outs'][0]['code'] == 'SUCCESS'){
                \app\admin\model\Activity::update(['releaseid'=>$logArr['outs'][0]['releaseid']],['id'=>$oldParams['id']]);
            }
        }
        fwrite($fp, $log.$log_str."\n");
        fclose($fp);
        return json_decode($log,true);
    }
    
    /*
     * 活动结束后上报参加该活动的志愿者活动时长，先加入活动，再上报时长。
     */
    private function report($params){
        
        $log_str = json_encode($params);
        $oldParams = $params;
        $params = [
            'method' => 'joinActRelease',
            'xml' => [
                'rels' => [
                    'info' => [
                        'releaseid'         => $params['releaseid'],
                        'idcode'            => $params['idcard'],
                        'jointime'          => $params['jointime'],
                        'uniquecode'        => md5($params['idcard'].$params['jointime']),
                    ]
                ]
            ]
        ];
        $logFile = LOG_PATH.DS."report_tb.log";
        $fp = fopen($logFile, 'a+');
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        $log = myhttp($url);
        fwrite($fp, $log.$log_str."\n");
        fclose($fp);
        $this->addJobTime($oldParams);
       
        return json_decode($log,true);
    }
    
    private function addJobTime($params){
        $log_str = json_encode($params);
        $oldParams = $params;
        $params = [
            'method' => 'reportServerTime',
            'xml' => [
                'servertime' => [
                    'info' => [
                        'releaseid'         => $params['releaseid'],
                        'idcode'            => $params['idcard'],
                        'uniquecode'        => md5($params['idcard'].$params['jointime'].$params['servertime']),
                        'servertime'        => $params['servertime']
                    ]
                ]
            ]
        ];
        $logFile = LOG_PATH.DS."report_tb.log";
        $fp = fopen($logFile, 'a+');
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        $log = myhttp($url);
        if($log){
            $logArr = json_decode($log,true);
            if($logArr['infos'][0]['code'] == 'SUCCESS'){
                \app\admin\model\ActivityBmLog::update(['is_report'=>1],['id'=>$oldParams['id']]);
            }
        }
        fwrite($fp, $log.$log_str."\n");
        fclose($fp);
    }
    //验签
    private function getSzSign($array)
    {
        $query = '';
        ksort($array);
        foreach ($array as $k => $v) {
            $query .= $k . $v;
        }
        $url = $this->szzy_secret_key . $query . $this->szzy_secret_key;
        return strtoupper(md5($url));
    }
    /*
     * 检测志愿者是否已经注册
     */
    private function checkVolunteer($params){
          $params = [
            'method' => 'getUserInfo',
            'xml' =>
            [
                'qurey' => [
                    'idcode'    => $params['idcard'],
                    'type'      => 2
                ]
            ]
        ];
        
        $array = [
            'appKey' => $this->szzy_app_key,
            'format' => 'json',
            'locale' => 'zh_CN',
            'method' => $params['method'],
            'request_xml' => \EasyWeChat\Kernel\Support\XML::buildUTF($params['xml']),
            'sign_method' => 'md5',
            'v' => '1.0',
        ];
        
        $sign = $this->getSzSign($array);
        $array['sign'] = $sign;
        $url = $this->inteface_url . http_build_query($array);
        $data = myhttp($url);
        $logFile = LOG_PATH.DS."volunteer_tb.log";
        $fp = fopen($logFile, 'a+');
        fwrite($fp, $data."\n");
        fclose($fp);
        $data = json_decode($data, true);
        if($data['code'] == 'SUCCESS'){
            
            return true;
        }
        return false;
    }
} 

?>