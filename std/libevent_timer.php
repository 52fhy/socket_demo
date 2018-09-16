<?php 

$TIME_INTVAL = 1000000;

function ev_timer($fd, $events, $args){
    static $c;
    $c++;
    echo time()." hello\n";
    event_timer_add($args[1], $args[0]);

    if($c > 5){
        event_timer_del($args[1]);
    }
}

$base = event_base_new();
$event = event_new();
event_set($event, 0, EV_TIMEOUT, 'ev_timer', [$TIME_INTVAL, $event]);
event_base_set($event, $base);
event_add($event, $TIME_INTVAL);

event_base_loop($base);