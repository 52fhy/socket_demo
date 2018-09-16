<?php 

//安装SIGALRM信号
pcntl_signal(SIGALRM, function(){
    echo "SIGALRM\n";
    pcntl_alarm(5);  //再次调用，会重新发送一个SIGALRM信号
});
pcntl_alarm(5);//发送一个SIGALRM信号
echo "run...\n";
//死循环，否则进程会退出
while(1){
    pcntl_signal_dispatch();
    sleep(3);
}