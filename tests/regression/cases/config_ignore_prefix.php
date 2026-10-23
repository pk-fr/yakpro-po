<?php
// yakpro-args: --config-file conf/ignore_prefix.cnf
class KeepMe { function hi() { return "kept"; } }
echo (new KeepMe)->hi(), get_class(new KeepMe), "\n";
