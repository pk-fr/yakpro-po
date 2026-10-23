<?php
// yakpro-args: --no-obfuscate-namespace-name
namespace Lib { class Tool { function run() { return 'ran'; } } }
namespace App { use Lib\Tool; echo (new Tool)->run(), "\n"; }
