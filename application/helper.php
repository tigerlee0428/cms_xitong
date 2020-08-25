<?php

use think\Config;
use think\Url;

if (!function_exists('qrcode')) {
    function qrcode($url, $size = 8, $path = false)
    {
        \PHPQRCode\QRcode::png($url, $path, 'L', $size, 2);
        exit;
    }
}
if (!function_exists('thumb_img')) {
    //缩略图生成
    function thumb_img($pic ,$thumb = 'thumb'){
        return pathinfo($pic, PATHINFO_DIRNAME) . '/'. $thumb ."_" . pathinfo($pic, PATHINFO_FILENAME) . '.' . pathinfo($pic, PATHINFO_EXTENSION);
    }
}
if (!function_exists('authcode')) {
    /**
     * 可逆加密函数，基于base64加密
     * @param $string  需加密字符串
     * @param $operation  解密/加密
     * @param $key  秘钥
     * return 返回加密或解密后的字符串
     **/
    function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
        $ckey_length = 4;
        // 密匙
        $key = md5($key != '' ? $key : config('authkey'));
        // 密匙a会参与加解密  conf
        $keya = md5(substr($key, 0, 16));
        // 密匙b会用来做数据完整性验证
        $keyb = md5(substr($key, 16, 16));
        // 密匙c用于变化生成的密文
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
        // 参与运算的密匙
        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);
        // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性  
        // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确  
        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);


        $result = '';
        $box = range(0, 255);


        $rndkey = array();
        // 产生密匙簿
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }
        // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        // 核心加解密部分
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            // 从密匙簿得出密匙进行异或，再转成字符
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'DECODE') {
            // substr($result, 0, 10) == 0 验证数据有效性  
            // substr($result, 0, 10) - time() > 0 验证数据有效性  
            // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性  
            // 验证数据有效性，请看未加密明文的格式 	
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因  
            // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码  	
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }
}


if (!function_exists('children')) {
    function children($list, $parent_id = 0)
    {
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $k => $v) {
                $refer[$v['id']] =& $list[$k];
            }
            foreach ($list as $key => $data) {
                //判断是否存在parent
                $parentId = $data['pid'];
                if ($parent_id == $parentId) {
                    $tree[$data['id']] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        $parent['child'][] =& $list[$key];
                    }
                }
            }
        }
        return $tree;
    }
}

if (!function_exists('is_wx')) {
    //判断是否在微信中
    function is_wx()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }
}

if (!function_exists('format_time')) {
    //格式化时间
    function format_time($time, $format = 'Y-m-d H:i:s')
    {
        $time_str = '-';
        if ($time) {
            $time_str = date($format, $time);
        }
        return $time_str;
    }
}

if (!function_exists('myhttp')) {
    function myhttp($url, $post_data = '', $time_out = 5)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, "");
        if ($time_out) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $time_out);   //只需要设置一个秒的数量就可以
        }
        // 设置你需要抓取的URL
        curl_setopt($curl, CURLOPT_URL, $url);
        if ($post_data) {
            curl_setopt($curl, CURLOPT_POST, 1);    //确定递交方式,POST\GET
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        }
        // 设置header
        curl_setopt($curl, CURLOPT_HEADER, false);
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        //处理IPV6
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        //模拟用户使用的浏览器，在HTTP请求中包含一个"user-agent"头的字符串。
        //curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (compatible; MSIE 6.0; Windows NT 5.0)");
        // 运行cURL，请求网页
        $data = curl_exec($curl);
        // 关闭URL请求
        curl_close($curl);
        return $data;
    }
}
if (!function_exists('_ua_key')) {
    function _ua_key()
    {
        $ua = md5($_SERVER['HTTP_USER_AGENT'] . get_onlineip()) . config('authkey');
        return hash("sha256", $ua);
    }
}

