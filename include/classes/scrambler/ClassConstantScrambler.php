<?php

namespace Obfuscator\Classes\Scrambler;

use Obfuscator\Classes\Config;

/**
 * Description of ClassConstantScrambler
 *
 * @author kminekmatej
 */
class ClassConstantScrambler extends AbstractScrambler
{

    public function __construct(Config $conf, ?string $target_directory)
    {
        global $t_pre_defined_class_constants;
        global $t_pre_defined_class_constants_by_class;

        parent::__construct($conf, $target_directory);

        $this->t_ignore = array_flip(self::RESERVED_FUNCTION_NAMES) + get_defined_constants(false);

        if ($conf->t_ignore_pre_defined_classes != 'none') {
            if ($conf->t_ignore_pre_defined_classes == 'all') {
                $this->t_ignore += $t_pre_defined_class_constants;
            }

            if (is_array($conf->t_ignore_pre_defined_classes)) {
                $t_class_names = array_map('strtolower', $conf->t_ignore_pre_defined_classes);
                foreach ($t_class_names as $class_name) {
                    if (isset($t_pre_defined_class_constants_by_class[$class_name])) {
                        $this->t_ignore += $t_pre_defined_class_constants_by_class[$class_name];
                    }
                }
            }
        }
        
        if (isset($conf->t_ignore_class_constants)) {
            $this->t_ignore += array_flip($conf->t_ignore_class_constants);
        }
        
        if (isset($conf->t_ignore_class_constants_prefix)) {
            $this->t_ignore_prefix = array_flip($conf->t_ignore_class_constants_prefix);
        }
    }

    protected function getScrambleType(): string
    {
        return "class_constant";
    }

    /** @return ClassConstantScrambler */
    public static function getScrambler(): static
    {
        return parent::$scramblers[$this->getScrambleType()];
    }
}
