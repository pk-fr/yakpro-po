#!/usr/bin/env php
<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015-2018 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================
if (isset($_SERVER["SERVER_SOFTWARE"]) && ($_SERVER["SERVER_SOFTWARE"]!="") ){ echo "<h1>Comand Line Interface Only!</h1>"; die; }


const PHP_PARSER_DIRECTORY  = 'PHP-Parser';


#require_once 'include/check_version.php';

require_once 'include/get_default_defined_objects.php';     // include this file before defining something....


require_once 'include/classes/config.php';
require_once 'include/classes/scrambler.php';
require_once 'include/functions.php';
require_once 'version.php';

include      'include/retrieve_config_and_arguments.php';

#require_once 'include/classes/parser_extensions/my_autoloader.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once 'include/classes/parser_extensions/my_pretty_printer.php';
require_once 'include/classes/parser_extensions/my_node_visitor.php';


if ($clean_mode && file_exists("$target_directory/yakpro-po/.yakpro-po-directory") )
{
    if (!$conf->silent) fprintf(STDERR,"Info:\tRemoving directory\t= [%s]%s","$target_directory/yakpro-po",PHP_EOL);
    remove_directory("$target_directory/yakpro-po");
    exit;
}

//$parser             = new PhpParser\Parser(new PhpParser\Lexer\Emulative);      // $parser = new PhpParser\Parser(new PhpParser\Lexer);
use PhpParser\Error;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\PrettyPrinter;

switch($conf->parser_mode)
{
    case 'PREFER_PHP7': $parser_mode = ParserFactory::PREFER_PHP7;  break;
    case 'PREFER_PHP5': $parser_mode = ParserFactory::PREFER_PHP5;  break;
    case 'ONLY_PHP7':   $parser_mode = ParserFactory::ONLY_PHP7;    break;
    case 'ONLY_PHP5':   $parser_mode = ParserFactory::ONLY_PHP5;    break;
    default:            $parser_mode = ParserFactory::PREFER_PHP5;  break;
}

$parser = (new ParserFactory)->create($parser_mode);


//$traverser          = new PhpParser\NodeTraverser;
$traverser          = new NodeTraverser;

if ($conf->obfuscate_string_literal)    $prettyPrinter      = new myPrettyprinter;
//else                                    $prettyPrinter      = new PhpParser\PrettyPrinter\Standard;
else                                    $prettyPrinter      = new PrettyPrinter\Standard;

$t_scrambler = array();
foreach(array('variable','function','method','property','class','class_constant','constant','label') as $scramble_what)
{
    $t_scrambler[$scramble_what] = new Scrambler($scramble_what, $conf, ($process_mode=='directory') ? $target_directory : null);
}
if ($whatis!=='')
{
    if ($whatis{0} == '$') $whatis = substr($whatis,1);
    foreach(array('variable','function','method','property','class','class_constant','constant','label') as $scramble_what)
    {
        if ( ( $s = $t_scrambler[$scramble_what]-> unscramble($whatis)) !== '')
        {
            switch($scramble_what)
            {
                case 'variable':
                case 'property':
                    $prefix = '$';
                    break;
                default:
                    $prefix = '';
            }
            echo "$scramble_what: {$prefix}{$s}".PHP_EOL;
        }
    }
    exit;
}

$traverser->addVisitor(new MyNodeVisitor);

switch($process_mode)
{
    case 'file':
        $obfuscated_str =  obfuscate($source_file);
        if ($obfuscated_str===null) { exit;                                         }
        if ($target_file   ===''  ) { echo $obfuscated_str.PHP_EOL.PHP_EOL; exit;   }
        file_put_contents($target_file,$obfuscated_str);
        exit;
    case 'directory':
        if (isset($conf->t_skip) && is_array($conf->t_skip)) foreach($conf->t_skip as $key=>$val) $conf->t_skip[$key] = "$source_directory/$val";
        if (isset($conf->t_keep) && is_array($conf->t_keep)) foreach($conf->t_keep as $key=>$val) $conf->t_keep[$key] = "$source_directory/$val";
        obfuscate_directory($source_directory,"$target_directory/yakpro-po/obfuscated");
        exit;
}

?>
