<?php

namespace Obfuscator\Classes\Scrambler;

use Obfuscator\Classes\Config;

/**
 * Description of LabelScrambler
 *
 * @author kminekmatej
 */
class LabelScrambler extends AbstractScrambler
{

    public function __construct(Config $conf, ?string $target_directory)
    {
        parent::__construct($conf, $target_directory);

        $this->t_ignore = array_flip(self::RESERVED_FUNCTION_NAMES);

        if (isset($conf->t_ignore_labels)) {
            $this->t_ignore += array_flip($conf->t_ignore_labels);
        }

        if (isset($conf->t_ignore_labels_prefix)) {
            $this->t_ignore_prefix += array_flip($conf->t_ignore_labels_prefix);
        }
    }

    protected function getScrambleType(): string
    {
        return "label";
    }

    public static function getScrambler(): LabelScrambler
    {
        return parent::$scramblers[$this->getScrambleType()];
    }
}
