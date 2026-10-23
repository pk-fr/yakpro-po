<?php
namespace Lib\Util { class Str { static function up($s) { return strtoupper($s); } } class Num { static function two() { return 2; } } function helper() { return 'h'; } const VERSION = 'v1'; }
namespace App {
use Lib\Util\{Str, Num};
use function Lib\Util\helper;
use const Lib\Util\VERSION;
echo Str::up('a'), Num::two(), helper(), VERSION, "\n";
}
