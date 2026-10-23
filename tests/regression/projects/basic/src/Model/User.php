<?php
namespace App\Model;

enum Role: string { case Admin = "a"; case Guest = "g"; }

final class User
{
    public function __construct(public readonly string $login, private Role $role = Role::Guest) {}
    public function isAdmin(): bool { return $this->role === Role::Admin; }
}
