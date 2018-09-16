<?php
$base  = new  EventBase ();
$n  =  2 ; //sec

//初始化定时器
$event = new Event($base, null, Event::TIMEOUT, 'ev_timer',  $n );
$event->add($n);//sec

function ev_timer($fd, $what, $arg){
    echo  " $arg  seconds elapsed\n" ;
    global $event;
    $event->del();
}

$base->loop();