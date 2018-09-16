<?php

class Server{

    private $socket = null;

    public function __construct()
    {
        //建立链接
        $this->socket = stream_socket_server("tcp://0.0.0.0:9201", $errno, $errstr);
        if(!$this->socket) die("server start fail.".$errno.$errstr);
    }

    public function run()
    {
        //循环接受客户端连接
        while(1){
            echo "waiting client...\n";
            $conn = stream_socket_accept($this->socket, -1);
            if(!$conn) continue;//出错跳过

            //回调连接事件
            if(method_exists($this, 'onConnect')) call_user_func(array($this, 'onConnect'), $conn);

            //读取数据：需要循环读取，因为一次性不一定读取完成
            $buffer = '';
            $recv_msg = '';
            while(1){
                
                $buffer = fread($conn, 100);
                var_dump($buffer);
                echo "len:".strlen($buffer)."\n";
                if($buffer === false || $buffer === ''){
                    if(method_exists($this, 'onClose')) call_user_func(array($this, 'onClose'), $conn);
                    break;//客户端断开，退出循环
                }

                $pos = strpos($buffer, "\n");
                var_dump($pos);
                if($pos === false){
                    $recv_msg .= $buffer;
                }else{
                    $recv_msg .= trim(substr($buffer, 0, $pos+1));//截取实际长度字符串
                    $buffer = substr($buffer,$pos+1);

                    if(method_exists($this, 'onMessage')){
                        call_user_func(array($this, 'onMessage'), $conn, $recv_msg);
                    }

                    if($recv_msg == "quit"){
                        echo "client quit\n";
                        fclose($conn);
                        break;
                    }

                    $recv_msg='';
                }
            }
        }
    }

    public function onConnect($conn)
    {
        echo "onConnect\n";
        fwrite($conn, "onConnect"."\n");
    }

    public function onMessage($conn, $msg)
    {
        echo "recv:".$msg."\n";
        fwrite($conn, "recv:".$msg."\n");
    }

    public function onClose($conn)
    {
        echo "onClose\n";
        fwrite($conn, "onClose"."\n");
    }
}

$server = new Server();
$server->run();