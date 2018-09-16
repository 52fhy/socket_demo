<?php

$socket = stream_socket_client("tcp://127.0.0.1:9201", $errno, $erstr);
if(!$socket) die("err");

$clients = [$socket, STDIN];

fwrite(STDOUT, "ENTER MSG:");

while(1){
    $read = $clients;
    $ret = stream_select($read, $w, $e, 0);
    if(false === $ret){
        exit("stream_select err\n");
    }

    foreach($read as $client){
        if($client == $socket){
            $msg = stream_socket_recvfrom($socket, 1024);
            echo "\nRecv: {$msg}\n";
            fwrite(STDOUT, "ENTER MSG:");
        }elseif($client == STDIN){
            $msg = trim(fgets(STDIN));
            if($msg == 'quit'){ //必须trim此处才会相等
                exit("quit\n");
            }
            
            fwrite($socket, $msg);
            fwrite(STDOUT, "ENTER MSG:");
        }
    }
}
