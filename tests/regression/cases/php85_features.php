<?php
// min-php: 8.5
final class Temp { public function __construct(final public readonly float $celsius = 0.0) {} public function f(): float { return $this->celsius * 9 / 5 + 32; } }
function shout(string $s): string { return strtoupper($s) . '!'; }
echo 'abc' |> strrev(...) |> shout(...), "\n";
echo (new Temp(100))->f(), "\n";
echo array_first([3, 4]), array_last([3, 4]), "\n";
