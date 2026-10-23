<?php
// min-php: 8.0
function make(string $first, string $second = 'b', string ...$rest) { return "$first-$second-" . implode('', $rest); }
class K { function __construct(public int $size = 1) {} static function create(int $count) { return new self(size: $count); } function m(int $value) { return $value * 2; } }
echo make(second: 'x', first: 'y'), "\n";
echo str_pad(string: 'a', length: 3, pad_string: '*'), "\n";
echo K::create(count: 5)->size, (new K)->m(value: 4), "\n";
echo json_encode(value: [1]), "\n";
