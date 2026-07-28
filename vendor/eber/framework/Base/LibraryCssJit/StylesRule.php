<?php

namespace Base\LibraryCssJit;

class StylesRule extends JitRuleBase
{
    public function processWord(string $word): ?string
    {
        // Bases
        $statics = [
            'textareaResize' => ".textareaResize { field-sizing: content; }\n",
            'logo' => ".logo { height: 40px; width: auto; margin-right: 10px; padding: 5px 0px; object-fit: cover; }\n",
            'no-desk' => "@media screen and (min-width: 993px) { .no-desk, .no-desk-css { display: none !important; } }\n",
            'no-desk-css' => "@media screen and (min-width: 993px) { .no-desk, .no-desk-css { display: none !important; } }\n",
            'no-tablet' => "@media screen and (min-width: 577px) and (max-width: 992px) { .no-tablet, .no-tablet-css { display: none !important; } }\n",
            'no-tablet-css' => "@media screen and (min-width: 577px) and (max-width: 992px) { .no-tablet, .no-tablet-css { display: none !important; } }\n",
            'no-phone' => "@media screen and (max-width: 576px) { .no-phone, .no-phone-css { display: none !important; } }\n",
            'no-phone-css' => "@media screen and (max-width: 576px) { .no-phone, .no-phone-css { display: none !important; } }\n",
            'hidden' => ".hidden { display: none !important; }\n",
            'inline-block' => ".inline-block { display: inline-block !important; }\n",
            'inline' => ".inline { display: inline !important; }\n",
            'block' => ".block { display: block !important; }\n",
            'grid-row-center' => ".grid-row-center { justify-items: center; }\n",
        ];

        if (isset($statics[$word])) {
            return $statics[$word];
        }

        // SVG Opacity
        if (preg_match('/^svg-opacity-(\d+)$/', $word, $m)) {
            $val = $m[1] / 100;
            return ".{$word} { fill-opacity: {$val}; }\n";
        }

        return null;
    }
}
