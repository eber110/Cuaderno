<?php

namespace Base\LibraryCssJit;

class ProvisionsRule extends JitRuleBase
{
    public function processWord(string $word): ?string
    {
        // Especiales de alto
        if (preg_match('/^h(-max)?(-min)?-d?vh(?:-(desk|mid|sml))?$/', $word, $m)) {
            $isMax = strpos($word, 'hmax-') === 0 || strpos($word, 'h-max-') === 0;
            $isMin = strpos($word, 'hmin-') === 0 || strpos($word, 'h-min-') === 0;
            $isDvh = strpos($word, 'dvh') !== false;
            
            $prop = 'height';
            if ($isMax) $prop = 'max-height';
            if ($isMin) $prop = 'min-height';
            
            $val = $isDvh ? '100dvh' : '100vh';
            $rule = ".{$word} { {$prop}: {$val}; }";
            return $this->wrapMediaQuery($m[3] ?? '', $rule);
        }
        if ($word === 'h-dvh') {
            return ".h-dvh { height: 100dvh; }\n";
        }
        if ($word === 'h-vh') {
            return ".h-vh { height: 100vh; }\n";
        }

        // Width
        if (preg_match('/^wpx-?(min|max)(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $type = $m[1] === 'min' ? 'min-width' : 'max-width';
            return $this->wrapMediaQuery($m[2] ?? '', ".{$word} { {$type}: {$m[3]}px; }");
        }
        if (preg_match('/^w-?(min|max)(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $type = $m[1] === 'min' ? 'min-width' : 'max-width';
            return $this->wrapMediaQuery($m[2] ?? '', ".{$word} { {$type}: {$m[3]}%; }");
        }
        if (preg_match('/^w(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { width: {$m[2]}%; }");
        }
        if (preg_match('/^wpx(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { width: {$m[2]}px; }");
        }
        if (preg_match('/^wem(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { width: {$m[2]}em; }");
        }

        // Height
        if (preg_match('/^hpx-?(min|max)(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $type = $m[1] === 'min' ? 'min-height' : 'max-height';
            return $this->wrapMediaQuery($m[2] ?? '', ".{$word} { {$type}: {$m[3]}px; }");
        }
        if (preg_match('/^h-?(min|max)(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $type = $m[1] === 'min' ? 'min-height' : 'max-height';
            return $this->wrapMediaQuery($m[2] ?? '', ".{$word} { {$type}: {$m[3]}%; }");
        }
        if (preg_match('/^h(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { height: {$m[2]}%; }");
        }
        if (preg_match('/^hpx(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { height: {$m[2]}px; }");
        }
        if (preg_match('/^hem(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { min-height: {$m[2]}em; }");
        }

        // Margin Global & Específicos
        if (preg_match('/^m(t|r|b|l)?(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $dir = $m[1] ?? '';
            $media = $m[2] ?? '';
            $val = $m[3] ?? '';
            
            $prop = 'margin';
            if ($dir === 't') $prop .= '-top';
            if ($dir === 'r') $prop .= '-right';
            if ($dir === 'b') $prop .= '-bottom';
            if ($dir === 'l') $prop .= '-left';
            
            return $this->wrapMediaQuery($media, ".{$word} { {$prop}: {$val}px; }");
        }

        // Padding Global & Específicos
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

        // Font-size
        if (preg_match('/^x(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { font-size: {$m[2]}px; }");
        }
        if (preg_match('/^xem(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { font-size: {$m[2]}em; }");
        }

        // Border radius
        if (preg_match('/^br(tl|tr|bl|br)?(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            $dir = $m[1] ?? '';
            $media = $m[2] ?? '';
            $val = $m[3] ?? '';
            
            $prop = 'border-radius';
            if ($dir === 'tl') $prop = 'border-top-left-radius';
            if ($dir === 'tr') $prop = 'border-top-right-radius';
            if ($dir === 'bl') $prop = 'border-bottom-left-radius';
            if ($dir === 'br') $prop = 'border-bottom-right-radius';
            
            $rule = ".{$word} { {$prop}: {$val}px !important;";
            if ($dir === '') $rule .= " overflow: clip;";
            $rule .= " }";
            
            return $this->wrapMediaQuery($media, $rule);
        }

        // Z-index
        if (preg_match('/^z(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { z-index: {$m[2]}; }");
        }
        if (preg_match('/^z-n(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
            return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { z-index: -{$m[2]}; }");
        }

        // No hizo match
        return null;
    }
}
