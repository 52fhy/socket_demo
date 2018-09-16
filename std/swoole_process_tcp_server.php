<?php 

class TcpServer{
    const MAX_PROCESS = 3;//最大进程数
    private $pids = []; //存储子进程pid
    private $socket;
    private $mpid;

    public function run(){
        $process = new swoole_process(function(){
            $this->mpid = $id = getmypid();   
            echo time()." Master process, pid {$id}\n"; 

            //创建tcp server
            $this->socket = stream_socket_server("tcp://0.0.0.0:9201", $errno, $errstr);
            if(!$this->socket) exit("start server err: $errstr --- $errno");

            for($i=0; $i<self::MAX_PROCESS;$i++){
                $this->start_worker_process();
            }
    
            echo "waiting client...\n";
    
            //Master进程等待子进程退出，必须是死循环
            while(1){
                foreach($this->pids as $k=>$pid){
                    if($pid){
                        $res = swoole_process::wait();
                        if ( $res ){
                            echo time()." Worker process $pid exit, will start new... \n";
                            $this->start_worker_process();
                            unset($this->pids[$k]);
                        }
                    }
                }
                sleep(1);//让出1s时间给CPU
            }
        }, false, false); //不启用管道通信
        swoole_process::daemon(); //守护进程
        $process->start();//注意：start之后的变量子进程里面是获取不到的
    }

    /**
     * 创建worker进程，接受客户端连接
     */
    private function start_worker_process(){
        $process = new swoole_process(function(swoole_process $worker){
            $this->acceptClient($worker);
        }, false, false);
        $pid = $process->start();
        $this->pids[] = $pid;
    }

    private function acceptClient(&$worker)
    {
        //子进程一直等待客户端连接，不能退出
        while(1){
            
            $conn = stream_socket_accept($this->socket, -1);
            if($this->onConnect) call_user_func($this->onConnect, $conn); //回调连接事件

            //开始循环读取消息
            $recv = ''; //实际收到消息
            $buffer = ''; //缓冲消息
            while(1){
                $this->checkMpid($worker);
                
                $buffer = fread($conn, 20);

                //没有收到正常消息
                if($buffer === false || $buffer === ''){
                    if($this->onClose) call_user_func($this->onClose, $conn); //回调断开连接事件
                    break;//结束读取消息，等待下一个客户端连接
                }

                $pos = strpos($buffer, "\n"); //消息结束符
                if($pos === false){
                    $recv .= $buffer;                            
                }else{
                    $recv .= trim(substr($buffer, 0, $pos+1));

                    if($this->onMessage) call_user_func($this->onMessage, $conn, $recv); //回调收到消息事件

                    //客户端强制关闭连接
                    if($recv == "quit"){
                        echo "client close conn\n";
                        fclose($conn);
                        break;
                    }

                    $recv = ''; //清空消息，准备下一次接收
                }
            }
        }
    }

    //检查主进程是否存在，若不存在子进程在干完手头活后退出
    public function checkMpid(&$worker){
        if(!swoole_process::kill($this->mpid,0)){
            $worker->exit();
            // 这句提示,实际是看不到的.需要写到日志中
            echo "Master process exited, I [{$worker['pid']}] also quit\n";
        }
    }

    function __destruct() {
        @fclose($this->socket);
    }
}

$server =  new TcpServer();

$server->onConnect = function($conn){
    echo "onConnect -- accepted " . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"conn success\n");
};

$server->onMessage = function($conn,$msg){
    echo "onMessage --" . $msg . "\n";
    fwrite($conn,"received ".$msg."\n");
};

$server->onClose = function($conn){
    echo "onClose --" . stream_socket_get_name($conn,true) . "\n";
    fwrite($conn,"onClose "."\n");
};

$server->run();