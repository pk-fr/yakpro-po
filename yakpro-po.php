#!/usr/bin/php
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

require_once 'PHP-Parser/lib/bootstrap.php';

// case-sensitive:      variable names, constant name, array keys, class properties
// case-insensitive:    function names, class names, class method names, namespaces, keywords and constructs

// when we use the word ignore, that means that it is ignored during the obfuscation process (i.e. not obfuscated)

//TODO : namespaces, interfaces ,traits

class Config
{
    public $t_ignore_module_methods     = array('core', 'Exception', 'PDO');	// array where values are internal known module names.

    public $t_ignore_constants          = null;         // array where values are names to ignore.
    public $t_ignore_variables          = null;         // array where values are names to ignore.
    public $t_ignore_functions          = null;         // array where values are names to ignore.
    public $t_ignore_methods            = null;         // array where values are names to ignore.
    public $t_ignore_properties         = null;         // array where values are names to ignore.
    public $t_ignore_classes            = null;         // array where values are names to ignore.
    public $t_ignore_namespaces         = null;         // array where values are names to ignore.

    public $t_ignore_constants_prefix   = null;         // array where values are prefix of names to ignore.
    public $t_ignore_variables_prefix   = null;         // array where values are prefix of names to ignore.
    public $t_ignore_functions_prefix   = null;         // array where values are prefix of names to ignore.
    public $t_ignore_methods_prefix     = null;         // array where values are prefix of names to ignore.
    public $t_ignore_properties_prefix  = null;         // array where values are prefix of names to ignore.
    public $t_ignore_classes_prefix     = null;         // array where values are prefix of names to ignore.
    public $t_ignore_namespaces_prefix  = null;         // array where values are prefix of names to ignore.



    public $scramble_mode               = 'identifier'; // allowed modes are identifier, hexa, numeric
    public $scramble_length             = null;         // min length of scrambled names (max = 16 for identifier, 32 for hexa and numeric)

    public $t_obfuscate_php_extension   = array('php');

    public $obfuscate_constant_name     = true;         // self explanatory
    public $obfuscate_variable_name     = true;         // self explanatory
    public $obfuscate_function_name     = true;         // self explanatory
    public $obfuscate_class_name        = true;         // self explanatory
    public $obfuscate_property_name     = true;         // self explanatory
    public $obfuscate_method_name       = true;         // self explanatory
    public $obfuscate_namespace_name    = true;         // self explanatory

    public $strip_indentation           = true;         // all your obfuscated code will be generated on a single line
    public $abort_on_error              = true;         // self explanatory
    public $confirm                     = true;         // rfu : will answer Y on confirmation request (reserved for future use ... or not...)
    public $silent                      = false;        // display or not Information level messages.

    public $t_keep                      = false;        // array of directory or file pathnames to keep 'as is' ...  i.e. not obfuscate.
    public $t_skip                      = false;        // array of directory or file pathnames to skip when exploring source tree structure ... they will not be on target!

    public $source_directory            = null;         // self explanatory
    public $target_directory            = null;         // self explanatory

    public $user_comment                = null;         // user comment to insert inside each obfuscated file

    public $extract_comment_from_line   = null;         // when both 2 are set, each obfuscated file will contain an extract of the corresponding source file,
    public $extract_comment_to_line     = null;         // starting from extract_comment_from_line number, and endng at extract_comment_to_line line number.

    private $comment                    = '';

    function __construct()
    {
        $this->comment .= "/*   ________________________________________________".PHP_EOL;
        $this->comment .= "    |    Obfuscated by YAK Pro - Php Obfuscator      |".PHP_EOL;
        $this->comment .= "    |  GitHub: https://github.com/pk-fr/yakpro-po    |".PHP_EOL;
        $this->comment .= "    |________________________________________________|".PHP_EOL;
        $this->comment .= "*/".PHP_EOL;
    }

    public function get_comment() { return $this->comment; }
}


