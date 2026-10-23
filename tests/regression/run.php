<?php
//========================================================================
// Differential regression tests for yakpro-po.
//
// Runs original and obfuscated code in separate processes (same PHP binary
// as this runner) and compares exit code and normalized output.
//      cases/*.php         single file, obfuscated in file mode
//      projects/<name>/    directory, obfuscated in directory mode, entry point main.php
// Usage:
//      php tests/regression/run.php [filter] [--runs=N]
// Case header directives (optional, anywhere in the file):
//      // min-php: 8.4                      skip on older PHP
//      // yakpro-args: --no-shuffle-statements --config-file conf/x.cnf
//========================================================================

$root       = dirname(__DIR__, 2);
$filter     = '';
$runs       = 3;                                            // statement shuffling is random: obfuscate each case several times
foreach (array_slice($argv, 1) as $arg)
{
    if (preg_match('/^--runs=(\d+)$/', $arg, $m)) $runs = (int) $m[1];
    else                                          $filter = $arg;
}

function run_process(array $cmd, $timeout = 30)
{
    $proc = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($proc)) return [-1, '', 'proc_open failed'];
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    $out = $err = '';
    $start = microtime(true);
    for (;;)
    {
        $out   .= stream_get_contents($pipes[1]);
        $err   .= stream_get_contents($pipes[2]);
        $status = proc_get_status($proc);
        if (!$status['running']) break;
        if (microtime(true) - $start > $timeout) { proc_terminate($proc); return [-2, $out, $err . "\nTIMEOUT"]; }
        usleep(10000);
    }
    $out .= stream_get_contents($pipes[1]);
    $err .= stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    proc_close($proc);
    return [$status['exitcode'], $out, $err];
}

function normalize($s, $dir)
{
    $s = str_replace([$dir, str_replace('\\', '/', $dir)], 'DIR', $s);
    $s = preg_replace('/(on line |:)\d+/', '$1N', $s);
    return str_replace("\r\n", "\n", $s);
}

// returns null when the obfuscated code behaves like the original, an error message otherwise
function compare($original, array $obfuscate_cmd, $obfuscated, $runs)
{
    global $php, $ini;
    [$exp_code, $exp_out] = run_process(array_merge([$php], $ini, [$original]));
    $expected = normalize($exp_out, dirname($original));
    for ($i = 0; $i < $runs; ++$i)
    {
        $obf_cmd = str_replace('{run}', $i, $obfuscate_cmd);
        $obf     = str_replace('{run}', $i, $obfuscated);
        [$code, $out, $err] = run_process($obf_cmd);
        if ($code !== 0 || !is_file($obf)) return "obfuscation failed (exit $code):\n$out$err";

        [$code, $out] = run_process(array_merge([$php], $ini, [$obf]));
        $actual = normalize($out, dirname($obf));
        if ($code !== $exp_code || $actual !== $expected)
        {
            return "behaviour differs (run $i, exit $exp_code => $code)\n--- expected\n$expected\n--- actual\n$actual\n--- obfuscated: $obf";
        }
    }
    return null;
}

$tmp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'yakpro-po-tests-' . getmypid();
@mkdir($tmp_dir);

$php  = PHP_BINARY;
$ini  = ['-d', 'display_startup_errors=0', '-d', 'display_errors=1', '-d', 'error_reporting=-1', '-d', 'html_errors=0', '-d', 'log_errors=0'];
$pass = $fail = $skip = 0;

$t_tests = [];
foreach (glob(__DIR__ . '/cases/*.php') as $case)
{
    $name = basename($case, '.php');
    $src  = file_get_contents($case);
    $args = (strpos($src, '--config-file') === false) ? ['--config-file', "$root/yakpro-po.cnf"] : [];
    if (preg_match('~//\s*yakpro-args:(.*)$~m', $src, $m))
    {
        foreach (preg_split('/\s+/', trim($m[1])) as $a) $args[] = ($a !== '' && is_file(__DIR__ . "/$a")) ? __DIR__ . "/$a" : $a;
    }
    $obf = $tmp_dir . DIRECTORY_SEPARATOR . "$name.{run}.php";
    $t_tests[$name] = [$src, $case, array_merge([$php, "$root/yakpro-po.php"], $args, ['--silent', $case, '-o', $obf]), $obf];
}
foreach (glob(__DIR__ . '/projects/*', GLOB_ONLYDIR) as $project)
{
    $name   = 'project_' . basename($project);
    $target = $tmp_dir . DIRECTORY_SEPARATOR . "$name.{run}";
    $t_tests[$name] = [file_get_contents("$project/main.php"), "$project/main.php", [$php, "$root/yakpro-po.php", '--config-file', "$root/yakpro-po.cnf", '--silent', $project, '-o', $target], "$target/yakpro-po/obfuscated/main.php"];
}

foreach ($t_tests as $name => [$src, $original, $obfuscate_cmd, $obfuscated])
{
    if ($filter !== '' && strpos($name, $filter) === false) continue;
    if (preg_match('~//\s*min-php:\s*([\d.]+)~', $src, $m) && version_compare(PHP_VERSION, $m[1], '<'))
    {
        echo "SKIP  $name (needs PHP {$m[1]})\n"; ++$skip; continue;
    }
    $error = compare($original, $obfuscate_cmd, $obfuscated, $runs);
    if ($error === null) { echo "PASS  $name\n"; ++$pass; }
    else                 { echo "FAIL  $name\n      " . str_replace("\n", "\n      ", trim($error)) . "\n"; ++$fail; }
}

if ($filter === '' || strpos('target_inside_source', $filter) !== false)     // output directory must not be re-obfuscated as source
{
    $dir = $tmp_dir . DIRECTORY_SEPARATOR . 'target_inside_source';
    @mkdir($dir);
    file_put_contents("$dir/main.php", '<?php echo "ok\n";');
    run_process([$php, "$root/yakpro-po.php", '--config-file', "$root/yakpro-po.cnf", '--silent', $dir, '-o', $dir]);
    if (is_dir("$dir/yakpro-po/obfuscated/yakpro-po") || !is_file("$dir/yakpro-po/obfuscated/main.php")) { echo "FAIL  target_inside_source\n"; ++$fail; }
    else                                                                                                     { echo "PASS  target_inside_source\n"; ++$pass; }
}

echo "\nPHP " . PHP_VERSION . ": $pass passed, $fail failed, $skip skipped\n";
exit($fail ? 1 : 0);
