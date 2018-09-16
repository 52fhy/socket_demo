<?php 

$pid = pcntl_fork();

if($pid == -1){
    exit("error");
}elseif($pid){
    echo "I am parent.my pid:".getmypid();

    while(1){
        sleep(3);
    }
}else{
    echo "I am child.my pid:".getmypid();
    exit();
}