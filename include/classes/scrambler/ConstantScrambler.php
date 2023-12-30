<?php

namespace Obfuscator\Classes\Scrambler;

use Obfuscator\Classes\Config;

/**
 * Description of ConstantScrambler
 *
 * @author kminekmatej
 */
class ConstantScrambler extends AbstractScrambler
{

    public function __construct(Config $conf, ?string $target_directory)
    {
        parent::__construct($conf, $target_directory);

        $this->t_ignore = array_flip(self::RESERVED_FUNCTION_NAMES);
        $this->t_ignore = array_merge($this->t_ignore, get_defined_constants(false));

        if (isset($conf->t_ignore_constants)) {
            $this->t_ignore += array_flip($conf->t_ignore_constants);
        }

        if (isset($conf->t_ignore_constants_prefix)) {
            $this->t_ignore_prefix = array_flip($conf->t_ignore_constants_prefix);
        }
    }

    protected function getScrambleType(): string
    {
        return "constant";
    }

    public static function getScrambler(): ConstantScrambler
    {
        return parent::$scramblers[$this->getScrambleType()];
    }
}
