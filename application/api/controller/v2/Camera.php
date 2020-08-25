<?php

namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;

/**
 * 直播接口
 */
class Camera extends ApiCommon
{
    protected $noNeedLogin = ['cameraList', 'index', 'postAlarm', 'updateStatus', 'updateRecord', 'alarmLog'];
    protected $noNeedRight = '*';
    protected $model = null;

    protected function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Camera;
    }

    public function cameraList()
    {
        $page = intval(input("page"));
        $pagesize = intval(input("pagesize", 10));
        $orders = trim(input("orders", "add_time desc"));
        $page = max($page, 1);
        $pagesize = $pagesize ? $pagesize : 10;
        if (!self::_chk_login()) {
            $lang = lang("not_login");
            err(200, "not_login", $lang['code'], $lang['message']);
        }
        $params = [
            'page' => $page,
            'pagesize' => $pagesize,
            'orders' => $orders
        ];
        $where = ['is_up' => 1];
        $userInfo = User_mod::UserInfo($this->uid);
        $where['group_id'] = $userInfo['cgid'];
        $cameraList = Camera_mod::getCameraList($where, $params);

        foreach ($cameraList['list'] as $k => $v) {
            $cameraList['list'][$k]['formate_add_time'] = format_time("add_time");
        }
        ok([
            "items" => $cameraList['list'],
            "page" => $page,
            "pagesize" => $pagesize,
            "totalpage" => ceil($cameraList['total'] / $pagesize),
            "total" => $cameraList['total']
        ]);
    }

    //直播详情
    public function index()
    {
        $id = intval(input("id"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $cameraInfo = \app\admin\model\Camera::get($id);
        $url = "http://59.63.205.84:10000/interface/interface.php?act=getLiveInfo";
        $para = [
            "client" => "cms",
            "id" => encrypt($cameraInfo['third_id']),
        ];
        $liveInfo = myhttp($url, $para);
        $liveInfo = json_decode($liveInfo, true);

        if ($liveInfo['status'] == 0) {
            ok(['live_path' => $liveInfo['url_hls'], 'token' => $liveInfo['token']]);
        } else {
            err(200, "", $liveInfo['status'], $liveInfo['error_desc']);
        }
    }

    //直播快照
    public function postAlarm()
    {
        $id = trim(input("id"));
        $tpe = intval(input("tpe"));
        if (!$id) {
            $lang = lang("params_not_valid");
            err(200, "params_not_valid", $lang['code'], $lang['message']);
        }
        $data = [
            "tpe" => $tpe,
            "uid" => 1,
            "add_time" => time()
        ];
        $inf = Camera_mod::alarmInsert($data);
        if (!$inf) err();
        $liveInfo = Camera_mod::getCameraInfo(["id" => $id]);
        $para = [
            "client" => "cms",
            "id" => encrypt($liveInfo['third_id']),
        ];
        $infourl = config("cst_live_path") . "?act=getLiveInfo";
        $info = myhttp($infourl, $para);
        $info = json_decode($info, true);
        if ($info['status'] == 1) {
            mtReturn(200, $info['error_desc'], $_REQUEST['navtabid'], false);
        }
        $url = config("cst_live_path") . "?act=postAlarm";
        $para = [
            "client" => "cms",
            "token" => encrypt($info['token']),
            "type" => encrypt($tpe),
            "id" => encrypt($inf)
        ];
        $Snapshot = myhttp($url, $para);
        $Snapshot = json_decode($Snapshot, true);
        if ($Snapshot['status'] == 0) {
            ok();
        } else {
            err(200, "", $Snapshot['status'], $Snapshot['error_desc']);
        }
    }

    public function updateStatus()
    {
        $source_id = intval(input('source_id'));
        $status = intval(input('status'));
        if (!$source_id) {
            err(200, "", '10021', '参数错误');
        }
        $info = \app\admin\model\Camera::update(['is_up' => $status], ['third_id' => $source_id]);
        $info ? ok() : err();
    }

    public function updateRecord()
    {
        $source_id = intval(input('source_id'));
        $status = intval(input('status'));
        if (!$source_id || !isset($status)) {
            err(200, "", '10021', '参数错误');
        }
        $info = Camera_mod::cameraUpdate(['is_record' => $status], ['third_id' => $source_id]);
        $info ? ok() : err();
    }

    public function alarmLog()
    {
        $img = trim(input('img'));
        $id = intval(input('id'));
        $status = intval(input('status'));
        if (!$id || !$img) {
            err(200, "", '10021', '参数错误');
        }
        $img = $this->_dealImg($img);
        $data = [
            'img' => $img,
            'status' => $status
        ];
        $inf = Camera_mod::alarmUpdate($data, ['id' => $id]);
        if ($inf) {
            ok();
        } else {
            err();
        }
    }

    public function _dealImg($url)
    {
        //$url = 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1561436832290&di=1ac74a4d7279db5d68ec0807d40c47be&imgtype=0&src=http%3A%2F%2Fpic37.nipic.com%2F20140113%2F8800276_184927469000_2.png';
        $data = myhttp($url);
        $ext_name = strrchr($url, '.');
        if (!file_exists(ROOT_PATH . 'public' . '/attaches/image/' . date('Ymd'))) {
            mkdir(ROOT_PATH . 'public' . '/attaches/image/' . date('Ymd'));
        }
        $newimg = '/attaches/image/' . str_replace("\\", "/", date('Ymd') . DS . md5(microtime(true))) . $ext_name;
        file_put_contents(ROOT_PATH . 'public' . $newimg, $data);
        return $newimg;
    }
}
