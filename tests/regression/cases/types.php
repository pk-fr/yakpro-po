<?php
// min-php: 8.2
interface Named {}
class Foo implements Named { }
class Bar { }
class Holder {
    private Foo|Bar $item;
    public ?Foo $maybe = null;
    public function set(Foo|Bar $i): static { $this->item = $i; return $this; }
    public function get(): Foo|Bar { return $this->item; }
    public function both(Foo&Named $x): (Foo&Named)|null { return $x; }
}
$h = new Holder;
$f = function (Foo $x): Foo { return $x; };
$g = fn(Bar $b): Bar => $b;
echo get_class($h->set($f(new Foo))->get()) === get_class(new Foo) ? "ok" : "ko", "\n";
echo get_class($h->set($g(new Bar))->get()) === get_class(new Bar) ? "ok" : "ko", "\n";
echo $h->both(new Foo) instanceof Named ? "ok" : "ko", "\n";
$h->maybe = new Foo; echo "ok\n";
