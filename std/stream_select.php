<?php 

$socket = stream_socket_server ("tcp://0.0.0.0:9201", $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
if (false === $socket ) {
    echo "$errstr($errno)\n";
    exit();
}

$clients = [$socket];
echo "waiting client...\n";

while(1){
    $read = $clients;
    $ret = stream_select($read, $w, $e, 0);
    if(false === $ret){
        break;
    }

    foreach($read as $client){
        if($client == $socket){ //新客户端
            $conn = stream_socket_accept($socket, -1);
            if (false === $socket ) {
                exit("accept error\n");
            }
        
            echo "new Client! fd:".intval($conn)."\n";

            $clients[] = $conn;
        }else{
            $buffer = fread($client, 1024);

            //非正常关闭
            if(false === $buffer || $buffer === ''){
                echo "fread fail\n";
                $key = array_search($client, $clients);
                unset($clients[$key]);
                break;
            }

            $msg = trim($buffer, "\n\r");

            //强制关闭
            if($msg == "quit"){
                echo "client close\n";
                $key = array_search($client, $clients);
                unset($clients[$key]);
                fclose($client);
                break;
            }

            echo "recv: $msg\n";
            fwrite($conn, "recv: $msg\n");
        }
    }
}

fclose($socket);
