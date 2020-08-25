<?php
namespace app\api\controller\v2;

use app\api\controller\v2\ApiCommon;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use EasyWeChat\Kernel\Support\Collection;
/**
 * 资讯接口
 */
class Article extends ApiCommon
{
    protected $noNeedLogin = ['likeArticleList','hotArticleList','articleList','index','like','collection','collectionList'];
    protected $noNeedRight = '*';
    protected $model = null;
    protected function _initialize(){
        parent::_initialize();
        $this->model = new \app\admin\model\Article;
    }

    /**
     * 文章详情
     * @param int $id 文章ID
     * @param int $share 是否来自分享
     * @param int $cardNo 是否来自电视（盒号）
     *
     */
    public function index()
    {
        $id = intval(input("id"));
        $duration = 0;
        $share = intval(input("share"));
        $cardNo = trim(input("cardno"));
        $articleInfo = $this->model->get($id);
        if(!$articleInfo){
            $lang = lang("not_article");
            err(200,"not_article",$lang['code'],$lang['message']);
        }
        $articleInfo = $articleInfo->toArray();
        if($share != 1){
    		$this->model->save(['click_count'=>$articleInfo['click_count']+1], ['id'=>$id]);
        }
        $video_path = $video_path_tv = '';
        switch($articleInfo['tpe']){
            case 4:
                $videoInfo = \app\common\model\Video::get($articleInfo['video_id'])->toArray();
                $duration = $videoInfo['duration'];
                //$video_path = $videoInfo['address'];
                $video_path = str_replace(config('cvs_callback_play_url'), cfg("cst_video_public_url"), $videoInfo['m_url']);
                $video_path_tv = str_replace(config('cvs_callback_play_url'), '/',$videoInfo['p_url']);
                break;
            case 5:
                $liveInfo = \app\common\model\Camera::get($articleInfo['video_id']);
                if($liveInfo){
                    $liveInfo = $liveInfo->toArray();
                    $url = cfg("cst_live_url")."act=getLiveInfo";
                    $para=[
                        "client"    => "cms",
                        "id"        => encrypt($liveInfo['third_id']),
                    ];
                    $live = json_decode(myhttp($url,$para),true);
                    $video_path = str_replace(cfg("cst_live_private_url"), cfg("cst_live_public_url"), $live['url_hls']);
                    $video_path_tv = $live['url_hls'];
                }
                break;
        }
        $data = [
            'id'        => $articleInfo['id'],
            'title'     => $articleInfo['title'],
            'brief'     => $articleInfo['brief'],
            'tpe'       => $articleInfo['tpe'],
            'img'       => $articleInfo['img'],
            'images'    => $articleInfo['images'] ? explode(",",$articleInfo['images']) : [],
            'video_id'  => $articleInfo['video_id'],
            'video_path'=> $video_path,
            'video_path_tv'=> $video_path_tv,
            'author'    => $articleInfo['author'],
            'category'  => $articleInfo['category'],
            'category_name' => \app\common\model\Category::where(['id'=>$articleInfo['category']])->value('title'),
            'format_add_time'  => format_time($articleInfo['add_time']),
            'click_count'=> $articleInfo['click_count'],
            'duration'  => $duration,
            'likes'     => $articleInfo['likes'],
            'collection_count'     => $articleInfo['collection_count'],
            'area_name' => $articleInfo['area_id'] ? \app\common\model\Area::where(['id'=>$articleInfo['area_id']])->value('name') : '实践中心',
        ];
        if($this->uid){
            $data['is_like'] = \app\common\model\ArticleLikeLog::where(['aid'=>$id,'uid'=>$this->uid])->count() ? 1 : 0;
        }
        if($cardNo){
            $Cwhere['cardno'] = $cardNo;
            $Cwhere['aid'] = $id;
            $data['is_collect'] = \app\common\model\ArticleCollectionLog::where($Cwhere)->count() ? 1 : 0;
        }
        $table = \app\admin\model\CategoryModule::where(['code'=>$articleInfo['article_model']])->value("table");
        $table = "article_".$table;
        if(db()->query('SHOW TABLES LIKE '."'".config("database.prefix").$table."'")){
           $fields_sql = "SHOW COLUMNS FROM ".config("database.prefix").$table;
           $fields_data = db()->query($fields_sql);
           $articleExtend = Db::name($table)->where(['id'=>$id])->find();
           foreach($fields_data as $k => $v){
               if($v['Field'] == 'id'){
                   continue;
               }
               $data[$v['Field']] = $articleExtend[$v['Field']];
           }
        }
        ok($data);
    }


