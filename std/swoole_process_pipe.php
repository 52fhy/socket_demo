<?php 

$workers = [];

for ($i=0; $i<3; $i++) {
    $process = new swoole_process(function(swoole_process $worker){
        //子进程逻辑
        $cmd = $worker->read();

        ob_start();
        passthru($cmd);//执行外部程序并且显示未经处理的、原始输出，会直接打印输出。
        $return = ob_get_clean() ? : ' ';
        $return = trim($return).". worker pid:".$worker->pid."\n";
        
        // $worker->write($return);//写入数据到管道
        echo $return;//写入数据到管道。注意：子进程里echo也是写入到管道
    }, true); //第二个参数为true，启用管道通信
    $pid = $process->start();
    $workers[$pid] = $process;  
}

foreach($workers as $pid=>$worker){
    $worker->write('whoami'); //通过管道发数据到子进程。管道是单向的：发出的数据必须由另一端读取。不能读取自己发出去的
    $recv = $worker->read();//同步阻塞读取管道数据
    echo "recv result: $recv";
}

//回收子进程
while(count($workers)){
    foreach($workers as $pid=>$worker){
        $ret = swoole_process::wait();
        if($ret){
            echo "worker exit: $pid\n";
            unset($workers[$pid]);
        }
    }
}

