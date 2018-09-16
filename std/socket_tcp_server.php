<?php 

//参数domain: AF_INET,AF_INET6,AF_UNIX
//参数type: SOCK_STREAM,SOCK_DGRAM
//参数protocol: SOL_TCP,SOL_UDP
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); 
if(!$socket) die("create server fail:".socket_strerror(socket_last_error())."\n");

//绑定
$ret = socket_bind($socket, "0.0.0.0", 9201);
if(!$ret) die("bind server fail:".socket_strerror(socket_last_error())."\n");

//监听
$ret = socket_listen($socket, 2);
if(!$ret) die("listen server fail:".socket_strerror(socket_last_error())."\n");
echo "waiting client...\n";

while(1){
    //阻塞等待客户端连接
    $conn = socket_accept($socket);
    if(!$conn){
        echo "accept server fail:".socket_strerror(socket_last_error())."\n";
        break;
    }

    echo "client connect succ.\n";

    parseRecv($conn);
}

function parseRecv($conn)
{
    //循环读取消息
    $recv = ''; //实际接收到的消息
    while(1){
        $buffer = socket_read($conn, 3); //每次读取3byte
        if($buffer === false || $buffer === ''){
            echo "client closed\n";
            socket_close($conn); //关闭本次连接
            break;
        }

        //解析单次消息，协议：换行符
        $pos = strpos($buffer, "\n");
        if($pos === false){ //消息未读取完毕，继续读取
            $recv .= $buffer;
        }else{ //消息读取完毕
            $recv .= trim(substr($buffer, 0, $pos+1)); //去除换行符及空格

            //客户端主动端口连接
            if($recv == 'quit'){
                echo "client closed\n";
                socket_close($conn); //关闭本次连接
                break;
            }

            echo "recv: $recv \n";
            socket_write($conn, "$recv \n"); //发送消息

            $recv = '';
        }
    }
}

socket_close($socket);