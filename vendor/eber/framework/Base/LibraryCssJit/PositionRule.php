<?php

namespace Base\LibraryCssJit;

class PositionRule extends JitRuleBase
{
    public function processWord(string $word): ?string
    {
        // Bases
        $statics = [
            'relative' => ".relative { position: relative; }\n",
            'absolute' => ".absolute { position: absolute; }\n",
            'fixed' => ".fixed { position: fixed !important; }\n",
            'sticky' => ".sticky { position: sticky !important; }\n",
            'sticky-top' => ".sticky-top { position: sticky !important; top: 0%; }\n",
            'sticky-bottom' => ".sticky-bottom { position: -webkit-sticky; position: sticky !important; bottom: 0; height: 5px; }\n",
            'floatl' => ".floatl { float: left !important; }\n",
            'floatr' => ".floatr { float: right !important; }\n",
            'float-start' => ".float-start { float: inline-start !important; }\n",
            'float-end' => ".float-end { float: inline-end !important; }\n",
        ];

        if (isset($statics[$word])) {
            return $statics[$word];
        }

        // Top, Bottom, Left, Right
        if (preg_match('/^(top|bottom|left|right)(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m) || preg_match('/^(top|bottom|left|right)(\d+)$/', $word, $m)) {
            $prop = $m[1];
            if (count($m) === 4) {
                $media = $m[2];
                $val = $m[3];
            } else {
                $media = '';
                $val = $m[2];
            }
            return $this->wrapMediaQuery($media, ".{$word} { {$prop}: {$val}%; }");
        }

        // Top sin numero es 0%
        if ($word === 'top') return ".top { top: 0%; }\n";
        if ($word === 'bottom') return ".bottom { bottom: 0%; }\n";
        if ($word === 'left') return ".left { left: 0%; }\n";
        if ($word === 'right') return ".right { right: 0%; }\n";

        return null;
    }
}
