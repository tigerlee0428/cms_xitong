<?php

namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\Hook;
/**
 * 通用接口
 */
class Commons extends ApiCommon
{
    protected $noNeedLogin = ['get_sign','postView','postIntegral','postShare','swf_upload_pic','horse'];
    protected $noNeedRight = '*';
    protected function _initialize()
    {
        parent::_initialize();
    }
    /**
     * 获取签名
     * 
     */
    public function get_sign()
    {
        $sign = make_sign();
        ok(['sign' => $sign]);
    }
    /**
     * 访问统计
     * @param string $cardNo 机顶盒号
     * @param int $duration 页面访问时长
     * @param int $id 详情页ID
     * @param string $device 设备端标识，PC，WX，TV
     * @param int $category 栏目ID
     * @param int $tpe 来源类型，1资讯，2活动，3其它
     */
   
    public function postView()
    {
        $cardNo         = trim(input("cardNo"));
        $duration       = intval(input("duration", 30));
        $id             = intval(input("id"));
        $platf          = intval(input("platf"));
        $category       = intval(input("category"));
        $tpe            = intval(input("tpe", 1));
        if ($duration == 0) {
            ok();
        }
        if ($id && $tpe == 1) {
            $articleInfo = \app\common\model\Article::get($id);
            if($articleInfo){
                $articleInfo = $articleInfo->toArray();
                $this->_getIntegral($articleInfo);
                $category = intval($articleInfo['category']);
            }            
        }
        
        $params = [
            'cardNo'    => $cardNo,
            'device'    => trim(input("device")),
            'ip'        => get_onlineip(),
            'duration'  => $duration,
            'tpe'       => $tpe,
            'sid'       => $id,
            'category'  => $category,
            'platf'     => $platf,
            'add_time'  => time(),
            'uid'       => $this->uid,
            'is_video'  => isset($articleInfo['tpe']) ? $articleInfo['tpe'] == 4 || $articleInfo['tpe'] == 5 ? 1 : 0 :0,
            'subject'   => $tpe == 1 ? \app\common\model\Article::where(['id' => $id])->value("title") : (($tpe == 2) ? \app\common\model\Activity::where(['id' => $id])->value("title") :''),
        ];
        Hook::listen("visits", $params);
        ok();
    }
    /**
     * 学习长达N分钟记录积分
     * @param int $learn 学习时长
     */
    
    public function postIntegral(){
        $time = intval(input("learn",10));
        if($this->uid){
            $integralInfo = [
                'event_code'        => 'ReadAndLearn',
                'uid'               => $this->uid,
                'area_id'           => 0,
                'note'              => date("Y-m-d").'浏览学习'.$time.'分钟以上 ',
            ];
            \think\Hook::listen("integral",$integralInfo);
        }
        ok();
    }

    /**
     * 分享资讯获得积分
     * @param int $id 资讯ID
     */
    public function postShare(){
        if($this->uid ){
            $id= intval(input('id'));
            $title = \app\common\model\Article::where(['id'=>$id])->value("title");
            $integralInfo = [
                'event_code'        => 'share',
                'uid'               => $this->uid,
                'area_id'           => 0,
                'note'              => '分享了'.$title,
            ];
            \think\Hook::listen("integral",$integralInfo);
        }
        ok();
    }
    /**
     * 图片上传
     * 
     */
    public function swf_upload_pic()
    {
        $file = trim(input("file", "file"));
        $file_info = $this->upload($file);
        $allow_origin = config("allow_orgin");
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        if (in_array($origin, $allow_origin)) {
            header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Origin: ' . $origin);
        }
        if ($file_info['status'] == 200) {
            $pic = $file_info['path'];
            $data = array("filename" => $pic);
            ret($data);
            exit;
        } else {
            $lang = lang('upload_error');
            err(200, "upload_error", $lang['code'], $file_info['message']);
        }

    }

    /**
     * 图片批量上传
     *
     */
    public function upload_files_by_viper()
    {
        // 获取表单上传文件
        $files = request()->file('image', $width = 150, $height = 150);
        $res = [];
        if ($files) {
            foreach ($files as $file) {
                // 移动到框架应用根目录/uploads/ 目录下
                $info = $file->move(ROOT_PATH . 'public' . DS . 'attaches/case/');
                $img_path = '/attaches/case/' . str_replace(";\\", "/", $info->getSaveName());
                $res[] = $img_path;
            }
            return ok($res);
        }
    }

    private function upload($field_name)
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file($field_name, $width = 150, $height = 150);

        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $uploadDir = DS . 'attaches/case/';
            $info = $file->move(ROOT_PATH . 'public' . $uploadDir);
            if ($info) {
                $fileInfo = $file->getInfo();
                //验证是否为图片文件
                $imagewidth = $imageheight = 0;
                if($fileInfo['type']=='image/jpeg' || $fileInfo['type']=='image/png')
                {
                    $imgInfo = getimagesize($fileInfo['tmp_name']);
                    if (!$imgInfo || !isset($imgInfo[0]) || !isset($imgInfo[1])) {
                        $this->error(__('Uploaded file is not a valid image'));
                    }
                    $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                    $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
                    $image = \think\Image::open(ROOT_PATH . '/public' . $uploadDir . $info->getSaveName());
                    $image->thumb(220, 220 * $imageheight / $imagewidth)->save(ROOT_PATH . '/public' . $uploadDir . str_replace("\\","\\thumb_",$info->getSaveName()));
                }
                $img_path = '/attaches/case/' . str_replace("\\", "/", $info->getSaveName());
                return ['status' => 200, 'path' => $img_path];
            } else {
                // 上传失败获取错误信息
                return ['status' => 400, 'msg' => '上传失败'];
            }
        }
    }

 
    
    //积分记录
    private function _getIntegral($articleInfo){
        if($this->uid){
            switch($articleInfo['tpe']){
                case 4:
                case 5:
                    $integralInfo = [
                        'event_code'        => 'SeeFilm',
                        'uid'               => $this->uid,
                        'area_id'           => 0,
                        'note'              => '观看视频'.$articleInfo['title'],
                        'obj_id'            => $articleInfo['id']
                    ];
                    \think\Hook::listen("integral",$integralInfo);
                    break;
                case 1:
                case 2:
                case 3:
                    $integralInfo = [
                        'event_code'        => 'ReadNews',
                        'uid'               => $this->uid,
                        'area_id'           => 0,
                        'note'              => '观看资讯'.$articleInfo['title'],
                        'obj_id'            => $articleInfo['id']
                    ];
                    \think\Hook::listen("integral",$integralInfo);
                    break;
            }            
        }
    }
public function horse(){
        $horse = cfg("horse");
        ok($horse);
    }
}
