<?php 

function ev_timer(){
    static $i = 0;
    echo "#{$i}\talarm\n";
    $i++;
    if ($i > 5) {
        //清除定时器
        swoole_process::alarm(-1);

        //退出进程
        swoole_process::kill(getmypid());
        
    }
}

//安装信号
swoole_process::signal(SIGALRM, 'ev_timer');

//触发定时器信号
swoole_process::alarm(100 * 1000);//100ms

echo getmypid()."\n"; //该句会顺序执行，后续无需使用while循环防止进程直接退出
