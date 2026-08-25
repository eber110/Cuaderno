<?php

namespace Base\LibraryCssJit;

class FontRule extends JitRuleBase
{
    public function processWord(string $word): ?string
    {
        // Globales y Bases
        $statics = [
            // Fuentes del sistema y variables
            'font-alumini' => ".font-alumini { font-family: var(--font3) !important; line-height: 0.7em; }\n",
            'font-roboto-condensed' => ".font-roboto-condensed { font-family: var(--font2) !important; }\n",
            'font-sans' => ".font-sans { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif !important; }\n",
            'font-serif' => ".font-serif { font-family: ui-serif, Georgia, Cambria, \"Times New Roman\", Times, serif !important; }\n",
            'font-mono' => ".font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, \"Liberation Mono\", \"Courier New\", monospace !important; }\n",
            'font-1' => ".font-1, .font1 { font-family: var(--font) !important; }\n",
            'font1' => ".font-1, .font1 { font-family: var(--font) !important; }\n",
            'font-2' => ".font-2, .font2 { font-family: var(--font2) !important; }\n",
            'font2' => ".font-2, .font2 { font-family: var(--font2) !important; }\n",
            'font-3' => ".font-3, .font3 { font-family: var(--font3) !important; }\n",
            'font3' => ".font-3, .font3 { font-family: var(--font3) !important; }\n",
            'font-4' => ".font-4, .font4 { font-family: var(--font4) !important; }\n",
            'font4' => ".font-4, .font4 { font-family: var(--font4) !important; }\n",

            // Transformaciones de texto
            'upper' => ".upper { text-transform: uppercase !important; }\n",
            'uppercase' => ".uppercase { text-transform: uppercase !important; }\n",
            'lower' => ".lower { text-transform: lowercase !important; }\n",
            'lowercase' => ".lowercase { text-transform: lowercase !important; }\n",
            'capitalize' => ".capitalize { text-transform: capitalize !important; }\n",
            'normal-case' => ".normal-case, .none-case { text-transform: none !important; }\n",
            'none-case' => ".normal-case, .none-case { text-transform: none !important; }\n",

            // Párrafos y pseudo-elementos (Primera letra / Primera línea)
            'capitalize-p' => ".capitalize-p::first-letter { text-transform: uppercase !important; }\n",
            'capitalize-first' => ".capitalize-first::first-letter { text-transform: uppercase !important; }\n",
            'upper-p' => ".upper-p::first-letter { text-transform: uppercase !important; }\n",
            'upper-first-letter' => ".upper-first-letter::first-letter { text-transform: uppercase !important; }\n",
            'lower-p' => ".lower-p::first-letter { text-transform: lowercase !important; }\n",
            'lower-first-letter' => ".lower-first-letter::first-letter { text-transform: lowercase !important; }\n",
            'upper-first-line' => ".upper-first-line::first-line { text-transform: uppercase !important; }\n",
            'drop-cap' => ".drop-cap::first-letter, .dropcap::first-letter { float: left; font-size: 3.2em; line-height: 0.8; font-weight: 700; margin-right: 8px; margin-bottom: -2px; }\n",
            'dropcap' => ".drop-cap::first-letter, .dropcap::first-letter { float: left; font-size: 3.2em; line-height: 0.8; font-weight: 700; margin-right: 8px; margin-bottom: -2px; }\n",
            'indent-p' => ".indent-p, .indent { text-indent: 1.5em; }\n",
            'indent' => ".indent-p, .indent { text-indent: 1.5em; }\n",
            'indent-0' => ".indent-0 { text-indent: 0 !important; }\n",

            // Títulos y display
            'title' => ".title { font-size: 35px; font-weight: 400; margin: 0px; padding: 0px; text-wrap: balance; }\n",
            'display-1' => ".display-1 { font-weight: 700; font-size: 64px; }\n",
            'display-2' => ".display-2 { font-weight: 700; font-size: 54px; }\n",
            'display-3' => ".display-3 { font-weight: 700; font-size: 44px; }\n",

            // Decoración de texto
            'underline' => ".underline { text-decoration: underline !important; }\n",
            'line-through' => ".line-through, .strike { text-decoration: line-through !important; }\n",
            'strike' => ".line-through, .strike { text-decoration: line-through !important; }\n",
            'overline' => ".overline { text-decoration: overline !important; }\n",
            'no-underline' => ".no-underline, .no-decor { text-decoration: none !important; }\n",
            'no-decor' => ".no-underline, .no-decor { text-decoration: none !important; }\n",
            'underline-dotted' => ".underline-dotted { text-decoration: underline dotted !important; }\n",
            'underline-dashed' => ".underline-dashed { text-decoration: underline dashed !important; }\n",
            'underline-wavy' => ".underline-wavy { text-decoration: underline wavy !important; }\n",
            'underline-double' => ".underline-double { text-decoration: underline double !important; }\n",
            'decor-auto' => ".decor-auto { text-decoration-thickness: auto !important; }\n",
            'decor-from-font' => ".decor-from-font { text-decoration-thickness: from-font !important; }\n",

            // Ajuste y desbordamiento de texto (Truncate, Wrap, Break)
            'truncate' => ".truncate, .text-truncate, .ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }\n",
            'text-truncate' => ".truncate, .text-truncate, .ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }\n",
            'ellipsis' => ".truncate, .text-truncate, .ellipsis { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }\n",
            'text-clip' => ".text-clip { text-overflow: clip; }\n",
            'text-nowrap' => ".text-nowrap, .nowrap { white-space: nowrap !important; }\n",
            'nowrap' => ".text-nowrap, .nowrap { white-space: nowrap !important; }\n",
            'text-wrap' => ".text-wrap { white-space: normal !important; }\n",
            'text-pre' => ".text-pre, .pre { white-space: pre !important; }\n",
            'pre' => ".text-pre, .pre { white-space: pre !important; }\n",
            'text-pre-wrap' => ".text-pre-wrap, .pre-wrap { white-space: pre-wrap !important; }\n",
            'pre-wrap' => ".text-pre-wrap, .pre-wrap { white-space: pre-wrap !important; }\n",
            'text-pre-line' => ".text-pre-line, .pre-line { white-space: pre-line !important; }\n",
            'pre-line' => ".text-pre-line, .pre-line { white-space: pre-line !important; }\n",
            'break-words' => ".break-words { overflow-wrap: break-word; word-break: break-word; }\n",
            'break-all' => ".break-all { word-break: break-all; }\n",
            'break-keep' => ".break-keep { word-break: keep-all; }\n",
            'break-normal' => ".break-normal { overflow-wrap: normal; word-break: normal; }\n",
            'text-balance' => ".text-balance { text-wrap: balance; }\n",
            'text-pretty' => ".text-pretty { text-wrap: pretty; }\n",
            'line-clamp-none' => ".line-clamp-none { display: block; -webkit-line-clamp: unset; overflow: visible; }\n",

            // Espaciado entre letras (Tracking)
            'tracking-tighter' => ".tracking-tighter { letter-spacing: -0.05em; }\n",
            'tracking-tight' => ".tracking-tight { letter-spacing: -0.025em; }\n",
            'tracking-normal' => ".tracking-normal { letter-spacing: 0em; }\n",
            'tracking-wide' => ".tracking-wide { letter-spacing: 0.025em; }\n",
            'tracking-wider' => ".tracking-wider { letter-spacing: 0.05em; }\n",
            'tracking-widest' => ".tracking-widest { letter-spacing: 0.1em; }\n",

            // Espaciado entre palabras (Word Spacing)
            'word-spacing-sm' => ".word-spacing-sm { word-spacing: 0.05em; }\n",
            'word-spacing-md' => ".word-spacing-md { word-spacing: 0.1em; }\n",
            'word-spacing-lg' => ".word-spacing-lg { word-spacing: 0.2em; }\n",

            // Pesos y estilos
            'ita' => ".ita, .italic { font-style: italic !important; }\n",
            'italic' => ".ita, .italic { font-style: italic !important; }\n",
            'not-italic' => ".not-italic, .ita-normal { font-style: normal !important; }\n",
            'ita-normal' => ".not-italic, .ita-normal { font-style: normal !important; }\n",
            'oblique' => ".oblique { font-style: oblique !important; }\n",
            'bold' => ".bold { font-weight: bold; }\n",
            'font-thin' => ".font-thin, .thin { font-weight: 100 !important; }\n",
            'thin' => ".font-thin, .thin { font-weight: 100 !important; }\n",
            'font-extralight' => ".font-extralight, .extralight { font-weight: 200 !important; }\n",
            'extralight' => ".font-extralight, .extralight { font-weight: 200 !important; }\n",
            'font-light' => ".font-light, .light { font-weight: 300 !important; }\n",
            'light' => ".font-light, .light { font-weight: 300 !important; }\n",
            'font-normal' => ".font-normal, .normal { font-weight: 400 !important; }\n",
            'normal' => ".font-normal, .normal { font-weight: 400 !important; }\n",
            'font-medium' => ".font-medium, .medium { font-weight: 500 !important; }\n",
            'medium' => ".font-medium, .medium { font-weight: 500 !important; }\n",
            'font-semibold' => ".font-semibold, .semibold { font-weight: 600 !important; }\n",
            'semibold' => ".font-semibold, .semibold { font-weight: 600 !important; }\n",
            'font-bold' => ".font-bold { font-weight: 700 !important; }\n",
            'font-extrabold' => ".font-extrabold, .extrabold { font-weight: 800 !important; }\n",
            'extrabold' => ".font-extrabold, .extrabold { font-weight: 800 !important; }\n",
            'font-black' => ".font-black, .black { font-weight: 900 !important; }\n",
            'black' => ".font-black, .black { font-weight: 900 !important; }\n",

            // Variantes y características numéricas (OpenType)
            'small-caps' => ".small-caps { font-variant: small-caps; }\n",
            'all-small-caps' => ".all-small-caps { font-variant-caps: all-small-caps; }\n",
            'tabular-nums' => ".tabular-nums, .nums-tabular { font-variant-numeric: tabular-nums; }\n",
            'nums-tabular' => ".tabular-nums, .nums-tabular { font-variant-numeric: tabular-nums; }\n",
            'proportional-nums' => ".proportional-nums { font-variant-numeric: proportional-nums; }\n",
            'ordinal' => ".ordinal { font-variant-numeric: ordinal; }\n",
            'slashed-zero' => ".slashed-zero { font-variant-numeric: slashed-zero; }\n",
            'oldstyle-nums' => ".oldstyle-nums { font-variant-numeric: oldstyle-nums; }\n",
            'lining-nums' => ".lining-nums { font-variant-numeric: lining-nums; }\n",

            // Alineación vertical
            'align-baseline' => ".align-baseline { vertical-align: baseline !important; }\n",
            'align-top' => ".align-top { vertical-align: top !important; }\n",
            'align-middle' => ".align-middle { vertical-align: middle !important; }\n",
            'align-bottom' => ".align-bottom { vertical-align: bottom !important; }\n",
            'align-text-top' => ".align-text-top { vertical-align: text-top !important; }\n",
            'align-text-bottom' => ".align-text-bottom { vertical-align: text-bottom !important; }\n",
            'align-sub' => ".align-sub, .text-sub { vertical-align: sub !important; }\n",
            'text-sub' => ".align-sub, .text-sub { vertical-align: sub !important; }\n",
            'align-super' => ".align-super, .text-super { vertical-align: super !important; }\n",
            'text-super' => ".align-super, .text-super { vertical-align: super !important; }\n",

            // Selección de texto / Touch
            'text-protected' => ".text-protected { -webkit-touch-callout: none; user-select: none; }\n",
            'select-none' => ".select-none { -webkit-user-select: none; user-select: none; }\n",
            'select-text' => ".select-text { -webkit-user-select: text; user-select: text; }\n",
            'select-all' => ".select-all { -webkit-user-select: all; user-select: all; }\n",
            'select-auto' => ".select-auto { -webkit-user-select: auto; user-select: auto; }\n",

            // Sombras de texto
            'text-outline' => ".text-outline { text-shadow: 0px 0px 3px rgb(0, 0, 0); }\n",
            'text-shadow-sm' => ".text-shadow-sm { text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2); }\n",
            'text-shadow-md' => ".text-shadow-md { text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3); }\n",
            'text-shadow-lg' => ".text-shadow-lg { text-shadow: 0 4px 8px rgba(0, 0, 0, 0.4); }\n",
            'text-shadow-none' => ".text-shadow-none { text-shadow: none !important; }\n",

            // Colores de texto base
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

        // Alineación de texto con media queries (text-l, text-r, text-c, text-j, text-center, text-left, text-right, text-justify, text-start, text-end)
        if (preg_match('/^text-(l|r|c|j|left|right|center|justify|start|end)(?:-(desk|mid|sml))?$/', $word, $m)) {
            $alignMap = [
                'l' => 'left',
                'left' => 'left',
                'r' => 'right',
                'right' => 'right',
                'c' => 'center',
                'center' => 'center',
                'j' => 'justify',
                'justify' => 'justify',
                'start' => 'start',
                'end' => 'end'
            ];
            $align = $alignMap[$m[1]] ?? 'left';
            $media = $m[2] ?? '';
            return $this->wrapMediaQuery($media, ".{$word} { text-align: {$align} !important; }");
        }

        // Line clamp dinámico (line-clamp-1, line-clamp-2, clamp-3, etc.)
        if (preg_match('/^(?:line-)?clamp-(\d+)$/', $word, $m)) {
            $lines = $m[1];
            return ".{$word} { display: -webkit-box; -webkit-box-orient: vertical; -webkit-line-clamp: {$lines}; overflow: hidden; }\n";
        }

        // Letter-spacing dinámico en px (ls-1 -> 1px, ls-n1 -> -1px, tracking-2 -> 2px)
        if (preg_match('/^(?:ls|tracking)-(n)?(\d+)$/', $word, $m)) {
            $sign = ($m[1] ?? '') === 'n' ? '-' : '';
            $val = $m[2];
            return ".{$word} { letter-spacing: {$sign}{$val}px; }\n";
        }

        // Text-indent dinámico (indent-10 -> 10px, indent-em2 -> 2em, indent-n5 -> -5px)
        if (preg_match('/^indent-(em)?(n)?(\d+)$/', $word, $m)) {
            $unit = ($m[1] ?? '') === 'em' ? 'em' : 'px';
            $sign = ($m[2] ?? '') === 'n' ? '-' : '';
            $val = $m[3];
            return ".{$word} { text-indent: {$sign}{$val}{$unit}; }\n";
        }

        // Underline offset dinámico (underline-offset-2, underline-offset-4)
        if (preg_match('/^underline-offset-(\d+)$/', $word, $m)) {
            $val = $m[1];
            return ".{$word} { text-underline-offset: {$val}px !important; }\n";
        }

        // Word spacing dinámico (ws-2, ws-4, ws-n1)
        if (preg_match('/^ws-(n)?(\d+)$/', $word, $m)) {
            $sign = ($m[1] ?? '') === 'n' ? '-' : '';
            $val = $m[2];
            return ".{$word} { word-spacing: {$sign}{$val}px; }\n";
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
