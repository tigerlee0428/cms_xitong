<?php

namespace app\admin\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\common\model\Video as Video_mod;
class VideoService extends Command
{
     protected function configure()
    {
        $this->setName('VideoService')->setDescription('HuoQu Dayong Dianbo video list ');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->_videoService();
    }

    //与大勇点播系统数据同步
    private function _videoService()
    {
        $list_url = config("cst_video_path")."/interface/interface.php?act=getVodList2&size=1000&page=1&userid=".config("cst_video_admin_id");
        $data = myhttp($list_url);
        $data = json_decode($data,true);

        if(is_array($data)){
            if(isset($data['list']) && !empty($data['list'])){
                foreach($data['list'] as $key => $value){
                    $row = Video_mod::get(['third_id'=>$value['media_id']]);
                    if($row){
                        continue;
                    }
                    $imgUrl = config("cst_video_path")."/getthumbnail.php?mediaid=".$value['media_id']."&width=320";
                    $content = file_get_contents($imgUrl);
                    $video_img = "/attaches/image/".date("YmdHis").md5($value['media_id'].config("authkey").rand(1,100)).".jpg";
                    file_put_contents(ROOT_PATH.'public'.$video_img, $content);
                    //去重处理
                    $data = [
                        'title'         => $value['title'],
                        'addtime'       => strtotime($value['add_time']),
                        'third_id'      => $value['media_id'],
                        'address'       => str_replace('10.2.11.147:5700','192.168.3.100',$value['url']),
                        'video_img'     => $video_img,
                        'duration'      => $value['duaration'],
                        'third_source'  => $value['media_key']
                    ];
                    $inf = Video_mod::create($data);                   
                }
            }
            echo 'Synchronous completion';
        }else{
            echo 'No video needs to Synchronous!';
        }
    }
}
