<?php

namespace Obfuscator\Test;

class File
{
    public function foo(): void
    {
        $file = file(__FILE__);
    }
}
