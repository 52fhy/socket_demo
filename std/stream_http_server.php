<?php

class StreamHttpServer{
    
    private $socket;
    private $clients = [];
    private $pids = [];
    public $worker_num = 3;
    public $daemon = 0;

    private function forkWorker()
    {
        $pid = pcntl_fork();
        if($pid === -1){
            echo "foek fail\n";
            exit(0);
        }elseif($pid > 0){
            $this->pids[$pid] = $pid;
        }else{
            //子进程
            if(isset($this->OnWorkerStart)){
                call_user_func($this->OnWorkerStart, $this->socket, getmypid());
            }
            
            while(1){
                $read = $this->clients;
                $write = $err = null;
                $ret = @stream_select($read, $write, $err, 2);
                if(false === $ret){
                    echo "select fail\n";
                    break;
                }
    
                foreach ($read as $k => $fd) {
                    if($fd == $this->socket){
                        $conn = stream_socket_accept($this->socket, -1);
                        if (false === $conn ) {
                            exit("accept error\n");
                        }
                        stream_set_blocking($conn, 0);
            
                        $this->clients[] = $conn;
    
                        if(isset($this->OnConn)){
                            call_user_func($this->OnConn, $this->socket, $conn);
                        }
                    }else{
    
                        $buffer = fread($fd, 65535);
                    
                        //非正常关闭
                        if(false === $buffer || '' === $buffer){
                            echo "fread fail\n";
                            $key = array_search($fd, $this->clients);
                            unset($this->clients[$key]);
                            break;
                        }
                
                        $msg = trim($buffer, "\n\r");
    
                        if(isset($this->OnMessage)){
                            call_user_func($this->OnMessage, $this->socket, $fd, $msg);
                        }
    
                        $decode = $this->parse_http($msg);
    
                        if(isset($this->OnRequest)){
                            call_user_func($this->OnRequest, $this->socket, $fd, $decode);
                        }
                    }
                }
            }
        }
    }

    private function waitWorker()
    {
        while(count($this->pids)){
            $ret = pcntl_wait($status, WNOHANG);
            echo $ret."\n";
            if($ret > 0){
                echo "worker $ret exit, will fork new...";
                unset($this->pids[$ret]);
                $this->pids[$ret] = $this->forkWorker();
            }

            sleep(1);
        }
    }

    private function daemonize()
    {
        if(!$this->daemon) return;

        umask(0);
        $pid = pcntl_fork();
        if (-1 === $pid) {
            throw new Exception('fork fail');
        } elseif ($pid > 0) {
            exit(0);
        }
        if (-1 === posix_setsid()) {
            exit("setsid fail");
        }

        //TODO: ret重定向
    }

    public function run()
    {
        $this->daemonize();

        $this->socket = stream_socket_server("tcp://0.0.0.0:9201", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);

        if (false === $this->socket ) {
            echo "$errstr($errno)\n";
            exit();
        }

        $this->clients[] = $this->socket;

        if(isset($this->OnMaster)){
            call_user_func($this->OnMaster, $this->socket);
        }

        for($i=0; $i<$this->worker_num; $i++){
            $this->forkWorker();
        }

        $this->waitWorker();
    }

    public function send($fd, $content= '')
    {
        $header = "HTTP/1.1 200 OK\r\n";
        $header .= "Content-Type: text/html;charset=utf-8\r\n";
        $header .= "Server: StreamHttpServer\r\nContent-Length: " . strlen($content) . "\r\n\r\n";

        $ret = fwrite($fd, $header . $content);
    }

