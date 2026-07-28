<?php

namespace Base\LibraryCssJit;

class GridRule extends JitRuleBase
{
    public function processWord(string $word): ?string
    {
        // Bases
        if ($word === 'grid') {
            return ".grid { display: grid; }\n";
        }
        if ($word === 'grid-center') {
            return ".grid-center { display: grid; height: 95vh; align-content: center; justify-content: center; }\n";
        }
        if ($word === 'center-grid-y') {
            return ".center-grid-y { display: grid; align-content: center; }\n";
        }

        // Columnas
        if (preg_match('/^col(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $num = $m[2];
            $media = $m[1] ?? '';
            return $this->wrapMediaQuery($media, ".{$word} { grid-template-columns: repeat({$num}, 1fr); }");
        }

        // Span
        if (preg_match('/^span(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $num = $m[2];
            $media = $m[1] ?? '';
            $important = '';
            
            $rule = "grid-column: span {$num};";
            if ($num == 0) {
                if ($media === 'sml') {
                    $rule = "grid-column: span 0 !important; position: absolute; z-index: 0;";
                } else {
                    $rule = "grid-column: span 0; display: none;";
                }
            }
            return $this->wrapMediaQuery($media, ".{$word} { {$rule} }");
        }

        return null;
    }
}
