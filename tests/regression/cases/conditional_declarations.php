<?php
// declarations nested in if/else must stay conditional
if (PHP_VERSION_ID > 0) { function pick() { return "a"; } } else { function pick() { return "b"; } }
if (!class_exists('Polyfill')) { class Polyfill { function v() { return 1; } } }
if (!interface_exists('Shape')) { interface Shape {} }
echo pick(), (new Polyfill)->v(), "\n";
