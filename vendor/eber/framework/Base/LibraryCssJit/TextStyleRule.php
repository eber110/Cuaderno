<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para estilos y efectos avanzados de texto:
 * - Texto sin fondo con borde de color (Text Stroke / Contorno).
 * - Texto con degradado multidireccional (left, right, top, bottom, center/radial, diag).
 * - Sombras de texto (negra con opacidad ajustable y ligadas a variables de color).
 * - Efectos sugeridos: Neón / Glow, 3D extruido, Relieve / Grabado (emboss/deboss),
 *   Shimmer metálico, Difuminado (spoiler / blur) y Subrayado decorativo temático.
 */
class TextStyleRule extends JitRuleBase
{
  /**
   * Procesa una palabra y devuelve la regla CSS correspondiente o null.
   *
   * @param string $word Palabra/clase detectada en el código fuente
   * @return string|null
   */
  public function processWord(string $word): ?string
  {
    $statics = [
      // Sombras negras predefinidas
      "text-shadow-black-sm" => ".text-shadow-black-sm { text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.4); }\n",
      "text-shadow-black-md" => ".text-shadow-black-md { text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); }\n",
      "text-shadow-black-lg" => ".text-shadow-black-lg { text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.6); }\n",
      "text-shadow-black-xl" => ".text-shadow-black-xl { text-shadow: 6px 6px 16px rgba(0, 0, 0, 0.7); }\n",

      // Relieve y grabado
      "text-emboss" => ".text-emboss { text-shadow: 1px 1px 1px rgba(255, 255, 255, 0.7), -1px -1px 1px rgba(0, 0, 0, 0.4); }\n",
      "text-deboss" => ".text-deboss { text-shadow: -1px -1px 1px rgba(255, 255, 255, 0.7), 1px 1px 1px rgba(0, 0, 0, 0.4); }\n",

      // 3D neutro
      "text-3d" => ".text-3d { text-shadow: 1px 1px 0 #999, 2px 2px 0 #888, 3px 3px 0 #777, 4px 4px 0 #666, 5px 5px 6px rgba(0, 0, 0, 0.5); }\n",

      // Shimmer neutro (brillo plateado)
      "text-shimmer" => ".text-shimmer { background: linear-gradient(90deg, #888 0%, #fff 50%, #888 100%); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent; display: inline-block; }\n",

      // Texto difuminado (spoiler / blur)
      "text-blur" => ".text-blur { filter: blur(4px); transition: filter 0.25s ease; cursor: pointer; }\n.text-blur:hover { filter: none; }\n",
      "text-blur-sm" => ".text-blur-sm { filter: blur(2px); transition: filter 0.25s ease; cursor: pointer; }\n.text-blur-sm:hover { filter: none; }\n",
      "text-blur-lg" => ".text-blur-lg { filter: blur(8px); transition: filter 0.25s ease; cursor: pointer; }\n.text-blur-lg:hover { filter: none; }\n",

      // Trazos estáticos
      "text-stroke-white" => ".text-stroke-white { -webkit-text-stroke: 1px #ffffff; -webkit-text-fill-color: transparent; color: transparent; }\n",
      "text-stroke-black" => ".text-stroke-black { -webkit-text-stroke: 1px #000000; -webkit-text-fill-color: transparent; color: transparent; }\n",

      // Subrayados grosores auxiliares
      "underline-w2" => ".underline-w2 { text-decoration-thickness: 2px !important; }\n",
      "underline-w3" => ".underline-w3 { text-decoration-thickness: 3px !important; }\n",
      "underline-w4" => ".underline-w4 { text-decoration-thickness: 4px !important; }\n",

      // Dirección, espejo y orientación de texto
      "text-normal" => ".text-normal { writing-mode: horizontal-tb !important; text-orientation: mixed !important; direction: ltr !important; --text-rotate: 0deg !important; rotate: 0deg !important; scale: 1 1 !important; transform: none !important; unicode-bidi: normal !important; }\n",
      "text-mirror" => ".text-mirror, .text-mirror-x, .text-flip-x { display: inline-block; scale: -1 1; }\n",
      "text-mirror-x" => ".text-mirror, .text-mirror-x, .text-flip-x { display: inline-block; scale: -1 1; }\n",
      "text-flip-x" => ".text-mirror, .text-mirror-x, .text-flip-x { display: inline-block; scale: -1 1; }\n",
      "text-mirror-y" => ".text-mirror-y, .text-flip-y { display: inline-block; scale: 1 -1; }\n",
      "text-flip-y" => ".text-mirror-y, .text-flip-y { display: inline-block; scale: 1 -1; }\n",
      "text-mirror-xy" => ".text-mirror-xy, .text-flip-xy { display: inline-block; scale: -1 -1; }\n",
      "text-flip-xy" => ".text-mirror-xy, .text-flip-xy { display: inline-block; scale: -1 -1; }\n",
      "text-inverted" => ".text-inverted, .text-upside-down { display: inline-block; --text-rotate: 180deg; rotate: 180deg; }\n",
      "text-upside-down" => ".text-inverted, .text-upside-down { display: inline-block; --text-rotate: 180deg; rotate: 180deg; }\n",
      "text-reverse" => ".text-reverse, .text-bidi-override { direction: rtl; unicode-bidi: bidi-override; display: inline-block; }\n",
      "text-bidi-override" => ".text-reverse, .text-bidi-override { direction: rtl; unicode-bidi: bidi-override; display: inline-block; }\n",
      "text-rtl" => ".text-rtl { direction: rtl; }\n",
      "text-ltr" => ".text-ltr { direction: ltr; }\n",

      // Modos de escritura vertical nativos
      "text-vertical" => ".text-vertical, .text-vertical-rl { writing-mode: vertical-rl; }\n",
      "text-vertical-rl" => ".text-vertical, .text-vertical-rl { writing-mode: vertical-rl; }\n",
      "text-vertical-lr" => ".text-vertical-lr { writing-mode: vertical-lr; }\n",
      "text-horizontal" => ".text-horizontal { writing-mode: horizontal-tb; }\n",
      "text-upright" => ".text-upright { writing-mode: vertical-rl; text-orientation: upright; }\n",
      "text-sideways" => ".text-sideways { writing-mode: vertical-rl; text-orientation: sideways; }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // 1. TEXT STROKE (Texto hueco sin fondo, solo borde de color): .text-stroke1..N, .text-stroke1-w2..w6
    if (preg_match('/^text-stroke(\d+)(?:-w(\d+))?(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $width = isset($m[2]) && $m[2] !== "" ? (int)$m[2] : 1;
      $media = $m[3] ?? "";
      $color = $this->getColorVar($index);
      return $this->wrapMediaQuery(
        $media,
        ".{$word} { -webkit-text-stroke: {$width}px {$color}; -webkit-text-fill-color: transparent; color: transparent; display: inline-block; }"
      );
    }

    // 1b. TEXT STROKE CON RELLENO (Borde sobre texto sólido): .text-stroke-fill1..N, .text-stroke-fill1-w2
    if (preg_match('/^text-stroke-fill(\d+)(?:-w(\d+))?(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $width = isset($m[2]) && $m[2] !== "" ? (int)$m[2] : 1;
      $media = $m[3] ?? "";
      $color = $this->getColorVar($index);
      return $this->wrapMediaQuery(
        $media,
        ".{$word} { -webkit-text-stroke: {$width}px {$color}; display: inline-block; }"
      );
    }

    // 1c. TEXT STROKE HOVER: .text-stroke1-hover..N
    if (preg_match('/^text-stroke(\d+)-hover$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      $hover = $this->getColorHoverVar($index);
      return ".{$word} { -webkit-text-stroke: 1px {$color}; -webkit-text-fill-color: transparent; color: transparent; display: inline-block; transition: var(--transition, all 0.15s ease-in-out); }\n.{$word}:hover { -webkit-text-stroke-color: {$hover}; }\n";
    }

    // 1d. GROSOR DE TRAZO INDEPENDIENTE: .text-stroke-w1..text-stroke-w10
    if (preg_match('/^text-stroke-w(\d+)$/', $word, $m)) {
      $width = (int)$m[1];
      return ".{$word} { -webkit-text-stroke-width: {$width}px !important; }\n";
    }

    // 2. TEXT GRADIENT (Degradado dependiente de variables con direcciones: left, right, top, bottom, center/radial, diag):
    // Ejemplos: .text-gradient-r-1-2, .text-gradient-right-1, .text-gradient-b-3-4, .text-gradient-center-1-2, .text-gradient-radial-2-6
    if (preg_match('/^text-gradient-(r|l|t|b|c|right|left|top|bottom|center|radial|diag|tr|br)-(\d+)(?:-(\d+))?(?:-(desk|mid|sml))?$/', $word, $m)) {
      $dirRaw = $m[1];
      $indexA = (int)$m[2];
      $indexB = isset($m[3]) && $m[3] !== "" ? (int)$m[3] : null;
      $media = $m[4] ?? "";

      if ($indexA < 1) {
        return null;
      }

      $colorA = $this->getColorVar($indexA);
      $colorB = $indexB !== null && $indexB >= 1
        ? $this->getColorVar($indexB)
        : "color-mix(in srgb, {$colorA}, white 40%)";

      $gradientCss = match ($dirRaw) {
        "r", "right" => "linear-gradient(to right, {$colorA}, {$colorB})",
        "l", "left" => "linear-gradient(to left, {$colorA}, {$colorB})",
        "t", "top" => "linear-gradient(to top, {$colorA}, {$colorB})",
        "b", "bottom" => "linear-gradient(to bottom, {$colorA}, {$colorB})",
        "c", "center", "radial" => "radial-gradient(circle, {$colorA}, {$colorB})",
        "diag", "tr" => "linear-gradient(45deg, {$colorA}, {$colorB})",
        "br" => "linear-gradient(135deg, {$colorA}, {$colorB})",
      };

      $rule = ".{$word} { background-image: {$gradientCss}; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent; display: inline-block; }";
      return $this->wrapMediaQuery($media, $rule);
    }

    // 3. SOMBRA NEGRA CON OPACIDAD AJUSTABLE: .text-shadow-op10..100 o .text-shadow-black-10..100
    if (preg_match('/^text-shadow-(?:op|black-)(\d+)$/', $word, $m)) {
      $pct = (int)$m[1];
      if ($pct >= 0 && $pct <= 100) {
        $opacity = $pct / 100;
        return ".{$word} { text-shadow: 2px 2px 4px rgba(0, 0, 0, {$opacity}); }\n";
      }
    }

    // 4. SOMBRA SEGÚN VARIABLES DE COLOR: .text-shadow1..N y variantes de tamaño (sm, md, lg, xl)
    if (preg_match('/^text-shadow(\d+)(?:-(sm|md|lg|xl))?(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $size = $m[2] ?? "md";
      $media = $m[3] ?? "";
      $color = $this->getColorVar($index);

      $shadowDef = match ($size) {
        "sm" => "1px 1px 2px {$color}",
        "md" => "2px 2px 6px {$color}",
        "lg" => "4px 4px 12px {$color}",
        "xl" => "6px 6px 20px {$color}",
      };

      return $this->wrapMediaQuery($media, ".{$word} { text-shadow: {$shadowDef}; }");
    }

    // 5. SUGERENCIA: TEXT GLOW / NEON: .text-glow1..N (sm, lg)
    if (preg_match('/^text-glow(\d+)(?:-(sm|lg))?(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $size = $m[2] ?? "md";
      $media = $m[3] ?? "";
      $color = $this->getColorVar($index);

      $glowDef = match ($size) {
        "sm" => "0 0 4px {$color}, 0 0 8px {$color}",
        "lg" => "0 0 10px {$color}, 0 0 20px {$color}, 0 0 40px {$color}, 0 0 60px {$color}",
        default => "0 0 5px {$color}, 0 0 15px {$color}, 0 0 25px {$color}",
      };

      return $this->wrapMediaQuery($media, ".{$word} { text-shadow: {$glowDef}; }");
    }

    // 6. SUGERENCIA: TEXT 3D TEMÁTICO: .text-3d-1..N
    if (preg_match('/^text-3d-(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $media = $m[2] ?? "";
      $color = $this->getColorVar($index);
      $shadow3d = "1px 1px 0 color-mix(in srgb, {$color}, black 20%), 2px 2px 0 color-mix(in srgb, {$color}, black 35%), 3px 3px 0 color-mix(in srgb, {$color}, black 50%), 4px 4px 5px rgba(0, 0, 0, 0.4)";
      return $this->wrapMediaQuery($media, ".{$word} { text-shadow: {$shadow3d}; }");
    }

    // 7. SUGERENCIA: TEXT SHIMMER (Metálico animable): .text-shimmer1..N
    if (preg_match('/^text-shimmer(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { background: linear-gradient(90deg, {$color} 0%, color-mix(in srgb, {$color}, white 70%) 50%, {$color} 100%); background-size: 200% auto; -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; color: transparent; display: inline-block; }\n";
    }

    // 8. SUGERENCIA: SUBRAYADO TEMÁTICO: .underline1..N
    if (preg_match('/^underline(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { text-decoration: underline !important; text-decoration-color: {$color} !important; text-underline-offset: 4px; }\n";
    }

    // 9. ROTACIÓN DE TEXTO EN ÁNGULOS (90°, 180°, 270°, etc.): .text-rotate-90, .text-rotate-180, .text-rotate-n90, .text-rotate-45
    if (preg_match('/^text-rotate-(n)?(\d+)(?:deg)?(?:-(desk|mid|sml))?$/', $word, $m)) {
      $sign = ($m[1] ?? "") === "n" ? "-" : "";
      $deg = $m[2];
      $media = $m[3] ?? "";
      $angle = "{$sign}{$deg}deg";
      $css = ".{$word} { display: inline-block; --text-rotate: {$angle}; rotate: {$angle}; }";
      return $this->wrapMediaQuery($media, $css);
    }

    // 10. ROTACIÓN INTERACTIVA AL HOVER: .text-rotate-hover-90, .text-rotate-hover-180...
    if (preg_match('/^text-rotate-hover-(n)?(\d+)(?:deg)?$/', $word, $m)) {
      $sign = ($m[1] ?? "") === "n" ? "-" : "";
      $deg = $m[2];
      $angle = "{$sign}{$deg}deg";
      return ".{$word} { display: inline-block; transition: rotate var(--transition, 0.2s ease-in-out); }\n" .
             ".{$word}:hover { --text-rotate: {$angle}; rotate: {$angle}; }\n";
    }

    return null;
  }
}
