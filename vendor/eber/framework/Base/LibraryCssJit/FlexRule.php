<?php

namespace Base\LibraryCssJit;

class FlexRule extends JitRuleBase
{
    private $verticalAlignments = [
        'top' => 'start',
        'bottom' => 'end',
        'center' => 'center',
        'between' => 'space-between',
        'around' => 'space-around',
        'evenly' => 'space-evenly',
        'baseline' => 'baseline',
        'stretch' => 'stretch'
    ];

    private $horizontalAlignments = [
        'start' => 'start',
        'end' => 'end',
        'center' => 'center',
        'between' => 'space-between',
        'around' => 'space-around',
        'evenly' => 'space-evenly',
        'baseline' => 'baseline',
        'stretch' => 'stretch'
    ];

    public function processWord(string $word): ?string
    {
        // Bases
        if ($word === 'flex') {
            return ".flex { display: flex; }\n";
        }
        if ($word === 'wrap') {
            return ".wrap { flex-wrap: wrap; }\n";
        }
        if ($word === 'no-wrap') {
            return ".no-wrap { flex-wrap: nowrap; }\n";
        }

        // Flex Direction con media queries
        if (preg_match('/^flex-(column|row)(?:-(desk|mid|sml))?$/', $word, $m)) {
            $dir = $m[1];
            $media = $m[2] ?? '';
            $important = $media ? ' !important' : '';
            return $this->wrapMediaQuery($media, ".{$word} { display: flex{$important}; flex-direction: {$dir}{$important}; }");
        }

        // Combinaciones de alineación (e.g. top-center, top-center-desk)
        // Ya que el HTML tendrá: class="flex-column top-center"
        // JIT recibe "top-center" y debe generar tanto column como row para asegurar compatibilidad.
        if (preg_match('/^([a-z]+)-([a-z]+)(?:-(desk|mid|sml))?$/', $word, $m)) {
            $vert = $m[1];
            $horz = $m[2];
            $media = $m[3] ?? '';

            if (isset($this->verticalAlignments[$vert]) && isset($this->horizontalAlignments[$horz])) {
                $yCol = $this->verticalAlignments[$vert]; // justify-content en column
                $xCol = $this->horizontalAlignments[$horz]; // align-items en column

                $yRow = $this->verticalAlignments[$vert]; // align-items en row
                $xRow = $this->horizontalAlignments[$horz]; // justify-content en row

                $important = $media ? ' !important' : '';
                
                $css = ".flex-column.{$word}, .flex-column-desk.{$word}, .flex-column-mid.{$word}, .flex-column-sml.{$word} { justify-content: {$yCol}{$important}; align-items: {$xCol}{$important}; }\n";
                $css .= "  .flex-row.{$word}, .flex-row-desk.{$word}, .flex-row-mid.{$word}, .flex-row-sml.{$word} { align-items: {$yRow}{$important}; justify-content: {$xRow}{$important}; }";

                return $this->wrapMediaQuery($media, $css);
            }
        }

        return null;
    }
}
