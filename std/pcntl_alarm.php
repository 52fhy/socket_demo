<?php 

declare(ticks = 1);

pcntl_signal(SIGALRM, function(){
    echo "SIGALRM\n";
    pcntl_alarm(5);  
});
pcntl_alarm(5);

echo "run...\n";

while(1){}