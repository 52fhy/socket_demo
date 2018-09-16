
<?php 

//配合pcntl_signal使用，简单的说，是为了让系统产生时间云，让信号捕捉函数能够捕捉到信号量
declare(ticks = 1);//#2

//安装SIGCHLD信号
pcntl_signal(SIGCHLD, function(){
    echo "SIGCHLD \r\n";
    pcntl_wait($status);
}); //#3

$pid = pcntl_fork();
if($pid == -1){
    exit("fork fail");
}elseif($pid){
    $id = getmypid();   
    echo "Parent process,pid {$id}, child pid {$pid}\n";   

    //先sleep一下，否则代码一直循环，信号处理不会处理
    while(1){sleep(3);} //#1
}else{
    $id = getmypid();   
    echo "Child process,pid {$id}\n";   
    sleep(2); 
    exit();
}
