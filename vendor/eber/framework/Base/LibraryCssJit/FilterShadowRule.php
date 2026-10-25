<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para filtros y sombras (.shadow1..N, direccionales, inset, .outline1..N, .sepiaX, .shineX, .opmixX).
 * Vinculada dinámicamente a los colores configurados en _configTheme.css con fallback a gris claro (#d3d3d3).
 */
class FilterShadowRule extends JitRuleBase
{
  /**
   * Procesa una palabra y devuelve la regla CSS de sombra o filtro correspondiente o null.
   *
   * @param string $word Palabra/clase detectada en el código fuente
   * @return string|null
   */
  public function processWord(string $word): ?string
  {
    $statics = [
      "shadow" => ".shadow { filter: drop-shadow(5px 5px 2.3px rgba(0, 0, 0, 0.43)); }\n",
      "shadow-hover" => ".shadow-hover { filter: drop-shadow(5px 5px 2.3px rgba(0, 0, 0, 0.43)); transition: var(--transition-slow, all 0.25s ease-in-out); }\n.shadow-hover:hover { filter: drop-shadow(2px 2px 2.3px rgba(0, 0, 0, 0.43)); }\n",
      "shadow-c" => ".shadow-c { --shadow-color: rgba(0, 0, 0, 0.43); }\n",
      "shadow-inset-c" => ".shadow-inset-c { --inset-color: rgba(0, 0, 0, 0.25); }\n",
      "shadow-s-xs" => ".shadow-s-xs { --shadow-x: calc(var(--shadow-x, 0px) * 0.4); --shadow-y: calc(var(--shadow-y, 0px) * 0.4); --shadow-blur: 1px; }\n",
      "shadow-s-sm" => ".shadow-s-sm { --shadow-x: calc(var(--shadow-x, 0px) * 0.6); --shadow-y: calc(var(--shadow-y, 0px) * 0.6); --shadow-blur: 1.5px; }\n",
      "shadow-s-md" => ".shadow-s-md { --shadow-blur: 2.3px; }\n",
      "shadow-s-lg" => ".shadow-s-lg { --shadow-blur: 4px; }\n",
      "shadow-s-xl" => ".shadow-s-xl { --shadow-blur: 6px; }\n",
      "shadow-s-2xl" => ".shadow-s-2xl { --shadow-blur: 10px; }\n",
      "shine-border" => ".shine-border { filter: drop-shadow(-0.01px -0.1px 0.2px rgb(255, 255, 255)) drop-shadow(0.01px 0.1px 0.2px rgb(44, 44, 44)); }\n.shine-border:hover { filter: drop-shadow(-0.1px -0.1px 0.2px rgb(255, 255, 255)) drop-shadow(0.01px 0.1px 0.2px rgb(44, 44, 44)); }\n",
      "shine-hover" => ".shine-hover { filter: drop-shadow(0px 0px 0px rgba(255, 255, 255, 0)); transition: var(--transition-slow, all 0.25s ease-in-out); }\n.shine-hover:hover { filter: drop-shadow(0px 0px 1px rgb(255, 255, 255)) drop-shadow(0px 0px 10px rgba(255, 255, 255, 0.5)); }\n",
      "shine-shadow" => ".shine-shadow { filter: drop-shadow(0px 0px 1px rgb(255, 255, 255)) drop-shadow(0px 0px 10px rgb(255, 255, 255)); }\n",
      "shadow-header" => ".shadow-header { box-shadow: 0px 15px 15px -23px rgba(0, 0, 0, 1); -webkit-box-shadow: 0px 15px 15px -23px rgba(0, 0, 0, 1); -moz-box-shadow: 0px 15px 15px -23px rgba(0, 0, 0, 1); }\n",
      "blur" => ".blur { backdrop-filter: blur(5px) !important; -webkit-backdrop-filter: blur(5px) !important; }\n",
      "sepia" => ".sepia { filter: sepia(40%) !important; }\n",
      "logo-overlay" => ".logo-overlay { background-color: var(--body, #ffffff); mix-blend-mode: luminosity; }\n",
      "photo-overlay" => ":where(a:has(> img.photo-overlay)), :where(figure:has(> img.photo-overlay)) { position: relative; display: block; overflow: hidden; width: 100%; height: 100%; }\nimg.photo-overlay { display: block; width: 100%; height: 100%; object-fit: cover; }\na:has(> img.photo-overlay)::after, figure:has(> img.photo-overlay)::after { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--body, #ffffff); mix-blend-mode: color; opacity: var(--photo-overlay-opacity, 0.2); pointer-events: none; z-index: 2; transition: opacity var(--transition, 0.3s ease); }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // 1. Sombras numeradas por color: .shadow1, .shadow2... .shadow10
    if (preg_match('/^shadow(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { filter: drop-shadow(5px 5px 2.3px {$color}); }\n";
    }

    // 2. Sombras numeradas con hover: .shadow1-hover, .shadow2-hover... .shadow10-hover
    if (preg_match('/^shadow(\d+)-hover$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { filter: drop-shadow(5px 5px 2.3px {$color}); transition: var(--transition-slow, all 0.25s ease-in-out); }\n.{$word}:hover { filter: drop-shadow(2px 2px 2.3px {$color}); }\n";
    }

    // 3. Sombras direccionales combinables: .shadow-l, .shadow-r, .shadow-t, .shadow-b
    if (preg_match('/^shadow-(l|r|t|b)$/', $word, $m)) {
      $dir = $m[1];
      $base = $this->getDirectionalShadowBase();
      $dirMap = [
        "l" => "--shadow-x: -5px;",
        "r" => "--shadow-x: 5px;",
        "t" => "--shadow-y: -5px;",
        "b" => "--shadow-y: 5px;",
      ];
      return $base . ".{$word} { {$dirMap[$dir]} filter: drop-shadow(var(--shadow-x) var(--shadow-y) var(--shadow-blur) var(--shadow-color)); }\n";
    }

    // 4. Offsets numéricos de sombras direccionales: .shadow-o-3, .shadow-o-5, .shadow-o-10...
    if (preg_match('/^shadow-o-(\d+)$/', $word, $m)) {
      $offset = (int)$m[1];
      return ".shadow-l.{$word} { --shadow-x: -{$offset}px; }\n.shadow-r.{$word} { --shadow-x: {$offset}px; }\n.shadow-t.{$word} { --shadow-y: -{$offset}px; }\n.shadow-b.{$word} { --shadow-y: {$offset}px; }\n";
    }

    // 5. Color de sombra direccional combinable: .shadow-c-1, .shadow-c-2... .shadow-c-10
    if (preg_match('/^shadow-c-(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { --shadow-color: {$color}; }\n";
    }

    // 6. Sombras direccionales legacy con color: .shadow-l-1..N, .shadow-r-1..N, .shadow-t-1..N, .shadow-b-1..N
    if (preg_match('/^shadow-(l|r|t|b)-(\d+)$/', $word, $m)) {
      $dir = $m[1];
      $index = (int)$m[2];
      if ($index < 1) {
        return null;
      }
      $base = $this->getDirectionalShadowBase();
      $color = $this->getColorVar($index);
      $dirMap = [
        "l" => "--shadow-x: -5px;",
        "r" => "--shadow-x: 5px;",
        "t" => "--shadow-y: -5px;",
        "b" => "--shadow-y: 5px;",
      ];
      return $base . ".{$word} { {$dirMap[$dir]} --shadow-color: {$color}; filter: drop-shadow(var(--shadow-x) var(--shadow-y) var(--shadow-blur) var(--shadow-color)); }\n";
    }

    // 7. Sombras interiores (inset) combinables: .shadow-inset-l, .shadow-inset-r, .shadow-inset-t, .shadow-inset-b
    if (preg_match('/^shadow-inset-(l|r|t|b)$/', $word, $m)) {
      $dir = $m[1];
      $base = $this->getInsetShadowBase();
      $dirMap = [
        "l" => "--inset-x: 5px;",
        "r" => "--inset-x: -5px;",
        "t" => "--inset-y: 5px;",
        "b" => "--inset-y: -5px;",
      ];
      return $base . ".{$word} { {$dirMap[$dir]} box-shadow: inset var(--inset-x) var(--inset-y) var(--inset-blur) var(--inset-spread) var(--inset-color); }\n";
    }

    // 8. Color de sombra interior: .shadow-inset-c-1, .shadow-inset-c-2... .shadow-inset-c-10
    if (preg_match('/^shadow-inset-c-(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { --inset-color: {$color}; }\n";
    }

    // 9. Contornos por color: .outline1, .outline2... .outline10
    if (preg_match('/^outline(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { filter: drop-shadow(0.5px 0.5px 1px {$color}); }\n";
    }

    // 10. Filtro sepia porcentual: .sepia10, .sepia20... .sepia100
    if (preg_match('/^sepia(\d+)$/', $word, $m)) {
      $pct = (int)$m[1];
      if ($pct >= 0 && $pct <= 100) {
        return ".{$word} { filter: sepia({$pct}%) !important; }\n";
      }
    }

    // 11. Brillo: .shine1 (1.0) a .shine10 (0.1)
    if (preg_match('/^shine(\d+)$/', $word, $m)) {
      $step = (int)$m[1];
      if ($step >= 1 && $step <= 10) {
        $brightness = round((11 - $step) / 10, 1);
        return ".{$word} { filter: brightness({$brightness}); }\n";
      }
    }

    // 12. Opacidad de photo-overlay: .opmix1 (0.1) a .opmix9 (0.9) / .opmix10 (1.0)
    if (preg_match('/^opmix(\d+)$/', $word, $m)) {
      $step = (int)$m[1];
      if ($step >= 1 && $step <= 10) {
        $opacity = round($step / 10, 1);
        return ".{$word} { --photo-overlay-opacity: {$opacity} !important; }\n";
      }
    }

    // 13. Blur directo: .blur5, .blur10...
    if (preg_match('/^blur(\d+)$/', $word, $m)) {
      $px = (int)$m[1];
      return ".{$word} { backdrop-filter: blur({$px}px) !important; -webkit-backdrop-filter: blur({$px}px) !important; }\n";
    }

    return null;
  }

  /**
   * Emite una sola vez las variables base para sombras direccionales.
   *
   * @return string
   */
  private function getDirectionalShadowBase(): string
  {
    return $this->emitOnce(
      "shadow_dir_base",
      "[class*=\"shadow-l\"], [class*=\"shadow-r\"], [class*=\"shadow-t\"], [class*=\"shadow-b\"] { --shadow-x: 0px; --shadow-y: 0px; --shadow-blur: 2.3px; --shadow-color: rgba(0, 0, 0, 0.43); }\n"
    );
  }

  /**
   * Emite una sola vez las variables base para sombras interiores (inset).
   *
   * @return string
   */
  private function getInsetShadowBase(): string
  {
    return $this->emitOnce(
      "shadow_inset_base",
      "[class*=\"shadow-inset-l\"], [class*=\"shadow-inset-r\"], [class*=\"shadow-inset-t\"], [class*=\"shadow-inset-b\"] { --inset-x: 0px; --inset-y: 0px; --inset-blur: 4px; --inset-spread: 0px; --inset-color: rgba(0, 0, 0, 0.25); }\n"
    );
  }
}