if (!function_exists('format_option')) {

    function format_option($cateList, $cur_cat_id = '')
    {
        $list = [];
        foreach ($cateList as $k => $v) {
            $list[$v['id']] = $v;
        }

        $cate_str = '<option value="0" >请选择</option>';
        foreach ($list as $curdata) {
            $select = '';
            if ($curdata['id'] == $cur_cat_id) {
                $select = ' selected = "selected"';
            }

            $cate_str .= '<option value="' . $curdata['id'] . '" ' . $select . '>' . $curdata['title'] . '</option>';
            if (isset($curdata['child'])) {
                $cate_str .= get_zcate($curdata['child'], $cur_cat_id);
            }
        }
        return $cate_str;
    }
}
if (!function_exists('get_zcate')) {
    function get_zcate($cur_zcate, $cur_cat_id = 0, &$cj = 1)
    {
        $html = '';
        if ($cur_zcate) {
            foreach ($cur_zcate as $cur) {
                $select = '';
                if ($cur['id'] == $cur_cat_id || in_array($cur['id'], (array)$cur_cat_id)) {
                    $select = ' selected = "selected"';
                }
                $sj = '';
                for ($i = 0; $i < $cj; $i++) {
                    $sj .= '　　';
                }

                if (isset($cur['child'])) {
                    $html .= '<option value="' . $cur['id'] . '"' . $select . '>' . $sj . '|-' . $cur['title'] . '</option>';
                    $cuj = $cj + 1;
                    $html .= get_zcate($cur['child'], $cur_cat_id, $cuj);
                } else {
                    $html .= '<option value="' . $cur['id'] . '"' . $select . '>' . $sj . '|-' . $cur['title'] . '</option>';
                }
            }
        }
        return $html;
    }
}
if (!function_exists('password')) {
    /**
     * 密码生成
     */
    function password($password, $salt = '')
    {
        return md5(md5($password) . $salt);
    }
}


if (!function_exists('get_onlineip')) {
    /**
     * 获取客户端用户IP地址
     */
    function get_onlineip($format = 0)
    {
        $onlineip = '';
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $onlineip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $onlineip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $onlineip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $onlineip = $_SERVER['REMOTE_ADDR'];
        }
        if ($format && $onlineip) {
            $ips = explode('.', $onlineip);
            for ($i = 0; $i < 3; $i++) {
                $ips[$i] = intval($ips[$i]);
            }
            return sprintf('%03d%03d%03d', $ips[0], $ips[1], $ips[2]);
        }
        return $onlineip;
    }
}


if (!function_exists('ok')) {
    function ok($data = array(), $msg = '')
    {
        if (is_array($data) && !$data) {
            $data = (object)array();
        }
        $a = array('status' => 200, 'exception' => '', 'code' => 0, 'message' => $msg, 'data' => $data);
        ret($a);
    }
}
if (!function_exists('err')) {
    function err($status = 200, $exception = '', $code = 1, $msg = '', $data = array())
    {
        if (is_array($data) && !$data) {
            $data = (object)array();
        }
        $a = array('status' => $status, 'exception' => $exception, 'code' => $code, 'message' => $msg, 'data' => $data);
        ret($a);
    }

}

if (!function_exists('skterr')) {
    function skterr($status = 200, $exception = '', $code = 1, $msg = '', $data = array())
    {
        if (is_array($data) && !$data) {
            $data = (object)array();
        }
        $a = array('status' => $status, 'exception' => $exception, 'code' => $code, 'message' => $msg, 'data' => $data);
        return $a;
    }

}

if (!function_exists('sktok')) {
    function sktok($data = array(), $msg = '')
    {
        if (is_array($data) && !$data) {
            $data = (object)array();
        }
        $a = array('status' => 200, 'exception' => '', 'code' => 0, 'message' => $msg, 'data' => $data);
        return $a;
    }
}
if (!function_exists('ret')) {
    function ret($data)
    {
        $data = json_encode($data);
        header('Content-type: application/json');

        if (!empty($_GET['callback'])) {
            header("Access-Control-Allow-Origin: *");   //全域名
            header("Access-Control-Allow-Credentials: true");   //是否可以携带cookie
            echo $_GET['callback'] . '(' . $data . ')'; // jsonp
            exit;
        }
        $allow_origin = config("allow_orgin");
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        if (in_array($origin, $allow_origin)) {
            header('Access-Control-Allow-Credentials:true');
            header('Access-Control-Allow-Origin: '.$origin );
            //header("Access-Control-Allow-Origin: http://localhost"); //允许的来源
            //OPTIONS通过后，保存的时间，如果不超过这个时间，是不会再次发起OPTIONS请求的。
            header("Access-Control-Max-Age: 86400");
            //!!!之前我碰到和你一样的问题，这个没有加导致的。
            header("Access-Control-Allow-Headers: Content-Type");
            //允许的请求方式
            header("Access-Control-Allow-Methods: OPTIONS, GET, PUT, POST, DELETE");
        }
        echo $data;
        exit;
    }
}
if (!function_exists('random_str')) {
    function random_str($length)
    {
        //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组     
        $arr = array_merge(range(0, 9), range('a', 'z'));
        $str = '';
        $arr_len = count($arr);
        for ($i = 0; $i < $length; $i++) {
            $rand = mt_rand(0, $arr_len - 1);
            $str .= $arr[$rand];
        }
        return $str;

    }
}
if (!function_exists('notice')) {
    function notice($data)
    {
        $redis = new \Redis();
        $redis->connect("127.0.0.1", 6379);
        $redis->lpush("wx-notice-msg-list-".md5(\think\Env::get("database.database")), json_encode($data));
    }
}

