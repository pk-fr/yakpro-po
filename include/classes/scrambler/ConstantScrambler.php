<?php

namespace Obfuscator\Classes\Scrambler;

use Exception;
use Obfuscator\Classes\Config;
use Obfuscator\Classes\Exception\ScramblerException;
use PhpParser\Node;
use PhpParser\Node\Const_;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;

use function count;

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

    /** @return ConstantScrambler */
    public static function getScrambler(): static
    {
        return parent::$scramblers["constant"];
    }

    public function isScrambled(Node $node): bool
    {
        return $node instanceof FuncCall;
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
            FuncCall::class => $this->scrambleFuncCallNode($node),
            ConstFetch::class => $this->scrambleConstFetchNode($node),
            Const_::class => $this->scrambleConstNode($node),
            default => false,
        };
    }

    /**
     * @param FuncCall $node
     * @return bool True if Node has been modified
     * @throws Exception
     */
    private function scrambleFuncCallNode(FuncCall $node): bool
    {
        if (! isset($node->name->parts)) {                      // not set when indirect call (i.e.function name is a variable value!)
            return false;
        }
        $parts = $node->name->parts;

        $fn_name = $parts[count($parts) - 1];
        if (!is_string($fn_name) || !in_array($fn_name, ["define", "defined"])) {
            return false;
        }

        if (!isset($node->args[0]->value)) {
            throw new ScramblerException("$fn_name() must have a an argument to be set to be compatible with yakpro-po");
        }
        if (($fn_name == 'define') && (count($node->args) != 2)) {
            throw new ScramblerException("$fn_name() must have 2 arguments (when first is a literal string) to be compatible with yakpro-po");
        }
        $arg = $node->args[0]->value;
        if (!($arg instanceof String_)) {
            throw new ScramblerException("$fn_name() must have a literal-string argument to be compatible with yakpro-po");
        }
        $name = $arg->value;
        if (!is_string($name) || (strlen($name) == 0)) {
            throw new Exception("$fn_name() must have a non-empty string argument to be compatible with yakpro-po");
        }

        $r = parent::scramble($name);
        if ($r !== $name) {
            $arg->value = $r;
            return true;
        }

        return false;
    }

    /**
     * @param ConstFetch $node
     * @return bool True if Node has been modified
     */
    private function scrambleConstFetchNode(ConstFetch $node): bool
    {
        $parts = $node->name->parts;
        $name = $parts[count($parts) - 1];
        if (is_string($name) && (strlen($name) !== 0)) {
            $r = parent::scramble($name);
            if ($r !== $name) {
                $node->name->parts[count($parts) - 1] = $r;
                return true;
            }
        }

        return false;
    }
    
    private function scrambleConstNode(Const_ $node): bool
    {
        $name = $this->getIdentifierName($node->name);
        if (is_string($name) && (strlen($name) !== 0)) {
            $r = parent::scramble($name);
            if ($r !== $name) {
                $this->setIdentifierName($node->name, $r);
                return true;
            }
        }

        return false;
    }
}
