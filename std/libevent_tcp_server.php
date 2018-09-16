<?php 

$receive = [];
$master = [];
$buffers = [];

$socket = stream_socket_server ("tcp://0.0.0.0:9201", $errno, $errstr);
if (false === $socket ) {
    echo "$errstr($errno)\n";
    exit();
}
if (!$socket) die($errstr."--".$errno);
// stream_set_blocking($socket,0); //可选
$id = (int)$socket;
$master[$id] = $socket;

echo "waiting client...\n";


//accept事件回调函数，参数分别是$fd, $events, $arg。
//也就是 event_set 函数的$fd, $events, $arg参数。
function ev_accept($socket, $flag, $base){
    global $receive;
    global $master;
    global $buffers;

    $connection = stream_socket_accept($socket);
    stream_set_blocking($connection, 0);
    $id = (int)$connection;

    echo "new Client $id\n";

    $event = event_new();
    event_set($event, $connection, EV_READ | EV_PERSIST, 'ev_read', $id);
    event_base_set($event, $base);
    event_add($event);

    $master[$id] = $connection; 
    $receive[$id] = ''; 
    $buffers[$id] = $event; //如果去掉该行，客户端强制断开再连接，服务端无法正常收到消息
}

//read事件回调函数
function ev_read($buffer, $flag, $id)
{
    
    global $receive;
    global $master;
    global $buffers;

    //该方法里的$buffer和$master[$id]指向相同的内容
    // var_dump(func_get_args(), $master[$id] );

    //循环读取并解析客户端消息
    while( 1 ) {
        $read = @fread($buffer, 1024);

        //客户端异常断开
        if($read === '' || $read === false){
            break;
        }

        $pos = strpos($read, "\n");
        if($pos === false)
        {
            $receive[$id] .= $read;
            // echo "received:".$read.";not all package,continue recdiveing\n";
        }else{
            $receive[$id] .= trim(substr ($read,0,$pos+1));
            $read = substr($read,$pos+1);
            
            switch ( $receive[$id] ){
                case "quit":
                    echo "client close conn\n";
                    
                    // fclose($master[$id]); //断开客户端连接
                    // event_del($buffers[$id]); //删除事件

                    //下面的写法与上面调用函数效果一样，都是关闭客户端连接
                    unset($master[$id]);
                    unset($buffers[$id]);
                    break;
                default:
                    // echo "all package:\n";
                    echo $receive[$id]."\n";
                    break;
            }
            $receive[$id]='';
        }
    }
}

//创建全局event base
$base = event_base_new();
//创建 event
$event = event_new(); 
//设置 event：其中$events设置为EV_READ | EV_PERSIST ；回调事件为ev_accept，参数 $base
//EV_PERSIST可以让注册的事件在执行完后不被删除,直到调用event_del()删除.
event_set($event, $socket, EV_READ | EV_PERSIST, 'ev_accept', $base); 
// 全局event base添加 当前event
event_base_set($event, $base);
event_add($event);
echo  "start run...\n";

//进入事件循环
event_base_loop($base);

//下面这句不会被执行
echo "This code will not be executed.\n";
