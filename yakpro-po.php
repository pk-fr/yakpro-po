#!/usr/bin/env php
<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015-2020 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================
if (isset($_SERVER["SERVER_SOFTWARE"]) && ($_SERVER["SERVER_SOFTWARE"] != "")) {
    echo "<h1>Comand Line Interface Only!</h1>";
    die;
}


require_once 'include/get_default_defined_objects.php';     // include this file before defining something....

require_once __DIR__ . "/vendor/autoload.php";

require_once 'include/functions.php';
require_once 'version.php';

require_once 'include/retrieve_config_and_arguments.php';

if ($clean_mode && file_exists("$target_directory/yakpro-po/.yakpro-po-directory")) {
    if (!$conf->silent) {
        fprintf(STDERR, "Info:\tRemoving directory\t= [%s]%s", "$target_directory/yakpro-po", PHP_EOL);
    }
    remove_directory("$target_directory/yakpro-po");
    exit(31);
}

use Obfuscator\Classes\ParserExtensions\MyNodeVisitor;
use Obfuscator\Classes\ParserExtensions\MyPrettyPrinter;
use Obfuscator\Classes\Scrambler;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

switch ($conf->parser_mode) {
    case 'PREFER_PHP7':
        $parser_mode = ParserFactory::PREFER_PHP7;
        break;
    case 'PREFER_PHP5':
        $parser_mode = ParserFactory::PREFER_PHP5;
        break;
    case 'ONLY_PHP7':
        $parser_mode = ParserFactory::ONLY_PHP7;
        break;
    case 'ONLY_PHP5':
        $parser_mode = ParserFactory::ONLY_PHP5;
        break;
    default:
        $parser_mode = ParserFactory::PREFER_PHP5;
        break;
}

$parser = (new ParserFactory())->create($parser_mode);


$traverser          = new NodeTraverser();

if ($conf->obfuscate_string_literal) {
    $prettyPrinter      = new MyPrettyPrinter();
} else {
    $prettyPrinter      = new PrettyPrinter\Standard();
}

$dir = $process_mode == 'directory' ? $target_directory : null;

$t_scrambler = [
    'variable' => new Scrambler\VariableScrambler($conf, $dir),
    'function_or_class' => new Scrambler\FunctionOrClassScrambler($conf, $dir),
    'method' => new Scrambler\MethodScrambler($conf, $dir),
    'property' => new Scrambler\PropertyScrambler($conf, $dir),
    'class_constant' => new Scrambler\ClassConstantScrambler($conf, $dir),
    'constant' => new Scrambler\ConstantScrambler($conf, $dir),
    'label' => new Scrambler\LabelScrambler($conf, $dir),
];

if ($whatis !== '') {
    if ($whatis[0] == '$') {
        $whatis = substr($whatis, 1);
    }
//    foreach(array('variable','function','method','property','class','class_constant','constant','label') as $scramble_what)
    foreach (array('variable','function_or_class','method','property','class_constant','constant','label') as $scramble_what) {
        if (( $s = $t_scrambler[$scramble_what]-> unscramble($whatis)) !== '') {
            switch ($scramble_what) {
                case 'variable':
                case 'property':
                    $prefix = '$';
                    break;
                default:
                    $prefix = '';
            }
            echo "$scramble_what: {$prefix}{$s}" . PHP_EOL;
        }
    }
    exit(32);
}

$traverser->addVisitor(new MyNodeVisitor());

switch ($process_mode) {
    case 'file':
        $obfuscated_str =  obfuscate($source_file);
        if ($obfuscated_str === null) {
            exit(33);
        }
        if ($target_file   === '') {
            echo $obfuscated_str . PHP_EOL . PHP_EOL;
            exit(34);
        }
        file_put_contents($target_file, $obfuscated_str);
        exit(0);
    case 'directory':
        if (isset($conf->t_skip) && is_array($conf->t_skip)) {
            foreach ($conf->t_skip as $key => $val) {
                $conf->t_skip[$key] = "$source_directory/$val";
            }
        }
        if (isset($conf->t_keep) && is_array($conf->t_keep)) {
            foreach ($conf->t_keep as $key => $val) {
                $conf->t_keep[$key] = "$source_directory/$val";
            }
        }
        var_dump($conf->t_keep);
        obfuscate_directory($source_directory, "$target_directory/yakpro-po/obfuscated");
        exit(0);
}

?>
