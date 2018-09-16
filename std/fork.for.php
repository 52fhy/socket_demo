<?php 

$child_pids = [];

for($i=0;$i<3; $i++){
    $pid = pcntl_fork();
    if($pid == -1){
        exit("fork fail");
    }elseif($pid){
        $child_pids[$pid] = $pid;

        $id = getmypid();   
        echo time()." Parent process,pid {$id}, child pid {$pid}\n";   
    }else{
        $id = getmypid(); 
        $rand =   rand(1,3);
        echo time()." Child process,pid {$id},sleep $rand\n";   
        sleep($rand); //故意设置时间不一样
        exit();//子进程需要exit,防止子进程也进入for循环
    }
}

// while(count($child_pids)){
//     foreach ($child_pids as $key => $pid) {
//         // $res = pcntl_wait($status, WNOHANG);
//         $res = pcntl_waitpid($pid, $status, WNOHANG);
//         if ($res == -1 || $res > 0){
//             echo time()." Child process exit,pid {$pid}\n";   
//             unset($child_pids[$key]);
//         }else{
//             // echo time()." Wait End,pid {$pid}\n";   
//         }
//     }
    
// } 

while(count($child_pids)){
    $pid = pcntl_wait($status, WNOHANG);
    if ($pid == -1 || $pid > 0){
        if($pid > 0){
            echo time()." Child process exit,pid {$pid}\n";   
            unset($child_pids[$pid]);
        }else{
            echo time()." Wait break,pid {$pid}\n";   
            break;
        } 
    }else{
        // echo time()." Wait End,pid {$pid}\n";   
    }
    
} 
