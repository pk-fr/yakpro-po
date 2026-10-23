<?php
namespace App\Service\helpers;

function fmt(string $name, bool $admin = false): string { return sprintf("%s%s", $name, $admin ? " (admin)" : ""); }
