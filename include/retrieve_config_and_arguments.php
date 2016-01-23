<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================

$t_args                 = $argv;
$t_yakpro_po_pathinfo   = pathinfo(realpath(array_shift($t_args)));
$yakpro_po_dirname      = $t_yakpro_po_pathinfo['dirname'];

$config_filename        = '';
$process_mode           = '';   // can be: 'file' or 'directory'

$pos = array_search('-h',$t_args); if (!isset($pos) || ($pos===false)) $pos = array_search('--help',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $lang = '';
    if (($x = getenv('LANG'))!==false) $s = strtolower($x); $x = explode('_',$x); $x = $x[0];
         if (file_exists("$yakpro_po_dirname/locale/$x/README.md"))  $help = file_get_contents("$yakpro_po_dirname/locale/$x/README.md");
    else if (file_exists("$yakpro_po_dirname/README.md"))            $help = file_get_contents("$yakpro_po_dirname/README.md");
    else $help = "Help File not found!";

    $pos    = stripos($help,'####');    if ($pos!==false) $help = substr($help,$pos+strlen('####'));
    $pos    = stripos($help,'####');    if ($pos!==false) $help = substr($help,0,$pos);
    $help   = trim(str_replace(array('## ','`'),array('',''),$help));
    echo "$help".PHP_EOL;
    exit;
}

$pos = array_search('--config-file',$t_args);
if ( isset($pos) && ($pos!==false) && isset($t_args[$pos+1]) )
{
    $argument_config_filename = $t_args[$pos+1];
    array_splice($t_args,$pos,2);           // remove the 2 args and reorder
} else $argument_config_filename = '';

$pos = array_search('-o',$t_args); if (!isset($pos) || ($pos===false)) $pos = array_search('--output-file',$t_args);
if ( isset($pos) && ($pos!==false) && isset($t_args[$pos+1]) )
{
    $target = $t_args[$pos+1];
    array_splice($t_args,$pos,2);           // remove the 2 args and reorder
} else $target = '';

$pos = array_search('--clean',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $clean_mode = true;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
} else $clean_mode = false;

$pos = array_search('--silent',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $force_conf_silent = true;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
} else $force_conf_silent = false;;

$pos = array_search('--whatis',$t_args);
if ( isset($pos) && ($pos!==false) && isset($t_args[$pos+1]) )
{
    $whatis = $t_args[$pos+1];
    array_splice($t_args,$pos,2);           // remove the 2 args and reorder
    $force_conf_silent = true;
} else $whatis = '';

$pos = array_search('--debug',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $debug_mode = true;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
} else $debug_mode = false;;

$pos = array_search('--debug',$t_args);     // repeated --debug
if (isset($pos) && ($pos!==false) )
{
    $debug_mode = 2;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
}


// $t_args now containes remaining parameters.
// We will first look for config file, and then we will analyze $t_args accordingly

$config_file_namepart = 'yakpro-po.cnf';    if (($x = getenv('YAKPRO_PO_CONFIG_FILENAME'))!==false) $config_file_namepart = $x;

                                                            $t_where    = array();
if ($argument_config_filename!='')                          $t_where[]  = $argument_config_filename;                        // --config-file argument
if (($x = getenv('YAKPRO_PO_CONFIG_FILE'))     !==false)    $t_where[]  = $x;                                               // YAKPRO_PO_CONFIG_FILE
if (($x = getenv('YAKPRO_PO_CONFIG_DIRECTORY'))!==false)    $t_where[]  = "$x/$config_file_namepart";                       // YAKPRO_PO_CONFIG_DIRECTORY
                                                            $t_where[]  = $config_file_namepart;                            // current_working_directory
                                                            $t_where[]  = "config/$config_file_namepart";                   // current_working_directory/config
if (($x = getenv('HOME'))!==false)                          $t_where[]  = "$x/$config_file_namepart";                       // HOME
if ( $x                  !==false)                          $t_where[]  = "$x/config/$config_file_namepart";                // HOME/config
                                                            $t_where[]  = "/usr/local/YAK/yakpro-po/$config_file_namepart"; // /usr/local/YAK/yakpro-po
                                                            $t_where[]  = "$yakpro_po_dirname/yakpro-po.cnf";               // source_code_directory/default_conf_filename

foreach($t_where As $dummy => $where) if (check_config_file($where)) { $config_filename = $where; break; }

$conf = new Config;

if ($force_conf_silent)     $conf->silent = true;

