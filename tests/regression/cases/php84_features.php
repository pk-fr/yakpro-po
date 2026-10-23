<?php
// min-php: 8.4
class User {
    public string $full { get => $this->first . ' ' . $this->last; }
    public string $first { set(string $value) { $this->first = ucfirst($value); } }
    public private(set) string $last = '';
    public function __construct(string $first, string $last) { $this->first = $first; $this->last = $last; }
}
$u = new User('ada', 'Lovelace');
echo $u->full, "\n";
echo new ArrayObject([1, 2])->count(), "\n";
echo array_find([1, 2, 3], fn($v) => $v > 1), "\n";
