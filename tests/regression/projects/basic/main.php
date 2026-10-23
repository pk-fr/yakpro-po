<?php
// min-php: 8.1
// entry point of the multi-file project test
require_once __DIR__ . "/src/Model/User.php";
require_once __DIR__ . "/src/Service/helpers/fmt.php";
require_once __DIR__ . "/src/Service/Greeter.php";

use App\Service\Greeter;
use App\Model\User;

$g = new Greeter;
foreach ([new User("ann"), Greeter::admin("bob")] as $u) echo $g->greet($u), "\n";
