<?php
require 'Base/LibraryCssJit/JitRuleInterface.php';
require 'Base/LibraryCssJit/JitRuleBase.php';
require 'Base/LibraryCssJit/ProvisionsRule.php';

class ProvisionsRuleTest extends Base\LibraryCssJit\JitRuleBase {
    public function processWord(string $word): ?string {
        if (preg_match('/^p(t|r|b|l)?(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $dir = $m[1] ?? '';
            $media = $m[2] ?? '';
            $val = $m[3] ?? '';
            
            $prop = 'padding';
            if ($dir === 't') $prop .= '-top';
            if ($dir === 'r') $prop .= '-right';
            if ($dir === 'b') $prop .= '-bottom';
            if ($dir === 'l') $prop .= '-left';
            
            return $this->wrapMediaQuery($media, ".{$word} { {$prop}: {$val}px !important; }");
        }
        return null;
    }
}
$rule = new ProvisionsRuleTest();
$tests = ['pt10', 'pt-10', 'pt-desk-10', 'pt-desk10', 'p10', 'p-desk-10', 'p-desk10'];
foreach($tests as $word) {
    echo $word . " -> " . ($rule->processWord($word) ?? "NULL") . "\n";
}
