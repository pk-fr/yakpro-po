<?php
trait Hello { public function say() { return 'Hello'; } }
trait World { public function say() { return 'World'; } }
class Greeter { use Hello, World { Hello::say insteadof World; World::say as protected sayWorld; Hello::say as shout; } function both() { return $this->say() . $this->sayWorld() . $this->shout(); } }
echo (new Greeter)->both(), "\n";
