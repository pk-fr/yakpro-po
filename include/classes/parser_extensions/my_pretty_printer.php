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

class myPrettyprinter extends PhpParser\PrettyPrinter\Standard
{
    private function obfuscate_string($str)
    {
        $l = strlen($str);
        $result = '';
        for($i=0;$i<$l;++$i)
        {
            $result .= mt_rand(0,1) ? "\x".dechex(ord($str[$i])) : "\\".decoct(ord($str[$i]));
        }
        return $result;
    }


    public function pScalar_String(PhpParser\Node\Scalar\String_ $node) : string
    {
        $result = $this->obfuscate_string($node->value);            if (!strlen($result)) return "''";
        return  '"'.$this->obfuscate_string($node->value).'"';
    }


    //TODO: pseudo-obfuscate HEREDOC string
   
    
    protected function pScalar_InterpolatedString(PhpParser\Node\Scalar\InterpolatedString $node): string 
    {
        /*
        if ($node->getAttribute('kind') === Scalar\String_::KIND_HEREDOC) {
            $label = $node->getAttribute('docLabel');
            if ($label && !$this->encapsedContainsEndLabel($node->parts, $label)) {
                $nl = $this->phpVersion->supportsFlexibleHeredoc() ? $this->nl : $this->newline;
                if (count($node->parts) === 1
                    && $node->parts[0] instanceof Node\InterpolatedStringPart
                    && $node->parts[0]->value === ''
                ) {
                    return "<<<$label$nl$label{$this->docStringEndToken}";
                }

                return "<<<$label$nl" . $this->pEncapsList($node->parts, null)
                     . "$nl$label{$this->docStringEndToken}";
            }
        }
        return '"' . $this->pEncapsList($node->parts, '"') . '"';
        */
        $result = '';
        foreach ($node->parts as $element)
        {
            if ($element instanceof PhpParser\Node\InterpolatedStringPart)
            {
                $result .=  $this->obfuscate_string($element->value);
            }
            else
            {
                $result .= '{' . $this->p($element) . '}';
            }
        }
        return '"'.$result.'"';
    }
}

?>
