<?php

namespace Obfuscator\Classes\Scrambler;

/**
 * Description of VariableScrambler
 *
 * @author kminekmatej
 */
class VariableScrambler extends AbstractScrambler
{

    public function __construct(\stdClass $conf, ?string $target_directory)
    {
        parent::__construct($conf, $target_directory);
        $this->t_ignore = array_flip(self::RESERVED_VARIABLE_NAMES);

        if (isset($conf->t_ignore_variables)) {
            $this->t_ignore += array_flip($conf->t_ignore_variables);
        }

        if (isset($conf->t_ignore_variables_prefix)) {
            $this->t_ignore_prefix = array_flip($conf->t_ignore_variables_prefix);
        }
    }

    protected function getScrambleType(): string
    {
        return "variable";
    }
}
