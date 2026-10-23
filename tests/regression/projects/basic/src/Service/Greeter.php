<?php
namespace App\Service;

use App\Model\{User, Role};
use function App\Service\helpers\fmt;

interface Greets { public function greet(User $u): string; }

class Greeter implements Greets
{
    public function greet(User $u): string { return fmt(name: $u->login, admin: $u->isAdmin()); }
    public static function admin(string $login): User { return new User($login, role: Role::Admin); }
}
