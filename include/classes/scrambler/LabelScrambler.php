<?php

namespace Obfuscator\Classes\Scrambler;

use Obfuscator\Classes\Config;
use PhpParser\Node;
use PhpParser\Node\Stmt\Goto_;
use PhpParser\Node\Stmt\Label;

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

    /** @return LabelScrambler */
    public static function getScrambler(): static
    {
        return parent::$scramblers["label"];
    }

    public function isScrambled(Node $node): bool
    {
        return $node instanceof Label || $node instanceof Goto_;
    }

    /**
     * Scramble node using this scrambler
     *
     * @param Node $node
     * @return bool
     */
    public function scrambleNode(Node $node): bool
    {
        if (!($node instanceof Label || $node instanceof Goto_)) {
            return false;
        }

        return $this->scrambleNodeName($node);
    }
}
