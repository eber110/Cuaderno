<?php
$m = [];
preg_match('/^w(?:-(desk|mid|sml))?-?(\d+)$/', 'w100', $m);
print_r($m);