if (!function_exists('make_sign')) {
    function make_sign($str = '')
    {
        return md5(_ua_key() . $str);
    }
}

if (!function_exists('cfg')) {
    function cfg($str)
    {
        return \think\Config::get("site." . $str);
    }
}


if (!function_exists('strpage')) {
    function strpage($str, $pgsize = 100)
    {
        $pagestr = [];
        //中文标点
        $char = "。、！？：；﹑•＂…‘’“”〝〞∕¦‖—　〈〉﹞﹝「」‹›〖〗】【»«』『〕〔》《﹐¸﹕︰﹔！¡？¿﹖﹌﹏﹋＇´ˊˋ―﹫︳︴¯＿￣﹢﹦﹤‐­˜﹟﹩﹠﹪﹡﹨﹍﹉﹎﹊ˇ︵︶︷︸︹︿﹀︺︽︾ˉ﹁﹂﹃﹄︻︼（）";
        preg_match_all('/[' . $char . ']|[ ]{,}/u', $str, $arr);
        $mystr = preg_replace('/[' . $char . ']|[ ]{,}/u', "*#*", $str);
        $myarr = explode("*#*", $mystr);
        $len = 0;
        $s = '';
        for ($i = 0; $i < count($myarr); $i++) {
            $len += strlen($myarr[$i]);
            $s .= $myarr[$i] . $arr[0][$i];
            if ($len > $pgsize) {
                $pagestr[] = $s;
                $s = '';
                $len = 0;
            }
        }
        $pagestr[] = $s;
        return $pagestr;
    }
}

//判断密码复杂度
if (!function_exists('checkPassword')) {
    function checkPassword($str)
    {
        preg_match_all('%[0-9]%', $str, $number);
        preg_match_all('%[a-zA-z]%', $str, $string);
        $numberLength = count($number[0]);
        $stringLength = count($string[0]);
        if ($numberLength == strlen($str) || $stringLength == strlen($str)) {
            return false;
        }
        return true;
    }
}


//数组排序
if (!function_exists('array_sort')) {
    function array_sort($array, $keys, $sort = 'asc')
    {
        $newArr = $valArr = array();
        foreach ($array as $key => $value) {
            $valArr[$key] = $value[$keys];
        }
        ($sort == 'asc') ? asort($valArr) : arsort($valArr);
        reset($valArr);
        foreach ($valArr as $key => $value) {
            $newArr[$key] = $array[$key];
        }
        return $newArr;
    }
}

if (!function_exists('encrypt')) {
    function encrypt($str)
    {
        $pubkey = openssl_pkey_get_public(file_get_contents(ROOT_PATH . 'public' . DS . 'key/rsa_public_key.pem'));
        openssl_public_encrypt($str, $encrypt_data, $pubkey);
        $encrypt_data = base64_encode($encrypt_data);
        return $encrypt_data;
    }
}

