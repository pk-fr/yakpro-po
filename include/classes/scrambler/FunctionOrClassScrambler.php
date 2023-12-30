<?php
namespace Obfuscator\Classes\Scrambler;

use Obfuscator\Classes\Config;

/**
 * Same instance is used for scrambling classes, interfaces, and traits.  and namespaces... and functions ...for aliasing
 *
 * @author kminekmatej
 */
class FunctionOrClassScrambler extends AbstractScrambler
{

    protected bool $case_sensitive = false;

    public function __construct(Config $conf, ?string $target_directory)
    {
        global $t_pre_defined_classes;

        parent::__construct($conf, $target_directory);

        $this->t_ignore = array_flip(self::RESERVED_FUNCTION_NAMES) +
            parent::flipToLowerCase(get_defined_functions()['internal']);

        if (isset($conf->t_ignore_functions)) {
            $this->t_ignore += parent::flipToLowerCase($conf->t_ignore_functions);
        }

        if (isset($conf->t_ignore_functions_prefix)) {
            $this->t_ignore_prefix = parent::flipToLowerCase($conf->t_ignore_functions_prefix);
        }

        $this->t_ignore += array_flip(self::RESERVED_CLASS_NAMES);
        $this->t_ignore += array_flip(self::RESERVED_VARIABLE_NAMES);

        if ($conf->t_ignore_pre_defined_classes != 'none') {
            if ($conf->t_ignore_pre_defined_classes == 'all') {
                $this->t_ignore += array_merge($this->t_ignore, $t_pre_defined_classes);
            }
            if (is_array($conf->t_ignore_pre_defined_classes)) {
                $t_class_names = array_map('strtolower', $conf->t_ignore_pre_defined_classes);

                foreach ($t_class_names as $class_name) {
                    if (isset($t_pre_defined_classes[$class_name])) {
                        $this->t_ignore[$class_name] = 1;
                    }
                }
            }
        }

        if (isset($conf->t_ignore_classes)) {
            $this->t_ignore += parent::flipToLowerCase($conf->t_ignore_classes);
        }

        if (isset($conf->t_ignore_interfaces)) {
            $this->t_ignore += parent::flipToLowerCase($conf->t_ignore_interfaces);
        }
        if (isset($conf->t_ignore_traits)) {
            $this->t_ignore += parent::flipToLowerCase($conf->t_ignore_traits);
        }
        if (isset($conf->t_ignore_namespaces)) {
            $this->t_ignore += parent::flipToLowerCase($conf->t_ignore_namespaces);
        }
        if (isset($conf->t_ignore_classes_prefix)) {
            $this->t_ignore_prefix += parent::flipToLowerCase($conf->t_ignore_classes_prefix);
        }
        if (isset($conf->t_ignore_interfaces_prefix)) {
            $this->t_ignore_prefix += parent::flipToLowerCase($conf->t_ignore_interfaces_prefix);
        }
        if (isset($conf->t_ignore_traits_prefix)) {
            $this->t_ignore_prefix += parent::flipToLowerCase($conf->t_ignore_traits_prefix);
        }
        if (isset($conf->t_ignore_namespaces_prefix)) {
            $this->t_ignore_prefix += parent::flipToLowerCase($conf->t_ignore_namespaces_prefix);
        }
    }

    protected function getScrambleType(): string
    {
        return "function_or_class";
    }

    public static function getScrambler(): FunctionOrClassScrambler
    {
        return parent::$scramblers[$this->getScrambleType()];
    }
}