class Scrambler
{
    private $t_first_chars          = null;     // allowed first char of a generated identifier
    private $t_chars                = null;     // allowed all except first char of a generated identifier
    private $l1                     = null;     // length of $t_first_chars string
    private $l2                     = null;     // length of $t_chars       string
    private $r                      = null;     // seed and salt for random char generation, modified at each iteration.
    private $scramble_type          = null;     // type on which scrambling is done (i.e. variable, function, etc.)
    private $case_sensitive         = null;     // self explanatory
    private $scramble_mode          = null;     // allowed modes are 'identifier', 'hexa', 'numeric'
    private $scramble_length        = null;     // current length of scrambled names
    private $scramble_length_min    = null;     // min     length of scrambled names
    private $scramble_length_max    = null;     // max     length of scrambled names
    private $t_ignore               = null;     // array where keys are names to ignore.
    private $t_ignore_prefix        = null;     // array where keys are prefix of names to ignore.
    private $t_scramble             = null;     // array of scrambled items (key = source name , value = scrambled name)
    private $t_rscramble            = null;     // array of reversed scrambled items (key = scrambled name, value = source name)
    private $context_directory      = null;     // where to save/restore context
    private $silent                 = null;     // display or not Information level messages.

    private $t_reserved_variable_names = array('this', '_SERVER', '_POST', '_GET', '_REQUEST', '_COOKIE','_SESSION', '_ENV', '_FILES');
    private $t_reserved_function_names = array( '__halt_compiler','__autoload', 'abstract', 'and', 'array', 'as', 'bool', 'break', 'callable', 'case', 'catch',
                                                'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else',
                                                'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile',
                                                'eval', 'exit', 'extends', 'false', 'final', 'finally', 'float', 'for', 'foreach', 'function', 'global', 'goto', 'if',
                                                'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'int', 'interface', 'isset', 'list',
                                                'namespace', 'new', 'null', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once',
                                                'return', 'static', 'string', 'switch', 'throw', 'trait', 'true', 'try', 'unset', 'use', 'var', 'while', 'xor','yield',
                                                'apache_request_headers'                    // seems that it is not included in get_defined_functions ..
                                            );

    private $t_reserved_class_names = array('parent', 'self', 'static',                    // same reserved names for classes, interfaces  and traits...
                                            'int', 'float', 'bool', 'string', 'true', 'false', 'null', 'resource', 'object', 'scalar', 'mixed', 'numeric',
                                            'directory', 'exception', 'closure', 'generator',
                                            'PDOException' );

    private $t_reserved_method_names = array( 'core'      => array('__construct', '__destruct', '__call', '__callstatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__tostring', '__invoke', '__set_state', '__clone','__debuginfo' ),
                                              'Exception' => array('getmessage', 'getprevious', 'getcode', 'getfile', 'getline', 'gettrace', 'gettraceasstring'),
                                              'PDO'       => array('begintransaction', 'commit', 'errorcode', 'errorinfo', 'exec', 'getattribute', 'getavailabledrivers', 'intransaction', 'lastinsertid', 'prepare', 'query', 'quote', 'rollback', 'setattribute',
                                                                   'bindcolumn', 'bindparam', 'bindvalue', 'closecursor', 'columncount', 'debugdumpparams', 'execute', 'fetch', 'fetchall', 'fetchcolumn', 'fetchobject', 'getcolumnmeta', 'nextrowset', 'rowcount', 'setfetchmode'
                                                                  )
                                            );

