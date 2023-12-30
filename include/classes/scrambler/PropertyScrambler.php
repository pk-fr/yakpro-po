<?php

namespace Obfuscator\Classes\Scrambler;

use Obfuscator\Classes\Config;

/**
 * Description of PropertyScrambler
 *
 * @author kminekmatej
 */
class PropertyScrambler extends AbstractScrambler
{

    public function __construct(Config $conf, ?string $target_directory)
    {
        global $t_pre_defined_class_properties;
        global $t_pre_defined_class_properties_by_class;

        parent::__construct($conf, $target_directory);

        $this->t_ignore = array_flip(self::RESERVED_VARIABLE_NAMES);
        
        if ($conf->t_ignore_pre_defined_classes != 'none') {
            if ($conf->t_ignore_pre_defined_classes == 'all') {
                $this->t_ignore += $t_pre_defined_class_properties;
            }

            if (is_array($conf->t_ignore_pre_defined_classes)) {
                $t_class_names = array_map('strtolower', $conf->t_ignore_pre_defined_classes);
                foreach ($t_class_names as $class_name) {
                    if (isset($t_pre_defined_class_properties_by_class[$class_name])) {
                        $this->t_ignore += $t_pre_defined_class_properties_by_class[$class_name];
                    }
                }
            }
        }

        if (isset($conf->t_ignore_properties)) {
            $this->t_ignore += array_flip($conf->t_ignore_properties);
        }

        if (isset($conf->t_ignore_properties_prefix)) {
            $this->t_ignore_prefix = array_flip($conf->t_ignore_properties_prefix);
        }
    }

    protected function getScrambleType(): string
    {
        return "property";
    }

    /** @return PropertyScrambler */
    public static function getScrambler(): static
    {
        return parent::$scramblers["property"];
    }
}
