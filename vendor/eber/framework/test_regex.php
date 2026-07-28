<?php
$tests = ['mt-desk-10', 'm-desk-10', 'mt10', 'm10', 'pt-desk-10', 'p-desk-10', 'pt10', 'p10'];
foreach($tests as $word) {
    echo "Testing $word:\n";
    if (preg_match('/^m(t|r|b|l)?(?:-(desk|mid|sml))?-(\d+)$/', $word, $m) || preg_match('/^m(t|r|b|l)?(\d+)$/', $word, $m)) {
        var_dump($m);
    }
    if (preg_match('/^p(t|r|b|l)?(?:-(desk|mid|sml))?-(\d+)$/', $word, $m) || preg_match('/^p(t|r|b|l)?(\d+)$/', $word, $m)) {
        var_dump($m);
    }
}
