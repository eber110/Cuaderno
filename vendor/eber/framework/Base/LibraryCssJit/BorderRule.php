<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para utilidades de bordes (.border1..N, .border-b-1..N, direccionales y anchos).
 * Vinculada dinámicamente a los colores configurados en _configTheme.css con fallback a gris claro (#d3d3d3).
 */
class BorderRule extends JitRuleBase
{
  /**
   * Procesa una palabra y devuelve la regla CSS de borde correspondiente o null.
   *
   * @param string $word Palabra/clase detectada en el código fuente
   * @return string|null
   */
  public function processWord(string $word): ?string
  {
    $statics = [
      "border-none" => ".border-none { border: none !important; }\n",
      "border-0" => ".border-0 { border-width: 0px !important; }\n",
      "border-t-none" => ".border-t-none { border-top: none !important; }\n",
      "border-b-none" => ".border-b-none { border-bottom: none !important; }\n",
      "border-l-none" => ".border-l-none { border-left: none !important; }\n",
      "border-r-none" => ".border-r-none { border-right: none !important; }\n",
      "border-solid" => ".border-solid { border-style: solid !important; }\n",
      "border-dashed" => ".border-dashed { border-style: dashed !important; }\n",
      "border-dotted" => ".border-dotted { border-style: dotted !important; }\n",
      "border-double" => ".border-double { border-style: double !important; }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // 1. Bordes completos por número de color: .border1, .border2... .borderN (con soporte responsive)
    if (preg_match('/^border(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $media = $m[2] ?? "";
      $color = $this->getColorVar($index);
      return $this->wrapMediaQuery($media, ".{$word} { border: 1px solid {$color}; }");
    }

    // 2. Bordes direccionales por número de color: .border-b-1, .border-t-2, .border-l-3, .border-r-4, .border-x-1, .border-y-1...
    if (preg_match('/^border-(b|t|l|r|x|y)-(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $dir = $m[1];
      $index = (int)$m[2];
      if ($index < 1) {
        return null;
      }
      $media = $m[3] ?? "";
      $color = $this->getColorVar($index);

      $declaration = match ($dir) {
        "b" => "border-bottom: 1px solid {$color};",
        "t" => "border-top: 1px solid {$color};",
        "l" => "border-left: 1px solid {$color};",
        "r" => "border-right: 1px solid {$color};",
        "x" => "border-left: 1px solid {$color}; border-right: 1px solid {$color};",
        "y" => "border-top: 1px solid {$color}; border-bottom: 1px solid {$color};",
      };

      return $this->wrapMediaQuery($media, ".{$word} { {$declaration} }");
    }

    // 3. Bordes con efecto hover: .border1-hover, .border2-hover... .borderN-hover
    if (preg_match('/^border(\d+)-hover$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      $hover = $this->getColorHoverVar($index);
      return ".{$word} { border: 1px solid {$color}; transition: var(--transition, all 0.15s ease-in-out); }\n.{$word}:hover { border-color: {$hover}; }\n";
    }

    // 4. Ancho de borde en píxeles: .border-w1, .border-w2... .border-w10 (con soporte responsive)
    if (preg_match('/^border-w(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $px = (int)$m[1];
      $media = $m[2] ?? "";
      return $this->wrapMediaQuery($media, ".{$word} { border-width: {$px}px !important; }");
    }

    // 5. Ancho de borde direccional: .border-b-w1, .border-t-w2...
    if (preg_match('/^border-(b|t|l|r|x|y)-w(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $dir = $m[1];
      $px = (int)$m[2];
      $media = $m[3] ?? "";
      $prop = match ($dir) {
        "b" => "border-bottom-width: {$px}px !important;",
        "t" => "border-top-width: {$px}px !important;",
        "l" => "border-left-width: {$px}px !important;",
        "r" => "border-right-width: {$px}px !important;",
        "x" => "border-left-width: {$px}px !important; border-right-width: {$px}px !important;",
        "y" => "border-top-width: {$px}px !important; border-bottom-width: {$px}px !important;",
      };
      return $this->wrapMediaQuery($media, ".{$word} { {$prop} }");
    }

    return null;
  }
}
