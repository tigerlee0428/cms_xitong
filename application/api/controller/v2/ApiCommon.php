<?php
namespace app\api\controller\v2;

use app\common\controller\Api;
/**
 * 父控制器
 * @author 17291
 *
 */
class ApiCommon extends Api
{
    protected $uid;
    protected $is_realname;
    protected function _initialize(){
        parent::_initialize();
        $this->uid = $this->auth->id;
        $this->is_realname = $this->auth->mobile && $this->auth->realname ? true : false;
    }
}
