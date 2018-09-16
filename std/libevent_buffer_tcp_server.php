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
stream_set_blocking($socket,0);
$id = (int)$socket;
$master[$id] = $socket;

echo "waiting client...\n";


function ev_accept($socket, $flag, $base){
    global $receive;
    global $master;
    global $buffers;

    $connection = stream_socket_accept($socket);
    stream_set_blocking($connection, 0);
    $id = (int)$connection;

    echo "new Client $id\n";

    //#1 下面改成了event_buffer事件，与event事件有些不同
    //event_buffer_new额外支持写、错误事件
    $buffer = event_buffer_new($connection, 'ev_read', 'ev_write', 'ev_error', $id);
    event_buffer_base_set($buffer, $base);
    //指定超时时间，单位秒
    event_buffer_timeout_set($buffer, 30, 30);
    //设置水位，参考：https://www.cnblogs.com/nengm1988/p/8203784.html
    event_buffer_watermark_set($buffer, EV_READ, 0, 0xffffff);
    //设置优先级
    event_buffer_priority_set($buffer, 10);
    //开启event_buffer
    event_buffer_enable($buffer, EV_READ | EV_PERSIST);

    $master[$id] = $connection;
    $receive[$id] = '';
    $buffers[$id] = $buffer;
}

//#2 read回调，由于使用了event_buffer,这里仅接受2个参数，分别是fd和arg
function ev_read($buffer, $id)
{
    // var_dump(func_get_args());
    global $receive;
    global $master;
    global $buffers;

    while( 1 ) {
        //#3 使用event_buffer_read，而不是fread
        $read = @event_buffer_read($buffer, 1024);
        if($read === '' || $read === false)
        {
            break;
        }
        $pos = strpos($read, "\n");
        if($pos === false)
        {
            $receive[$id] .= $read;
            echo "received:".$read.";not all package,continue recdiveing\n";
        }else{
            $receive[$id] .= trim(substr ($read,0,$pos+1));
            $read = substr($read,$pos+1);
            
            switch ( $receive[$id] ){
                case "quit":
                    echo "client close conn\n";
                    
                    unset($master[$id]);
                    unset($buffers[$id]);

                    // fclose($master[$id]);
                    // event_buffer_free($buffers[$id]);
                    break;
                default:
                    echo "all package:\n";
                    echo $receive[$id]."\n";
                    break;
            }
            $receive[$id]='';
        }
    }
}

function ev_write($buffer, $id)
{
    echo "$id -- " ."\n";
}

function ev_error($buffer, $error, $id)
{
    echo "ev_error - ".$error."\n";
}

$base = event_base_new();
$event = event_new();
event_set($event, $socket, EV_READ | EV_PERSIST, 'ev_accept', $base);
event_base_set($event, $base);
event_add($event);
echo  "start run...\n";
event_base_loop($base);
