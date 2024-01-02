<?php

namespace Obfuscator\Classes\Scrambler;

use Obfuscator\Classes\Config;
use PhpParser\Node;

/**
 * Description of VariableScrambler
 *
 * @author kminekmatej
 */
class VariableScrambler extends AbstractScrambler
{
    public function __construct(Config $conf, ?string $target_directory)
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

    /** @return VariableScrambler */
    public static function getScrambler(): static
    {
        return parent::$scramblers["variable"];
    }

    public function isScrambled(Node $node): bool
    {
        return true;
    }

    /**
     * Scramble node using this scrambler
     *
     * @param Node $node
     * @return bool
     */
    public function scrambleNode(Node $node): bool
    {
        return match (get_class($node)) {
            Variable::class => $this->scrambleVariableNode($node),
            Catch_::class,ClosureUse::class,Param::class, => $this->scrambleNodesUsingVar($node),
            default => false,
        };
    }

    private function scrambleVariableNode(Variable $node): bool
    {
        $name = $node->name;
        if (is_string($name) && (strlen($name) !== 0)) {
            $r = parent::scramble($name);
            if ($r !== $name) {
                $node->name = $r;
                return true;
            }
        }

        return false;
    }

    private function scrambleNodesUsingVar(Node $node): bool
    {
        $name = $node->{'var'};                             // equivalent to $node->var, that works also on my php version!
        if (is_string($name) && (strlen($name) !== 0)) {    // but 'var' is a reserved function name, so there is no warranty
            // that it will work in the future, so the $node->{'var'} form
            $r = parent::scramble($name);                   // has been used!
            if ($r !== $name) {
                $node->{'var'} = $r;
                return true;
            }
        }

        return false;
    }
}
