<?php
$word = 'hem5';
if (preg_match('/^h(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
    var_dump($m);
} else {
    echo "No match for h\n";
}
