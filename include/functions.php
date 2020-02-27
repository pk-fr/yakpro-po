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

function obfuscate($filename)                   // takes a file_path as input, returns the corresponding obfuscated code as a string
{
    global $conf;
    global $parser,$traverser,$prettyPrinter;
    global $debug_mode;
    
    $src_filename = $filename;
    $tmp_filename = $first_line = '';
    $t_source = file($filename);
    if (substr($t_source[0],0,2)=='#!')
    {
        $first_line = array_shift($t_source);
        $tmp_filename = tempnam(sys_get_temp_dir(), 'po-');
        file_put_contents($tmp_filename,implode(PHP_EOL,$t_source));
        $filename = $tmp_filename; // override 
    }
    
    try
    {
        $source = php_strip_whitespace($filename);
        fprintf(STDERR,"Obfuscating %s%s",$src_filename,PHP_EOL);
        //var_dump( token_get_all($source));    exit;
        if ($source==='')
        {
            if ($conf->allow_and_overwrite_empty_files) return $source;
            throw new Exception("Error obfuscating [$src_filename]: php_strip_whitespace returned an empty string!");
        }
        try
        {
            $stmts  = $parser->parse($source);  // PHP-Parser returns the syntax tree
        }
        catch (PhpParser\Error $e)                              // if an error occurs, then redo it without php_strip_whitespace, in order to display the right line number with error!
        {
            $source = file_get_contents($filename);
            $stmts  = $parser->parse($source);
        }
        if ($debug_mode===2)                                    //  == 2 is true when debug_mode is true!
        {
            $source = file_get_contents($filename);
            $stmts  = $parser->parse($source);
        }
        if ($debug_mode) var_dump($stmts);

        $stmts  = $traverser->traverse($stmts);                 //  Use PHP-Parser function to traverse the syntax tree and obfuscate names
        if ($conf->shuffle_stmts && (count($stmts)>2) )
        {
            $last_inst  = array_pop($stmts);
            $last_use_stmt_pos = -1;
            foreach($stmts as $i => $stmt)                      // if a use statement exists, do not shuffle before the last use statement
            {                                                   //TODO: enhancement: keep all use statements at their position, and shuffle all sub-parts
                if ( $stmt instanceof PhpParser\Node\Stmt\Use_ ) $last_use_stmt_pos = $i;
            }

            if ($last_use_stmt_pos<0)   { $stmts_to_shuffle = $stmts;                                   $stmts = array();                                       }
            else                        { $stmts_to_shuffle = array_slice($stmts,$last_use_stmt_pos+1); $stmts = array_slice($stmts,0,$last_use_stmt_pos+1);    }
            
            $stmts      = array_merge($stmts,shuffle_statements($stmts_to_shuffle));
            $stmts[]    = $last_inst;
        }
        // if ($debug_mode) var_dump($stmts);

        
        $code   = trim($prettyPrinter->prettyPrintFile($stmts));            //  Use PHP-Parser function to output the obfuscated source, taking the modified obfuscated syntax tree as input

        if (isset($conf->strip_indentation) && $conf->strip_indentation)    // self-explanatory
        {
            $code = remove_whitespaces($code);
        }
        $endcode = substr($code,6);

        $code  = '<?php'.PHP_EOL;
        $code .= $conf->get_comment();                                          // comment obfuscated source
        if (isset($conf->extract_comment_from_line) && isset($conf->extract_comment_to_line) )
        {
            $t_source = file($filename);
            for($i=$conf->extract_comment_from_line-1;$i<$conf->extract_comment_to_line;++$i) $code .= $t_source[$i];
        }
        if (isset($conf->user_comment))
        {
            $code .= '/*'.PHP_EOL.$conf->user_comment.PHP_EOL.'*/'.PHP_EOL;
        }
        $code .= $endcode;
        
        if (($tmp_filename!='') && ($first_line!=''))
        {
            $code = $first_line.$code;
            unlink($tmp_filename);
        }
        
        return trim($code);
    }
    catch (Exception $e)
    {
        fprintf(STDERR,"Obfuscator Parse Error [%s]:%s\t%s%s", $filename,PHP_EOL, $e->getMessage(),PHP_EOL);
        return null;
    }
}

function check_preload_file($filename)                       // self-explanatory
{
    for($ok=false;;)
    {
        if (!file_exists($filename)) return false;
        if (!is_readable($filename))
        {
            fprintf(STDERR,"Warning:[%s] is not readable!%s",$filename,PHP_EOL);
            return false;
        }
        $fp     = fopen($filename,"r"); if($fp===false) break;
        $line   = trim(fgets($fp));     if ($line!='<?php')                                     { fclose($fp); break; }
        $line   = trim(fgets($fp));     if ($line!='// YAK Pro - Php Obfuscator: Preload File') { fclose($fp); break; }
        fclose($fp);
        $ok     = true;
        break;
    }
    if (!$ok) fprintf(STDERR,"Warning:[%s] is not a valid yakpro-po preload file!%s\tCheck if file is php, and if magic line is present!%s",$filename,PHP_EOL,PHP_EOL);
    return $ok;
}

