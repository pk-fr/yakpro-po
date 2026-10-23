<?php
namespace A;
function f() { return 'A'; }
namespace B;
function f() { return 'B'; }
namespace C;
echo \A\f(), \B\f(), "\n";