//截取字符串
if (!function_exists('my_cutstr')) {
    function my_cutstr($string, $length, $dot = ' ...')
    {
        if (strlen($string) <= $length) {
            return $string;
        }

        $string = str_replace(array('&amp;', '&quot;', '&lt;', '&gt;', '&nbsp;'), array('&', '"', '<', '>', ''), $string);
        $strcut = '';
        if (strtolower(CHARSET) == 'utf-8' || true) {

            $n = $tn = $noc = 0;
            while ($n < strlen($string)) {

                $t = ord($string[$n]);
                if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                    $tn = 1;
                    $n++;
                    $noc++;
                } elseif (194 <= $t && $t <= 223) {
                    $tn = 2;
                    $n += 2;
                    $noc += 2;
                } elseif (224 <= $t && $t < 239) {
                    $tn = 3;
                    $n += 3;
                    $noc += 2;
                } elseif (240 <= $t && $t <= 247) {
                    $tn = 4;
                    $n += 4;
                    $noc += 2;
                } elseif (248 <= $t && $t <= 251) {
                    $tn = 5;
                    $n += 5;
                    $noc += 2;
                } elseif ($t == 252 || $t == 253) {
                    $tn = 6;
                    $n += 6;
                    $noc += 2;
                } else {
                    $n++;
                }

                if ($noc >= $length) {
                    break;
                }

            }
            if ($noc > $length) {
                $n -= $tn;
            }

            $strcut = substr($string, 0, $n);

        } else {
            for ($i = 0; $i < $length; $i++) {
                $strcut .= ord($string[$i]) > 127 ? $string[$i] . $string[++$i] : $string[$i];
            }
        }

        $strcut = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $strcut);

        return $strcut . $dot;
    }
}
if (!function_exists('filter_wx_nick_name')) {
    function filter_wx_nick_name($str)
    {
        if ($str) {
            $name = $str;
            $name = preg_replace('/\xEE[\x80-\xBF][\x80-\xBF]|\xEF[\x81-\x83][\x80-\xBF]/', '', $name);
            $name = preg_replace('/xE0[x80-x9F][x80-xBF]‘.‘|xED[xA0-xBF][x80-xBF]/S', '?', $name);
            $return = json_decode(preg_replace_callback("/(\\\ud[0-9a-f]{3})/", function ($r) {
                return '';
            }, json_encode($name)));


            if (!$return) {
                return $return;
            }
        } else {
            $return = '';
        }
        return $return;
    }
}


//简单的查询where条件转义
if (!function_exists('initWhere')) {
    function initWhere($where)
    {
        $mywhere = [];
        foreach ($where as $k => $v) {
            $op = '=';
            if (is_array($v)) {
                $op = $v[0];
                $v = $v[1];
            }
            $mywhere[] = [$k, $op, $v];
        }
        return $mywhere;
    }
}
//简单的查询where条件转义
if (!function_exists('autoImg')) {
    function autoImg($title)
    {
        $auto_img_path = "/attaches/image/auto/default.png";
        $image = \think\Image::open('.' . $auto_img_path);
        $cover = "/attaches/image/auto/" . md5($title . rand(1, 1000)) . ".png";
        $thumb = str_replace("/auto/","/auto/thumb_",$cover);
        $length = iconv_strlen($title, "UTF-8");
        $strLen = $length > 32 ? round($length / 3) : (($length > 16) ? round($length / 2) : $length);
        $title1 = mb_substr($title, 0, $strLen);
        $title2 = mb_substr($title, $strLen, $strLen);
        $title3 = mb_substr($title, 2 * $strLen, $strLen);
        if ($title3) {
            $image->text($title1, './attaches/image/auto/PingFangHK-Regular.otf', 24, '#ffffff', \think\Image::WATER_CENTER, [0, -52])->text($title2, './attaches/image/auto/PingFangHK-Regular.otf', 24, '#ffffff', \think\Image::WATER_CENTER)->text($title3, './attaches/image/auto/PingFangHK-Regular.otf', 24, '#ffffff', \think\Image::WATER_CENTER, [0, 52])->save("." . $cover);
        } elseif ($title2) {
            $image->text($title1, './attaches/image/auto/PingFangHK-Regular.otf', 24, '#ffffff', \think\Image::WATER_CENTER, [0, -26])->text($title2, './attaches/image/auto/PingFangHK-Regular.otf', 24, '#ffffff', \think\Image::WATER_CENTER, [0, 26])->save("." . $cover);
        } else {
            $image->text($title1, './attaches/image/auto/PingFangHK-Regular.otf', 24, '#ffffff', \think\Image::WATER_CENTER)->save("." . $cover);
        }
        //$imagethumb = \think\Image::open(ROOT_PATH . '/public/' .$cover);
        //$imagethumb->thumb(220, 165)->save(ROOT_PATH . '/public/' .$thumb);
        return $cover;
    }
}

