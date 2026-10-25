<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para utilidades generales de visualización, cursores, desbordamiento, imágenes, transiciones y capas.
 */
class StylesRule extends JitRuleBase
{
  /**
   * Procesa una palabra y devuelve la regla CSS utilitaria correspondiente o null.
   *
   * @param string $word Palabra/clase detectada en el código fuente
   * @return string|null
   */
  public function processWord(string $word): ?string
  {
    $statics = [
      "textareaResize" => ".textareaResize { field-sizing: content; }\n",
      "logo" => ".logo { height: 40px; width: auto; margin-right: 10px; padding: 5px 0px; object-fit: cover; }\n",
      "no-desk" => "@media screen and (min-width: 993px) { .no-desk, .no-desk-css { display: none !important; } }\n",
      "no-desk-css" => "@media screen and (min-width: 993px) { .no-desk, .no-desk-css { display: none !important; } }\n",
      "no-tablet" => "@media screen and (min-width: 577px) and (max-width: 992px) { .no-tablet, .no-tablet-css { display: none !important; } }\n",
      "no-tablet-css" => "@media screen and (min-width: 577px) and (max-width: 992px) { .no-tablet, .no-tablet-css { display: none !important; } }\n",
      "no-phone" => "@media screen and (max-width: 576px) { .no-phone, .no-phone-css { display: none !important; } }\n",
      "no-phone-css" => "@media screen and (max-width: 576px) { .no-phone, .no-phone-css { display: none !important; } }\n",
      "hidden" => ".hidden { display: none !important; }\n",
      "inline-block" => ".inline-block { display: inline-block !important; }\n",
      "inline" => ".inline { display: inline !important; }\n",
      "block" => ".block { display: block !important; }\n",
      "grid-row-center" => ".grid-row-center { justify-items: center; }\n",

      // Transiciones
      "transition" => ".transition { transition: var(--transition, all 0.15s ease-in-out) !important; }\n",
      "transition-slow" => ".transition-slow { transition: var(--transition-slow, all 0.25s ease-in-out) !important; }\n",
      "no-theme-transition" => ".no-theme-transition { transition: none !important; }\n",

      // Iconos modo oscuro y tamaños de icono
      "dm-icon-light" => ".dm-icon-light { font-size: 18px; line-height: 1; }\n",
      "dm-icon-dark" => ".dm-icon-dark { font-size: 18px; line-height: 1; }\n",
      "ico" => ".ico { width: 16px; height: 16px; object-fit: cover; }\n",
      "icon" => ".icon { width: 36px !important; height: 36px !important; border-radius: 50% !important; }\n",

      // Cursores e interacción
      "cursor-default" => ".cursor-default { cursor: default !important; }\n",
      "pointer" => ".pointer { cursor: pointer !important; }\n",
      "no-href" => ".no-href { pointer-events: none; cursor: default; }\n",
      "inactive" => ".inactive { color: var(--text-inactive, #bdbdbd); -webkit-touch-callout: none; -webkit-user-select: none; user-select: none; }\n",

      // Relleno y proporción de imágenes
      "fill" => ".fill { object-fit: fill !important; }\n",
      "contain" => ".contain { object-fit: contain !important; }\n",
      "cover" => ".cover { object-fit: cover !important; height: 100%; width: 100%; }\n",
      "cover-hpx" => ".cover-hpx { object-fit: cover !important; width: 100%; }\n",
      "image-limit" => ".image-limit { min-height: 200px; max-height: 400px; }\n",
      "ar-square" => ".ar-square { aspect-ratio: 1/1 !important; object-fit: cover; }\n",
      "ar-rect" => ".ar-rect { aspect-ratio: 16/9; object-fit: cover; }\n",
      "ar-rect-vert" => ".ar-rect-vert { aspect-ratio: 9/12; object-fit: cover; }\n",
      "img-blur" => ".img-blur { filter: blur(5px); }\n",

      // Overflow y opacidad
      "overflow" => ".overflow { overflow: clip; }\n",
      "overflowX" => ".overflowX { overflow-x: clip; }\n",
      "overflow-hidden" => ".overflow-hidden { overflow: hidden; }\n",
      "scroll" => ".scroll { overflow: scroll; }\n",
      "opacity0" => ".opacity0 { opacity: 0; }\n",
      "opacity1" => ".opacity1 { opacity: 1; }\n",

      // Capas y alineación auxiliar
      "before" => ".before { z-index: 1000000; }\n",
      "before-form" => ".before-form { z-index: 1000200; }\n",
      "before-form-overlay" => ".before-form-overlay { z-index: 1000150; }\n",
      "before-menu" => ".before-menu { z-index: 1000100; }\n",
      "before-menu-overlay" => ".before-menu-overlay { z-index: 1000050; }\n",
      "centered-object" => ".centered-object { display: flex; flex-direction: column; align-items: center; justify-content: center; }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // Z-index con prefijo largo: .z-index-5, .z-index-10...
    if (preg_match('/^z-index-(\d+)$/', $word, $m)) {
      return ".{$word} { z-index: {$m[1]}; }\n";
    }

    // SVG Opacity: .svg-opacity-50
    if (preg_match('/^svg-opacity-(\d+)$/', $word, $m)) {
      $val = $m[1] / 100;
      return ".{$word} { fill-opacity: {$val}; }\n";
    }

    return null;
  }
}
