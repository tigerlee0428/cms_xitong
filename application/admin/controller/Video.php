<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 系统 - 视频管理
 *
 * @icon fa fa-circle-o
 */
class Video extends Backend
{

    /**
     * Video模型对象
     * @var \app\admin\model\Video
     */
    protected $model = null;
    protected $noNeedRight = ['add_post'];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\Video;
        $this->searchFields = "title";
    }

    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();

            foreach ($list as $k => $v) {
                $list[$k]['video_img'] = str_replace(config('cvs_callback_play_url'), cfg("cst_video_public_url"), $v['video_img']);
                $list[$k]['p_url'] = str_replace(config('cvs_callback_play_url'), cfg("cst_video_public_url"), $v['p_url']);
                $list[$k]['m_url'] = str_replace(config('cvs_callback_play_url'), cfg("cst_video_private_url"), $v['m_url']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    public function add_post()
    {
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Content-type: text/html; charset=utf-8");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            exit; // finish preflight CORS requests here
        }
        if (!empty($_REQUEST['debug'])) {
            $random = rand(0, intval($_REQUEST['debug']));
            if ($random === 0) {
                header("HTTP/1.0 500 Internal Server Error");
                exit;
            }
        }

        @set_time_limit(5 * 60);
        $targetDir = '.' . DIRECTORY_SEPARATOR . 'attaches' . DIRECTORY_SEPARATOR . 'video_tmp';
        $uploadDir = '.' . DIRECTORY_SEPARATOR . 'attaches' . DIRECTORY_SEPARATOR . 'video' . DIRECTORY_SEPARATOR . date('Ymd');
        $cleanupTargetDir = true; // Remove old files
        $maxFileAge = 5 * 3600; // Temp file age in seconds
        if (!file_exists($targetDir)) {
            @mkdir($targetDir);
        }
        if (!file_exists($uploadDir)) {
            @mkdir($uploadDir);
        }
        if (isset($_REQUEST["name"])) {
            $fileName = $_REQUEST["name"];
        } elseif (!empty($_FILES)) {
            $fileName = $_FILES["file"]["name"];
        } else {
            $fileName = uniqid("file_");
        }
        $oldName = $fileName;
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        $chunk = isset($_REQUEST["chunk"]) ? intval($_REQUEST["chunk"]) : 0;
        $chunks = isset($_REQUEST["chunks"]) ? intval($_REQUEST["chunks"]) : 1;
        if ($cleanupTargetDir) {
            if (!is_dir($targetDir) || !$dir = opendir($targetDir)) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "Failed to open temp directory."}, "id" : "id"}');
            }
            while (($file = readdir($dir)) !== false) {
                $tmpfilePath = $targetDir . DIRECTORY_SEPARATOR . $file;
                if ($tmpfilePath == "{$filePath}_{$chunk}.part" || $tmpfilePath == "{$filePath}_{$chunk}.parttmp") {
                    continue;
                }
                if (preg_match('/\.(part|parttmp)$/', $file) && (@filemtime($tmpfilePath) < time() - $maxFileAge)) {
                    @unlink($tmpfilePath);
                }
            }
            closedir($dir);
        }

        if (!$out = @fopen("{$filePath}_{$chunk}.parttmp", "wb")) {
            die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
        }
        if (!empty($_FILES)) {
            if ($_FILES["file"]["error"] || !is_uploaded_file($_FILES["file"]["tmp_name"])) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 103, "message": "Failed to move uploaded file."}, "id" : "id"}');
            }
            if (!$in = @fopen($_FILES["file"]["tmp_name"], "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        } else {
            if (!$in = @fopen("php://input", "rb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "Failed to open input stream."}, "id" : "id"}');
            }
        }
        while ($buff = fread($in, 4096)) {
            fwrite($out, $buff);
        }
        @fclose($out);
        @fclose($in);
        rename("{$filePath}_{$chunk}.parttmp", "{$filePath}_{$chunk}.part");
        $index = 0;
        $done = true;
        for ($index = 0; $index < $chunks; $index++) {
            if (!file_exists("{$filePath}_{$index}.part")) {
                $done = false;
                break;
            }
        }
        if ($done) {
            $pathInfo = pathinfo($fileName);
            $hashStr = substr(md5($pathInfo['basename']), 8, 16);
            $hashName = time() . $hashStr . '.' . $pathInfo['extension'];
            $uploadPath = $uploadDir . DIRECTORY_SEPARATOR . $hashName;

            if (!$out = @fopen($uploadPath, "wb")) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 102, "message": "Failed to open output stream."}, "id" : "id"}');
            }
            if (flock($out, LOCK_EX)) {
                for ($index = 0; $index < $chunks; $index++) {
                    if (!$in = @fopen("{$filePath}_{$index}.part", "rb")) {
                        break;
                    }
                    while ($buff = fread($in, 4096)) {
                        fwrite($out, $buff);
                    }
                    @fclose($in);
                    @unlink("{$filePath}_{$index}.part");
                }
                flock($out, LOCK_UN);
            }
            @fclose($out);
            $response['title'] = $oldName;
            $response['address'] = '/attaches/video/' . date('Ymd') . '/' . $hashName;
            $response['extension'] = $pathInfo['extension'];
            $response['addtime'] = time();
            $result = \app\common\model\Video::insertGetId($response);

            $postdata['callback'] = config("cvs_callback_api_url") . '/api/common/video';
            $postdata['address'] = config("cvs_callback_api_url") . $response['address'];
            $postdata['video_id'] = trim($result);
            $postdata['is_oss'] = '2';
            $postdata['action'] = 'addVodFile';
            $cvsurl = config("cvs_api_url") . '/api/v1/clients';

            $data_string = json_encode($postdata);
            $ch = curl_init($cvsurl);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string))
            );
            $resultcvs = curl_exec($ch);
            curl_close($ch);
            $arrresultcvs = json_decode($resultcvs, true);
            \app\common\model\Video::update(['error_info' => $arrresultcvs['error_desc'], 'status' => $arrresultcvs['status']], ['id' => $result]);
            die(json_encode($postdata));
        }
    }

    public function againAdd($ids){
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }

        $postdata['callback'] = config("cvs_callback_api_url") . '/api/common/video';
        $postdata['address'] = config("cvs_callback_api_url") . $row['address'];
        $postdata['video_id'] = trim($ids);
        $postdata['is_oss'] = '2';
        $postdata['action'] = 'addVodFile';

        $cvsurl = config("cvs_api_url") . '/api/v1/clients';
        $data_string = json_encode($postdata);
        $ch = curl_init($cvsurl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );
        $resultcvs = curl_exec($ch);
        curl_close($ch);
        $arrresultcvs = json_decode($resultcvs, true);

        \app\common\model\Video::update(['error_info' => $arrresultcvs['error_desc'], 'status' => $arrresultcvs['status']], ['id' => $ids]);
        $this->success();
    }

    /**
     * 选择
     */
    public function select()
    {
        if ($this->request->isAjax()) {
            return $this->index();
        }
        return $this->view->fetch();
    }


    public function preview($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $row = $row->toArray();
        $row['address'] = \think\Env::get("interface_domain") . $row['address'];
        $row['p_url'] = str_replace(config('cvs_callback_play_url'),cfg("cst_video_public_url") ,$row['p_url']);
        $row['m_url'] = str_replace(config('cvs_callback_play_url'),cfg("cst_video_private_url"),$row['m_url']);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

}
