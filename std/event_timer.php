<?php
$base  = new  EventBase ();
$n  =  2 ; //sec

//初始化定时器
$e  =  Event :: timer ( $base , function( $n ) use (& $e ) {
    echo  " $n  seconds elapsed\n" ;
     $e -> delTimer ();
},  $n );

//添加定时器
$e -> addTimer ( $n ); //sec

$base -> loop ();