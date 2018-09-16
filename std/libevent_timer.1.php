<?php 
$TIME_INTVAL = 1000000; //单位微秒

//回调函数
function ev_timer($fd, $events, $args){
    // var_dump(func_get_args()); //打印结果：参数fd为NULL，参数events固定为EV_TIMEOUT常量
    static $c;
    $c++;
    echo time()." hello\n";
    event_timer_add($args[1], $args[0]);

    if($c > 5){
        event_timer_del($args[1]); //删除定时器
    }
}

$base = event_base_new();
$ev_timer = event_timer_new();
event_timer_set($ev_timer, 'ev_timer', [$TIME_INTVAL, $ev_timer]);
event_base_set($ev_timer, $base);
event_timer_add($ev_timer, $TIME_INTVAL);

event_base_loop($base);