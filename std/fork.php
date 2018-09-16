<?php 


$pid = pcntl_fork();
if($pid == -1){
    exit("fork fail");
}elseif($pid){
    $id = getmypid();   
    echo "Parent process,pid {$id}, child pid {$pid}\n";   

    posix_kill($pid, SIGKILL);

    while(1){
        $res = pcntl_wait($status);
        //$res = pcntl_waitpid($pid, $status, WNOHANG);
        //-1代表error, 大于0代表子进程已退出,返回的是子进程的pid,非阻塞时0代表没取到退出子进程
        if ($res == -1 || $res > 0){

            if(!pcntl_wifexited($status)){
                //进程非正常退出
                echo "service exit unusally; pid is $pid\n";
            }else{
                //获取进程终端的退出状态码;
                $code = pcntl_wexitstatus($status);
                echo "service exit code: $code;pid is $pid \n";
            }
        
            if(pcntl_wifsignaled($status)){
                //不是通过接受信号中断
                echo "service term not by signal;pid is $pid \n";
            }else{
                $signal = pcntl_wtermsig($status);
                echo "service term by signal $signal;pid is $pid\n";
            }

            if(pcntl_wifstopped($status)){
                echo "service stop not unusally;pid is $pid \n";
            }else{
                $signal = pcntl_wstopsig($status);
                echo "service stop by signal $signal;pid is $pid\n";
            }

            break;
        }

        sleep(1);
    } 
}else{
    $id = getmypid();   
    echo "Child process,pid {$id}\n";   
    sleep(10); 
    exit();
}