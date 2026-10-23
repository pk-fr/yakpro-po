<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  https://pascal.kissian.net
//
// Copyright (c) 2015-2026 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================

class MyNodeVisitor extends PhpParser\NodeVisitorAbstract       // all parsing and replacement of scrambled names is done here!
{                                                               // see PHP-Parser for documentation!
    private $t_loop_stack                   = array();
    private $t_node_stack                   = array();
    private $current_class_name             = null;
    private $is_in_class_const_definition   = false;

    private function shuffle_stmts(PhpParser\Node &$node)
    {
        global $conf;
        if ($conf->shuffle_stmts)
        {
            if (isset($node->stmts))
            {
                $stmts              = $node->stmts;
                $chunk_size = shuffle_get_chunk_size($stmts);
                if ($chunk_size<=0)                 return false; // should never occur!
                
                if (count($stmts)>(2*$chunk_size))
                {
                    //    $last_inst      = array_pop($stmts);
                    $stmts          = shuffle_statements($stmts);
                    //    $stmts[]        = $last_inst;
                    $node->stmts    = $stmts;
                    return  true;
                }
            }
        }
        return false;
    }
    
    private function get_identifier_name(PhpParser\Node $node)
    {
        if ($node instanceof PhpParser\Node\Identifier || $node instanceof PhpParser\Node\VarLikeIdentifier) return $node->name;
        return '';
    }
    
    private function set_identifier_name(PhpParser\Node &$node,$name)
    {
        if ($node instanceof PhpParser\Node\Identifier || $node instanceof PhpParser\Node\VarLikeIdentifier)
        {
            $node->name = $name;
        }
    }
    
    private function get_node_name(PhpParser\Node $node)
    {
        if ($node->name instanceof PhpParser\Node\Name)
        {
            $parts = explode('\\',$node->name);
            $name  = $parts[count($parts)-1];
            return $name;
        }
        return false;
    }