    /**
     * 文章列表
     * @param int $cid    栏目分类ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param string $device    设备标识
     * @param int $area_id    区域ID
     * @param int $is_show_index    是否首页显示
     * @param int $tpe    文章类型
     * @param int $keyword    关键词
     * @return array
     *
     */

    public function articleList(){
        $cid        = intval(input("cid"));
        $page       = intval(input("page"));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $page       = max($page,1);
        $device     = trim(input("device"));
        $keyword    = trim(input("keyword"));
        $area_id    = intval(input("area_id",$this->auth->area_id));
        $is_show_index = intval(input("is_show_index"));
        $tpe        = trim(input("tpe"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $cateList = [];
        $cate = Collection(\app\common\model\Category::all())->toArray();
        foreach($cate as $k => $v){
            $cateList[$v['id']] = $v['title'];
        }
        $where = ['is_show'=>1,'is_check'=>1,"is_publish"=>1,'is_del'=>0,'is_final_check'=>1];
        switch($device){
            case 'pc':
                $where['is_pc_show'] = 1;
                break;
            case 'wx':
                $where['is_wx_show'] = 1;
                break;
            case 'tv':
                $where['is_tv_show'] = 1;
                break;
        }
        if($cid){
            $where['category'] = ["in",\app\common\model\Cfg::childCategory($cid)];
        }
        if($is_show_index){
            $where['is_show_index'] = $is_show_index;
        }

        if($tpe){
            if(is_numeric($tpe)){
        	   $where['tpe'] = $tpe;
            }
            if(is_array(explode(",",$tpe))){
                $where['tpe'] = ['in',explode(",",$tpe)];
            }
        }
        if($this->uid){
            $where['uid'] = $this->uid;
            unset($where['is_final_check']);
        }
        if($area_id){
            $where['area_id'] = ['in',\app\common\model\Cfg::childArea($area_id)];
        }
        if($keyword){
            $where['title'] = ['like',"%".$keyword."%"];
        }
        $articleList = \app\common\model\Article::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Article::where($where)->count();
        $list = [];
        foreach($articleList as $k => $v)
        {

            $video_path = $video_path_tv = "";
            switch($v['tpe']){
                case 4:
                    $videoInfo = \app\common\model\Video::get($v['video_id']);
                    $duration = $videoInfo['duration'];
                    $video_path = str_replace(config('cvs_callback_play_url'), config('cvs_proxy_url'), $videoInfo['m_url']);
                    $video_path_tv = str_replace(config('cvs_callback_play_url'), '/',$videoInfo['p_url']);
                    break;
            }
            $list[$k] = [
                'id'        => $v['id'],
                'title'     => $v['title'],
                'category'  => $cateList[$v['category']],
                'brief'     => $v['brief'],
                'tpe'       => $v['tpe'],
                'img'       => $v['img'],
                'thumb_img' => $v['img'],
                'images'    => $v['images'] ? explode(",",$v['images']) : [],
                'author'    => $v['author'],
                'area_id'   => $v['area_id'],
                'add_time'  => format_time($v['add_time'],"Y-m-d"),
                'click_count' => $v['click_count'],
                'collection_count'=> $v['collection_count'],
                'likes'     => $v['likes'],
                'video_path'=> $video_path,
                'video_path_tv'=> $video_path_tv,
                'area_name' => \app\common\model\Area::where(['id'=>$v['area_id']])->value("name"),
            ];
        }
        $categoryname = \app\common\model\Category::where(['id'=>$cid])->value('title');
        ok([
            "categoryname" => $categoryname,
            "items" => $list,
            "page"  => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
        	"total"     => $total
        ]);
    }



    /**
     * 点赞
     * @param int $id 文章ID
     */
    public function like(){
        $id = intval(input("id"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $articleInfo = $this->model->get($id);
        if (!$articleInfo) {
            $lang = lang("not_article");
            err(200, "not_article", $lang['code'], $lang['message']);
        }
        $articleInfo = $articleInfo->toArray();
        $likeInfo = \app\common\model\ArticleLikeLog::where(['aid'=>$id,'ua' => _ua_key(),'ip' => get_onlineip(),'daytime'=>strtotime(date("Y-m-d"))])->find();
        if(!$likeInfo){
            $data = [
                'aid'   => $id,
                'ua'    => _ua_key(),
                'ip'    => get_onlineip(),
                'daytime'  => strtotime(date("Y-m-d")),
                'uid'   => $this->uid
            ];
            \app\common\model\ArticleLikeLog::create($data);
            $this->model->update(['likes'=>$articleInfo['likes']+1],['id'=>$id]);
            if($this->uid){
                $integralInfo = [
                    'event_code'        => 'Like',
                    'uid'               => $this->uid,
                    'area_id'           => $this->auth->area_id,
                    'note'              => '点赞'.$articleInfo['title'],
                    'obj_id'            => $id,
                ];
                \think\Hook::listen("integral",$integralInfo);
            }
            ok(['likes'=>$articleInfo['likes']+1]);
        }
        $lang = lang("has_click");
        err(200, "has_click", $lang['code'], $lang['message']);
    }

    /**
     * 收藏
     * @param int $id 文章ID
     * @param string $cardNum 机顶盒号
     */
    public function collection(){
        $id = intval(input("id"));
        $cardNum = trim(input("cardNum"));
        if(!$id){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $articleInfo = $this->model->get($id);
        if (!$articleInfo) {
            $lang = lang("not_article");
            err(200, "not_article", $lang['code'], $lang['message']);
        }
        $articleInfo = $articleInfo->toArray();
        $where = [
            'aid'   => $id,
        ];
        if($this->auth->id){
            $where['uid'] = $this->auth->id;
        }
        if($cardNum){
            $where['cardno'] = $cardNum;
        }
        $collectionInfo = \app\common\model\ArticleCollectionLog::where($where)->find();
        if(!$collectionInfo){
            $data = [
                'aid'       => $id,
                'title'     => $articleInfo['title'],
                'add_time'  => time(),
                'uid'       => $this->auth->id,
                'cardno'    => $cardNum
            ];
            \app\common\model\ArticleCollectionLog::create($data);
            $this->model->update(['collection_count'=>$articleInfo['collection_count']+1],['id'=>$id]);

            ok(['collection_count'=>$articleInfo['collection_count']+1]);
        }
        $where['aid'] = $id;
        \app\common\model\ArticleCollectionLog::where($where)->delete();
        $lang = lang("cancel_collect");
        err(200,"cancel_collect",$lang['code'],$lang['message']);
    }

    /**
     * 我的收藏列表
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param string $cardNo    盒号
     * @return array
     *
     */

    public function collectionList(){
        $page       = intval(input("page"));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","add_time desc"));
        $cardNo     = trim(input("cardNo"));
        $page       = max($page,1);
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $where = [];
        if($cardNo){
            $where['cardno'] = $cardNo;
        }
        if($this->uid){
            $where['uid'] = $this->uid;
        }
        $articleList = \app\common\model\ArticleCollectionLog::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\ArticleCollectionLog::where($where)->count();
        $list = [];
        foreach($articleList as $k => $v)
        {
            $list[$k] = [
                'aid'       => $v['aid'],
                'id'        => $v['id'],
                'title'     => $v['title'],
                'add_time'  => format_time($v['add_time'],"Y-m-d"),
            ];
        }
        ok([
            "items" => $list,
            "page"  => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

    /**
     * 资讯投稿
     * @param string $title 资讯标题
     * @param array $img 图片
     * @param string $content 内容
     * @param int $category 栏目ID
     *
     */
    public function post(){
        $title      = trim(input("title"));
        $img        = input("img/a");
        $content    = trim(input("content"));
        $category   = intval(input("category"));

        if(!$content || !$title || !$category){
            $lang = lang("params_not_valid");
            err(200,"params_not_valid",$lang['code'],$lang['message']);
        }
        $article_model  = \app\common\model\Category::where(['id'=>$category])->value("model_type");
        $thumb = '';
        $images = '';
        if(is_array($img) && isset($img[0])){

            $thumb = $img[0];
            $images = implode(",",$img);
        }

        $params = array(
            "title"         => $title,
            "brief"         => $title,
            "add_time"      => time(),
            "click_count"   => rand(100,200),
            "category"      => $category,
            "is_show"       => 1,
            "tpe"           => 3,
            "img"           => $thumb,
            "images"        => $images,
            "content"       => $content,
            "area_id"       => $this->auth->area_id,
            "is_check"      => 1,
            "is_final_check"=> 0,
            "is_publish"    => 0,
            "is_tv_show"    => 1,
            "is_wx_show"    => 1,
            "is_pc_show"    => 1,
            "uid"           => $this->uid,
            "article_model" => $article_model
        );
        $extendParams = [];
        $extendTable = \app\admin\model\CategoryModule::where(['code'=>$params['article_model']])->value('table');
        if(Db::query('SHOW TABLES LIKE '."'".config("database.prefix")."article_".$extendTable."'")){
            $fields_sql = "SHOW COLUMNS FROM ".config("database.prefix")."article_".$extendTable;
            $fields_data = Db::query($fields_sql);
            foreach($fields_data as $k => $v){
                if($v['Field'] == 'id'){
                    continue;
                }
                $extendParams[$v['Field']] = $params[$v['Field']];
                unset($params[$v['Field']]);
            }
        }
        $result = false;
        Db::startTrans();
        try {
            /* //是否采用模型验证
            if ($this->modelValidate) {
                $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                $this->model->validateFailException(true)->validate($validate);
            } */
            $result = $this->model->allowField(true)->insertGetId($params);
            Db::commit();
        } catch (ValidateException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (PDOException $e) {
            Db::rollback();
            $this->error($e->getMessage());
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
        if ($result !== false) {
            $extendParams['id'] = $result;
            Db::name("article_".$extendTable)->insert($extendParams);
            ok();
        } else {
            $lang = lang("post_case_err");
            err(200,"post_case_err",$lang['code'],$lang['message']);
        }

    }

    /**
     * 热门资讯
     * @param int $cid    栏目分类ID
     * @param int $page      页码
     * @param int $pagesize  每页数
     * @param int $orders    排序
     * @param string $device    设备标识
     * @param int $area_id    区域ID
     * @param int $is_show_index    是否首页显示
     * @param int $tpe    文章类型
     * @param int $keyword    关键词
     * @return array
     *
     */

    public function hotArticleList(){
        $cid        = intval(input("cid"));
        $page       = intval(input("page"));
        $pagesize   = intval(input("pagesize",10));
        $orders     = trim(input("orders","click_count desc"));
        $page       = max($page,1);
        $device     = trim(input("device"));
        $keyword    = trim(input("keyword"));
        $area_id    = intval(input("area_id",$this->auth->area_id));
        $is_show_index = intval(input("is_show_index"));
        $tpe        = trim(input("tpe"));
        $page = max($page,1);
        $pagesize = $pagesize ? $pagesize : 10;
        $cateList = [];
        $cate = Collection(\app\common\model\Category::all())->toArray();
        foreach($cate as $k => $v){
            $cateList[$v['id']] = $v['title'];
        }
        $where = ['is_show'=>1,'is_check'=>1,"is_publish"=>1,'is_del'=>0,'is_final_check'=>1];
        switch($device){
            case 'pc':
                $where['is_pc_show'] = 1;
                break;
            case 'wx':
                $where['is_wx_show'] = 1;
                break;
            case 'tv':
                $where['is_tv_show'] = 1;
                break;
        }
        if($cid){
            $where['category'] = ["in",\app\common\model\Cfg::childCategory($cid)];
        }
        if($is_show_index){
            $where['is_show_index'] = $is_show_index;
        }

        if($tpe){
            if(is_numeric($tpe)){
                $where['tpe'] = $tpe;
            }
            if(is_array(explode(",",$tpe))){
                $where['tpe'] = ['in',explode(",",$tpe)];
            }
        }
        if($this->uid){
            $where['uid'] = $this->uid;
            unset($where['is_final_check']);
        }
        if($area_id){
            $where['area_id'] = ['in',\app\common\model\Cfg::childArea($area_id)];
        }
        if($keyword){
            $where['title'] = ['like',"%".$keyword."%"];
        }
        $articleList = \app\common\model\Article::where($where)->page($page)->limit($pagesize)->order($orders)->select();
        $total = \app\common\model\Article::where($where)->count();
        $list = [];
        foreach($articleList as $k => $v)
        {
            $list[$k] = [
                'id'        => $v['id'],
                'title'     => $v['title'],
                'category'  => isset($cateList[$v['category']]) ? $cateList[$v['category']] : '',
                'brief'     => $v['brief'],
                'tpe'       => $v['tpe'],
                'img'       => $v['img'],
                'thumb_img' => thumb_img($v['img']),
                'images'    => $v['images'] ? explode(",",$v['images']) : [],
                'author'    => $v['author'],
                'area_id'   => $v['area_id'],
                'add_time'  => format_time($v['add_time'],"Y-m-d"),
                'click_count' => $v['click_count'],
                'collection_count'=> $v['collection_count'],
                'likes'     => $v['likes'],
                'area_name' => \app\common\model\Area::where(['id'=>$v['area_id']])->value("name"),
            ];
        }
        $categoryname = \app\common\model\Category::where(['id'=>$cid])->value('title');
        ok([
            "categoryname" => $categoryname,
            "items" => $list,
            "page"  => $page,
            "pagesize"  => $pagesize,
            "totalpage" => ceil($total/$pagesize),
            "total"     => $total
        ]);
    }

}
