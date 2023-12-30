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

// case-sensitive:      variable names, constant name, array keys, class properties, labels
// case-insensitive:    function names, class names, class method names, namespaces, keywords and constructs
// classes, interfaces, and traits share the same internal naming_space! only a single Scrambler instance for all of them!

namespace Obfuscator\Classes\Scrambler;

use function count;

abstract class AbstractScrambler
{
    private const SCRAMBLER_CONTEXT_VERSION = '1.1';

    private $t_first_chars          = null;     // allowed first char of a generated identifier
    private $t_chars                = null;     // allowed all except first char of a generated identifier
    private $l1                     = null;     // length of $t_first_chars string
    private $l2                     = null;     // length of $t_chars       string
    private $r                      = null;     // seed and salt for random char generation, modified at each iteration.
    protected bool $case_sensitive = true;      // case sensitive indication - scrambles override this property if case sensitive should be false
    private $scramble_mode          = null;     // allowed modes are 'identifier', 'hexa', 'numeric'
    private $scramble_length        = null;     // current length of scrambled names
    private $scramble_length_min    = null;     // min     length of scrambled names
    private $scramble_length_max    = null;     // max     length of scrambled names

    /** @var array|null array where keys are names to ignore */
    protected ?array $t_ignore = null;

    /** @var array|null array where keys are prefix of names to ignore */
    protected ?array $t_ignore_prefix = null;
    private $t_scramble             = [];     // array of scrambled items (key = source name , value = scrambled name)
    private $t_rscramble            = null;     // array of reversed scrambled items (key = scrambled name, value = source name)
    private $context_directory      = null;     // where to save/restore context
    private $silent                 = null;     // display or not Information level messages.
    private $label_counter          =    0;     // internal label counter.

    protected const RESERVED_VARIABLE_NAMES = array( 'this','GLOBALS','_SERVER', '_GET', '_POST', '_FILES', '_COOKIE','_SESSION', '_ENV', '_REQUEST',
                                                'php_errormsg','HTTP_RAW_POST_DATA','http_response_header','argc','argv'
                                              );

    protected const RESERVED_FUNCTION_NAMES = array( '__halt_compiler','__autoload', 'abstract', 'and', 'array', 'as', 'bool', 'break', 'callable', 'case', 'catch',
                                                'class', 'clone', 'const', 'continue', 'declare', 'default', 'die', 'do', 'echo', 'else',
                                                'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile',
                                                'eval', 'exit', 'extends', 'false', 'final', 'finally', 'float', 'for', 'foreach', 'function', 'global', 'goto', 'if','fn',
                                                'implements', 'include', 'include_once', 'instanceof', 'insteadof', 'int', 'interface', 'isset', 'list',
                                                'namespace', 'new', 'null', 'or', 'print', 'private', 'protected', 'public', 'require', 'require_once',
                                                'return', 'static', 'string', 'switch', 'throw', 'trait', 'true', 'try', 'unset', 'use', 'var', 'while', 'xor','yield',
                                                'apache_request_headers'                        // seems that it is not included in get_defined_functions ..
                                              );

    /**
     * same reserved names for classes, interfaces  and traits...
     */
    protected const RESERVED_CLASS_NAMES     = array('parent', 'self', 'static',
                                                'int', 'float', 'bool', 'string', 'true', 'false', 'null', 'void', 'iterable', 'object',  'resource', 'scalar', 'mixed', 'numeric','fn'
                                               );

    protected const RESERVED_METHOD_NAMES    = array('__construct', '__destruct', '__call', '__callstatic', '__get', '__set', '__isset', '__unset', '__sleep', '__wakeup', '__tostring', '__invoke', '__set_state', '__clone', '__debuginfo');

    abstract protected function getScrambleType(): string;     // type on which scrambling is done (i.e. variable, function, etc.)

    /**
     * Helper funtion to automatically lowercase all values and flip values and keys.
     * Result then contain array having lowercase keys
     *
     * @param array $inputArray
     * @return array
     */
    protected static function flipToLowerCase(array $inputArray): array
    {
        return array_flip(array_map('strtolower', $inputArray));
    }

    public function __construct(\stdClass $conf, ?string $target_directory)
    {
        $this->t_first_chars        = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $this->t_chars              = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
        $this->r                    = md5(microtime(true));     // random seed
        $this->t_scramble           = array();
        $this->silent               = $conf->silent;
        if (isset($conf->scramble_mode)) {
            switch ($conf->scramble_mode) {
                case 'numeric':
                    $this->scramble_length_max  = 32;
                    $this->scramble_mode        = $conf->scramble_mode;
                    $this->t_first_chars        = 'O';
                    $this->t_chars              = '0123456789';
                    break;
                case 'hexa':
                    $this->scramble_length_max  = 32;
                    $this->scramble_mode = $conf->scramble_mode;
                    $this->t_first_chars = 'abcdefABCDEF';
                    break;
                case 'identifier':
                default:
                    $this->scramble_length_max  = 16;
                    $this->scramble_mode = 'identifier';
            }
        }
        $this->l1                   = strlen($this->t_first_chars) - 1;
        $this->l2                   = strlen($this->t_chars) - 1;
        $this->scramble_length_min  = 2;
        $this->scramble_length      = 5;
        if (isset($conf->scramble_length)) {
            $conf->scramble_length += 0;
            if (($conf->scramble_length >= $this->scramble_length_min) && ($conf->scramble_length <= $this->scramble_length_max)) {
                $this->scramble_length = $conf->scramble_length;
            }
        }

        if (isset($target_directory)) {                                   // the constructor will restore previous saved context if exists
            $this->context_directory = $target_directory;
            if (file_exists("{$this->context_directory}/yakpro-po/context/{$this->getScrambleType()}")) {
                $t = unserialize(file_get_contents("{$this->context_directory}/yakpro-po/context/{$this->getScrambleType()}"));
                if ($t[0] !== self::SCRAMBLER_CONTEXT_VERSION) {
                    fprintf(STDERR, "Error:\tContext format has changed! run with --clean option!" . PHP_EOL);
                    $this->context_directory = null;        // do not overwrite incoherent values when exiting
                    exit(1);
                }
                $this->t_scramble       = $t[1];
                $this->t_rscramble      = $t[2];
                $this->scramble_length  = $t[3];
                $this->label_counter    = $t[4];
            }
        }
    }

