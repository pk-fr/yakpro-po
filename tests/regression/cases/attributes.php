<?php
// min-php: 8.0
#[Attribute]
class Route { public function __construct(public string $path) {} }
class Ctl { #[Route('/home')] public function home() {} }
$m = new ReflectionMethod(Ctl::class, (new ReflectionClass(Ctl::class))->getMethods()[0]->name);
echo $m->getAttributes()[0]->newInstance()->path, "\n";
