<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use think\Loader;
class Ueditor extends Backend{
	private $_config;
    protected $noNeedRight = ['index'];

	function __construct(){
		parent::__construct();
	}



	function index(){
		$editor_cnf_path = APP_PATH.'ueditor_config.json';
		$this->_config = json_decode(preg_replace("/\/\*[\s\S]+?\*\//", "", file_get_contents($editor_cnf_path)), true);
		$action = input('action');
		switch ($action){
			case 'config':
				$result =  json_encode($this->_config);
				break;
			/* 上传图片 */
		    case 'uploadimage':
		    /* 上传涂鸦 */
		    case 'uploadscrawl':
		    /* 上传视频 */
		    case 'uploadvideo':
		    /* 上传文件 */
		    case 'uploadfile':
		    	$result = $this->_action_upload();
		        break;
	        /* 列出图片 */
	        case 'listimage':
	        	$result = $this->_action_list();
	        	break;
	        /* 列出文件 */
	        case 'listfile':
	        	$result = $this->_action_list();
	        	break;
	        /* 抓取远程文件 */
	        case 'catchimage':
	        	$result = $this->_action_crawler();
	        	break;

	        default:
	        	$result = json_encode(array(
	        	'state'=> '请求地址出错'
	        			));
	        	break;
		}

		/* 输出结果 */
		if (isset($_GET["callback"])) {
			if (preg_match("/^[\w_]+$/", $_GET["callback"])) {
				echo htmlspecialchars($_GET["callback"]) . '(' . $result . ')';
			} else {
				echo json_encode(array(
						'state'=> 'callback参数不合法'
				));
			}
		} else {
			echo $result;
			exit;
		}
	}

	//上传图片
	private function _action_upload(){
		Loader::import("Uploader", EXTEND_PATH);


		$this->_config['imagePathFormat']  = ROOT_PATH.'public/attaches/image/'. date('Ymd') . '/'. time().mt_rand(10000,99999);
		$this->_config['scrawlPathFormat'] = ROOT_PATH.'public/attaches/image/'. date('Ymd') . '/'. time().mt_rand(10000,99999);
		$this->_config['videoPathFormat']  = ROOT_PATH.'public/attaches/video/'. date('Ymd') . '/'. time().mt_rand(10000,99999);
		$this->_config['filePathFormat']   = ROOT_PATH.'public/attaches/files/'. date('Ymd') . '/'. time().mt_rand(10000,99999);

		/* 上传配置 */
		$base64 = "upload";
		switch (htmlspecialchars(input('action'))) {
			case 'uploadimage':
				$config = array(
				"pathFormat" => $this->_config['imagePathFormat'],
				"maxSize"    => $this->_config['imageMaxSize'],
				"allowFiles" => $this->_config['imageAllowFiles']
				);
				$fieldName = $this->_config['imageFieldName'];
				break;
			case 'uploadscrawl':
				$config = array(
				"pathFormat" => $this->_config['scrawlPathFormat'],
				"maxSize"    => $this->_config['scrawlMaxSize'],
				"allowFiles" => $this->_config['scrawlAllowFiles'],
				"oriName" => "scrawl.png"
						);
				$fieldName = $this->_config['scrawlFieldName'];
				$base64 = "base64";
				break;
			case 'uploadvideo':
				$config = array(
				"pathFormat" => $this->_config['videoPathFormat'],
				"maxSize"    => $this->_config['videoMaxSize'],
				"allowFiles" => $this->_config['videoAllowFiles']
				);
				$fieldName = $this->_config['videoFieldName'];
				break;
			case 'uploadfile':
			default:
				$config = array(
				"pathFormat" => $this->_config['filePathFormat'],
				"maxSize"    => $this->_config['fileMaxSize'],
				"allowFiles" => $this->_config['fileAllowFiles']
				);
				$fieldName = $this->_config['fileFieldName'];
				break;
		}

		/* 生成上传实例对象并完成上传 */
		$up = new \Uploader($fieldName, $config, $base64);

		/**
		 * 得到上传文件所对应的各个参数,数组结构
		 * array(
		 *     "state" => "",          //上传状态，上传成功时必须返回"SUCCESS"
		 *     "url" => "",            //返回的地址
		 *     "title" => "",          //新文件名
		 *     "original" => "",       //原始文件名
		 *     "type" => ""            //文件类型
		 *     "size" => "",           //文件大小
		 * )
		 */
		/* 返回数据 */
		return json_encode($up->getFileInfo());
	}

