<?php
// min-php: 8.1
// broad PHP 8.0-8.2 syntax coverage that is expected to work
final class Counter { private static int $n = 0; public readonly array $items; function __construct(int ...$items) { $this->items = $items; } static function inc(): int { return ++static::$n; } }
function gen() { yield 1; yield from [2, 3]; return 4; }
$g = gen(); foreach ($g as $v) echo $v; echo $g->getReturn(), "\n";
$strlen = strlen(...); $inc = Counter::inc(...);
echo $strlen('abcd'), $inc(), $inc(), "\n";
$c = new Counter(...[5, 6]); echo array_sum($c->items), "\n";
echo match (true) { $c->items[0] > 9 => 'big', default => 'small' }, "\n";
$mult = 3; $f = fn($x) => fn($y) => $x * $y * $mult; echo $f(2)(5), "\n";
$name = 'World'; $arr = ['k' => 'v'];
echo <<<EOT
  Hello {$name} $arr[k] {$arr['k']}
    indented \$literal
  EOT . "\n";
echo <<<'NOW'
raw $name
NOW;
echo "\n";
try { throw new LogicException('le'); } catch (RuntimeException | LogicException $e) { echo get_class($e), $e->getMessage(), "\n"; } finally { echo "fin\n"; }
$x = null; echo $x ?? 'dflt', "\n"; $x ??= 'set'; echo $x, "\n";
['a' => $aa, 'b' => [$b1, $b2]] = ['a' => 1, 'b' => [2, 3]]; echo $aa + $b1 + $b2, "\n";
$s = static function () { return 'static'; }; echo $s(), "\n";
$fib = new Fiber(function (string $x): string { $y = Fiber::suspend($x . '1'); return $y . '3'; });
echo $fib->start('f'), $fib->resume('2') ?? '', $fib->getReturn(), "\n";
$o = new class(10) { private $v; function __construct(int $v) { $this->v = $v; } function v() { return $this->v; } }; echo $o->v(), "\n";
abstract class Shape { abstract function area(): float; function describe() { return sprintf('%.1f', $this->area()); } }
class Sq extends Shape { private $s; function __construct(float $s) { $this->s = $s; } function area(): float { return $this->s ** 2; } }
echo (new Sq(1.5))->describe(), "\n";
$i = 0; while (true) { if (++$i > 5) break; if ($i % 2) continue; echo $i; } echo "\n";
foreach ([1, 2, 3] as $k => $v) { switch ($v) { case 2: continue 2; default: echo $k; } } echo "\n";
for ($i = 0, $j = 10; $i < $j; $i += 3, $j -= 3) echo "$i:$j "; echo "\n";
