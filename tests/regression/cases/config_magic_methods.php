<?php
// yakpro-args: --config-file conf/no_predefined.cnf
// magic methods must keep their names even when pre-defined classes are not ignored
class Box { public $v = 1; function __serialize(): array { return ["w" => 5]; } function __unserialize(array $d): void { $this->v = $d["w"]; } }
echo unserialize(serialize(new Box))->v, "\n";
