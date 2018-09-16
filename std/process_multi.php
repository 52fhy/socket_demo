<?php 

//多进程管理

$pids = []; //存储子进程pid
$MAX_PROCESS = 3;//最大进程数

$pid = pcntl_fork();
if($pid <0){
    exit("fork fail\n");
}elseif($pid > 0){
    exit;//父进程退出
}else{
    // 从当前终端分离
    if (posix_setsid() == -1) {
        die("could not detach from terminal");
    }

    $id = getmypid();   
    echo time()." Master process, pid {$id}\n"; 

    for($i=0; $i<$MAX_PROCESS;$i++){
        start_worker_process();
    }

    //Master进程等待子进程退出，必须是死循环
    while(1){
        foreach($pids as $k=>$pid){
            if($pid){
                $res = pcntl_waitpid($pid, $status, WNOHANG);
                if ( $res == -1 || $res > 0 ){
                    echo time()." Worker process $pid exit, will start new... \n";
                    start_worker_process();
                    unset($pids[$k]);
                }
            }
        }
    }
}

/**
 * 创建worker进程
 */
function start_worker_process(){
    global $pids;
    $pid = pcntl_fork();
    if($pid <0){
        exit("fork fail\n");
    }elseif($pid > 0){
        $pids[] = $pid;
        // exit; //此处不可退出，否则Master进程就退出了
    }else{
        //实际代码
        $id = getmypid();   
        $rand = rand(1,3);
        echo time()." Worker process, pid {$id}. run $rand s\n"; 
        while(1){
            sleep($rand);
        }
    }
}