<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015-2016 Pascal KISSIAN
//
// Published under the MIT License
//          Consider it as a proof of concept!
//          No warranty of any kind.
//          Use and abuse at your own risks.
//========================================================================

class MyNodeVisitor extends PhpParser\NodeVisitorAbstract       // all parsing and replacement of scrambled names is done here!
{                                                               // see PHP-Parser for documentation!
    private $t_loop_stack                   = array();
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

    public function enterNode(PhpParser\Node $node)
    {
        global $conf;
        global $t_scrambler;

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
        if ($node instanceof PhpParser\Node\Stmt\Class_)
        {
            $name = $node->name;
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

        if ($conf->obfuscate_variable_name)
        {
            $scrambler = $t_scrambler['variable'];
            if ( ($node instanceof PhpParser\Node\Expr\Variable) || ($node instanceof PhpParser\Node\Stmt\StaticVar) || ($node instanceof PhpParser\Node\Param) )
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
            if ( ($node instanceof PhpParser\Node\Stmt\Catch_) || ($node instanceof PhpParser\Node\Expr\ClosureUse))
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
        }

        if ($conf->obfuscate_function_name)
        {
            $scrambler = $t_scrambler['function'];
            if ($node instanceof PhpParser\Node\Stmt\Function_)
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
            if ($node instanceof PhpParser\Node\Expr\FuncCall )
            {
                if (isset($node->name->parts))              // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = $node->name->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $node->name->parts[count($parts)-1] = $r;
                            $node_modified = true;
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Expr\FuncCall)      // processing function_exists('function_name');
            {
                if (isset($node->name->parts))                      // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = $node->name->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && ($name=='function_exists') )
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
        }

        if ($conf->obfuscate_class_name)
        {
            $scrambler = $t_scrambler['class'];
            if ($node instanceof PhpParser\Node\Stmt\Class_)
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
                if (isset($node->{'extends'}))
                {
                    $parts = $node->{'extends'}->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $node->{'extends'}->parts[count($parts)-1] = $r;
                            $node_modified = true;
                        }
                    }
                }
            }
            if (  ($node instanceof PhpParser\Node\Expr\New_)
               || ($node instanceof PhpParser\Node\Expr\StaticCall)
               || ($node instanceof PhpParser\Node\Expr\StaticPropertyFetch)
               || ($node instanceof PhpParser\Node\Expr\ClassConstFetch)
               || ($node instanceof PhpParser\Node\Expr\Instanceof_)
               )
            {
                if (isset($node->{'class'}->parts))
                {
                    $parts = $node->{'class'}->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $node->{'class'}->parts[count($parts)-1] = $r;
                            $node_modified = true;
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Param)
            {
                if (isset($node->type) && isset($node->type->parts))
                {
                    $parts = $node->type->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $node->type->parts[count($parts)-1] = $r;
                            $node_modified = true;
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\Catch_)
            {
                if (isset($node->type) && isset($node->type->parts))
                {
                    $parts = $node->type->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && (strlen($name) !== 0) )
                    {
                        $r = $scrambler->scramble($name);
                        if ($r!==$name)
                        {
                            $node->type->parts[count($parts)-1] = $r;
                            $node_modified = true;
                        }
                    }
                }
            }
        }

        if ($conf->obfuscate_interface_name)
        {
            $scrambler = $t_scrambler['class'];
            if ($node instanceof PhpParser\Node\Stmt\Interface_)
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
                if ( isset($node->{'extends'}) && count($node->{'extends'}) )
                {
                    for($j=0;$j<count($node->{'extends'});++$j)
                    {
                        $parts = $node->{'extends'}[$j]->parts;
                        $name  = $parts[count($parts)-1];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->{'extends'}[$j]->parts[count($parts)-1] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\Class_)
            {
                if ( isset($node->{'implements'}) && count($node->{'implements'}) )
                {
                    for($j=0;$j<count($node->{'implements'});++$j)
                    {
                        $parts = $node->{'implements'}[$j]->parts;
                        $name  = $parts[count($parts)-1];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->{'implements'}[$j]->parts[count($parts)-1] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
            }
       }

        if ($conf->obfuscate_trait_name)
        {
            $scrambler = $t_scrambler['class'];
            if ($node instanceof PhpParser\Node\Stmt\Trait_)
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
            if ($node instanceof PhpParser\Node\Stmt\TraitUse)
            {
                if ( isset($node->{'traits'}) && count($node->{'traits'}) )
                {
                    for($j=0;$j<count($node->{'traits'});++$j)
                    {
                        $parts = $node->{'traits'}[$j]->parts;
                        $name  = $parts[count($parts)-1];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->{'traits'}[$j]->parts[count($parts)-1] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
            }
        }

        if ($conf->obfuscate_property_name)
        {
            $scrambler = $t_scrambler['property'];
            if ( ($node instanceof PhpParser\Node\Expr\PropertyFetch) || ($node instanceof PhpParser\Node\Stmt\PropertyProperty) || ($node instanceof PhpParser\Node\Expr\StaticPropertyFetch) )
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
        }

        if ($conf->obfuscate_method_name)
        {
            $scrambler = $t_scrambler['method'];
            if ( ($node instanceof PhpParser\Node\Stmt\ClassMethod) || ($node instanceof PhpParser\Node\Expr\MethodCall) || ($node instanceof PhpParser\Node\Expr\StaticCall) )
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
        }

        if ($conf->obfuscate_constant_name)
        {
            $scrambler = $t_scrambler['constant'];
            if ($node instanceof PhpParser\Node\Expr\FuncCall)      // processing define('constant_name',value);
            {
                if (isset($node->name->parts))                      // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = $node->name->parts;
                    $name  = $parts[count($parts)-1];
                    if ( is_string($name) && ($name=='define') )
                    {
                        for($ok=false;;)
                        {
                            if (!isset($node->args[0]->value))      break;
                            if (count($node->args)!=2)              break;
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
                            throw new Exception("Error: your use of define() function is not compatible with yakpro-po!".PHP_EOL."\tOnly 2 parameters, when first is a literal string is allowed...");
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Expr\ConstFetch)
            {
                $parts = $node->name->parts;
                $name  = $parts[count($parts)-1];
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->name->parts[count($parts)-1] = $r;
                        $node_modified = true;
                    }
                }
            }
            if ( ($node instanceof PhpParser\Node\Const_) && !$this->is_in_class_const_definition )
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
        }
        
        if  ($conf->obfuscate_class_constant_name)
        {
            $scrambler  = $t_scrambler['class_constant'];
            if ( ($node instanceof PhpParser\Node\Const_) && $this->is_in_class_const_definition )
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
            if ($node instanceof PhpParser\Node\Expr\ClassConstFetch)
            {
                $name       = $node->name;
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
        }
        
        if ($conf->obfuscate_namespace_name)
        {
            $scrambler = $t_scrambler['class'];
            if ( ($node instanceof PhpParser\Node\Stmt\Namespace_) || ($node instanceof PhpParser\Node\Stmt\UseUse) )
            {
                if (isset($node->name->parts))
                {
                    $parts = $node->name->parts;
                    for($i=0;$i<count($parts);++$i)
                    {
                        $name  = $parts[$i];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->name->parts[$i] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\UseUse)
            {
                $name = $node->alias;
                if ( is_string($name) && (strlen($name) !== 0) )
                {
                    $r = $scrambler->scramble($name);
                    if ($r!==$name)
                    {
                        $node->alias = $r;
                        $node_modified = true;
                    }
                }
            }
            if ( ($node instanceof PhpParser\Node\Expr\FuncCall) || ($node instanceof PhpParser\Node\Expr\ConstFetch) )
            {
                if (isset($node->name->parts))              // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = $node->name->parts;
                    for($i=0;$i<count($parts)-1;++$i)       // skip last part, that is processed in his own section
                    {
                        $name  = $parts[$i];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->name->parts[$i] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
            }
            if (  ($node instanceof PhpParser\Node\Expr\New_)
               || ($node instanceof PhpParser\Node\Expr\Instanceof_)
               )
            {
                if (isset($node->{'class'}->parts))              // not set when indirect call (i.e.function name is a variable value!)
                {
                    $parts = $node->{'class'}->parts;
                    for($i=0;$i<count($parts)-1;++$i)       // skip last part, that is processed in his own section
                    {
                        $name  = $parts[$i];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->{'class'}->parts[$i] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\Class_)
            {
                if (isset($node->{'extends'}) && isset($node->{'extends'}->parts))
                {
                    $parts = $node->{'extends'}->parts;
                    for($i=0;$i<count($parts)-1;++$i)       // skip last part, that is processed in his own section
                    {
                        $name  = $parts[$i];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->{'extends'}->parts[$i] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
                if ( isset($node->{'implements'}) && count($node->{'implements'}) )
                {
                    for($j=0;$j<count($node->{'implements'});++$j)
                    {
                        $parts = $node->{'implements'}[$j]->parts;
                        for($i=0;$i<count($parts)-1;++$i)       // skip last part, that is processed in his own section
                        {
                            $name  = $parts[$i];
                            if ( is_string($name) && (strlen($name) !== 0) )
                            {
                                $r = $scrambler->scramble($name);
                                if ($r!==$name)
                                {
                                    $node->{'implements'}[$j]->parts[$i] = $r;
                                    $node_modified = true;
                                }
                            }
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Param)
            {
                if (isset($node->type) && isset($node->type->parts))
                {
                    $parts = $node->type->parts;
                    for($i=0;$i<count($parts)-1;++$i)       // skip last part, that is processed in his own section
                    {
                        $name  = $parts[$i];
                        if ( is_string($name) && (strlen($name) !== 0) )
                        {
                            $r = $scrambler->scramble($name);
                            if ($r!==$name)
                            {
                                $node->type->parts[$i] = $r;
                                $node_modified = true;
                            }
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\Interface_)
            {
                if (isset($node->{'extends'}) && isset($node->{'extends'}->parts))
                {
                    for($j=0;$j<count($node->{'extends'});++$j)
                    {
                        $parts = $node->{'extends'}[$j]->parts;
                        for($i=0;$i<count($parts)-1;++$i)       // skip last part, that is processed in his own section
                        {
                            $name  = $parts[$i];
                            if ( is_string($name) && (strlen($name) !== 0) )
                            {
                                $r = $scrambler->scramble($name);
                                if ($r!==$name)
                                {
                                    $node->{'extends'}[$j]->parts[$i] = $r;
                                    $node_modified = true;
                                }
                            }
                        }
                    }
                }
            }
            if ($node instanceof PhpParser\Node\Stmt\TraitUse)
            {
                if ( isset($node->{'traits'}) && count($node->{'traits'}) )
                {
                    for($j=0;$j<count($node->{'traits'});++$j)
                    {
                        $parts = $node->{'traits'}[$j]->parts;
                        for($i=0;$i<count($parts)-1;++$i)       // skip last part, that is processed in his own section
                        {
                            $name  = $parts[$i];
                            if ( is_string($name) && (strlen($name) !== 0) )
                            {
                                $r = $scrambler->scramble($name);
                                if ($r!==$name)
                                {
                                    $node->{'traits'}[$j]->parts[$i] = $r;
                                    $node_modified = true;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($conf->obfuscate_label_name)                    // label: goto label;   -
        {
            $scrambler = $t_scrambler['label'];
            if ( ($node instanceof PhpParser\Node\Stmt\Label) || ($node instanceof PhpParser\Node\Stmt\Goto_) )
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
        }

        if ($conf->obfuscate_if_statement)                  // if else elseif   are replaced by goto ...
        {
            $scrambler = $t_scrambler['label'];
            if ( ($node instanceof PhpParser\Node\Stmt\If_) )
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

                $init                   = $node->init;
                $condition              = (isset($node->cond) && sizeof($node->cond)) ? $node->cond[0] : null;
                $loop                   = $node->loop;
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
                $goto_continue          = array(new PhpParser\Node\Stmt\Goto_($label_loop_continue_name));
                $if                     = new PhpParser\Node\Stmt\If_($condition);
                $if->stmts              = $goto_continue;
                return                  array_merge($label_continue,$stmts,array($if),$label_break);
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
        
        if ($node_modified) return $node;
    }
}

?>
