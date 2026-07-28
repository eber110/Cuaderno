<?php

namespace Base\LibraryCssJit;

class ContainerRule extends JitRuleBase
{
    public function processWord(string $word): ?string
    {
        // Contenedores Base
        if (preg_match('/^container-xl(?:-(desk|mid|sml))?$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { width: 100%; margin: 0px; max-width: none; }\n");
        }
        if (preg_match('/^container(?:-(desk|mid|sml))?$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { max-width: 90%; width: var(--width-window); margin: 0 auto; }\n");
        }

        // Gaps
        if (preg_match('/^gap(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { gap: {$m[2]}px; }");
        }
        if (preg_match('/^gap-rem(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { gap: {$m[2]}rem; }");
        }
        if (preg_match('/^gap-em(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { gap: {$m[2]}em; }");
        }

        return null;
    }
}
