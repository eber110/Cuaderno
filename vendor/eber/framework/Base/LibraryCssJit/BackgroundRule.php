<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para fondos (.back1..N, .back1..N-hover, .back1..N-blurX, .opX, .back-[url], modificadores y overlays).
 * Vinculada dinámicamente a los colores configurados en _configTheme.css con fallback a gris claro (#d3d3d3).
 */
class BackgroundRule extends JitRuleBase
{
  /**
   * Procesa una palabra y devuelve la regla CSS de fondo correspondiente o null.
   *
   * @param string $word Palabra/clase detectada en el código fuente
   * @return string|null
   */
  public function processWord(string $word): ?string
  {
    $statics = [
      "body-back" => ".body-back { background-color: var(--body, #ffffff); }\n",
      "back-none" => ".back-none { background: none; }\n",
      "back-success" => ".back-success { position: relative; z-index: 1; }\n.back-success::before { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--success, #00b900); filter: var(--sepia, sepia(40%)) !important; z-index: -1; }\n",
      "back-success-hover" => ".back-success-hover { position: relative; z-index: 1; }\n.back-success-hover::before { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--success, #00b900); filter: var(--sepia, sepia(40%)) !important; transition: var(--transition, all 0.15s ease-in-out) !important; z-index: -1; }\n.back-success-hover:hover::before { background-color: var(--hover-success, #04ea04); filter: var(--sepia, sepia(40%)); }\n",
      "back-caution" => ".back-caution { position: relative; z-index: 1; }\n.back-caution::before { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--caution, #ff8c00); filter: var(--sepia, sepia(40%)) !important; z-index: -1; }\n",
      "back-caution-hover" => ".back-caution-hover { position: relative; z-index: 1; }\n.back-caution-hover::before { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--caution, #ff8c00); filter: var(--sepia, sepia(40%)) !important; transition: var(--transition, all 0.15s ease-in-out) !important; z-index: -1; }\n.back-caution-hover:hover::before { background-color: var(--hover-caution, #fead4b); filter: var(--sepia, sepia(40%)); }\n",
      "back-danger" => ".back-danger { position: relative; z-index: 1; }\n.back-danger::before { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--danger, #ff0000); filter: var(--sepia, sepia(40%)) !important; z-index: -1; }\n",
      "back-danger-hover" => ".back-danger-hover { position: relative; z-index: 1; }\n.back-danger-hover::before { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: var(--danger, #ff0000); filter: var(--sepia, sepia(40%)) !important; transition: var(--transition, all 0.15s ease-in-out) !important; z-index: -1; }\n.back-danger-hover:hover::before { background-color: var(--hover-danger, #fc6161); filter: var(--sepia, sepia(40%)); }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // 1. Fondos sólidos por número de color: .back1, .back2... .back10 (con soporte responsive opcional)
    if (preg_match('/^back(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $media = $m[2] ?? "";
      $color = $this->getColorVar($index);
      return $this->wrapMediaQuery($media, ".{$word} { background-color: {$color} !important; }");
    }

    // 2. Fondos con efecto hover/focus: .back1-hover, .back2-hover... .back10-hover
    if (preg_match('/^back(\d+)-hover$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      $hover = $this->getColorHoverVar($index);
      return ".{$word} { background-color: {$color} !important; transition: var(--transition, all 0.15s ease-in-out) !important; }\n.{$word}:hover, .{$word}:focus { background-color: {$hover} !important; }\n";
    }

    // 3. Fondos translúcidos con backdrop-filter blur: .back1-blur5, .back2-blur20... .backN-blurX
    if (preg_match('/^back(\d+)-blur(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      $blur = (int)$m[2];
      if ($index < 1) {
        return null;
      }
      $color = $this->getColorVar($index);
      return ".{$word} { background-color: color-mix(in srgb, {$color} var(--blur-opacity, 40%), transparent) !important; backdrop-filter: blur({$blur}px); -webkit-backdrop-filter: blur({$blur}px); }\n";
    }

    // 4. Opacidad para fondos blur: .op10, .op20... .op100
    if (preg_match('/^op(\d+)$/', $word, $m)) {
      $pct = (int)$m[1];
      if ($pct >= 0 && $pct <= 100) {
        return ".{$word} { --blur-opacity: {$pct}% !important; }\n";
      }
    }

    // 5. Fondos dinámicos con imagen por ruta: .back-[/Public/Img/hero.webp] (con soporte responsive opcional)
    if (preg_match('/^back-\[(.+)\](?:-(desk|mid|sml))?$/', $word, $m)) {
      $url = trim($m[1]);
      $media = $m[2] ?? "";

      // Sanitizar: prevenir inyección de comillas, saltos de línea o protocolos no deseados
      if (preg_match('/[\'"\(\)\r\n]|javascript:/i', $url)) {
        return null;
      }

      $escapedClass = addcslashes($word, '[]/.:?#=%+!*@~&');
      $css = ".{$escapedClass} { background-image: url('{$url}') !important; background-repeat: no-repeat; background-position: center; background-size: cover; }";
      return $this->wrapMediaQuery($media, $css);
    }

    // 6. Modificadores de ajuste de imagen de fondo: .back-cover, .back-contain, .back-auto
    if (preg_match('/^back-(cover|contain|auto)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $size = $m[1];
      $media = $m[2] ?? "";
      return $this->wrapMediaQuery($media, ".{$word} { background-size: {$size} !important; }");
    }

    // 7. Modificadores de posición de imagen de fondo: .back-top, .back-bottom, .back-center
    if (preg_match('/^back-(top|bottom|center)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $pos = match ($m[1]) {
        "top" => "top center",
        "bottom" => "bottom center",
        "center" => "center",
      };
      $media = $m[2] ?? "";
      return $this->wrapMediaQuery($media, ".{$word} { background-position: {$pos} !important; }");
    }

    // 8. Fondos con overlay de color y opacidad: .back1-overlay-20, .back-overlay-50...
    if (preg_match('/^back(?:(\d+))?-overlay-(\d+)(?:-(desk|mid|sml))?$/', $word, $m)) {
      $index = ($m[1] === "" || $m[1] === null) ? 1 : (int)$m[1];
      $pct = (int)$m[2];
      if ($index < 1 || $pct < 0 || $pct > 100) {
        return null;
      }
      $media = $m[3] ?? "";
      $color = $this->getColorVar($index);
      $opacity = round($pct / 100, 2);
      $css = ".{$word} { position: relative; z-index: 1; }\n.{$word}::before { content: \"\"; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: {$color} !important; opacity: {$opacity}; z-index: -1; pointer-events: none; }";
      return $this->wrapMediaQuery($media, $css);
    }

    return null;
  }
}