    function __construct($type,$conf,$target_directory)
    {
        $this->scramble_type        = $type;
        $this->t_first_chars        = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->t_chars              = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $this->r                    = md5(microtime(true));     // random seed
        $this->t_scramble           = array();
        $this->silent               = $conf->silent;
        if (isset($conf->scramble_mode))
        {
            switch($conf->scramble_mode)
            {
                case 'numeric':
                    $this->scramble_length_max  = 32;
                    $this->scramble_mode        = $conf->scramble_mode;
                    $this->t_first_chars        = 'O';
                    $this->t_chars              = '0123456789';
                    break;
                case 'hexa':
                    $this->scramble_length_max	= 32;
                    $this->scramble_mode = $conf->scramble_mode;
                    $this->t_first_chars = 'abcdefABCDEF';
                    break;
                case 'identifier':
                default:
                    $this->scramble_length_max	= 16;
                    $this->scramble_mode = 'identifier';
            }
        }
        $this->l1                   = strlen($this->t_first_chars)-1;
        $this->l2                   = strlen($this->t_chars      )-1;
        $this->scramble_length_min  = 2;
        $this->scramble_length      = 5;
        if (isset($conf->scramble_length))
        {
            $conf->scramble_length += 0;
            if ( ($conf->scramble_length >= $this->scramble_length_min) && ($conf->scramble_length <= $this->scramble_length_max) )
            {
                $this->scramble_length  = $conf->scramble_length;
            }
        }
        switch($type)
        {
            case 'constant':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                $this->t_ignore             = array_merge($this->t_ignore,get_defined_constants(false));
                if (isset($conf->t_ignore_constants))
                {
                    $t                      = $conf->t_ignore_constants;                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_constants_prefix))
                {
                    $t                      = $conf->t_ignore_constants_prefix;         $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'variable':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_variable_names);
                if (isset($conf->t_ignore_variables))
                {
                    $t                      = $conf->t_ignore_variables;                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_variables_prefix))
                {
                    $t                      = $conf->t_ignore_variables_prefix;         $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
             case 'function':
                $this->case_sensitive       = false;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                $t                          = get_defined_functions();                  $t = array_map('strtolower',$t['internal']);    $t = array_flip($t);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                if (isset($conf->t_ignore_functions))
                {
                    $t                      = $conf->t_ignore_functions;                $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_functions_prefix))
                {
                    $t                      = $conf->t_ignore_functions_prefix;         $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
           case 'property':
                $this->case_sensitive       = true;
                $this->t_ignore             = array_flip($this->t_reserved_variable_names);
                if (isset($conf->t_ignore_properties))
                {
                    $t                      = $conf->t_ignore_properties;               $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_properties_prefix))
                {
                    $t                      = $conf->t_ignore_properties_prefix;        $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'class':
                $this->case_sensitive       = false;
                $this->t_ignore             = array_flip($this->t_reserved_class_names);
                $this->t_ignore             = array_merge($this->t_ignore, array_flip($this->t_reserved_variable_names));
                $this->t_ignore             = array_merge($this->t_ignore, array_flip($this->t_reserved_function_names));
                $t                          = get_defined_functions();                  $t = array_flip($t['internal']);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                if (isset($conf->t_ignore_classes))
                {
                    $t                      = $conf->t_ignore_classes;                  $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_classes_prefix))
                {
                    $t                      = $conf->t_ignore_classes_prefix;           $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'method':
                $this->case_sensitive       = false;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                if (isset($conf->t_ignore_module_methods)) foreach($conf->t_ignore_module_methods as $dummy => $module_name)
                {
                    $t                      = array_map('strtolower',$this->t_reserved_method_names[$module_name]);                     $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                $t                          = get_defined_functions();                  $t = array_map('strtolower',$t['internal']);    $t = array_flip($t);
                $this->t_ignore             = array_merge($this->t_ignore,$t);
                if (isset($conf->t_ignore_methods))
                {
                    $t                      = $conf->t_ignore_methods;                  $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_methods_prefix))
                {
                    $t                      = $conf->t_ignore_methods_prefix;           $t = array_map('strtolower',$t);                $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
            case 'namespace':
                $this->case_sensitive       = false;
                $this->t_ignore             = array_flip($this->t_reserved_function_names);
                if (isset($conf->t_ignore_namespaces))
                {
                    $t                      = $conf->t_ignore_namespaces;                $t = array_flip($t);
                    $this->t_ignore         = array_merge($this->t_ignore,$t);
                }
                if (isset($conf->t_ignore_namespaces_prefix))
                {
                    $t                      = $conf->t_ignore_namespaces_prefix;         $t = array_flip($t);
                    $this->t_ignore_prefix  = $t;
                }
                break;
        }
        if (isset($target_directory))           // the constructor will restore previous saved context if exists
        {
            $this->context_directory = $target_directory;
            if (file_exists("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}"))
            {
                $t = unserialize(file_get_contents("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}"));
                $this->t_scramble	= $t[0];
                $this->t_rscramble	= $t[1];
            }
        }
    }

    function __destruct()
    {
        if (!$this->silent) fprintf(STDERR,"Info:\t[%-9s] scrambled \t: %7d%s",$this->scramble_type,sizeof($this->t_scramble),PHP_EOL);
        if (isset($this->context_directory))    // the desstructor will save the current context
        {
            file_put_contents("{$this->context_directory}/yakpro-po/context/{$this->scramble_type}",serialize(array($this->t_scramble,$this->t_rscramble)));
        }
    }

