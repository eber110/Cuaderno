<?php

namespace Base\LibraryCssJit;

abstract class JitRuleBase implements JitRuleInterface
{
    /**
     * Envuelve el CSS en media query si tiene sufijo
     */
    protected function wrapMediaQuery(string $suffix, string $rule): string
    {
        if ($suffix === 'desk') {
            return "@media screen and (min-width: 993px) {\n  $rule\n}\n";
        } elseif ($suffix === 'mid') {
            return "@media screen and (min-width: 577px) and (max-width: 992px) {\n  $rule\n}\n";
        } elseif ($suffix === 'sml') {
            return "@media screen and (max-width: 576px) {\n  $rule\n}\n";
        }
        return $rule . "\n";
    }
}
