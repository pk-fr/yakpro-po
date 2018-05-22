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

// when we use the word ignore, that means that it is ignored during the obfuscation process (i.e. not obfuscated)

class Config
{
    public $t_ignore_pre_defined_classes    = 'all';        // 'all' (default value) , 'none',  or array of pre-defined classes that you use in your software:
                                                            //      ex: array('Exception', 'PDO', 'PDOStatement', 'PDOException');
                                                            // As instantiation is done at runtime, it is impossible to statically determinate when a method call is detected, on which class the object belong.
                                                            // so, all method names that exists in a pre_defined_class to ignore are ignored within every classes.
                                                            // if you have some method names in your classes that have the same name that a predefine class method, it will not be obfuscated.
                                                            // you can limit the number of method names to ignore by providing an array of the pre-defined classes you really use in your software!
                                                            // same behaviour for properties...

    public $t_ignore_constants              = null;         // array where values are names to ignore.
    public $t_ignore_variables              = null;         // array where values are names to ignore.
    public $t_ignore_functions              = null;         // array where values are names to ignore.
    public $t_ignore_class_constants        = null;         // array where values are names to ignore.
    public $t_ignore_methods                = null;         // array where values are names to ignore.
    public $t_ignore_properties             = null;         // array where values are names to ignore.
    public $t_ignore_classes                = null;         // array where values are names to ignore.
    public $t_ignore_interfaces             = null;         // array where values are names to ignore.
    public $t_ignore_traits                 = null;         // array where values are names to ignore.
    public $t_ignore_namespaces             = null;         // array where values are names to ignore.
    public $t_ignore_labels                 = null;         // array where values are names to ignore.

    public $t_ignore_constants_prefix       = null;         // array where values are prefix of names to ignore.
    public $t_ignore_variables_prefix       = null;         // array where values are prefix of names to ignore.
    public $t_ignore_functions_prefix       = null;         // array where values are prefix of names to ignore.
    
    public $t_ignore_class_constants_prefix = null;         // array where values are prefix of names to ignore.
    public $t_ignore_properties_prefix      = null;         // array where values are prefix of names to ignore.
    public $t_ignore_methods_prefix         = null;         // array where values are prefix of names to ignore.

    public $t_ignore_classes_prefix         = null;         // array where values are prefix of names to ignore.
    public $t_ignore_interfaces_prefix      = null;         // array where values are names to ignore.
    public $t_ignore_traits_prefix          = null;         // array where values are names to ignore.
    public $t_ignore_namespaces_prefix      = null;         // array where values are prefix of names to ignore.
    public $t_ignore_labels_prefix          = null;         // array where values are prefix of names to ignore.

    public $parser_mode                     = 'PREFER_PHP5';// allowed modes are 'PREFER_PHP7', 'PREFER_PHP5', 'ONLY_PHP7', 'ONLY_PHP5'
                                                            // see PHP-Parser documentation for meaning...

    public $scramble_mode                   = 'identifier'; // allowed modes are identifier, hexa, numeric
    public $scramble_length                 = null;         // min length of scrambled names (max = 16 for identifier, 32 for hexa and numeric)

    public $t_obfuscate_php_extension       = array('php');

    public $obfuscate_constant_name         = true;         // self explanatory
    public $obfuscate_variable_name         = true;         // self explanatory
    public $obfuscate_function_name         = true;         // self explanatory
    public $obfuscate_class_name            = true;         // self explanatory
    public $obfuscate_interface_name        = true;         // self explanatory
    public $obfuscate_trait_name            = true;         // self explanatory
    public $obfuscate_class_constant_name   = true;         // self explanatory
    public $obfuscate_property_name         = true;         // self explanatory
    public $obfuscate_method_name           = true;         // self explanatory
    public $obfuscate_namespace_name        = true;         // self explanatory
    public $obfuscate_label_name            = true;         // label: , goto label;  obfuscation
    public $obfuscate_if_statement          = true;         // obfuscate if else elseif statements
    public $obfuscate_loop_statement        = true;         // obfuscate for while do while statements
    public $obfuscate_string_literal        = true;         // pseudo-obfuscate string literals

    public $shuffle_stmts                   = true;         // shuffle chunks of statements!  disable this obfuscation (or minimize the number of chunks) if performance is important for you!
    public $shuffle_stmts_min_chunk_size    =    1;         // minimum number of statements in a chunk! the min value is 1, that gives you the maximum of obfuscation ... and the minimum of performance...
    public $shuffle_stmts_chunk_mode        = 'fixed';      // 'fixed' or 'ratio' in fixed mode, the chunk_size is always equal to the min chunk size!
    public $shuffle_stmts_chunk_ratio       =   20;         // ratio > 1  100/ratio is the percentage of chunks in a statements sequence  ratio = 2 means 50%  ratio = 100 mins 1% ...
                                                            // if you increase the number of chunks, you increase also the obfuscation level ... and you increase also the performance overhead!

    public $strip_indentation               = true;         // all your obfuscated code will be generated on a single line
    public $abort_on_error                  = true;         // self explanatory
    public $confirm                         = true;         // rfu : will answer Y on confirmation request (reserved for future use ... or not...)
    public $silent                          = false;        // display or not Information level messages.

    public $t_keep                          = false;        // array of directory or file pathnames to keep 'as is' ...  i.e. not obfuscate.
    public $t_skip                          = false;        // array of directory or file pathnames to skip when exploring source tree structure ... they will not be on target!
    public $allow_and_overwrite_empty_files = false;        // allow empty files to be kept as is

    public $source_directory                = null;         // self explanatory
    public $target_directory                = null;         // self explanatory

    public $user_comment                    = null;         // user comment to insert inside each obfuscated file

    public $extract_comment_from_line       = null;         // when both 2 are set, each obfuscated file will contain an extract of the corresponding source file,
    public $extract_comment_to_line         = null;         // starting from extract_comment_from_line number, and endng at extract_comment_to_line line number.

    private $comment                        = '';

    function __construct()
    {
        $this->comment .= "/*   __________________________________________________".PHP_EOL;
        $this->comment .= "    |  Obfuscated by YAK Pro - Php Obfuscator  %-5.5s   |".PHP_EOL;
        $this->comment .= "    |              on %s              |".PHP_EOL;
        $this->comment .= "    |    GitHub: https://github.com/pk-fr/yakpro-po    |".PHP_EOL;
        $this->comment .= "    |__________________________________________________|".PHP_EOL;
        $this->comment .= "*/".PHP_EOL;
    }

    public function get_comment()
    {
        global $yakpro_po_version;
        $now = strftime("%F %T");

        return sprintf($this->comment,$yakpro_po_version,$now);
    }

    public function validate()
    {
        $this->shuffle_stmts_min_chunk_size += 0;
        if ($this->shuffle_stmts_min_chunk_size<1)  $this->shuffle_stmts_min_chunk_size = 1;
        
        $this->shuffle_stmts_chunk_ratio += 0;
        if ($this->shuffle_stmts_chunk_ratio<2)     $this->shuffle_stmts_chunk_ratio = 2;

        if ($this->shuffle_stmts_chunk_mode!='ratio') $this->shuffle_stmts_chunk_mode = 'fixed';
        
        if (!isset( $this->t_ignore_pre_defined_classes))                                                       $this->t_ignore_pre_defined_classes = 'all';
        if (!is_array($this->t_ignore_pre_defined_classes) && ( $this->t_ignore_pre_defined_classes != 'none')) $this->t_ignore_pre_defined_classes = 'all';
    }
}

?>