    private function str_scramble($s)                                   // scramble the string according parameters
    {
        $c1         = $this->t_first_chars[mt_rand(0, $this->l1)];      // first char of the identifier
        $c2         = $this->t_chars      [mt_rand(0, $this->l2)];      // prepending salt for md5
        $this->r    = str_shuffle(md5($c2.$s.md5($this->r)));           // 32 chars random hex number derived from $s and lot of pepper and salt

        $s	= $c1;
        switch($this->scramble_mode)
        {
            case 'numeric':
                for($i=0,$l=$this->scramble_length-1;$i<$l;++$i) $s .= $this->t_chars[base_convert(substr($this->r,$i,2),16,10)%($this->l2+1)];
                break;
            case 'hexa':
                for($i=0,$l=$this->scramble_length-1;$i<$l;++$i) $s .= substr($this->r,$i,1);
                break;
            case 'identifier':
            default:
                for($i=0,$l=$this->scramble_length-1;$i<$l;++$i) $s .= $this->t_chars[base_convert(substr($this->r,2*$i,2),16,10)%($this->l2+1)];
        }
        return $s;
    }

    private function case_shuffle($s)   // this function is used to even more obfuscate insensitive names: on each acces to the name, a differrent randomized case of each letter is used.
    {
        for($i=0;$i<strlen($s);++$i) $s{$i} = mt_rand(0,1) ? strtoupper($s{$i}) : strtolower($s{$i});
        return $s;
    }

    public function scramble($s)
    {
        $r = $this->case_sensitive ? $s : strtolower($s);
        if ( isset($this->t_ignore[$r]  )) return $s;

        if (isset($this->t_ignore_prefix))
        {
            foreach($this->t_ignore_prefix as $key => $dummy) if (substr($r,0,strlen($key))===$key) return $s;
        }

        if (!isset($this->t_scramble[$r]))      // if not already scrambled:
        {
            for($i=0;$i<20;++$i)                // try at max 20 times if the random generated scrambled string has already beeen generated!
            {
                $x = $this->str_scramble($s);
                $y = $this->case_sensitive ? $x : strtolower($x);
                if (isset($this->t_rscramble[$y]) || isset($this->t_ignore[$y]) )           // this random value is either already used or a reserved name
                {
                    if (($i==5) && ($this->scramble_length < $this->scramble_length_max))  ++$this->scramble_length;    // if not found after 5 attempts, increase the length...
                    continue;                                                                                           // the next attempt will always be successfull, unless we already are maxlength
                }
                $this->t_scramble [$r] = $y;
                $this->t_rscramble[$y] = $r;
                break;
            }
            if (!isset($this->t_scramble[$r]))
            {
                fprintf(STDERR,"Scramble Error: Identifier not found after 20 iterations!%sAborting...%s",PHP_EOL,PHP_EOL); // should statistically never occur!
                exit;
            }
        }
        return $this->case_sensitive ? $this->t_scramble[$r] : $this->case_shuffle($this->t_scramble[$r]);
    }
}


