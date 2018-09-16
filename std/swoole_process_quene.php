<?php 

$workers = [];

for ($i=0; $i<3; $i++) {
    $process = new swoole_process(function(swoole_process $worker){
        //子进程逻辑
        sleep(1); //防止父进程还未往消息队列中加入内容直接退出
        while($cmd = $worker->pop()){
            // echo "recv from master: $cmd\n";

            ob_start();
            passthru($cmd);//执行外部程序并且显示未经处理的、原始输出，会直接打印输出。
            $return = ob_get_clean() ? : ' ';
            $return = "res: ".trim($return).". worker pid: ".$worker->pid."\n";
            
            echo $return;
            // sleep(1);
        }

        $worker->exit(0);
    }, false, false); //不创建管道

    $process->useQueue(1, 2 | swoole_process::IPC_NOWAIT); //使用消息队列
    $pid = $process->start();
    $workers[$pid] = $process;
}

//由于所有进程是共享使用一个消息队列，所以只需向一个子进程发送消息即可
$worker = current($workers);
for ($i=0; $i<3; $i++) {
    $worker->push('whoami'); //发送消息
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

