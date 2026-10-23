<?php
// min-php: 8.0
class Point {
    public function __construct(public int $x = 0, protected readonly int $y = 0, private ?string $label = null) {
        $this->label ??= "p$x";
    }
    public function describe(): string { return "$this->label($this->x,$this->y)"; }
}
echo (new Point(1, 2))->describe(), "\n", (new Point())->describe(), "\n";
