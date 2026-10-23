<?php
// min-php: 8.3
interface HasVersion { const string VERSION = '1'; }
class Impl implements HasVersion { const string NAME = 'impl'; }
echo Impl::VERSION, Impl::NAME, json_validate('{}') ? 'y' : 'n', "\n";
$o = new readonly class { public function __construct(public int $a = 1) {} }; echo $o->a, "\n";