class MyNodeVisitor extends PhpParser\NodeVisitorAbstract       // all parsing and replacement of scrambled names is done here!
{                                                               // see PHP-Parser for documentation!
    public function enterNode(PhpParser\Node $node)
    {
        global $conf;
        global $t_scrambler;

        $node_modified = false;
        
        if ($conf->obfuscate_variable_name)
        {
            $scrambler = $t_scrambler['variable'];
            if ( ($node instanceof PhpParser\Node\Expr\Variable) || ($node instanceof PhpParser\Node\Stmt\StaticVar) || ($node instanceof PhpParser\Node\Param) )
            {
                $name = $node->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name = $r;
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\Catch_)
            {
                $name = $node->{'var'};                             // equivalent to $node->var, that works also on my php version!
                if ( is_string($name) && (strlen($name) !== 0) )    // but 'var' is a reserved function name, so there is no warranty
                {                                                   // that it will work in the future, so the $node->{'var'} form
                    $r = $scrambler->scramble($name);               // has been used!
                    if ($r!==$name)
                    {
                        $node->{'var'} = $r;
                        $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_function_name)
        {
            $scrambler = $t_scrambler['function'];
            if ($node instanceof PhpParser\Node\Stmt\Function_)
            {
                $name = $node->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name = $r;
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Expr\FuncCall )
            {
                if (isset($node->name->parts))              // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = $node->name->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $node->name->parts[count($parts)-1] = $r;
                            $node_modified = true;
                        }
                    }
                }
            }
        }

        if ($conf->obfuscate_class_name)
        {
            $scrambler = $t_scrambler['class'];
            if ($node instanceof PhpParser\Node\Stmt\Class_)
            {
                $name = $node->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name = $r;
                        $node_modified = true;
                    }
                }
            }
            if ( ($node instanceof PhpParser\Node\Expr\New_) || ($node instanceof PhpParser\Node\Expr\StaticCall) || ($node instanceof PhpParser\Node\Expr\StaticPropertyFetch) )
            {
                $parts = $node->class->parts;
                $name  = $parts[count($parts)-1];
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->class->parts[count($parts)-1] = $r;
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\Catch_)
            {
                $parts = $node->type->parts;
                $name  = $parts[count($parts)-1];
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->class->parts[count($parts)-1] = $r;
                        $node_modified = true;
                    }
                }
            }
        }
        

        if ($conf->obfuscate_property_name)
        {
            $scrambler = $t_scrambler['property'];
            if ( ($node instanceof PhpParser\Node\Expr\PropertyFetch) || ($node instanceof PhpParser\Node\Stmt\PropertyProperty) || ($node instanceof PhpParser\Node\Expr\StaticPropertyFetch) )
            {
                $name = $node->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name = $r;
                        $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_method_name)
        {
            $scrambler = $t_scrambler['method'];
            if ( ($node instanceof PhpParser\Node\Stmt\ClassMethod) || ($node instanceof PhpParser\Node\Expr\MethodCall) || ($node instanceof PhpParser\Node\Expr\StaticCall) )
            {
                $name = $node->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name = $r;
                        $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_constant_name)
        {
            $scrambler = $t_scrambler['constant'];
            if ($node instanceof PhpParser\Node\Expr\FuncCall)      // processing define('constant_name',value);
            {
                if (isset($node->name->parts))                      // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = $node->name->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && ($name=='define') )
                    {
                        for($ok=false;;)
                        {
                            if (!isset($node->args[0]->value))      break;
                            if (count($node->args)!=2)              break;
                            $arg = $node->args[0]->value;           if (! ($arg instanceof PhpParser\Node\Scalar\String_) ) break;
                            $name = $arg->value;                    if (! is_string($name) || (strlen($name) == 0) )        break;
                            $ok     = true;
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $arg->value = $r;
                                $node_modified = true;
                            }
                            break;
                        }
                        if (!$ok)
                        {
                            throw new Exception("Error: your use of define() function is not compatible with yakpro-po!".PHP_EOL."\tOnly 2 paremeters, when first ia a string is allowed...");
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Expr\ConstFetch)
            {
                $parts = $node->name->parts;
                $name  = $parts[count($parts)-1];
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name->parts[count($parts)-1] = $r;
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Const_)
            {
                $name = $node->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name = $r;
                        $node_modified = true;
                    }
                }
            }
        }
        
        if ($conf->obfuscate_namespace_name)
        {
            $scrambler = $t_scrambler['namespace'];
        }
        if ($node_modified) return $node;
    }
/*
    Not used yet...
    public function beforeTraverse(array $nodes)    { }
    public function leaveNode(PhpParser\Node $node) { }
    public function afterTraverse(array $nodes)     { }
*/
}


