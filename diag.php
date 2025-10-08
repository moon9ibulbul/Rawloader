<?php
header('Content-Type: text/plain');
echo "disable_functions = ".ini_get('disable_functions')."\n";
echo "exec: ".(function_exists('exec')?'ON':'OFF')."\n";
echo "popen: ".(function_exists('popen')?'ON':'OFF')."\n";
echo "proc_open: ".(function_exists('proc_open')?'ON':'OFF')."\n";