    /**
     * 解析http协议
     */
    private function parse_http($http)
    {
            // 初始化
            $_POST = $_GET = $_COOKIE = $_REQUEST = $_SESSION = $_FILES =  array();
            $GLOBALS['HTTP_RAW_POST_DATA'] = '';
            // 需要设置的变量名
            $_SERVER = array (
                  'QUERY_STRING' => '',
                  'REQUEST_METHOD' => '',
                  'REQUEST_URI' => '',
                  'SERVER_PROTOCOL' => '',
                  'SERVER_SOFTWARE' => '',
                  'SERVER_NAME' => '',
                  'HTTP_HOST' => '',
                  'HTTP_USER_AGENT' => '',
                  'HTTP_ACCEPT' => '',
                  'HTTP_ACCEPT_LANGUAGE' => '',
                  'HTTP_ACCEPT_ENCODING' => '',
                  'HTTP_COOKIE' => '',
                  'HTTP_CONNECTION' => '',
                  'REMOTE_ADDR' => '',
                  'REMOTE_PORT' => '0',
            );
             
            // 将header分割成数组
            $exp_res = explode("\r\n\r\n", $http, 2);
            $http_header = $exp_res[0];
            $http_body = isset($exp_res[0]) ? $exp_res[0] : '';

            $header_data = explode("\r\n", $http_header);
             
            $exp_res = explode(' ', $header_data[0]);
            $_SERVER['REQUEST_METHOD'] = isset($exp_res[0]) ? $exp_res[0] : '';
            $_SERVER['REQUEST_URI'] = isset($exp_res[1]) ? $exp_res[1] : '';
            $_SERVER['SERVER_PROTOCOL'] = isset($exp_res[2]) ? $exp_res[2] : '';
             
            unset($header_data[0]);
            foreach($header_data as $content)
            {
                // \r\n\r\n
                if(empty($content))
                {
                    continue;
                }
                list($key, $value) = explode(':', $content, 2);
                $key = strtolower($key);
                $value = trim($value);
                switch($key)
                {
                    // HTTP_HOST
                    case 'host':
                        $_SERVER['HTTP_HOST'] = $value;
                        $tmp = explode(':', $value);
                        $_SERVER['SERVER_NAME'] = $tmp[0];
                        if(isset($tmp[1]))
                        {
                            $_SERVER['SERVER_PORT'] = $tmp[1];
                        }
                        break;
                    // cookie
                    case 'cookie':
                        $_SERVER['HTTP_COOKIE'] = $value;
                        parse_str(str_replace('; ', '&', $_SERVER['HTTP_COOKIE']), $_COOKIE);
                        break;
                    // user-agent
                    case 'user-agent':
                        $_SERVER['HTTP_USER_AGENT'] = $value;
                        break;
                    // accept
                    case 'accept':
                        $_SERVER['HTTP_ACCEPT'] = $value;
                        break;
                    // accept-language
                    case 'accept-language':
                        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $value;
                        break;
                    // accept-encoding
                    case 'accept-encoding':
                        $_SERVER['HTTP_ACCEPT_ENCODING'] = $value;
                        break;
                    // connection
                    case 'connection':
                        $_SERVER['HTTP_CONNECTION'] = $value;
                        break;
                    case 'referer':
                        $_SERVER['HTTP_REFERER'] = $value;
                        break;
                    case 'if-modified-since':
                        $_SERVER['HTTP_IF_MODIFIED_SINCE'] = $value;
                        break;
                    case 'if-none-match':
                        $_SERVER['HTTP_IF_NONE_MATCH'] = $value;
                        break;
                    case 'content-type':
                        if(!preg_match('/boundary="?(\S+)"?/', $value, $match))
                        {
                            $_SERVER['CONTENT_TYPE'] = $value;
                        }
                        else
                        {
                            $_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
                            $http_post_boundary = '--'.$match[1];
                        }
                        break;
                }
            }
             
            // 需要解析$_POST
            if($_SERVER['REQUEST_METHOD'] === 'POST')
            {
                if(isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'multipart/form-data')
                {
                    //TODO
                }
                else
                {
                    parse_str($http_body, $_POST);
                    // $GLOBALS['HTTP_RAW_POST_DATA']
                    $GLOBALS['HTTP_RAW_POST_DATA'] = $http_body;
                }
            }
             
            // QUERY_STRING
            $_SERVER['QUERY_STRING'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
            if($_SERVER['QUERY_STRING'])
            {
                // $GET
                parse_str($_SERVER['QUERY_STRING'], $_GET);
            }
            else
            {
                $_SERVER['QUERY_STRING'] = '';
            }
             
            // REQUEST
            $_REQUEST = array_merge($_GET, $_POST);

            return array('get'=>$_GET, 'post'=>$_POST, 'cookie'=>$_COOKIE, 'server'=>$_SERVER, 'files'=>$_FILES);
     }
}

$http = new StreamHttpServer();
$http->worker_num = 2; //worker进程数
$http->daemon = 1;//是否启用守护进程

$http->OnWorkerStart = function($server, $pid){
    echo "OnWorkerStart $pid!\n";
};

$http->OnConn = function($server, $conn){
    echo "new Client! fd:".intval($conn)."\n";
};

$http->OnMessage = function($server, $conn, $msg){
    // echo "recv: $msg\n";
};

$http->OnRequest = function($server, $conn, $response) use($http){
    
    $http->send($conn, $_SERVER['REQUEST_URI']);
};

$http->run();