<?php

namespace Base\LibraryCssJit;

class FontRule extends JitRuleBase
{
    public function processWord(string $word): ?string
    {
        // Globales y Bases
        $statics = [
            'font-alumini' => ".font-alumini { font-family: var(--font3) !important; line-height: 0.7em; }\n",
            'font-roboto-condensed' => ".font-roboto-condensed { font-family: var(--font2) !important; }\n",
            'upper' => ".upper { text-transform: uppercase !important; }\n",
            'lower' => ".lower { text-transform: lowercase !important; }\n",
            'capitalize' => ".capitalize { text-transform: capitalize !important; }\n",
            'title' => ".title { font-size: 35px; font-weight: 400; margin: 0px; padding: 0px; text-wrap: balance; }\n",
            'display-1' => ".display-1 { font-weight: 700; font-size: 64px; }\n",
            'display-2' => ".display-2 { font-weight: 700; font-size: 54px; }\n",
            'display-3' => ".display-3 { font-weight: 700; font-size: 44px; }\n",
            'text-l' => ".text-l { text-align: left !important; }\n",
            'text-r' => ".text-r { text-align: right !important; }\n",
            'text-c' => ".text-c { text-align: center !important; }\n",
            'text-protected' => ".text-protected { -webkit-touch-callout: none; user-select: none; }\n",
            'text-outline' => ".text-outline { text-shadow: 0px 0px 3px rgb(0, 0, 0); }\n",
            'ita' => ".ita { font-style: italic !important; }\n",
            'bold' => ".bold { font-weight: bold; }\n",
            'textw' => ".textw, .textw a { color: white !important; }\n",
            'texto' => ".texto, .texto a { color: var(--text-primary) !important; }\n",
            'textc' => ".textc, .textc a { color: var(--text-secondary) !important; }\n",
            'textb' => ".textb, .textb a { color: black !important; }\n",
            'color-success' => ".color-success, .color-success a { color: var(--success); filter: var(--sepia); }\n",
            'color-caution' => ".color-caution, .color-caution a { color: var(--caution); filter: var(--sepia); }\n",
            'color-danger' => ".color-danger, .color-danger a { color: var(--danger); filter: var(--sepia); }\n",
        ];

        if (isset($statics[$word])) {
            return $statics[$word];
        }

        // Line-height
        if (preg_match('/^line-h(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
            $num = $m[1];
            $media = $m[2] ?? '';
            // Si es 07, es 0.7. Si es 15, es 1.5. Si es 1, es 1.
            $val = strlen($num) > 1 ? $num[0] . '.' . substr($num, 1) : $num;
            return $this->wrapMediaQuery($media, ".{$word} { line-height: {$val}em !important; }");
        }

        // Bold
        if (preg_match('/^bold(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
            $weight = $m[1];
            $media = $m[2] ?? '';
            return $this->wrapMediaQuery($media, ".{$word} { font-weight: {$weight}; }");
        }
        if (preg_match('/^bold(?:-(desk|mid|sml))$/', $word, $m)) {
            $media = $m[1] ?? '';
            return $this->wrapMediaQuery($media, ".{$word} { font-weight: bold; }");
        }

        // Colors
        if (preg_match('/^color([1-8])$/', $word, $m)) {
            $num = $m[1];
            $var = $num === '1' ? '--back-color' : "--back-color{$num}";
            $imp = $num !== '1' ? ' !important' : '';
            return ".color{$num}, .color{$num} a { color: var({$var}){$imp}; }\n";
        }
        if (preg_match('/^color([1-8])-hover$/', $word, $m)) {
            $num = $m[1];
            $var = $num === '1' ? '--back-color' : "--back-color{$num}";
            $varHover = $num === '1' ? '--back-color-hover' : "--back-color{$num}-hover";
            return ".color{$num}-hover, .color{$num}-hover a { color: var({$var}); transition: var(--transition); }\n.color{$num}-hover:hover, .color{$num}-hover:hover a { color: var({$varHover}); }\n";
        }

        return null;
    }
}
