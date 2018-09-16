<?php 

$socket = stream_socket_server ("udp://0.0.0.0:9201", $errno, $errstr, STREAM_SERVER_BIND);
if (false === $socket ) {
    echo "$errstr($errno)\n";
    exit();
}

while(1){
    // $buffer = fread($socket, 1024);
    $buffer = stream_socket_recvfrom($socket, 1024, 0, $addr);
    echo $addr;

    //非正常关闭
    if(false === $buffer){
        echo "fread fail\n";
        break;
    }

    $msg = trim($buffer, "\n\r");

    //强制关闭
    if($msg == "quit"){
        echo "client close\n";
        fclose($socket);
        break;
    }

    echo "recv: $msg\n";
    // fwrite($socket, "recv: $msg\n");
    stream_socket_sendto($socket, "recv: $msg\n", 0, $addr);
}
