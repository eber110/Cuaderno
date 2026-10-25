<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para el sistema de animaciones (animation.css) e interacciones hover (animation-select.css).
 * Compila bajo demanda únicamente las clases y @keyframes utilizados en el proyecto.
 */
class AnimationRule extends JitRuleBase
{
  /**
   * Procesa una palabra y devuelve la regla CSS de animación o interacción correspondiente o null.
   *
   * @param string $word Palabra/clase detectada en el código fuente
   * @return string|null
   */
  public function processWord(string $word): ?string
  {
    $easeOut = "var(--animate-ease, cubic-bezier(0.25, 1, 0.5, 1))";
    $easeIn = "var(--animate-ease-in, cubic-bezier(0.55, 0, 1, 0.45))";
    $easeElastic = "var(--animate-ease-elastic, cubic-bezier(0.34, 1.56, 0.64, 1))";

    $hDurIn = "var(--hover-duration-in, 250ms)";
    $hDurOut = "var(--hover-duration-out, 350ms)";
    $hDurSoft = "var(--hover-duration-soft, 350ms)";
    $hEaseIn = "var(--hover-ease-in, cubic-bezier(0.25, 1, 0.5, 1))";
    $hEaseOut = "var(--hover-ease-out, cubic-bezier(0.25, 1, 0.5, 1))";
    $hEaseElastic = "var(--hover-ease-elastic, cubic-bezier(0.34, 1.56, 0.64, 1))";

    // 1. Clases base del sistema de animación, keyframes e interacciones hover
    $statics = [
      "observer" => ".observer { position: relative; }\n.observer [class*=\"ob-\"]:not(.animated), [class*=\"ob-\"]:not(.animated) { opacity: 0; -webkit-animation-name: none !important; animation-name: none !important; will-change: transform, opacity; }\n",
      "animated" => ".animated { -webkit-animation-duration: var(--animate-duration, 400ms); animation-duration: var(--animate-duration, 400ms); -webkit-animation-fill-mode: both; animation-fill-mode: both; -webkit-animation-delay: var(--animate-delay, 0ms); animation-delay: var(--animate-delay, 0ms); -webkit-animation-timing-function: {$easeOut}; animation-timing-function: {$easeOut}; }\nhtml.gsap-loaded .animated, html.gsap-loaded [data-animate] { transition: none !important; }\n",
      "animate-fast" => ".animate-fast { --animate-duration: 250ms; }\n",
      "animate-slow" => ".animate-slow { --animate-duration: 800ms; }\n",
      "animate-infinite" => ".animate-infinite { animation-iteration-count: infinite; }\n",
      "disabled-save-btn" => ".disabled-save-btn { opacity: 0.4; pointer-events: none !important; cursor: not-allowed !important; filter: grayscale(0.5); }\n",

      // Fade animations
      "fade-in" => ".fade-in { -webkit-animation-name: fadeIn; animation-name: fadeIn; --animate-duration: 600ms; }\n@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }\n",
      "fade-out" => ".fade-out { -webkit-animation-name: fadeOut; animation-name: fadeOut; -webkit-animation-timing-function: {$easeIn}; animation-timing-function: {$easeIn}; }\n@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }\n",

      // Slide animations
      "slide-in-left" => ".slide-in-left { -webkit-animation-name: slideInLeft; animation-name: slideInLeft; -webkit-animation-timing-function: {$easeOut}; animation-timing-function: {$easeOut}; }\n@keyframes slideInLeft { from { transform: translate3d(-100%, 0, 0); opacity: 0; } to { transform: translate3d(0, 0, 0); opacity: 1; } }\n",
      "slide-in-right" => ".slide-in-right { -webkit-animation-name: slideInRight; animation-name: slideInRight; -webkit-animation-timing-function: {$easeOut}; animation-timing-function: {$easeOut}; }\n@keyframes slideInRight { from { transform: translate3d(100%, 0, 0); opacity: 0; } to { transform: translate3d(0, 0, 0); opacity: 1; } }\n",
      "slide-in-top" => ".slide-in-top { -webkit-animation-name: slideInTop; animation-name: slideInTop; -webkit-animation-timing-function: {$easeOut}; animation-timing-function: {$easeOut}; }\n@keyframes slideInTop { from { transform: translate3d(0, -100%, 0); opacity: 0; } to { transform: translate3d(0, 0, 0); opacity: 1; } }\n",
      "slide-in-bottom" => ".slide-in-bottom { -webkit-animation-name: slideInBottom; animation-name: slideInBottom; -webkit-animation-timing-function: {$easeOut}; animation-timing-function: {$easeOut}; }\n@keyframes slideInBottom { from { transform: translate3d(0, 100%, 0); opacity: 0; } to { transform: translate3d(0, 0, 0); opacity: 1; } }\n",
      "slide-out-left" => ".slide-out-left { -webkit-animation-name: slideOutLeft; animation-name: slideOutLeft; -webkit-animation-timing-function: {$easeIn}; animation-timing-function: {$easeIn}; }\n@keyframes slideOutLeft { from { transform: translate3d(0, 0, 0); opacity: 1; } to { transform: translate3d(-100%, 0, 0); opacity: 0; } }\n",
      "slide-out-right" => ".slide-out-right { -webkit-animation-name: slideOutRight; animation-name: slideOutRight; -webkit-animation-timing-function: {$easeIn}; animation-timing-function: {$easeIn}; }\n@keyframes slideOutRight { from { transform: translate3d(0, 0, 0); opacity: 1; } to { transform: translate3d(100%, 0, 0); opacity: 0; } }\n",
      "slide-out-top" => ".slide-out-top { -webkit-animation-name: slideOutTop; animation-name: slideOutTop; -webkit-animation-timing-function: {$easeIn}; animation-timing-function: {$easeIn}; }\n@keyframes slideOutTop { from { transform: translate3d(0, 0, 0); opacity: 1; } to { transform: translate3d(0, -100%, 0); opacity: 0; } }\n",
      "slide-out-bottom" => ".slide-out-bottom { -webkit-animation-name: slideOutBottom; animation-name: slideOutBottom; -webkit-animation-timing-function: {$easeIn}; animation-timing-function: {$easeIn}; }\n@keyframes slideOutBottom { from { transform: translate3d(0, 0, 0); opacity: 1; } to { transform: translate3d(0, 100%, 0); opacity: 0; } }\n",

      // Bounce, Spin & Pulse
      "bounce-in" => ".bounce-in { -webkit-animation-name: bounceIn; animation-name: bounceIn; -webkit-animation-timing-function: {$easeElastic}; animation-timing-function: {$easeElastic}; --animate-duration: 550ms; }\n@keyframes bounceIn { from { transform: scale3d(0.3, 0.3, 0.3); opacity: 0; } to { transform: scale3d(1, 1, 1); opacity: 1; } }\n",
      "bounce-out" => ".bounce-out { -webkit-animation-name: bounceOut; animation-name: bounceOut; -webkit-animation-timing-function: {$easeIn}; animation-timing-function: {$easeIn}; }\n@keyframes bounceOut { from { transform: scale3d(1, 1, 1); opacity: 1; } to { transform: scale3d(0.3, 0.3, 0.3); opacity: 0; } }\n",
      "spin" => ".spin { -webkit-animation-name: spin; animation-name: spin; -webkit-animation-timing-function: linear; animation-timing-function: linear; --animate-duration: 1.5s; animation-iteration-count: infinite; }\n@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }\n",
      "pulse" => ".pulse { -webkit-animation-name: pulse; animation-name: pulse; --animate-duration: 2s; animation-iteration-count: infinite; }\n@keyframes pulse { 0% { transform: scale3d(1, 1, 1); } 50% { transform: scale3d(1.05, 1.05, 1.05); } 100% { transform: scale3d(1, 1, 1); } }\n",
      "pulse-once" => ".pulse-once { animation: sonarRipple 0.75s ease-out 1 forwards !important; }\n@keyframes sonarRipple { 0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.85); transform: scale(0.96); } 100% { box-shadow: 0 0 0 26px rgba(220, 38, 38, 0); transform: scale(1); } }\n",

      // Hover interactive system (animation-select.css)
      "hover-interactive" => ".hover-interactive { will-change: transform, box-shadow, filter, opacity; }\nhtml:not(.gsap-loaded) .hover-interactive { transition: transform {$hDurOut} {$hEaseOut}, box-shadow {$hDurOut} {$hEaseOut}, filter {$hDurOut} {$hEaseOut}, opacity {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-interactive:hover { transition: transform {$hDurIn} {$hEaseIn}, box-shadow {$hDurIn} {$hEaseIn}, filter {$hDurIn} {$hEaseIn}, opacity {$hDurIn} {$hEaseIn}; }\nhtml.gsap-loaded .hover-interactive { transition: none !important; }\n",
      "hover-scale" => ".hover-scale { will-change: transform; }\nhtml:not(.gsap-loaded) .hover-scale { transition: transform {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-scale:hover { transform: scale3d(var(--hover-scale, 1.20), var(--hover-scale, 1.20), 1); transition: transform {$hDurIn} {$hEaseElastic}; }\nhtml.gsap-loaded .hover-scale { transition: none !important; }\n",
      "hover-scale-soft" => ".hover-scale-soft { will-change: transform, box-shadow; }\nhtml:not(.gsap-loaded) .hover-scale-soft { transition: transform {$hDurSoft} {$hEaseOut}, box-shadow {$hDurSoft} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-scale-soft:hover { transform: scale3d(var(--hover-scale-soft, 1.02), var(--hover-scale-soft, 1.02), 1); box-shadow: var(--hover-shadow-soft, 0 6px 12px rgba(0, 0, 0, 0.12)); transition: transform {$hDurSoft} {$hEaseIn}, box-shadow {$hDurSoft} {$hEaseIn}; }\nhtml.gsap-loaded .hover-scale-soft { transition: none !important; }\n",
      "hover-shrink" => ".hover-shrink { will-change: transform; }\nhtml:not(.gsap-loaded) .hover-shrink { transition: transform {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-shrink:hover { transform: scale3d(var(--hover-scale-shrink, 0.95), var(--hover-scale-shrink, 0.95), 1); transition: transform {$hDurIn} {$hEaseIn}; }\nhtml.gsap-loaded .hover-shrink { transition: none !important; }\n",
      "hover-lift" => ".hover-lift { will-change: transform, box-shadow; }\nhtml:not(.gsap-loaded) .hover-lift { transition: transform {$hDurOut} {$hEaseOut}, box-shadow {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-lift:hover { transform: translate3d(0, var(--hover-y, -6px), 0); box-shadow: var(--hover-shadow, 0 10px 20px rgba(0, 0, 0, 0.15)); transition: transform {$hDurIn} {$hEaseIn}, box-shadow {$hDurIn} {$hEaseIn}; }\nhtml.gsap-loaded .hover-lift { transition: none !important; }\n",
      "hover-slide-right" => ".hover-slide-right { will-change: transform; }\nhtml:not(.gsap-loaded) .hover-slide-right { transition: transform {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-slide-right:hover { transform: translate3d(var(--hover-x, 6px), 0, 0); transition: transform {$hDurIn} {$hEaseIn}; }\nhtml.gsap-loaded .hover-slide-right { transition: none !important; }\n",
      "hover-tilt" => ".hover-tilt { will-change: transform; }\nhtml:not(.gsap-loaded) .hover-tilt { transition: transform {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-tilt:hover { transform: rotateZ(var(--hover-rotate-tilt, -3deg)) scale3d(var(--hover-scale, 1.20), var(--hover-scale, 1.20), 1); transition: transform {$hDurIn} {$hEaseElastic}; }\nhtml.gsap-loaded .hover-tilt { transition: none !important; }\n",
      "hover-spin" => ".hover-spin { will-change: transform; }\nhtml:not(.gsap-loaded) .hover-spin { transition: transform 600ms {$hDurOut}; }\nhtml:not(.gsap-loaded) .hover-spin:hover { transform: rotateZ(360deg); transition: transform 600ms {$hEaseIn}; }\nhtml.gsap-loaded .hover-spin { transition: none !important; }\n",
      "hover-glow" => ".hover-glow { will-change: filter; }\nhtml:not(.gsap-loaded) .hover-glow { transition: filter {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-glow:hover { filter: drop-shadow(0px 0px 8px var(--hover-glow-color, rgba(255, 255, 255, 0.4))); transition: filter {$hDurIn} {$hEaseIn}; }\nhtml.gsap-loaded .hover-glow { transition: none !important; }\n",
      "hover-shine" => ".hover-shine { position: relative; overflow: hidden; }\n.hover-shine::after { content: ''; position: absolute; top: 0; left: -150%; width: 50%; height: 100%; background: linear-gradient(to right, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.3) 50%, rgba(255, 255, 255, 0) 100%); transform: skewX(-25deg); transition: left {$hDurOut} ease; }\n.hover-shine:hover::after { left: 150%; transition: left 700ms cubic-bezier(0.4, 0, 0.2, 1); }\n",
      "hover-press" => ".hover-press { will-change: transform, box-shadow; }\nhtml:not(.gsap-loaded) .hover-press { transition: transform 100ms ease, box-shadow 100ms ease; }\nhtml:not(.gsap-loaded) .hover-press:active { transform: scale3d(0.97, 0.97, 1) translate3d(0, 2px, 0); box-shadow: var(--hover-shadow-active, 0 4px 8px rgba(0, 0, 0, 0.1)); }\nhtml.gsap-loaded .hover-press { transition: none !important; }\n",
      "hover-arrow" => ".hover-arrow { display: inline-flex; align-items: center; gap: 8px; }\n.hover-arrow svg, .hover-arrow i { will-change: transform; }\nhtml:not(.gsap-loaded) .hover-arrow svg, html:not(.gsap-loaded) .hover-arrow i { transition: transform {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .hover-arrow:hover svg, html:not(.gsap-loaded) .hover-arrow:hover i { transform: translate3d(5px, 0, 0); transition: transform {$hDurIn} {$hEaseElastic}; }\nhtml.gsap-loaded .hover-arrow, html.gsap-loaded .hover-arrow svg, html.gsap-loaded .hover-arrow i { transition: none !important; }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // Alias compartidos de Zoom/Scale (emite el keyframe una sola vez si se usan ambos)
    if ($word === "zoom-in" || $word === "scale-in") {
      return ".{$word} { -webkit-animation-name: zoomIn; animation-name: zoomIn; }\n" .
        $this->emitOnce("kf_zoomIn", "@keyframes zoomIn { from { transform: scale3d(0.85, 0.85, 0.85); opacity: 0; } to { transform: scale3d(1, 1, 1); opacity: 1; } }\n");
    }
    if ($word === "zoom-out" || $word === "scale-out") {
      return ".{$word} { -webkit-animation-name: zoomOut; animation-name: zoomOut; -webkit-animation-timing-function: {$easeIn}; animation-timing-function: {$easeIn}; }\n" .
        $this->emitOnce("kf_zoomOut", "@keyframes zoomOut { from { transform: scale3d(1, 1, 1); opacity: 1; } to { transform: scale3d(0.85, 0.85, 0.85); opacity: 0; } }\n");
    }

    // Alias de hover-lift sin sombra
    if ($word === "hover-lift-ns" || $word === "hover-lift-no-shadow") {
      return ".{$word} { will-change: transform; }\nhtml:not(.gsap-loaded) .{$word} { transition: transform {$hDurOut} {$hEaseOut}; }\nhtml:not(.gsap-loaded) .{$word}:hover { transform: translate3d(0, var(--hover-y, -6px), 0); transition: transform {$hDurIn} {$hEaseIn}; }\nhtml.gsap-loaded .{$word} { transition: none !important; }\n";
    }

    // 2. Umbral de Observador: ob-0, ob-10, ob-20, ob-30... ob-100 (con soporte responsive)
    if (preg_match('/^ob(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
      $media = $m[1] ?? "";
      $pct = (int)$m[2];
      $threshold = round($pct / 100, 2);

      $rule = ".{$word} { --ob-threshold: {$threshold}; }\n.{$word}:not(.animated) { opacity: 0; -webkit-animation-name: none !important; animation-name: none !important; will-change: transform, opacity; }";
      return $this->wrapMediaQuery($media, $rule);
    }

    // 3. Retardo de Animación: dl-200, dl-300, dl-1s, dl-desk-200, etc.
    if (preg_match('/^dl(?:-(desk|mid|sml))?-?(\d+)(ms|s)?$/', $word, $m)) {
      $media = $m[1] ?? "";
      $num = $m[2];
      $unit = $m[3] ?? "ms";
      $val = "{$num}{$unit}";

      $rule = ".{$word} { -webkit-animation-delay: {$val} !important; animation-delay: {$val} !important; --animate-delay: {$val}; }";
      return $this->wrapMediaQuery($media, $rule);
    }

    // 4. Duración de Animación: dur-400, dur-800, duration-500, etc.
    if (preg_match('/^(?:dur|duration)(?:-(desk|mid|sml))?-?(\d+)(ms|s)?$/', $word, $m)) {
      $media = $m[1] ?? "";
      $num = $m[2];
      $unit = $m[3] ?? "ms";
      $val = "{$num}{$unit}";

      $rule = ".{$word} { -webkit-animation-duration: {$val} !important; animation-duration: {$val} !important; --animate-duration: {$val}; }";
      return $this->wrapMediaQuery($media, $rule);
    }

    return null;
  }
}
