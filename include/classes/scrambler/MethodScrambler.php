<?php

namespace Obfuscator\Classes\Scrambler;

/**
 * Description of MethodScrambler
 *
 * @author kminekmatej
 */
class MethodScrambler extends AbstractScrambler
{

    protected bool $case_sensitive = false;

    public function __construct(\stdClass $conf, ?string $target_directory)
    {
        global $t_pre_defined_class_methods;
        global $t_pre_defined_class_methods_by_class;
        
        parent::__construct($conf, $target_directory);

        if ($conf->parser_mode == 'ONLY_PHP7') {
            $this->t_ignore = [];      // in php7 method names can be keywords
        } else {
            $this->t_ignore = array_flip(self::RESERVED_FUNCTION_NAMES);
        }

        $this->t_ignore += array_flip(self::RESERVED_METHOD_NAMES);

        $this->t_ignore += parent::flipToLowerCase(get_defined_functions()['internal']);

        if ($conf->t_ignore_pre_defined_classes != 'none') {
            if ($conf->t_ignore_pre_defined_classes == 'all') {
                $this->t_ignore += $t_pre_defined_class_methods;
            }

            if (is_array($conf->t_ignore_pre_defined_classes)) {
                $t_class_names = array_map('strtolower', $conf->t_ignore_pre_defined_classes);
                foreach ($t_class_names as $class_name) {
                    if (isset($t_pre_defined_class_methods_by_class[$class_name])) {
                        $this->t_ignore += $t_pre_defined_class_methods_by_class[$class_name];
                    }
                }
            }
        }

        if (isset($conf->t_ignore_methods)) {
            $this->t_ignore += parent::flipToLowerCase($conf->t_ignore_methods);
        }
        if (isset($conf->t_ignore_methods_prefix)) {
            $this->t_ignore_prefix += parent::flipToLowerCase($conf->t_ignore_methods_prefix);
        }
    }

    protected function getScrambleType(): string
    {
        return "method";
    }
}
