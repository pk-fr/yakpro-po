<?php
//========================================================================
// Author:  Pascal KISSIAN
// Resume:  http://pascal.kissian.net
//
// Copyright (c) 2015-2020 Pascal KISSIAN
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


    public function pScalar_String(PhpParser\Node\Scalar\String_ $node)
    {
        $result = $this->obfuscate_string($node->value);            if (!strlen($result)) return "''";
        return  '"'.$this->obfuscate_string($node->value).'"';
    }


    //TODO: pseudo-obfuscate HEREDOC string
    protected function pScalar_Encapsed(PhpParser\Node\Scalar\Encapsed $node)
    {
        /*
        if ($node->getAttribute('kind') === PhpParser\Node\Scalar\String_::KIND_HEREDOC) 
        {
            $label = $node->getAttribute('docLabel');
            if ($label && !$this->encapsedContainsEndLabel($node->parts, $label)) 
            {
                if (count($node->parts) === 1
                    && $node->parts[0] instanceof PhpParser\Node\Scalar\EncapsedStringPart
                    && $node->parts[0]->value === ''
                )
                {
                    return "<<<$label\n$label" . $this->docStringEndToken;
                }

                return "<<<$label\n" . $this->pEncapsList($node->parts, null) . "\n$label"
                     . $this->docStringEndToken;
            }
        }
        */
        $result = '';
        foreach ($node->parts as $element)
        {
            if ($element instanceof PhpParser\Node\Scalar\EncapsedStringPart)
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