	//列出图片、文件
	private function _action_list(){
		Loader::import("org/Uploader", EXTEND_PATH);

		$this->_config['fileManagerListPath'] = ROOT_PATH.'attaches/files/';
		$this->_config['imageManagerListPath'] = ROOT_PATH.'attaches/image/';
		/* 判断类型 */
		switch (input('action')) {
			/* 列出文件 */
			case 'listfile':
				$allowFiles = $this->_config['fileManagerAllowFiles'];
				$listSize = $this->_config['fileManagerListSize'];
				$path = $this->_config['fileManagerListPath'];
				break;
				/* 列出图片 */
			case 'listimage':
			default:
				$allowFiles = $this->_config['imageManagerAllowFiles'];
				$listSize = $this->_config['imageManagerListSize'];
				$path = $this->_config['imageManagerListPath'];
		}
		$allowFiles = substr(str_replace(".", "|", join("", $allowFiles)), 1);
		/* 获取参数 */
		$size = isset($_GET['size']) ? htmlspecialchars($_GET['size']) : $listSize;
		$start = isset($_GET['start']) ? htmlspecialchars($_GET['start']) : 0;
		$end = $start + $size;

		/* 获取文件列表 */
		$path = $this->_config['imageManagerListPath'];
		$files = $this->_getfiles($path, $allowFiles);
		if (!count($files)) {
			return json_encode(array(
					"state" => "no match file",
					"list" => array(),
					"start" => $start,
					"total" => count($files)
			));
		}

		/* 获取指定范围的列表 */
		$len = count($files);
		for ($i = min($end, $len) - 1, $list = array(); $i < $len && $i >= 0 && $i >= $start; $i--){
			$list[] = $files[$i];
		}
		//倒序
		//for ($i = $end, $list = array(); $i < $len && $i < $end; $i++){
		//    $list[] = $files[$i];
		//}

		/* 返回数据 */
		$result = json_encode(array(
				"state" => "SUCCESS",
				"list" => $list,
				"start" => $start,
				"total" => count($files)
		));

		return $result;
	}

	//抓取远程文件
	private function _action_crawler(){
		set_time_limit(0);
		Loader::import("org/Uploader", EXTEND_PATH);

		$this->_config['catcherPathFormat'] = ROOT_PATH.'public/attaches/image/'. date('Ymd') . '/'. time().mt_rand(10000,99999);

		/* 上传配置 */
		$config = array(
				"pathFormat" => $this->_config['catcherPathFormat'],
				"maxSize"    => $this->_config['catcherMaxSize'],
				"allowFiles" => $this->_config['catcherAllowFiles'],
				"oriName" => "remote.png"
		);
		$fieldName = $this->_config['catcherFieldName'];

		/* 抓取远程图片 */
		$list = array();
		if (isset($_POST[$fieldName])) {
			$source = $_POST[$fieldName];
		} else {
			$source = $_GET[$fieldName];
		}
		foreach ($source as $k=>$imgUrl) {
			$config['pathFormat'] = $this->_config['catcherPathFormat'].'_'.$k;
			$item = new \Uploader($imgUrl, $config, "remote");
			$info = $item->getFileInfo();
			array_push($list, array(
					"state" => $info["state"],
					"url" => $info["url"],
					"size" => $info["size"],
					"title" => htmlspecialchars($info["title"]),
					"original" => htmlspecialchars($info["original"]),
					"source" => htmlspecialchars($imgUrl)
			));
		}

		/* 返回抓取数据 */
		return json_encode(array(
				'state'=> count($list) ? 'SUCCESS':'ERROR',
				'list'=> $list
		));
	}

	/**
	 * 遍历获取目录下的指定类型的文件
	 * @param $path
	 * @param array $files
	 * @return array
	 */
	private function _getfiles($path, $allowFiles, &$files = array())
	{
		if (!is_dir($path)) return null;
		if(substr($path, strlen($path) - 1) != '/') $path .= '/';
		$handle = opendir($path);
		while (false !== ($file = readdir($handle))) {
			if ($file != '.' && $file != '..') {
				$path2 = $path . $file;
				if (is_dir($path2)) {
					$this->_getfiles($path2, $allowFiles, $files);
				} else {
					if (preg_match("/\.(".$allowFiles.")$/i", $file)) {
						$files[] = array(
								//'url'=> substr($path2, strlen($_SERVER['DOCUMENT_ROOT'])),
								'url'=> str_replace(ROOT_PATH, '/', $path2),
								'mtime'=> filemtime($path2)
						);
					}
				}
			}
		}
		return $files;
	}

}