function obfuscate($filename)                   // takes a filepath as input, returns the corresponding obfuscated code as a string
{
    global $conf;
    global $parser,$traverser,$prettyPrinter;
    global $debug_mode;

    try
    {
        $source = php_strip_whitespace($filename);
        fprintf(STDERR,"Obfuscating %s%s",$filename,PHP_EOL);
        //var_dump( token_get_all($source));	exit;
        if ($source==='') throw new Exception("Error obfuscating [$filename]: php_strip_whitespace returned an empty string!");
        try
        {
            $stmts	= $parser->parse($source.PHP_EOL.PHP_EOL);  // PHP-Parser returns the syntax tree
        }
        catch (PhpParser\Error $e)                              // if an error occurs, then redo it without php_strip_whitespace, in order to display the right line number with error!
        {
            $source = file_get_contents($filename);
            $stmts  = $parser->parse($source.PHP_EOL.PHP_EOL);
        }
        if ($debug_mode) var_dump($stmts);
        
        $stmts  = $traverser->traverse($stmts);                 //  Use PHP-Parser function to traverse the syntax tree and obfuscate names
        $code   = $prettyPrinter->prettyPrintFile($stmts);      //  Use PHP-Parser function to output the obfuscated source, taking the modified obfuscated syntax tree as input
        $code   = trim($code);                                  
        
        //	var_dump($stmts);

        if (isset($conf->strip_indentation) && $conf->strip_indentation)        // self-explanatory
        {
            $tmpfilename = tempnam('/tmp','po-');
            file_put_contents($tmpfilename,$code);
            $code = php_strip_whitespace($tmpfilename);
            unlink($tmpfilename);
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
        return $code;
    }
    catch (Exception $e)
    {
        fprintf(STDERR,"Obfuscator Parse Error [%s]:%s\t%s%s", $filename,PHP_EOL, $e->getMessage(),PHP_EOL);
        return null;
    }
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
    if (!$ok && $display_warning) fprintf(STDERR,"Warning:[%S] is not a valid yakpro-po config file!%s\tCheck if file is php, and if magic line is present!%s",$filename,PHP_EOL,PHP_EOL);
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
            exit(-1);
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

                 if (is_link("$path/$entry"))   unlink("$path/$entry" );            // remove symbolinc links first, to not dereference...
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

    if (!$dp = opendir($source_dir))
    {
        fprintf(STDERR,"Error:\t [%s] directory does not exists!%s",$source_dir,PHP_EOL);
        exit(-1);
    }
    $t_dir	= array();
    $t_file	= array();
    while (($entry = readdir($dp)) !== false)
    {
        if ($entry == "." || $entry == "..")    continue;

        $new_keep_mode = $keep_mode;
        
        $source_path = "$source_dir/$entry";    $source_stat = @lstat($source_path);
        $target_path = "$target_dir/$entry";    $target_stat = @lstat($target_path);
        if ($source_stat===false)
        {
            fprintf(STDERR,"Error:\t cannot stat [%s] !%s",$source_path,PHP_EOL);
            exit(-1);
        }

        if (isset($conf->t_skip) && is_array($conf->t_skip) && in_array($source_path,$conf->t_skip))    continue;

        if (is_link($source_path))
        {
            if ( ($target_stat!==false) && is_link($target_path) && ($source_stat['mtime']<=$target_stat['mtime']) )    continue;
            if (  $target_stat!==false  )
            {
                if (is_dir($target_path))	directory_remove($target_path);
                else
                {
                    if (unlink($target_path)===false)
                    {
                        fprintf(STDERR,"Error:\t cannot unlink [%s] !%s",$target_path,PHP_EOL);
                        exit(-1);
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
                        exit(-1);
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
            if ( ($target_stat!==false) && is_dir($target_path) )                               directory_remove($target_path);
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
                        exit;
                    }
                }
                file_put_contents($target_path,$obfuscated_str.PHP_EOL);
            }
            if ($keep) file_put_contents($target_path,file_get_contents($source_path));
            touch($target_path,$source_stat['mtime']);
            continue;
        }
    }
    closedir($dp);
}


//------------------------------------------------------------------------
//                      Start of Program is here!
//------------------------------------------------------------------------

//
//  Init phase.....
//

$t_args                 = $argv;
$t_yakpro_po_pathinfo   = pathinfo(realpath(array_shift($t_args)));
$yakpro_po_dirname      = $t_yakpro_po_pathinfo['dirname'];

$config_filename        = '';
$process_mode           = '';	// can be: 'file' or 'directory'

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

$pos = array_search('--debug',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $debug_mode = true;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
} else $debug_mode = false;;


// $t_args now containes remaining parameters.
// We will first look for config file, and then we will analyze $t_args accordingly

$config_file_namepart = 'yakpro-po.cnf';	if (($x = getenv('YAKPRO_PO_CONFIG_FILENAME'))!==false) $config_file_namepart = $x;

                                                            $t_where	= array();
if ($argument_config_filename!='')                          $t_where[]	= $argument_config_filename;                        // --config-file argument
if (($x = getenv('YAKPRO_PO_CONFIG_FILE'))     !==false)    $t_where[]	= $x;                                               // YAKPRO_PO_CONFIG_FILE
if (($x = getenv('YAKPRO_PO_CONFIG_DIRECTORY'))!==false)    $t_where[]	= "$x/$config_file_namepart";                       // YAKPRO_PO_CONFIG_DIRECTORY
                                                            $t_where[]	= $config_file_namepart;                            // current_working_directory
                                                            $t_where[]	= "config/$config_file_namepart";                   // current_working_directory/config
if (($x = getenv('HOME'))!==false)                          $t_where[]	= "$x/$config_file_namepart";                       // HOME
if ( $x                  !==false)                          $t_where[]	= "$x/config/$config_file_namepart";                // HOME/config
                                                            $t_where[]	= "/usr/local/YAK/yakpro-po/$config_file_namepart"; // /usr/local/YAK/yakpro-po
                                                            $t_where[]	= "$yakpro_po_dirname/yakpro-po.cnf";               // source_code_directory/default_conf_filename

foreach($t_where As $dummy => $where) if (check_config_file($where)) { $config_filename = $where; break; }

$conf = new Config;

if ($force_conf_silent)     $conf->silent = true;

if ($config_filename=='')   fprintf(STDERR,"Warning:No config file found... using default values!%s",PHP_EOL);
else
{
    $config_filename = realpath($config_filename);
    if (!$conf->silent) fprintf(STDERR,"Info:\tUsing [%s] Config File...%s",$config_filename,PHP_EOL);
    require_once $config_filename;
    if ($force_conf_silent) $conf->silent = true;
}
//var_dump($conf);

$pos = array_search('-y',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $conf->confirm = false;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
}

$pos = array_search('-s',$t_args); if (!isset($pos) || ($pos===false)) $pos = array_search('--no-strip-indentation',$t_args);
if (isset($pos) && ($pos!==false) )
{
    $conf->strip_indentation = false;
    array_splice($t_args,$pos,1);           // remove the arg and reorder
}

$pos = array_search('--scramble-mode',$t_args);
if ( isset($pos) && ($pos!==false) && isset($t_args[$pos+1]) )
{
    $conf->scramble_mode = $t_args[$pos+1];
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
                        if ($y!=$conf->get_comment())       // comment is a magic string, used to not overwrite wrong files!!!
                        {
                            $x = realpath($target_file);
                            fprintf(STDERR,"Error:\tTarget file [%s] exists and is not an obfuscated file!%s", ($x!==false) ? $x : $target_file,PHP_EOL);
                            exit(-1);
                        }
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


// all command line and config file parameters are now taken into account
// initialisation is done...
// let's do the real job!

if ($clean_mode && file_exists("$target_directory/yakpro-po/.yakpro-po-directory") )
{
    if (!$conf->silent) fprintf(STDERR,"Info:\tRemoving directory\t= [%s]%s","$target_directory/yakpro-po",PHP_EOL);
    remove_directory("$target_directory/yakpro-po");
    exit;
}

$parser             = new PhpParser\Parser(new PhpParser\Lexer\Emulative);      // $parser = new PhpParser\Parser(new PhpParser\Lexer);
$traverser          = new PhpParser\NodeTraverser;
$prettyPrinter      = new PhpParser\PrettyPrinter\Standard;

$t_scrambler = array();
foreach(array('variable','function','method','property','class','constant','namespace') as $dummy => $scramble_what)
{
    $t_scrambler[$scramble_what] = new Scrambler($scramble_what, $conf, ($process_mode=='directory') ? $target_directory : null);
}

$traverser->addVisitor(new MyNodeVisitor);

switch($process_mode)
{
    case 'file':
        $obfuscated_str =  obfuscate($source_file);
        if ($obfuscated_str===null) { exit;                               }
        if ($target_file==='')      { echo $obfuscated_str.PHP_EOL; exit; }
        file_put_contents($target_file,$obfuscated_str.PHP_EOL);
        exit;
    case 'directory':
        if (isset($conf->t_skip) && is_array($conf->t_skip)) foreach($conf->t_skip as $key=>$val) $conf->t_skip[$key] = "$source_directory/$val";
        if (isset($conf->t_keep) && is_array($conf->t_keep)) foreach($conf->t_keep as $key=>$val) $conf->t_keep[$key] = "$source_directory/$val";
        obfuscate_directory($source_directory,"$target_directory/yakpro-po/obfuscated");
        exit;
}

?>
