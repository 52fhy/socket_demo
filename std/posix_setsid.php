<?php

$pid = pcntl_fork();

if($pid == -1){
    exit("fork fail");
}elseif($pid>0){
    //父进程
    echo "fork success\n";
    exit();
}else{

    $sid = posix_setsid();
    if($sid == -1){
        echo "fork success\n";
        exit("could not detach from terminal");
    }

    //子进程
    for($i=0; $i<20;$i++){
        // echo 'loop' . $i . "\n";
        file_put_contents('demo.txt', $i . "--" . date("Y-m-d H:i:s", time()) . "\n", FILE_APPEND);
        sleep(1);
    }
}