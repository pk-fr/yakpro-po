<?php
// for(;a,b;) : every condition expression is evaluated, the last one decides
$log = [];
for ($i = 0; $log[] = $i, $i < 3; $i++) { }
echo implode(',', $log), "\n";