if (!function_exists('jwd')) {
    function jwd($address, $is_amap = true)
    {
        $x = 0;
        $y = 0;
        $wd = urlencode($address);
        $burl = "http://api.map.baidu.com/geocoder?address=" . $wd . "&output=json";
        $gurl = 'https://restapi.amap.com/v3/geocode/geo?key=969be7016fc5b9f0d8313a402bc0fc9d&address=' . $wd;
        $mydata = myhttp($is_amap ? $gurl : $burl);
        if ($mydata) {
            $dataarr = json_decode($mydata, true);
            if ($is_amap) {
                if ($dataarr['info'] == 'OK' && isset($dataarr['geocodes'][0]['location'])) {
                    $x = explode(',',$dataarr['geocodes'][0]['location'])[0];
                    $y = explode(',',$dataarr['geocodes'][0]['location'])[1];
                }
            }else{
                if ($dataarr['status'] == 'OK' && isset($dataarr['result']['location']['lng']) && isset($dataarr['result']['location']['lat'])) {
                    $x = $dataarr['result']['location']['lng'] + (rand(1, 50) - 25) / 1000000;
                    $y = $dataarr['result']['location']['lat'] + (rand(1, 50) - 25) / 1000000;
                }
            }

        }
        return [$x, $y];
    }
}

if (!function_exists('sensitiveWords')) {
    function sensitiveWords($content)
    {
        $content = trim($content);
        $file = 'assets/sensitive-words-master/wordsadv.txt';
        $filegun = 'assets/sensitive-words-master/wordsgun.txt';
        $filepol = 'assets/sensitive-words-master/wordspol.txt';
        $filesex = 'assets/sensitive-words-master/wordssex.txt';
        $fileurl = 'assets/sensitive-words-master/wordsurl.txt';

        $sensitivegun_array = array_map('rtrim', file($filegun));
        $sensitivepol_array = array_map('rtrim', file($filepol));
        $sensitivesex_array = array_map('rtrim', file($filesex));
        $sensitiveurl_array = array_map('rtrim', file($fileurl));

        //上面这个就是词库文件路径
        $sensitive_array = array_map('rtrim', file($file));

        $allarray = array_merge($sensitive_array, $sensitivepol_array, $sensitivegun_array, $sensitivesex_array, $sensitiveurl_array);
//            $allarray = array_merge($sensitive_array);
        //dump($allarray);
        foreach ($allarray as $key => $val) {
            if ($val == '') {
                continue;
            }

            if (strpos($content, $val) !== false) {
                return $val;
                //return true;
            }
        }
        return false;
    }

    /**
     * 获取推流地址
     * 如果不传key和过期时间，将返回不含防盗链的url
     * @param domain 您用来推流的域名
     *        streamName 您用来区别不同推流地址的唯一流名称
     *        key 安全密钥
     *        time 过期时间 sample 2016-11-12 12:00:00
     * @return String url
     */
    if (!function_exists('getPushUrl')) {
        function getPushUrl($domain, $streamName, $key = null, $time = null)
        {
            if ($key && $time) {
                $txTime = strtoupper(base_convert(strtotime($time), 10, 16));
                //txSecret = MD5( KEY + streamName + txTime )
                $txSecret = md5($key . $streamName . $txTime);
                $ext_str = "?" . http_build_query(array(
                        "txSecret" => $txSecret,
                        "txTime" => $txTime
                    ));
            }
            return "rtmp://" . $domain . "/live/" . $streamName . (isset($ext_str) ? $ext_str : "");
        }
    }
    
    
    
    /**
     * 格式化时间
     * 
     */
    if (!function_exists('format_time_moment')) {
        function format_time_moment($time,$format='Y-m-d H:i:s'){
            $time_str = '-';
            if($time){
                $xc_time = time() - $time;
                if($xc_time <= 30){
                    $time_str = '刚刚';
                }elseif($xc_time > 3 && $xc_time < 3600){
                    $time_str = ceil($xc_time/60).'分钟前';
                }elseif ($xc_time >= 3600 && $xc_time < 86400){
                    $time_str = ceil($xc_time/3600).'小时前';
                }elseif($xc_time >= 86400 && $xc_time < 86400 * 3){
                    $time_str = ceil($xc_time/86400).'天前';
                }else {
                    $time_str = date($format,$time);
                }
            }
            return $time_str;
        }
    }
}
