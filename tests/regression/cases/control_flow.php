<?php
// loop/if flattening into goto: nested break/continue levels, try/catch/finally, generators, references
function walk() {
    for ($i = 0; $i < 3; $i++) {
        $j = 0;
        while (true) {
            $j++;
            if ($j > 3) continue 2;
            if ($i == 1 && $j == 2) break 2;
            switch ($j) { case 1: continue 2; case 2: echo "[$i$j]"; break; default: echo "($i$j)"; }
        }
    }
    echo "\n";
}
walk();

function gen($n) { $i = 0; while ($i < $n) { try { if ($i % 2) throw new Exception("e$i"); yield $i; } catch (Exception $e) { yield $e->getMessage(); continue; } finally { $i++; } } }
foreach (gen(4) as $k => $v) echo "$k=$v "; echo "\n";

$a = [1, 2, 3]; foreach ($a as &$v) { $v *= 2; } unset($v); echo implode(',', $a), "\n";
$k = 0; do { if (++$k == 2) continue; echo $k; } while ($k < 4); echo "\n";
$x = 5;
if ($x > 10) echo "big"; elseif ($x > 3) echo "mid"; elseif ($x > 1) echo "small"; else echo "tiny"; echo "\n";
if (!($x === 5)) echo "no"; else echo "yes"; echo "\n";
$r = (function () { foreach ([1, 2, 3] as $v) { if ($v == 2) return $v * 10; } return 0; })(); echo $r, "\n";
$out = ''; for (;;) { $out .= 'x'; if (strlen($out) > 2) break; } echo $out, "\n";
$m = [[1, 2], [3, 4]]; foreach ($m as [$p, $q]) { if ($p == 3) { echo "found $q\n"; break; } }
$n = 0; while (true) { try { if (++$n > 2) break; continue; } finally { echo "f$n"; } } echo "\n";
