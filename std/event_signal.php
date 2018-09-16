<?php
$base  = new  EventBase ();

//初始化信号事件
$e  =  Event :: signal ( $base , SIGUSR1, function( $signum , $arg ) use (& $e ) {
    echo  " Caught signal $signum\n" ;
    $e->delSignal(); //移除信号
},  '');

//安装信号
$e -> addSignal (); //sec

//发送信号
posix_kill(posix_getpid (),  SIGUSR1);

$base -> loop ();