<?php 

use Swoole\Timer;

Timer::after(1000, function(){
    echo time(). " hello\n";
    Timer::tick(1000, function($timer_id, $params){
        static $c = 0;
        echo time(). " hello $params $c\n";

        $c++;
        if($c > 5){
            Timer::clear($timer_id);
        }
    }, 'this is param');
});