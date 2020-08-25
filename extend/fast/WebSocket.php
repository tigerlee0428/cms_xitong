<?php 
namespace fast;

/**
 * websocket 是由客户端发起的长连接：
 * 1、客户端携带 Sec-WebSocket-Key 向服务器发起请求
 * 2、服务器接收到 Sec-WebSocket-Key 后，通过加密算法生成 Sec-WebSocket-Accept 并返回客户端
 * 3、客户端验证 Sec-WebSocket-Accept，通过后双方建立长连接
 */
class WebSocket {
    var $master;
    var $sockets = []; // 所有连接进来的客户端
    var $users = []; // 所有用户

    function __construct($address, $port){
        // 创建 socket，写法基本固定
        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
        socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)    or die("socket_option() failed");
        socket_bind($this->master, $address, $port)                      or die("socket_bind() failed");
        socket_listen($this->master, 50)                                 or die("socket_listen() failed");
        
        $this->sockets[] = $this->master;
        $i = 0;
        // 循环监听 socket
        while(true)
        {
            $socketArr = $this->sockets;
            $write = NULL;
            $except = NULL;
            // 获取所有 socket 连接
            socket_select($socketArr, $write, $except, NULL);  //自动选择来消息的 socket 如果是握手 自动选择主机
            foreach ($socketArr as $socket)
            {
                
                // 判断新连接进来的客户端
                if ($socket == $this->master)
                {
                    // 接收新的客户端连接
                    $client = socket_accept($this->master);
                    // 小于 0 时连接失败
                    if ($client < 0){
                        continue;
                    } 
                    else
                    {
                        // 将新的连接放入连接池
                        $this->sockets[] = $client;
                        // 生成唯一 uuid 标记用户
                        $key = md5(\fast\Random::uuid());
                        $this->users[$key] = [
                            'socket'    =>  $client,  // 记录新连接进来 client 的 socket 信息
                            'hand'      =>  false       // 判断此个连接是否进行了握手
                        ];
                    }
                }
                else
                {
                    // 从已连接的 socket 接收数据
                    // $buffer 保存客户端提交过来的数据
                    // 2048 数据的最大长度
                    
                    $bytes = socket_recv($socket, $buffer, 2048, 0);
                    echo $bytes."\n";
                    $k = $this->search($socket);
                    if ($bytes < 9)
                    {
                        $this->disConnect($socket);
                    }
                    else
                    {
                        if (!$this->users[$k]['hand'])
                        {
                            $this->doHandShake($this->users[$k]['socket'], $buffer);
                        }
                        else
                        {
                            $buffer = $this->decode($buffer);
                            $result = $this->do($socket , $buffer);
                            $this->send($socket, json_encode($result));
                            //$this->disConnect($socket);
                        }
                    }
                }
            }
        }
    }
    private function do($socket,$buffer){
        if(strpos($buffer,"?") > 0){
            list($action,$params) = explode("?",$buffer);
            return $this->callFunction($action,$params);
        }
        $this->disConnect($socket);
    }
    private function callFunction($action,$params){
        list($class,$method) = explode("/",$action);
       
        $socket = "\\app\\api\\controller\\skt\\".$class;
        if(!class_exists($socket)){
            return skterr(200,'',100);
        }
        if(!method_exists($socket,$method)){
            return skterr(200,'',101);
        }
        $newClass = new $socket;
        parse_str($params,$arr);
        return $newClass->$method($arr);
    }
    private function search ($socket){
        foreach ($this->users as $k => $user)
        {
            if ($socket == $user['socket'])
            {
                return $k;
            }
        }
    }
    private function send($client, $msg)
    {
        $msg = $this->encode($msg);
        socket_write($client, $msg, strlen($msg));
    }
    /**
     * 关闭 socket 连接
     */
    private function disConnect($socket)
    {
        // 捕捉错误
        echo socket_strerror(socket_last_error());
        $index = array_search($socket, $this->sockets);
        socket_close($socket);
        if ($index >= 0)
        {
            array_splice($this->sockets, $index, 1); 
        }
    }
    /**
     * 握手，相应客户端的请求，返回 accept
     */
    private function doHandShake($socket, $buffer)
    {
        list($resource, $host, $origin, $key) = $this->getHeaders($buffer);
        $upgrade  = "HTTP/1.1 101 Switching Protocol\r\n" .
                    "Upgrade: websocket\r\n" .
                    "Connection: Upgrade\r\n" .
                    "Sec-WebSocket-Version: 13\r\n" . 
                    "Sec-WebSocket-Accept: " . $this->getAccept($key) . "\r\n\r\n";  //必须以两个回车结尾
        $sent = socket_write($socket, $upgrade, strlen($upgrade));
        $k = $this->search($socket);
        $this->users[$k]['hand'] = true;
        return true;
    }

    /**
     * 获取请求头的 key
     */
    private function getHeaders($req)
    {
        $r = $h = $o = $key = null;
        if (preg_match("/GET (.*) HTTP/"              ,$req,$match)) { $r = $match[1]; }
        if (preg_match("/Host: (.*)\r\n/"             ,$req,$match)) { $h = $match[1]; }
        if (preg_match("/Origin: (.*)\r\n/"           ,$req,$match)) { $o = $match[1]; }
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$req,$match)) { $key = $match[1]; }
        return array($r, $h, $o, $key);
    }

    /**
     * 生成服务器响应的 accept
     */
    private function getAccept($key)
    {
        //基于websocket version 13
        $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
        return $accept;
    }

    /**
     * 解析数据帧
     */
    private function decode($buffer)
    {
        $len = $masks = $data = $decoded = null;
        $len = ord($buffer[1]) & 127;
        if ($len === 126)
        {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } 
        else if ($len === 127) 
        {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } 
        else 
        {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) 
        {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    /**
     * 编码数据帧
     */
    private function encode($s)
    {
        $a = str_split($s, 125);
        if (count($a) == 1)
        {
            return "\x81" . chr(strlen($a[0])) . $a[0];
        }
        $ns = "";
        foreach ($a as $o)
        {
            $ns .= "\x81" . chr(strlen($o)) . $o;
        }
        return $ns;
    }
}