<?php
namespace Obfuscator\Classes\Scrambler;

use Exception;
use Obfuscator\Classes\Config;
use Obfuscator\Classes\Exception\ScramblerException;
use PhpParser\Node;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;

use function count;

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

    /** @return FunctionOrClassScrambler */
    public static function getScrambler(): static
    {
        return parent::$scramblers["function_or_class"];
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
    public function scrambleFunctionNode(Node $node): bool
    {
        return match (get_class($node)) {
            Function_::class => $this->scrambleFnNode($node),
            FuncCall::class => $this->scrambleFuncCallNode($node),
            default => false,
        };
    }

    /**
     * Scramble node using this scrambler
     *
     * @param Node $node
     * @return bool
     */
    public function scrambleClassNode(Node $node): bool
    {
        return match (get_class($node)) {
            Class_::class => $this->scrambleClsNode($node),
            New_::class, StaticCall::class, StaticPropertyFetch::class, ClassConstFetch::class, Instanceof_::class, => $this->scrambleLastClassPart($node),
            Param::class => $this->scrambleLastTypePart($node),
            ClassMethod::class, Function_::class => $this->scrambleReturnType($node),
            Catch_::class => $this->scrambleCatchNode($node),
            default => false,
        };
    }

    /**
     * Scramble node using this scrambler
     *
     * @param Node $node
     * @return bool
     */
    public function scrambleInterfaceNode(Node $node): bool
    {
        return match (gettype($node)) {
            Interface_::class => $this->scrambleNodeName($node) || $this->scrambleExtends($node),
            Class_::class => $this->scrambleImplements($node),
            default => false,
        };
    }

    /**
     * Scramble node using this scrambler
     *
     * @param Node $node
     * @return bool
     */
    public function scrambleTraitNode(Node $node): bool
    {
        return match (gettype($node)) {
            Trait_::class => $this->scrambleNodeName($node),
            TraitUse::class => $this->scrambleUsedTrait($node),
            default => false,
        };
    }

    private function scrambleLastClassPart(Node $node): bool
    {
        if (isset($node->{'class'}->parts)) {
            $parts = $node->{'class'}->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $node->{'class'}->parts[count($parts) - 1] = $r;
                    return true;
                }
            }
        }

        return false;
    }

    private function scrambleLastTypePart(Node $node): bool
    {
        if (isset($node->type) && isset($node->type->parts)) {
            $parts = $node->type->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $node->type->parts[count($parts) - 1] = $r;
                    return true;
                }
            }
        }

        return false;
    }

    private function scrambleReturnType(Node $node): bool
    {
        if (!isset($node->returnType)) {
            return false;
        }

        $node_tmp = $node->returnType;
        if ($node_tmp instanceof NullableType && isset($node_tmp->type)) {
            $node_tmp = $node_tmp->type;
        }

        if ($node_tmp instanceof Name && isset($node_tmp->parts)) {
            $parts = $node_tmp->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $node_tmp->parts[count($parts) - 1] = $r;
                    return true;
                }
            }
        }

        return false;
    }

    private function scrambleExtends(Node $node): bool
    {
        if (!isset($node->{'extends'}) || count($node->{'extends'}) == 0) {
            return false;
        }

        foreach ($node->{'extends'} as &$extend) {
            $parts = $extend->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $extend->parts[count($parts) - 1] = $r;
                    return true;
                }
            }
        }

        return false;
    }

    private function scrambleImplements(Node $node): bool
    {
        if (!isset($node->{'implements'}) || count($node->{'implements'}) == 0) {
            return false;
        }

        for ($j = 0; $j < count($node->{'implements'}); ++$j) {
            $parts = $node->{'implements'}[$j]->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $node->{'implements'}[$j]->parts[count($parts) - 1] = $r;
                    return true;
                }
            }
        }

        return false;
    }

    private function scrambleUsedTrait(TraitUse $node): bool
    {
        if (!isset($node->{'traits'}) || count($node->{'traits'}) == 0) {
            return false;
        }

        foreach ($node->{'traits'} as &$trait) {
            $parts = $trait->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $trait->parts[count($parts) - 1] = $r;
                    return true;
                }
            }
        }

        return false;
    }

    private function scrambleCatchNode(Catch_ $node): bool
    {
        if (!isset($node->types)) {
            return false;
        }

        foreach ($node->types as &$type) {
            $parts = $type->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $type->parts[count($parts) - 1] = $r;
                    return true;
                }
            }
        }

        return false;
    }

    private function scrambleClsNode(Class_ $node): bool
    {
        if ($node->name != null) {
            $node_modified = parent::scrambleNodeName($node);
        }

        if (isset($node->{'extends'})) {
            $parts = $node->{'extends'}->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $node->{'extends'}->parts[count($parts) - 1] = $r;
                    $node_modified = true;
                }
            }
        }
    }

    private function scrambleFnNode(Function_ $node): bool
    {
        $name = $node->name->name;
        if (is_string($name) && (strlen($name) !== 0)) {
            $r = parent::scramble($name);
            if ($r !== $name) {
                $node->name = $r;
                return true;
            }
        }

        return false;
    }

    private function scrambleFuncCallNode(Node $node)
    {
        if (isset($node->name->parts)) {              // not set when indirect call (i.e.function name is a variable value!)
            $parts = $node->name->parts;
            $name = $parts[count($parts) - 1];
            if (is_string($name) && (strlen($name) !== 0)) {
                $r = parent::scramble($name);
                if ($r !== $name) {
                    $node->name->parts[count($parts) - 1] = $r;
                    $node_modified = true;
                }
            }

            //special handling for function_exists func
            if (is_string($name) && ($name == 'function_exists')) {
                if (!isset($node->args[0]->value)) {
                    throw new ScramblerException("$name() must have a an argument to be set to be compatible with yakpro-po");
                }
                if (count($node->args) != 1) {
                    throw new ScramblerException("$name() must have exactly 1 literal-string argument to be compatible with yakpro-po");
                }
                $arg = $node->args[0]->value;
                if (!($arg instanceof String_)) {
                    throw new ScramblerException("$name() must have exactly 1 literal-string argument to be compatible with yakpro-po");
                }
                $name = $arg->value;
                if (!is_string($name) || (strlen($name) == 0)) {
                    throw new Exception("$name() must have a non-empty string argument to be compatible with yakpro-po");
                }

                $r = parent::scramble($name);
                if ($r !== $name) {
                    $arg->value = $r;
                    $node_modified = true;
                }
            }
        }
    }
}
