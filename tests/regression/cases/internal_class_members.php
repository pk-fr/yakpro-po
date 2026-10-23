<?php
// protected members of internal classes must keep their names
class MyEx extends Exception { function __construct() { $this->message = 'custom'; $this->code = 42; } }
$e = new MyEx; echo $e->getMessage(), $e->getCode(), "\n";
class Heap extends SplHeap { protected function compare($a, $b): int { return $b <=> $a; } }
$h = new Heap; foreach ([3, 1, 2] as $v) $h->insert($v);
foreach ($h as $v) echo $v; echo "\n";
class Sev extends ErrorException { function sev() { return $this->severity; } } echo (new Sev("m", 0, 3))->sev(), "\n";
