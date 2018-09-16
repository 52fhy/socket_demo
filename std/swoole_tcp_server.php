<?php 

use Swoole\Event;

daemonize();

$socket = stream_socket_server ("tcp://0.0.0.0:9201", $errno, $errstr);
if (false === $socket ) {
    echo "$errstr($errno)\n";
    exit();
}
if (!$socket) die($errstr."--".$errno);
// stream_set_blocking($socket,0); //可以去掉，没有涉及到read这个socket

echo "waiting client...\n";

function daemonize(){
    umask(0);
    $pid = pcntl_fork();
    if (-1 === $pid) {
        die('fork fail');
    } elseif ($pid > 0) {
        exit(0);
    }
    if (-1 === posix_setsid()) {
        die("setsid fail");
    }
    
    // Fork again avoid SVR4 system regain the control of terminal.
    $pid = pcntl_fork();
    if (-1 === $pid) {
        die("fork fail");
    } elseif (0 !== $pid) {
        exit(0);
    }
}

//accept事件回调函数，参数分别是$fd
function ev_accept($socket){
    global $master;

    $connection = stream_socket_accept($socket);

    //参数的设置将会影响到像 fgets() 和 fread() 这样的函数从资源流里读取数据。 
    //在非阻塞模式下，调用 fgets() 总是会立即返回；而在阻塞模式下，将会一直等到从资源流里面获取到数据才能返回。
    stream_set_blocking($connection, 0);//如果不设置，后续读取会阻塞
    $id = (int)$connection;

    echo "new Client $id\n";

    Event::add($connection, 'ev_read', null, SWOOLE_EVENT_READ); 
}

//read事件回调函数，参数是fd
function ev_read($buffer)
{
    $receive = '';

    //循环读取并解析客户端消息
    while( 1 ) {
        $read = @fread($buffer, 2);

        //客户端异常断开
        if($read === '' || $read === false){
            break;
        }

        $pos = strpos($read, "\n");
        if($pos === false)
        {
            $receive .= $read;
            // echo "received:".$read.";not all package,continue recdiveing\n";
        }else{
            $receive .= trim(substr ($read,0,$pos+1));
            $read = substr($read,$pos+1);
            
            switch ( $receive ){
                case "quit":
                    echo "client close conn\n";
                    
                    //关闭客户端连接
                    Event::del($buffer);//删除事件
                    fclose($buffer);//断开客户端连接
                    break;
                default:
                    // echo "all package:\n";
                    echo $receive."\n";
                    break;
            }
            $receive='';
        }
    }
}

Event::add($socket, 'ev_accept', null, SWOOLE_EVENT_READ); 
echo  "start run...\n";

//进入事件循环
Event::wait(); //

//下面这句不会被执行
echo "This code will not be executed.\n";
