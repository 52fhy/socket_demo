<?php
for($i =0; $i<1; $i++){
    $fp = stream_socket_client("tcp://127.0.0.1:9201", $errno, $errstr, 30);
    fwrite($fp,"GET / HTTP/1.1\r\nHost: www.qq.com\r\n\r\n");

    swoole_event_add($fp, function($fp) {
        echo $resp = fread($fp, 8192);
        //socket处理完成后，从epoll事件中移除socket
        swoole_event_del($fp);
        fclose($fp);
    });
    echo "Finish\n";  //swoole_event_add不会阻塞进程，这行代码会顺序执行
}

?>