function check_config_file($filename)                       // self-explanatory
{
    for($ok=false;;)
    {
        if (!file_exists($filename)) return false;
        if (!is_readable($filename))
        {
            fprintf(STDERR,"Warning:[%s] is not readable!%s",$filename,PHP_EOL);
            return false;
        }
        $fp     = fopen($filename,"r"); if($fp===false) break;
        $line   = trim(fgets($fp));     if ($line!='<?php')                                     { fclose($fp); break; }
        $line   = trim(fgets($fp));     if ($line!='// YAK Pro - Php Obfuscator: Config File')  { fclose($fp); break; }
        fclose($fp);
        $ok     = true;
        break;
    }
    if (!$ok) fprintf(STDERR,"Warning:[%s] is not a valid yakpro-po config file!%s\tCheck if file is php, and if magic line is present!%s",$filename,PHP_EOL,PHP_EOL);
    return $ok;
}

function create_context_directories($target_directory)      // self-explanatory
{
    foreach( array("$target_directory/yakpro-po","$target_directory/yakpro-po/obfuscated","$target_directory/yakpro-po/context") as $dummy => $dir)
    {
        if (!file_exists($dir)) mkdir($dir,0777,true);
        if (!file_exists($dir))
        {
            fprintf(STDERR,"Error:\tCannot create directory [%s]%s",$dir,PHP_EOL);
            exit(51);
        }
    }
    $target_directory = realpath($target_directory);
    if (!file_exists("$target_directory/yakpro-po/.yakpro-po-directory")) touch("$target_directory/yakpro-po/.yakpro-po-directory");
}


function remove_directory($path)                            // self-explanatory
{
    if ($dp = opendir($path))
    {
        while (($entry = readdir($dp)) !==  false )
        {
            if ($entry ==  ".") continue;
            if ($entry == "..") continue;

                 if (is_link("$path/$entry"))   unlink("$path/$entry" );            // remove symbolic links first, to not dereference...
            else if (is_dir ("$path/$entry"))   remove_directory("$path/$entry");
            else                                unlink("$path/$entry" );
        }
        closedir($dp);
        rmdir($path);
    }
}

function confirm($str)                                  // self-explanatory not yet used ... rfu
{
    global $conf;
    if (!$conf->confirm) return true;
    for(;;)
    {
        fprintf(STDERR,"%s [y/n] : ",$str);
        $r = strtolower(trim(fgets(STDIN)));
        if ($r=='y')    return true;
        if ($r=='n')    return false;
    }
}

function obfuscate_directory($source_dir,$target_dir,$keep_mode=false)   // self-explanatory recursive obfuscation
{
    global $conf;

    static $recursion_level = 0;

    if (++$recursion_level > $conf->max_nested_directory)
    {
        if ($conf->follow_symlinks)
        {
            fprintf(STDERR,"Error:\t [%s] nested directories have been created!\nloop detected when follow_symlinks option is set to true!%s",$conf->max_nested_directory,PHP_EOL);
            exit(52);
        }
    }
    if (!$dp = opendir($source_dir))
    {
        fprintf(STDERR,"Error:\t [%s] directory does not exists!%s",$source_dir,PHP_EOL);
        exit(53);
    }
    $t_dir  = array();
    $t_file = array();
    while (($entry = readdir($dp)) !== false)
    {
        if ($entry == "." || $entry == "..")    continue;

        $new_keep_mode = $keep_mode;

        $source_path = "$source_dir/$entry";    $source_stat = @lstat($source_path);
        $target_path = "$target_dir/$entry";    $target_stat = @lstat($target_path);
        if ($source_stat===false)
        {
            fprintf(STDERR,"Error:\t cannot stat [%s] !%s",$source_path,PHP_EOL);
            exit(54);
        }

        if (isset($conf->t_skip) && is_array($conf->t_skip) && in_array($source_path,$conf->t_skip))    continue;

        if (!$conf->follow_symlinks && is_link($source_path))
        {
            if ( ($target_stat!==false) && is_link($target_path) && ($source_stat['mtime']<=$target_stat['mtime']) )    continue;
            if (  $target_stat!==false  )
            {
                if (is_dir($target_path))   remove_directory($target_path);
                else
                {
                    if (unlink($target_path)===false)
                    {
                        fprintf(STDERR,"Error:\t cannot unlink [%s] !%s",$target_path,PHP_EOL);
                        exit(55);
                    }
                }
            }
            @symlink(readlink($source_path), $target_path);     // Do not warn on non existing symbolinc link target!
            if (strtolower(PHP_OS)=='linux')    $x = `touch '$target_path' --no-dereference --reference='$source_path' `;
            continue;
        }
        if (is_dir($source_path))
        {
            if ($target_stat!==false)
            {
                if (!is_dir($target_path))
                {
                    if (unlink($target_path)===false)
                    {
                        fprintf(STDERR,"Error:\t cannot unlink [%s] !%s",$target_path,PHP_EOL);
                        exit(56);
                    }
                }
            }
            if (!file_exists($target_path)) mkdir($target_path,0777, true);
            if (isset($conf->t_keep) && is_array($conf->t_keep) && in_array($source_path,$conf->t_keep))    $new_keep_mode = true;
            obfuscate_directory($source_path,$target_path,$new_keep_mode);
            continue;
        }
        if(is_file($source_path))
        {
            if ( ($target_stat!==false) && is_dir($target_path) )                               remove_directory($target_path);
            if ( ($target_stat!==false) && ($source_stat['mtime']<=$target_stat['mtime']) )     continue;                       // do not process if source timestamp is not greater than target

            $extension  = pathinfo($source_path,PATHINFO_EXTENSION);

            $keep = $keep_mode;
            if (isset($conf->t_keep) && is_array($conf->t_keep) && in_array($source_path,$conf->t_keep))    $keep = true;
            if (!in_array($extension,$conf->t_obfuscate_php_extension) )                                    $keep = true;

            if ($keep)
            {
                file_put_contents($target_path,file_get_contents($source_path));
            }
            else
            {
                $obfuscated_str =  obfuscate($source_path);
                if ($obfuscated_str===null)
                {
                    if (isset($conf->abort_on_error))
                    {
                        fprintf(STDERR, "Aborting...%s",PHP_EOL);
                        exit(57);
                    }
                }
                file_put_contents($target_path,$obfuscated_str.PHP_EOL);
            }
            touch($target_path,$source_stat['mtime']);
            chmod($target_path,$source_stat['mode']);
            chgrp($target_path,$source_stat['gid']);
            chown($target_path,$source_stat['uid']);
            continue;
        }
    }
    closedir($dp);
    --$recursion_level;
}

