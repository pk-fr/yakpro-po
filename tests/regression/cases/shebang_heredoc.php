#!/usr/bin/env php
<?php
// multi-line literals must survive the shebang handling
$s = "line1
line2";
echo strlen($s), "\n";