    public function __destruct()
    {
        //print_r($this->t_scramble);
        if (!$this->silent) {
            fprintf(STDERR, "Info:\t[%-17s] scrambled \t: %8d%s", $this->getScrambleType(), count($this->t_scramble), PHP_EOL);
        }
        if (isset($this->context_directory)) {                            // the destructor will save the current context
            $t      = array();
            $t[0]   = self::SCRAMBLER_CONTEXT_VERSION;
            $t[1]   = $this->t_scramble;
            $t[2]   = $this->t_rscramble;
            $t[3]   = $this->scramble_length;
            $t[4]   = $this->label_counter;
            file_put_contents("{$this->context_directory}/yakpro-po/context/{$this->getScrambleType()}", serialize($t));
        }
    }

    private function strScramble($s)                                   // scramble the string according parameters
    {
        $c1         = $this->t_first_chars[mt_rand(0, $this->l1)];      // first char of the identifier
        $c2         = $this->t_chars      [mt_rand(0, $this->l2)];      // prepending salt for md5
        $this->r    = str_shuffle(md5($c2 . $s . md5($this->r)));           // 32 chars random hex number derived from $s and lot of pepper and salt

        $s  = $c1;
        switch ($this->scramble_mode) {
            case 'numeric':
                for ($i = 0,$l = $this->scramble_length - 1; $i < $l; ++$i) {
                    $s .= $this->t_chars[base_convert(substr($this->r, $i, 2), 16, 10) % ($this->l2 + 1)];
                }
                break;
            case 'hexa':
                for ($i = 0,$l = $this->scramble_length - 1; $i < $l; ++$i) {
                    $s .= substr($this->r, $i, 1);
                }
                break;
            case 'identifier':
            default:
                for ($i = 0,$l = $this->scramble_length - 1; $i < $l; ++$i) {
                    $s .= $this->t_chars[base_convert(substr($this->r, 2 * $i, 2), 16, 10) % ($this->l2 + 1)];
                }
        }
        return $s;
    }

    private function caseShuffle($s)   // this function is used to even more obfuscate insensitive names: on each acces to the name, a different randomized case of each letter is used.
    {
        for ($i = 0; $i < strlen($s); ++$i) {
            $s[$i] = mt_rand(0, 1) ? strtoupper($s[$i]) : strtolower($s[$i]);
        }
        return $s;
    }

    public function scramble($s)
    {
        $r = $this->case_sensitive ? $s : strtolower($s);
        if (array_key_exists($r, $this->t_ignore)) {
            return $s;
        }

        if (isset($this->t_ignore_prefix)) {
            foreach ($this->t_ignore_prefix as $key => $dummy) {
                if (substr($r, 0, strlen($key)) === $key) {
                    return $s;
                }
            }
        }

        if (!isset($this->t_scramble[$r])) {      // if not already scrambled:
            for ($i = 0; $i < 50; ++$i) {                // try at max 50 times if the random generated scrambled string has already beeen generated!
                $x = $this->strScramble($s);
                $z = strtolower($x);
                $y = $this->case_sensitive ? $x : $z;
                if (isset($this->t_rscramble[$y]) || isset($this->t_ignore[$z])) {           // this random value is either already used or a reserved name
                    if (($i == 5) && ($this->scramble_length < $this->scramble_length_max)) {
                        ++$this->scramble_length;    // if not found after 5 attempts, increase the length...
                    }
                    continue;                                                                                           // the next attempt will always be successfull, unless we already are maxlength
                }
                $this->t_scramble [$r] = $y;
                $this->t_rscramble[$y] = $r;
                break;
            }
            if (!isset($this->t_scramble[$r])) {
                fprintf(STDERR, "Scramble Error: Identifier not found after 50 iterations!%sAborting...%s", PHP_EOL, PHP_EOL); // should statistically never occur!
                exit(2);
            }
        }
        return $this->case_sensitive ? $this->t_scramble[$r] : $this->caseShuffle($this->t_scramble[$r]);
    }

    public function unscramble($s)
    {
        if (!$this->case_sensitive) {
            $s = strtolower($s);
        }
        return isset($this->t_rscramble[$s]) ? $this->t_rscramble[$s] : '';
    }

    public function generateLabelName($prefix = "!label")
    {
        return $prefix . ($this->label_counter++);
    }
    
    public function getT_ignore()
    {
        return $this->t_ignore;
    }

    public function getT_ignore_prefix()
    {
        return $this->t_ignore_prefix;
    }
}
