<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class TemplateMsg extends Command
{
    protected function configure()
    {
        $this->setName('TemplateMsg')->setDescription('Here is the remark ');
    }

    //微信模板消息发送
    protected function execute(Input $input, Output $output)
    {
        $fp = fopen(LOG_PATH . 'TemplateMsg', 'w+');
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            $redis = new \Redis();
            $redis->connect("127.0.0.1", 6379);
            //$this->template_msg($redis);
            while (true) {
                $inf = $this->template_msg($redis);
                if ($inf == 0) {
                    break;
                }
            }
            echo 'Finish Job!';
            fclose($fp);
        } else {
            fclose($fp);
            echo 'Job doing!';
            die();
        }

    }

    private function template_msg($redis)
    {
        if ($redis->exists("wx-notice-msg-list-".md5(\think\Env::get("database.database")))) {
            $msgInfo = json_decode($redis->lpop("wx-notice-msg-list-".md5(\think\Env::get("database.database"))), true);
            if (!$msgInfo) {
                return 0;
            }
            if(isset($msgInfo['openid'])){
                $url = \think\Env::get("wxservice.url") . "msg/send";
                $msg = [
                    'temp_id'   => $msgInfo['temp_id'],
                    'openid'    => $msgInfo['openid'],
                    'url'       => isset($msgInfo['url']) ? $msgInfo['url'] : '',
                    'msg_data'  => json_encode($msgInfo['msg_data']),
                ];
                myhttp($url, $msg);
            }            
            if(isset($msgInfo['sys_msg'])){
                $this->_saveMessage($msgInfo['sys_msg']);
            }            
        }
        sleep(2);
        return 1;
    }

    private function _saveMessage($msg)
    {
        $sys_msg = [
            'title'     => $msg['title'],
            'brief'     => isset($msg['brief']) ? $msg['brief'] : '',
            'uid'       => $msg['uid'],
            'add_time'  => time(),
            'tpe'       => isset($msg['tpe']) ? $msg['tpe'] : 0,
            'url'       => isset($msg['url']) ? $msg['url'] : ''
        ];
        $info = Db::name('message')->where($sys_msg)->find();

        if (!$info) {
            Db::name('message')->insert($sys_msg);
        }
    }
}