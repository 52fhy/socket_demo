<?php 

$socket = stream_socket_server ("tcp://0.0.0.0:9201", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
if (false === $socket ) {
    echo "$errstr($errno)\n";
    exit();
}

while(1){
    echo "waiting client...\n";

    $conn = stream_socket_accept($socket, -1);
    if (false === $conn ) {
        exit("accept error\n");
    }

    echo "new Client! fd:".intval($conn)."\n";

    while(1){
        $buffer = fread($conn, 1024);

        //非正常关闭
        if(false === $buffer){
            echo "fread fail\n";
            break;
        }

        $msg = trim($buffer, "\n\r");

        //强制关闭
        if($msg == "quit"){
            echo "client close\n";
            fclose($conn);
            break;
        }

        echo "recv: $msg\n";
        fwrite($conn, "recv: $msg\n");
    }
}

fclose($socket);
