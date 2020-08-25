<?php 
namespace app\common\behavior;
class SendSms{
    
    public function run(&$params){        
         return $this->_sendSms($params);        
    }
    
    private function _sendSms($params){
        
        $api_url = 'http://v.juhe.cn/sms/send';
        $mobile = $params['mobile'];
        $code = $params['code'];
        $smsConf = array(
           'key'        => config("auth_sms.key"), //您申请的APPKEY
           'mobile'     => $mobile, //接受短信的用户手机号码
           'tpl_id'     => cfg("sms_id"), //您申请的短信模板ID，根据实际情况修改
           'tpl_value'  => urlencode('#code#='.$code) //您设置的模板变量，根据实际情况修改
       );
        
       $content = myhttp($api_url,$smsConf); //请求发送短信
       if($content){
           $result = json_decode($content,true);
           $error_code = $result['error_code'];
           if($error_code == 0){
               //状态为0，说明短信发送成功
               return true;
           }else{
               //状态非0，说明失败
               return false;
           }
       }else{
           //返回内容异常，以下可根据业务逻辑自行修改
           return false;
       }
    }   
} 

?>