    private function scramble_name(&$scrambler, &$node)         // only last part
    {
        if ($node instanceof PhpParser\Node\Name || $node->name instanceof PhpParser\Node\Name)
        {
            if ($node       instanceof PhpParser\Node\Name) $tmp_node = $node;
            if ($node->name instanceof PhpParser\Node\Name) $tmp_node = $node->name;

            $parts = explode('\\',$tmp_node->name);
            $name  = $parts[count($parts)-1];
            if ( is_string($name) && (strlen($name) !== 0) )
            {
                $r = $scrambler->scramble($name);
                if ($r!==$name)
                {
                    $parts[count($parts)-1] = $r;
                    $tmp_node->name = implode('\\', $parts);
                    return true;
                }
            }
        }
        return false;
    }
    private function scramble_names(&$scrambler, &$node)        // all except last part
    {
        if ($node instanceof PhpParser\Node\Name || $node->name instanceof PhpParser\Node\Name)
        {
            if ($node       instanceof PhpParser\Node\Name) $tmp_node = $node;
            if ($node->name instanceof PhpParser\Node\Name) $tmp_node = $node->name;

            $node_modified = false;
            $parts = explode('\\',$tmp_node->name);
            for($i=0;$i<count($parts)-1;++$i)
            {
                $name  = $parts[$i];
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $parts[$i] = $r;
                        $node_modified = true;
                    }
                }
            }
            if ($node_modified)
            {
                $tmp_node->name = implode('\\', $parts);
                return true;
            }
        }
        return false;
    }
    private function scramble_all_names(&$scrambler, &$node)    // all pparts
    {
        if ($node instanceof PhpParser\Node\Name || $node->name instanceof PhpParser\Node\Name)
        {
            if ($node       instanceof PhpParser\Node\Name) $tmp_node = $node;
            if ($node->name instanceof PhpParser\Node\Name) $tmp_node = $node->name;
            
            $node_modified = false;
            $parts = explode('\\',$tmp_node->name);
            for($i=0;$i<count($parts);++$i)
            {
                $name  = $parts[$i];
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $parts[$i] = $r;
                        $node_modified = true;
                    }
                }
            }
            if ($node_modified)
            {
                $tmp_node->name = implode('\\', $parts);
                return true;
            }
        }
        return false;
    }

    private function get_type_names($type)                     // Name nodes contained in a type declaration (nullable, union, intersection, DNF)
    {
        if ($type instanceof PhpParser\Node\Name)           return array($type);
        if ($type instanceof PhpParser\Node\NullableType)   return $this->get_type_names($type->type);
        $t = array();
        if ( ($type instanceof PhpParser\Node\UnionType) || ($type instanceof PhpParser\Node\IntersectionType) )
        {
            foreach($type->types as $sub_type) $t = array_merge($t,$this->get_type_names($sub_type));
        }
        return $t;
    }

    private function get_declared_type_names(PhpParser\Node $node)
    {
        if ( ($node instanceof PhpParser\Node\Param) || ($node instanceof PhpParser\Node\Stmt\Property) || ($node instanceof PhpParser\Node\Stmt\ClassConst) ) return $this->get_type_names($node->type);
        if ($node instanceof PhpParser\Node\FunctionLike)   return $this->get_type_names($node->getReturnType());
        return array();
    }

    private function contains_declaration($stmts)               // declarations must not be moved out of a conditional block
    {
        foreach($stmts ?? array() as $stmt) if ( ($stmt instanceof PhpParser\Node\Stmt\Function_) || ($stmt instanceof PhpParser\Node\Stmt\ClassLike) ) return true;
        return false;
    }

    private function is_user_defined_callee(PhpParser\Node $node)   // named arguments: parameter names of php internal functions/methods must not be scrambled
    {                                                               // (cannot always be determined statically: method calls are resolved by method name)
        global $t_pre_defined_classes, $t_pre_defined_class_methods;
        if ($node instanceof PhpParser\Node\Expr\FuncCall)
        {
            if (!($node->name instanceof PhpParser\Node\Name)) return false;
            $name = $node->name->getLast();
            return !function_exists($name) || !(new ReflectionFunction($name))->isInternal();
        }
        $class = ($node instanceof PhpParser\Node\Attribute) ? $node->name : ($node->class ?? null);   // New_, StaticCall, Attribute
        if ($class instanceof PhpParser\Node\Stmt\Class_) return true;                                  // anonymous class
        if ($class instanceof PhpParser\Node\Name)
        {
            $name = strtolower($class->getLast());
            if ( ($name == 'self') || ($name == 'static') ) return true;
            if ($name != 'parent')                          return !isset($t_pre_defined_classes[$name]);
        }
        if (isset($node->name) && ($node->name instanceof PhpParser\Node\Identifier))                  // MethodCall, NullsafeMethodCall, parent::method()
        {
            return !isset($t_pre_defined_class_methods[strtolower($node->name->name)]);
        }
        return false;
    }

    private function unpromote_constructor_properties(PhpParser\Node\Stmt\ClassLike $node)
    {   // a promoted property shares its name with a constructor parameter, but properties and variables are scrambled separately:
        // __construct(public int $x) {...}   is rewritten as   public int $x;  __construct(int $x) { $this->x = $x; ... }
        $constructor = $node->getMethod('__construct');
        if ($constructor === null) return;
        $cloner      = new PhpParser\NodeTraverser(new PhpParser\NodeVisitor\CloningVisitor);  // type and attribute nodes must not be shared (they are scrambled in place)
        $properties  = $assignments = array();
        foreach($constructor->params as $param)
        {
            if (!$param->isPromoted()) continue;
            $name           = $param->var->name;
            $type           = isset($param->type) ? $cloner->traverse(array($param->type))[0] : null;
            $properties[]   = new PhpParser\Node\Stmt\Property($param->flags ?: PhpParser\Modifiers::PUBLIC, array(new PhpParser\Node\PropertyItem($name)), array(), $type, $cloner->traverse($param->attrGroups), $param->hooks);
            $fetch          = new PhpParser\Node\Expr\PropertyFetch(new PhpParser\Node\Expr\Variable('this'), $name);
            $variable       = new PhpParser\Node\Expr\Variable($name);
            $assignments[]  = new PhpParser\Node\Stmt\Expression($param->byRef ? new PhpParser\Node\Expr\AssignRef($fetch,$variable) : new PhpParser\Node\Expr\Assign($fetch,$variable));
            $param->flags   = 0;
            $param->hooks   = array();
        }
        if (!count($properties)) return;
        $constructor->stmts = array_merge($assignments, $constructor->stmts ?? array());
        array_splice($node->stmts, array_search($constructor, $node->stmts, true), 0, $properties);   // keep declaration order of properties
    }

    public function enterNode(PhpParser\Node $node)
    {
        global $conf;
        global $t_scrambler;

        if (count($this->t_node_stack))
        {
            $node->setAttribute('parent', $this->t_node_stack[count($this->t_node_stack)-1]);
        }
        $this->t_node_stack[] = $node;

        if ( ($conf->obfuscate_property_name || $conf->obfuscate_variable_name) && ( ($node instanceof PhpParser\Node\Stmt\Class_) || ($node instanceof PhpParser\Node\Stmt\Trait_) ) )
        {
            $this->unpromote_constructor_properties($node);
        }

        if ($conf->obfuscate_loop_statement)                    // loop statements  are replaced by goto ...
        {
            $scrambler = $t_scrambler['label'];
            if (   ($node instanceof PhpParser\Node\Stmt\For_)   || ($node instanceof PhpParser\Node\Stmt\Foreach_) || ($node instanceof PhpParser\Node\Stmt\Switch_)
                || ($node instanceof PhpParser\Node\Stmt\While_) || ($node instanceof PhpParser\Node\Stmt\Do_) )
            {
                $label_loop_break_name      = $scrambler->scramble($scrambler->generate_label_name());
                $label_loop_continue_name   = $scrambler->scramble($scrambler->generate_label_name());
                $this->t_loop_stack[] = array($label_loop_break_name,$label_loop_continue_name);
           }
        }
        if ( ($node instanceof PhpParser\Node\Stmt\Class_) && ($node->name != null) )
        {
            $name = $this->get_identifier_name($node->name);
            if ( is_string($name) && (strlen($name) !== 0) )
            {
                $this->current_class_name = $name;
            }
        }
        if ($node instanceof PhpParser\Node\Stmt\ClassConst)
        {
            $this->is_in_class_const_definition = true;
        }
    }

    public function leaveNode(PhpParser\Node $node)
    {
        global $conf;
        global $t_scrambler;
        global $debug_mode;

        $node_modified = false;

        if ($node instanceof PhpParser\Node\Stmt\Class_)             $this->current_class_name = null;
        if ($node instanceof PhpParser\Node\Stmt\ClassConst)         $this->is_in_class_const_definition = false;

        if ($conf->obfuscate_string_literal)
        {
            if ($node instanceof PhpParser\Node\Stmt\InlineHTML)
            {
                $node = new PhpParser\Node\Stmt\Echo_([new PhpParser\Node\Scalar\String_($node->value)]);
                $node_modified = true;
            }
        }
        
        if ($conf->obfuscate_variable_name)
        {
            $scrambler = $t_scrambler['variable'];
            if ($node instanceof PhpParser\Node\Expr\Variable)
            {
                $name = $node->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name = $r;
                        $node_modified = true;
                    }
                }
            }
            if ( ($node instanceof PhpParser\Node\Stmt\Catch_) || ($node instanceof PhpParser\Node\ClosureUse) || ($node instanceof PhpParser\Node\Param) )
            {
                $name = $node->{'var'};                             // equivalent to $node->var, that works also on my php version!
                if ( is_string($name) && (strlen($name) !== 0) )    // but 'var' is a reserved function name, so there is no warranty
                {                                                   // that it will work in the future, so the $node->{'var'} form
                    $r = $scrambler->scramble($name);               // has been used!
                    if ($r!==$name)
                    {
                        $node->{'var'} = $r;
                        $node_modified = true;
                    }
                }
            }
            if ( ($node instanceof PhpParser\Node\Expr\CallLike) || ($node instanceof PhpParser\Node\Attribute) )
            {
                foreach($node->args as $arg)                        // named arguments must match the scrambled parameter names
                {
                    if ( !($arg instanceof PhpParser\Node\Arg) || !isset($arg->name) ) continue;
                    if (!$this->is_user_defined_callee($node))  break;
                    $r = $scrambler->scramble($arg->name->name);
                    if ($r!==$arg->name->name)
                    {
                        $arg->name->name = $r;
                        $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_function_name)
        {
            $scrambler = $t_scrambler['function_or_class'];
            if ($node instanceof PhpParser\Node\Stmt\Function_)
            {
                $name = $node->name->name;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name->name = $r;
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Expr\FuncCall )
            {
                // $node->name->parts was not set (prev version) when indirect call (i.e.function name is a variable value!)
                if ($this->scramble_name($scrambler, $node))  $node_modified = true;
                
                if ($this->get_node_name($node) == 'function_exists')
                {
                    for($ok=false;;)
                    {
                        if (!isset($node->args[0]->value))      break;
                        if (count($node->args)!=1)              break;
                        $arg = $node->args[0]->value;           if (! ($arg instanceof PhpParser\Node\Scalar\String_) ) { $ok = true; $warning = true; break; }
                        $name = $arg->value;                    if (! is_string($name) || (strlen($name) == 0) )        break;
                        $ok     = true;
                        $warning= false;
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $arg->value = $r;
                            $node_modified = true;
                        }
                        break;
                    }
                    if (!$ok)
                    {
                        throw new Exception("Error: your use of function_exists() function is not compatible with yakpro-po!".PHP_EOL."\tOnly 1 literal string parameter is allowed...");
                    }
                    if ($warning) fprintf(STDERR, "Warning: your use of function_exists() function is not compatible with yakpro-po!".PHP_EOL."\t Only 1 literal string parameter is allowed...".PHP_EOL);
               }
            }
        }

        if ($conf->obfuscate_class_name)
        {
            $scrambler = $t_scrambler['function_or_class'];
            if ( ($node instanceof PhpParser\Node\Stmt\Class_) || ($node instanceof PhpParser\Node\Stmt\Enum_) )
            {
                if ($node->name != null)
                {
                    $name = $this->get_identifier_name($node->name);
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $this->set_identifier_name($node->name,$r);
                            $node_modified = true;
                        }
                    }
                }
                if (isset($node->{'extends'}))
                {
                    if ($this->scramble_name($scrambler, $node->{'extends'}))  $node_modified = true;
                }
            }
            if (  ($node instanceof PhpParser\Node\Expr\New_)
               || ($node instanceof PhpParser\Node\Expr\StaticCall)
               || ($node instanceof PhpParser\Node\Expr\StaticPropertyFetch)
               || ($node instanceof PhpParser\Node\Expr\ClassConstFetch)
               || ($node instanceof PhpParser\Node\Expr\Instanceof_)
               )
            {
                if (isset($node->{'class'}))
                {
                    if ($this->scramble_name($scrambler, $node->{'class'}))  $node_modified = true;
                }
            }
            foreach($this->get_declared_type_names($node) as $type_name)       // parameter, return, property and class constant types
            {
                if ($this->scramble_name($scrambler, $type_name))  $node_modified = true;
            }
            if ($node instanceof PhpParser\Node\Attribute)
            {
                if ($this->scramble_name($scrambler, $node))  $node_modified = true;
            }
            if ($node instanceof PhpParser\Node\Stmt\Catch_)
            {
                if (isset($node->types))
                {
                    $types = $node->types;
                    foreach($types as &$type)
                    {
                        if ($this->scramble_name($scrambler, $type))  $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_interface_name)
        {
            $scrambler = $t_scrambler['function_or_class'];
            if ($node instanceof PhpParser\Node\Stmt\Interface_)
            {
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $this->set_identifier_name($node->name,$r);
                        $node_modified = true;
                    }
                }
                if ( isset($node->{'extends'}) && count($node->{'extends'}) )
                {
                    for($j=0;$j<count($node->{'extends'});++$j)
                    {
                        if ($this->scramble_name($scrambler, $node->{'extends'}[$j]))  $node_modified = true;
                    }
                }
            }
            if ( ($node instanceof PhpParser\Node\Stmt\Class_) || ($node instanceof PhpParser\Node\Stmt\Enum_) )
            {
                if ( isset($node->{'implements'}) && count($node->{'implements'}) )
                {
                    for($j=0;$j<count($node->{'implements'});++$j)
                    {
                        if ($this->scramble_name($scrambler, $node->{'implements'}[$j]))  $node_modified = true;
                    }
                }
            }
       }

        if ($conf->obfuscate_trait_name)
        {
            $scrambler = $t_scrambler['function_or_class'];
            if ($node instanceof PhpParser\Node\Stmt\Trait_)
            {
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $this->set_identifier_name($node->name,$r);
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\TraitUse)
            {
                if ( isset($node->{'traits'}) && count($node->{'traits'}) )
                {
                    for($j=0;$j<count($node->{'traits'});++$j)
                    {
                        if ($this->scramble_name($scrambler, $node->{'traits'}[$j]))  $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\TraitUseAdaptation)                // T::method insteadof U;  T::method as alias;
            {
                $t_names = ($node instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Precedence) ? $node->insteadof : array();
                if (isset($node->trait)) $t_names[] = $node->trait;
                foreach($t_names as $trait_name) if ($this->scramble_name($scrambler, $trait_name))  $node_modified = true;
            }
        }

        if ($conf->obfuscate_property_name)
        {
            $scrambler = $t_scrambler['property'];
            if ( ($node instanceof PhpParser\Node\Expr\PropertyFetch) || ($node instanceof PhpParser\Node\Expr\NullsafePropertyFetch) || ($node instanceof PhpParser\Node\PropertyItem) || ($node instanceof PhpParser\Node\Expr\StaticPropertyFetch) )
            {
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $this->set_identifier_name($node->name,$r);
                        $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_method_name)
        {
            $scrambler = $t_scrambler['method'];
            if ( ($node instanceof PhpParser\Node\Stmt\ClassMethod) || ($node instanceof PhpParser\Node\Expr\MethodCall) || ($node instanceof PhpParser\Node\Expr\NullsafeMethodCall) || ($node instanceof PhpParser\Node\Expr\StaticCall) )
            {
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $this->set_identifier_name($node->name,$r);
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\TraitUseAdaptation)                // T::method insteadof U;  T::method as alias;
            {
                foreach(array($node->method, $node->newName ?? null) as $identifier)
                {
                    if (!isset($identifier)) continue;
                    $r = $scrambler->scramble($identifier->name);
                    if ($r!==$identifier->name)
                    {
                        $identifier->name = $r;
                        $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_constant_name)
        {
            $scrambler = $t_scrambler['constant'];
            if ($node instanceof PhpParser\Node\Expr\FuncCall)      // processing define('constant_name',value);
            {
                if (isset($node->name) && ($node->name instanceof PhpParser\Node\Name))  // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = explode('\\',$node->name);
                    $fn_name    = $parts[count($parts)-1];
                    if ( is_string($fn_name) && ( ($fn_name=='define') || ($fn_name=='defined') ) )
                    {
                        for($ok=false;;)
                        {
                            if (!isset($node->args[0]->value))      break;
                            if ( ($fn_name=='define') && (count($node->args)!=2) )  break;
                            $arg = $node->args[0]->value;           if (! ($arg instanceof PhpParser\Node\Scalar\String_) ) break;
                            $name = $arg->value;                    if (! is_string($name) || (strlen($name) == 0) )        break;
                            $ok     = true;
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $arg->value = $r;
                                $node_modified = true;
                            }
                            break;
                        }
                        if (!$ok)
                        {
                            if ($fn_name=='define') throw new Exception("Error: your use of $fn_name() function is not compatible with yakpro-po!".PHP_EOL."\tOnly 2 parameters, when first is a literal string is allowed...");
                            else                    throw new Exception("Error: your use of $fn_name() function is not compatible with yakpro-po!".PHP_EOL."\tOnly 1 literal string parameter is allowed...");
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Expr\ConstFetch)
            {
                if ($this->scramble_name($scrambler, $node))  $node_modified = true;
            }
            if ( ($node instanceof PhpParser\Node\Const_) && !$this->is_in_class_const_definition )
            {
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $this->set_identifier_name($node->name,$r);
                        $node_modified = true;
                    }
                }
            }
        }
        
        if  ($conf->obfuscate_class_constant_name)
        {
            $scrambler  = $t_scrambler['class_constant'];
            if ( ( ($node instanceof PhpParser\Node\Const_) && $this->is_in_class_const_definition ) || ($node instanceof PhpParser\Node\Stmt\EnumCase) )
            {
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $this->set_identifier_name($node->name,$r);
                        $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Expr\ClassConstFetch)
            {
                $name       = $node->name;
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $this->set_identifier_name($node->name,$r);
                        $node_modified = true;
                    }
                }
            }
        }

        if ($node instanceof PhpParser\Node\UseItem)                // use A\B;  use function A\f;  use const A\C;  use A\{B, function f};
        {                                                           // namespace parts are processed with obfuscate_namespace_name
            $type = ($node->type != PhpParser\Node\Stmt\Use_::TYPE_UNKNOWN) ? $node->type : $node->getAttribute('parent')->type;
            if ($type == PhpParser\Node\Stmt\Use_::TYPE_CONSTANT)   $scrambler = $conf->obfuscate_constant_name ? $t_scrambler['constant'] : null;
            else if ($conf->obfuscate_namespace_name || ( ($type == PhpParser\Node\Stmt\Use_::TYPE_FUNCTION) ? $conf->obfuscate_function_name : $conf->obfuscate_class_name ) )
                                                                    $scrambler = $t_scrambler['function_or_class'];     // last part may also be a namespace
            else                                                    $scrambler = null;
            if (isset($scrambler))
            {
                if ($this->scramble_name($scrambler, $node))  $node_modified = true;
                if (isset($node->alias))
                {
                    $name = $this->get_identifier_name($node->alias);
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            //$node->alias = $r;
                            $this->set_identifier_name($node->alias,$r);
                            $node_modified = true;
                        }
                    }
                }
            }
        }

        
        if ($conf->obfuscate_namespace_name)
        {
            $scrambler = $t_scrambler['function_or_class'];
            if ($node instanceof PhpParser\Node\Stmt\Namespace_)
            {
                if ($this->scramble_all_names($scrambler, $node)) $node_modified = true;
            }
            if ($node instanceof PhpParser\Node\Stmt\GroupUse)
            {
                if ($this->scramble_all_names($scrambler, $node->prefix)) $node_modified = true;
            }
            if ( ($node instanceof PhpParser\Node\Expr\FuncCall) || ($node instanceof PhpParser\Node\Expr\ConstFetch) || ($node instanceof PhpParser\Node\UseItem) || ($node instanceof PhpParser\Node\Attribute) )
            {
                if ($this->scramble_names($scrambler, $node)) $node_modified = true;
            }
            if (  ($node instanceof PhpParser\Node\Expr\New_)
               || ($node instanceof PhpParser\Node\Expr\Instanceof_)
               || ($node instanceof PhpParser\Node\Expr\StaticCall)
               || ($node instanceof PhpParser\Node\Expr\StaticPropertyFetch)
               || ($node instanceof PhpParser\Node\Expr\ClassConstFetch)
               )
            {
                if (isset($node->{'class'}))              // parts was not set in prev version when indirect call (i.e.function name is a variable value!)
                {
                    if ($this->scramble_names($scrambler, $node->{'class'})) $node_modified = true;
                }
            }
            if ( ($node instanceof PhpParser\Node\Stmt\Class_) || ($node instanceof PhpParser\Node\Stmt\Enum_) )
            {
                if (isset($node->{'extends'}))
                {
                    if ($this->scramble_names($scrambler, $node->{'extends'})) $node_modified = true;
                }
                if ( isset($node->{'implements'}) && count($node->{'implements'}) )
                {
                    for($j=0;$j<count($node->{'implements'});++$j)
                    {
                        if ($this->scramble_names($scrambler, $node->{'implements'}[$j])) $node_modified = true;
                    }
                }
            }
            foreach($this->get_declared_type_names($node) as $type_name)
            {
                if ($this->scramble_names($scrambler, $type_name)) $node_modified = true;
            }
            if ($node instanceof PhpParser\Node\Stmt\Interface_)
            {
                if (isset($node->{'extends'}))
                {
                    for($j=0;$j<count($node->{'extends'});++$j)
                    {
                        if ($this->scramble_names($scrambler, $node->{'extends'}[$j])) $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\TraitUse)
            {
                if ( isset($node->{'traits'}) && count($node->{'traits'}) )
                {
                    for($j=0;$j<count($node->{'traits'});++$j)
                    {
                        if ($this->scramble_names($scrambler, $node->{'traits'}[$j])) $node_modified = true;
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\TraitUseAdaptation)
            {
                $t_names = ($node instanceof PhpParser\Node\Stmt\TraitUseAdaptation\Precedence) ? $node->insteadof : array();
                if (isset($node->trait)) $t_names[] = $node->trait;
                foreach($t_names as $trait_name) if ($this->scramble_names($scrambler, $trait_name)) $node_modified = true;
            }
            if ($node instanceof PhpParser\Node\Stmt\Catch_)
            {
                if (isset($node->types))
                {
                    $types = $node->types;
                    foreach($types as &$type)
                    {
                        if ($this->scramble_names($scrambler, $type))  $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_label_name)                    // label: goto label;   -
        {
            $scrambler = $t_scrambler['label'];
            if ( ($node instanceof PhpParser\Node\Stmt\Label) || ($node instanceof PhpParser\Node\Stmt\Goto_) )
            {
                $name = $this->get_identifier_name($node->name);
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name->name = $r;
                        $node_modified = true;
                    }
                }
            }
        }

        if ($conf->obfuscate_if_statement)                  // if else elseif   are replaced by goto ...
        {
            $scrambler                  = $t_scrambler['label'];
            $ok_to_scramble             = false;
            if ($node instanceof PhpParser\Node\Stmt\If_)       // except when it contains declarations: if (!function_exists('f')) { function f() {} }
            {
                $ok_to_scramble         = !$this->contains_declaration($node->stmts) && !$this->contains_declaration($node->{'else'}->stmts ?? null);
                foreach($node->elseifs as $elseif) if ($this->contains_declaration($elseif->stmts)) $ok_to_scramble = false;
            }
            if ( $ok_to_scramble )
            {
                $condition              = $node->cond;
                $stmts                  = $node->stmts;
                $else                   = isset($node->{'else'}) ? $node->{'else'}->stmts : null;
                $elseif                 = $node->elseifs;

                if (isset($elseif) && count($elseif))       // elseif mode
                {
                    $label_endif_name   = $scrambler->scramble($scrambler->generate_label_name());
                    $label_endif        = array(new PhpParser\Node\Stmt\Label($label_endif_name));
                    $goto_endif         = array(new PhpParser\Node\Stmt\Goto_($label_endif_name));

                    $new_nodes_1        = array();
                    $new_nodes_2        = array();
                    $label_if_name      = $scrambler->scramble($scrambler->generate_label_name());
                    $label_if           = array(new PhpParser\Node\Stmt\Label($label_if_name));
                    $goto_if            = array(new PhpParser\Node\Stmt\Goto_($label_if_name));
                    $if                 = new PhpParser\Node\Stmt\If_($condition);
                    $if->stmts          = $goto_if;
                    $new_nodes_1        = array_merge($new_nodes_1,array($if));
                    $new_nodes_2        = array_merge($new_nodes_2,$label_if,$stmts,$goto_endif);

                    for($i=0;$i<count($elseif);++$i)
                    {
                        $condition      = $elseif[$i]->cond;
                        $stmts          = $elseif[$i]->stmts;
                        $label_if_name  = $scrambler->scramble($scrambler->generate_label_name());
                        $label_if       = array(new PhpParser\Node\Stmt\Label($label_if_name));
                        $goto_if        = array(new PhpParser\Node\Stmt\Goto_($label_if_name));
                        $if             = new PhpParser\Node\Stmt\If_($condition);
                        $if->stmts      = $goto_if;
                        $new_nodes_1    = array_merge($new_nodes_1,array($if));
                        $new_nodes_2    = array_merge($new_nodes_2,$label_if,$stmts);
                        if ($i<count($elseif)-1)
                        {
                            $new_nodes_2= array_merge($new_nodes_2,$goto_endif);
                        }
                    }
                    if (isset($else))
                    {
                        $new_nodes_1    = array_merge($new_nodes_1,$else);
                    }
                    $new_nodes_1        = array_merge($new_nodes_1,$goto_endif);
                    $new_nodes_2        = array_merge($new_nodes_2,$label_endif);
                                        return array_merge($new_nodes_1,$new_nodes_2);
                }
                else                                    // no elseif :  if , else
                {
                    if (isset($else))                   // else statement found
                    {
                        $label_then_name    = $scrambler->scramble($scrambler->generate_label_name());
                        $label_then         = array(new PhpParser\Node\Stmt\Label($label_then_name));
                        $goto_then          = array(new PhpParser\Node\Stmt\Goto_($label_then_name));
                        $label_endif_name   = $scrambler->scramble($scrambler->generate_label_name());
                        $label_endif        = array(new PhpParser\Node\Stmt\Label($label_endif_name));
                        $goto_endif         = array(new PhpParser\Node\Stmt\Goto_($label_endif_name));
                        $node->stmts        = $goto_then;
                        $node->{'else'}     = null;
                                            return array_merge(array($node),$else,$goto_endif,$label_then,$stmts,$label_endif);
                    }
                    else                                // no else statement found
                    {
                        if ($condition instanceof PhpParser\Node\Expr\BooleanNot)     // avoid !! in generated code
                        {
                            $new_condition      = $condition->expr;
                        }
                        else
                        {
                            $new_condition      = new PhpParser\Node\Expr\BooleanNot($condition);
                        }
                        $label_endif_name   = $scrambler->scramble($scrambler->generate_label_name());
                        $label_endif        = array(new PhpParser\Node\Stmt\Label($label_endif_name));
                        $goto_endif         = array(new PhpParser\Node\Stmt\Goto_($label_endif_name));
                        $node->cond         = $new_condition;
                        $node->stmts        = $goto_endif;
                                            return array_merge(array($node),$stmts,$label_endif);
                    }
                }
            }
        }

        if ($conf->obfuscate_loop_statement)                  // for while do while   are replaced by goto ...
        {
            $scrambler = $t_scrambler['label'];
            if ($node instanceof PhpParser\Node\Stmt\For_)
            {
                list($label_loop_break_name,$label_loop_continue_name) = array_pop($this->t_loop_stack);

                //$init                   = $node->init;
                $init                   = null;
                if ((isset($node->init) && count($node->init))) foreach($node->init as $tmp) $init[] = new PhpParser\Node\Stmt\Expression($tmp);

                $t_cond                 = $node->cond;          // for(;a,b;) : all expressions are evaluated, the last one is the condition
                $condition              = count($t_cond) ? array_pop($t_cond) : null;

                //$loop                 = $node->loop;
                $loop                   = null;
                if ((isset($node->loop) && count($node->loop))) foreach($node->loop as $tmp) $loop[] = new PhpParser\Node\Stmt\Expression($tmp);

                $stmts                  = $node->stmts;
                $label_loop_name        = $scrambler->scramble($scrambler->generate_label_name());
                $label_loop             = array(new PhpParser\Node\Stmt\Label($label_loop_name));
                $goto_loop              = array(new PhpParser\Node\Stmt\Goto_($label_loop_name));
                $label_break            = array(new PhpParser\Node\Stmt\Label($label_loop_break_name));
                $goto_break             = array(new PhpParser\Node\Stmt\Goto_($label_loop_break_name));
                $label_continue         = array(new PhpParser\Node\Stmt\Label($label_loop_continue_name));
                $goto_continue          = array(new PhpParser\Node\Stmt\Goto_($label_loop_continue_name));

                $new_node               = array();
                if (isset($init))
                {
                    $new_node           = array_merge($new_node,$init);
                }
                $new_node               = array_merge($new_node,$label_loop);
                foreach($t_cond as $tmp) $new_node[] = new PhpParser\Node\Stmt\Expression($tmp);
                if (isset($condition))
                {
                    if ($condition instanceof PhpParser\Node\Expr\BooleanNot)     // avoid !! in generated code
                    {
                        $new_condition  = $condition->expr;
                    }
                    else
                    {
                        $new_condition  = new PhpParser\Node\Expr\BooleanNot($condition);
                    }
                    $if                 = new PhpParser\Node\Stmt\If_($new_condition);
                    $if->stmts          = $goto_break;
                    $new_node           = array_merge($new_node,array($if));
                }
                if (isset($stmts))
                {
                    $new_node           = array_merge($new_node,$stmts);
                }
                $new_node               = array_merge($new_node,$label_continue);
                if (isset($loop))
                {
                    $new_node           = array_merge($new_node,$loop);
                }
                $new_node               = array_merge($new_node,$goto_loop);
                $new_node               = array_merge($new_node,$label_break);
                return $new_node;
            }

            if ( $node instanceof PhpParser\Node\Stmt\Foreach_)
            {
                list($label_loop_break_name,$label_loop_continue_name) = array_pop($this->t_loop_stack);

                $label_break            = array(new PhpParser\Node\Stmt\Label($label_loop_break_name));
                $node->stmts[]          = new PhpParser\Node\Stmt\Label($label_loop_continue_name);
                                        $this->shuffle_stmts($node);
                return                  array_merge(array($node),$label_break);
            }

            if ( $node instanceof PhpParser\Node\Stmt\Switch_)
            {
                list($label_loop_break_name,$label_loop_continue_name) = array_pop($this->t_loop_stack);

                $label_break            = array(new PhpParser\Node\Stmt\Label($label_loop_break_name));
                $label_continue         = array(new PhpParser\Node\Stmt\Label($label_loop_continue_name));
                return                  array_merge(array($node),$label_continue,$label_break);
            }

            if ( $node instanceof PhpParser\Node\Stmt\While_)
            {
                list($label_loop_break_name,$label_loop_continue_name) = array_pop($this->t_loop_stack);

                $condition              = $node->cond;
                $stmts                  = $node->stmts;
                $label_break            = array(new PhpParser\Node\Stmt\Label($label_loop_break_name));
                $goto_break             = array(new PhpParser\Node\Stmt\Goto_($label_loop_break_name));
                $label_continue         = array(new PhpParser\Node\Stmt\Label($label_loop_continue_name));
                $goto_continue          = array(new PhpParser\Node\Stmt\Goto_($label_loop_continue_name));
                if ($condition instanceof PhpParser\Node\Expr\BooleanNot)     // avoid !! in generated code
                {
                    $new_condition      = $condition->expr;
                }
                else
                {
                    $new_condition      = new PhpParser\Node\Expr\BooleanNot($condition);
                }
                $if                     = new PhpParser\Node\Stmt\If_($new_condition);
                $if->stmts              = $goto_break;
                return                  array_merge($label_continue,array($if),$stmts,$goto_continue,$label_break);
            }

            if ( $node instanceof PhpParser\Node\Stmt\Do_)
            {
                list($label_loop_break_name,$label_loop_continue_name) = array_pop($this->t_loop_stack);

                $condition              = $node->cond;
                $stmts                  = $node->stmts;
                $label_break            = array(new PhpParser\Node\Stmt\Label($label_loop_break_name));
                $label_continue         = array(new PhpParser\Node\Stmt\Label($label_loop_continue_name));
                $label_loop_name        = $scrambler->scramble($scrambler->generate_label_name());
                $if                     = new PhpParser\Node\Stmt\If_($condition);
                $if->stmts              = array(new PhpParser\Node\Stmt\Goto_($label_loop_name));
                return                  array_merge(array(new PhpParser\Node\Stmt\Label($label_loop_name)),$stmts,$label_continue,array($if),$label_break);
            }

            if ($node instanceof PhpParser\Node\Stmt\Break_)
            {
                $n = 1;
                if (isset($node->num))
                {
                    if ($node->num instanceof PhpParser\Node\Scalar\LNumber)
                    {
                        $n = $node->num->value;
                    }
                    else
                    {
                        throw new Exception("Error: your use of break statement is not compatible with yakpro-po!".PHP_EOL."\tAt max 1 literal numeric parameter is allowed...");
                    }
                }
                if (count($this->t_loop_stack) - $n <0)
                {
                    throw new Exception("Error: break statement outside loop found!;".PHP_EOL.(($debug_mode==2) ? print_r($node,true) : '') );
                }
                list($label_loop_break_name,$label_loop_continue_name) = $this->t_loop_stack[count($this->t_loop_stack) - $n ];
                $node = new PhpParser\Node\Stmt\Goto_($label_loop_break_name);
                $node_modified = true;
            }
            if ($node instanceof PhpParser\Node\Stmt\Continue_)
            {
                $n = 1;
                if (isset($node->num))
                {
                    if ($node->num instanceof PhpParser\Node\Scalar\LNumber)
                    {
                        $n = $node->num->value;
                    }
                    else
                    {
                        throw new Exception("Error: your use of continue statement is not compatible with yakpro-po!".PHP_EOL."\tAt max 1 literal numeric parameter is allowed...");
                    }
                }
                if (count($this->t_loop_stack) - $n <0)
                {
                    throw new Exception("Error: continue statement outside loop found!;".PHP_EOL.(($debug_mode==2) ? print_r($node,true) : ''));
                }
                list($label_loop_break_name,$label_loop_continue_name) = $this->t_loop_stack[count($this->t_loop_stack) - $n ];
                $node = new PhpParser\Node\Stmt\Goto_($label_loop_continue_name);
                $node_modified = true;
            }
        }
        
        if ($conf->shuffle_stmts)
        {
            if (    ($node instanceof PhpParser\Node\Stmt\Function_)
                 || ($node instanceof PhpParser\Node\Expr\Closure)
                 || ($node instanceof PhpParser\Node\Stmt\ClassMethod)
                 || ($node instanceof PhpParser\Node\Stmt\Foreach_)     // occurs when $conf->obfuscate_loop_statement is set to false
                 || ($node instanceof PhpParser\Node\Stmt\If_)          // occurs when $conf->obfuscate_loop_statement is set to false
                 || ($node instanceof PhpParser\Node\Stmt\TryCatch)
                 || ($node instanceof PhpParser\Node\Stmt\Catch_)
                 || ($node instanceof PhpParser\Node\Stmt\Case_)
                 //|| ($node instanceof PhpParser\Node\Stmt\Namespace_)
              )
            {
                if ($this->shuffle_stmts($node))  $node_modified  = true;
            }

            if ( ($node instanceof PhpParser\Node\Stmt\If_) )           // occurs when $conf->obfuscate_if_statement is set to false
            {
                if (isset($node->{'else'}))
                {
                    if ($this->shuffle_stmts($node->{'else'}))  $node_modified  = true;
                }
                
                $elseif                 = $node->elseifs;
                if (isset($elseif) && count($elseif))       // elseif mode
                {
                    for($i=0;$i<count($elseif);++$i)
                    {
                        if ($this->shuffle_stmts($elseif[$i]))  $node_modified  = true;
                    }
                }
            }
        }
        array_pop($this->t_node_stack);
        if ($node_modified) return $node;
    }
}

?>
