<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  https://pascal.kissian.net
//
// Copyright (c) 2015-2026 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================

$yakpro_po_base_directory   = dirname(realpath($argv[0]));
//$php_parser_git_commandline = 'git clone --branch=4.x https://github.com/nikic/PHP-Parser.git';
$php_parser_git_commandline = 'git clone https://github.com/nikic/PHP-Parser.git';

if(!file_exists("$yakpro_po_base_directory/PHP-Parser/composer.json"))
{
    fprintf(STDERR,"Error:\tPHP-Parser is not correctly installed!%sYou can try to use the following command:%s\t# %s%s",PHP_EOL,PHP_EOL,$php_parser_git_commandline,PHP_EOL);
    exit(21);
}

$t_composer             = json_decode(file_get_contents("$yakpro_po_base_directory/PHP-Parser/composer.json"));   //print_r($t_composer);
$required_php_version   = $t_composer->{'require'}->{'php'};    $operator = '';for($i=0;!ctype_digit($c=$required_php_version[$i]);++$i) $operator.=$c; $required_php_version = substr($required_php_version,$i);

if (!version_compare(PHP_VERSION,$required_php_version,$operator))
{
    fprintf(STDERR,"Error:\tPHP Version must be %s %s%s",$operator,$required_php_version,PHP_EOL);
    exit(22);
}

$php_parser_version      = null;
if (isset($t_composer->{'extra'}) && isset($t_composer->{'extra'}->{'branch-alias'}) & isset($t_composer->{'extra'}->{'branch-alias'}->{'dev-master'})) $php_parser_version = $t_composer->{'extra'}->{'branch-alias'}->{'dev-master'};

if (!isset($php_parser_version))    // removed on August 13, 2025 ... so get version by parsing CHANGELOG.md
{
    $changelog_filename = "$yakpro_po_base_directory/PHP-Parser/CHANGELOG.md";
    if (file_exists($changelog_filename) && is_readable($changelog_filename))
    {
        $fp     = fopen($changelog_filename,"r");
        if($fp===false)
        {
            fprintf(STDERR,"Error: cannot open [%s] file!%s",$changelog_filename,PHP_EOL);
            exit(23);
        }
        while( ($str=fgets($fp)) !==false )
        {
            $t_str = explode(" ",$str);
            if (strtolower($t_str[0]) != 'version') continue;
            $php_parser_version = $t_str[1];
            break;
        }
    }
    else
    {
        fprintf(STDERR,"Error: cannot read [%s] file!%s",$changelog_filename,PHP_EOL);
        exit(24);
    }
    if (!isset($php_parser_version))
    {
        fprintf(STDERR,"Error: cannot determine PHP-Parser version%s",PHP_EOL);
        exit(25);
    }
}


if (substr($php_parser_version,0,2)!='5.')
{
    fprintf(STDERR,"Error:\tWrong version of PHP-Parser detected!%sCurrently, only 5.x branch of PHP-Parser is supported!%s\tYou can try to use the following command:%s\t# %s%s",PHP_EOL,PHP_EOL,PHP_EOL,$php_parser_git_commandline,PHP_EOL);
    exit(26);
}



?>
