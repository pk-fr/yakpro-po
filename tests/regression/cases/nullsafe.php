<?php
// min-php: 8.0
class Node { public ?Node $next = null; public $val = 7; function nextVal() { return $this->next?->val; } function me() { return $this; } }
$n = new Node; $n->next = new Node;
echo $n->nextVal(), "|", var_export($n->next->nextVal(), true), "|", $n?->me()?->val, "\n";