function shuffle_get_chunk_size(&$stmts)
{
    global $conf;

    $n = count($stmts);
    switch($conf->shuffle_stmts_chunk_mode)
    {
        case 'ratio':
            $chunk_size = sprintf("%d",$n/$conf->shuffle_stmts_chunk_ratio)+0;
            if ($chunk_size<$conf->shuffle_stmts_min_chunk_size) $chunk_size = $conf->shuffle_stmts_min_chunk_size;
            break;
        case 'fixed':
            $chunk_size = $conf->shuffle_stmts_min_chunk_size;
            break;
        default:
            $chunk_size =  1;       // should never occur!
    }
    return $chunk_size;
}

function shuffle_statements($stmts)
{
    global $conf;
    global $t_scrambler;

    if (!$conf->shuffle_stmts)          return $stmts;
    
    $chunk_size = shuffle_get_chunk_size($stmts);
    if ($chunk_size<=0)                 return $stmts; // should never occur!
    
    $n = count($stmts);
    if ($n<(2*$chunk_size))             return $stmts;
    
    $scrambler              = $t_scrambler['label'];
    $label_name_prev        = $scrambler->scramble($scrambler->generate_label_name());
    $first_goto             = new PhpParser\Node\Stmt\Goto_($label_name_prev);
    $t                      = array();
    $t_chunk                = array();
    for($i=0;$i<$n;++$i)
    {
        $t_chunk[]              = $stmts[$i];
        if (count($t_chunk)>=$chunk_size)
        {
            $label              = array(new PhpParser\Node\Stmt\Label($label_name_prev));
            $label_name         = $scrambler->scramble($scrambler->generate_label_name());
            $goto               = array(new PhpParser\Node\Stmt\Goto_($label_name));
            $t[]                = array_merge($label,$t_chunk,$goto);
            $label_name_prev    = $label_name;
            $t_chunk            = array();
        }
    }
    if (count($t_chunk)>0)
    {
        $label              = array(new PhpParser\Node\Stmt\Label($label_name_prev));
        $label_name         = $scrambler->scramble($scrambler->generate_label_name());
        $goto               = array(new PhpParser\Node\Stmt\Goto_($label_name));
        $t[]                = array_merge($label,$t_chunk,$goto);
        $label_name_prev    = $label_name;
        $t_chunk            = array();
    }
    
    $last_label             = new PhpParser\Node\Stmt\Label($label_name);
    shuffle($t);
    $stmts = array();
    $stmts[] = $first_goto;
    foreach($t as $dummy => $stmt)
    {
        foreach($stmt as $dummy => $inst) $stmts[] = $inst;
    }
    $stmts[] = $last_label;
    return $stmts;
}

function remove_whitespaces($str)
{
    $tmp_filename = @tempnam(sys_get_temp_dir(),'po-');
    file_put_contents($tmp_filename,$str);
    $str = php_strip_whitespace($tmp_filename);  // can remove more whitespaces
    unlink($tmp_filename);
    return $str;
}

?>
