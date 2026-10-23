<?php
// continue inside do-while must re-evaluate the condition, not restart the body
$i = 0;
do { $i++; if ($i < 3) continue; echo "body $i\n"; } while (false);
echo "i=$i\n";