if ($config_filename=='')   fprintf(STDERR,"Warning:No config file found... using default values!%s",PHP_EOL);
else
{
    $config_filename = realpath($config_filename);
    if (!$conf->silent) fprintf(STDERR,"Info:\tUsing [%s] Config File...%s",$config_filename,PHP_EOL);
    require_once $config_filename;
    $conf->validate();
    if ($force_conf_silent) $conf->silent = true;
}
//var_dump($conf);

$pos = array_search('-y',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $conf->confirm = false;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
}

$pos = array_search('-s',$t_args);                                  if (isset($pos) && ($pos!==false)) { $conf->strip_indentation               = false; array_splice($t_args,$pos,1); }
$pos = array_search('--no-strip-indentation',$t_args);              if (isset($pos) && ($pos!==false)) { $conf->strip_indentation               = false; array_splice($t_args,$pos,1); }
$pos = array_search('--strip-indentation',$t_args);                 if (isset($pos) && ($pos!==false)) { $conf->strip_indentation               = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-shuffle-statements',$t_args);             if (isset($pos) && ($pos!==false)) { $conf->shuffle_stmts                   = false; array_splice($t_args,$pos,1); }
$pos = array_search('--shuffle-statements',$t_args);                if (isset($pos) && ($pos!==false)) { $conf->shuffle_stmts                   = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-string-literal',$t_args);       if (isset($pos) && ($pos!==false)) { $conf->obfuscate_string_literal        = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-string-literal',$t_args);          if (isset($pos) && ($pos!==false)) { $conf->obfuscate_string_literal        = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-loop-statement',$t_args);       if (isset($pos) && ($pos!==false)) { $conf->obfuscate_loop_statement        = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-loop-statement',$t_args);          if (isset($pos) && ($pos!==false)) { $conf->obfuscate_loop_statement        = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-if-statement',$t_args);         if (isset($pos) && ($pos!==false)) { $conf->obfuscate_if_statement          = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-if-statement',$t_args);            if (isset($pos) && ($pos!==false)) { $conf->obfuscate_if_statement          = true;  array_splice($t_args,$pos,1); }


$pos = array_search('--no-obfuscate-constant-name',$t_args);        if (isset($pos) && ($pos!==false)) { $conf->obfuscate_constant_name         = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-constant-name',$t_args);           if (isset($pos) && ($pos!==false)) { $conf->obfuscate_constant_name         = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-variable-name',$t_args);        if (isset($pos) && ($pos!==false)) { $conf->obfuscate_variable_name         = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-variable-name',$t_args);           if (isset($pos) && ($pos!==false)) { $conf->obfuscate_variable_name         = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-function-name',$t_args);        if (isset($pos) && ($pos!==false)) { $conf->obfuscate_function_name         = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-function-name',$t_args);           if (isset($pos) && ($pos!==false)) { $conf->obfuscate_function_name         = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-class-name',$t_args);           if (isset($pos) && ($pos!==false)) { $conf->obfuscate_class_name            = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-class-name',$t_args);              if (isset($pos) && ($pos!==false)) { $conf->obfuscate_class_name            = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-class_constant-name',$t_args);  if (isset($pos) && ($pos!==false)) { $conf->obfuscate_class_constant_name   = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-class_constant-name',$t_args);     if (isset($pos) && ($pos!==false)) { $conf->obfuscate_class_constant_name   = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-interface-name',$t_args);       if (isset($pos) && ($pos!==false)) { $conf->obfuscate_interface_name        = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-interface-name',$t_args);          if (isset($pos) && ($pos!==false)) { $conf->obfuscate_interface_name        = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-trait-name',$t_args);           if (isset($pos) && ($pos!==false)) { $conf->obfuscate_trait_name            = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-trait-name',$t_args);              if (isset($pos) && ($pos!==false)) { $conf->obfuscate_trait_name            = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-property-name',$t_args);        if (isset($pos) && ($pos!==false)) { $conf->obfuscate_property_name         = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-property-name',$t_args);           if (isset($pos) && ($pos!==false)) { $conf->obfuscate_property_name         = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-method-name',$t_args);          if (isset($pos) && ($pos!==false)) { $conf->obfuscate_method_name           = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-method-name',$t_args);             if (isset($pos) && ($pos!==false)) { $conf->obfuscate_method_name           = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-namespace-name',$t_args);       if (isset($pos) && ($pos!==false)) { $conf->obfuscate_namespace_name        = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-namespace-name',$t_args);          if (isset($pos) && ($pos!==false)) { $conf->obfuscate_namespace_name        = true;  array_splice($t_args,$pos,1); }

$pos = array_search('--no-obfuscate-label-name',$t_args);           if (isset($pos) && ($pos!==false)) { $conf->obfuscate_label_name            = false; array_splice($t_args,$pos,1); }
$pos = array_search('--obfuscate-label-name',$t_args);              if (isset($pos) && ($pos!==false)) { $conf->obfuscate_label_name            = true;  array_splice($t_args,$pos,1); }




$pos = array_search('--scramble-mode',$t_args);
if ( isset($pos) && ($pos!==false) && isset($t_args[$pos+1]) )
{
    $conf->scramble_mode = $t_args[$pos+1];
    array_splice($t_args,$pos,2);           // remove the 2 args and reorder
}

$pos = array_search('--scramble-length',$t_args);
if ( isset($pos) && ($pos!==false) && isset($t_args[$pos+1]) )
{
    $conf->scramble_length = $t_args[$pos+1]+0;
    array_splice($t_args,$pos,2);           // remove the 2 args and reorder
}


switch(count($t_args))
{
    case 0:
        if (isset($conf->source_directory) && isset($conf->target_directory))
        {
            $process_mode       = 'directory';
            $source_directory   = $conf->source_directory;
            $target_directory   = $conf->target_directory;
            create_context_directories($target_directory);
            break;
        }
        fprintf(STDERR,"Error:\tsource_directory and target_directory not specified!%s\tneither within command line parameter,%s\tnor in config file!%s",PHP_EOL,PHP_EOL,PHP_EOL);
        exit(-1);
    case 1:
        $source_file = realpath($t_args[0]);
        if (($source_file!==false) && file_exists($source_file))
        {
            if (is_file($source_file) && is_readable($source_file))
            {
                $process_mode   = 'file';
                $target_file    = $target;
                if ( ($target_file!=='') && file_exists($target_file) )
                {
                    $x = realpath($target_file);
                    if (is_dir($x))
                    {
                        fprintf(STDERR,"Error:\tTarget file [%s] is a directory!%s", ($x!==false) ? $x : $target_file,PHP_EOL);
                        exit(-1);
                    }
                    if ( is_readable($x) && is_writable($x) && is_file($x) )
                    {
                        $fp = fopen($target_file,"r");
                        $y = fgets($fp);
                        $y = fgets($fp).fgets($fp).fgets($fp).fgets($fp).fgets($fp);
                        if (strpos($y,'    |  Obfuscated by YAK Pro - Php Obfuscator ')===false)       // comment is a magic string, used to not overwrite wrong files!!!
                        {
                            $x = realpath($target_file);
                            fprintf(STDERR,"Error:\tTarget file [%s] exists and is not an obfuscated file!%s", ($x!==false) ? $x : $target_file,PHP_EOL);
                            exit(-1);
                        }
                        fclose($fp);
                    }
                }
                break;
            }
            if (is_dir($source_file))
            {
                $process_mode       = 'directory';
                $source_directory   = $source_file;
                $target_directory   = $target;
                if (($target_directory=='') && isset($conf->target_directory)) $target_directory = $conf->target_directory;
                if ( $target_directory=='')
                {
                    fprintf(STDERR,"Error:\tTarget directory is not specified!%s",PHP_EOL);
                    exit(-1);
                }
                create_context_directories($target_directory);
                break;
            }
        }
        fprintf(STDERR,"Error:\tSource file [%s] is not readable!%s",($source_file!==false) ? $source_file : $t_args[0],PHP_EOL);
        exit(-1);
    default:
        fprintf(STDERR,"Error:\tToo much parameters are specified, I do not know how to deal with that!!!%s",PHP_EOL);
        exit(-1);
}
//print_r($t_args);

if (!$conf->silent) fprintf(STDERR,"Info:\tProcess Mode\t\t= %s%s",$process_mode,PHP_EOL);
switch($process_mode)
{
    case 'file':
        if (!$conf->silent) fprintf(STDERR,"Info:\tsource_file\t\t= [%s]%s",$source_file,PHP_EOL);
        if (!$conf->silent) fprintf(STDERR,"Info:\ttarget_file\t\t= [%s]%s",($target_file!=='') ? $target_file : 'stdout',PHP_EOL);
        break;
    case 'directory':
        if (!$conf->silent) fprintf(STDERR,"Info:\tsource_directory\t= [%s]%s",$source_directory,PHP_EOL);
        if (!$conf->silent) fprintf(STDERR,"Info:\ttarget_directory\t= [%s]%s",$target_directory,PHP_EOL);
        break;
}


?>
