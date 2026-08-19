<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para clases de animación, retardos, duraciones y umbrales de observación.
 * Procesa selectores dinámicos como ob-10..ob-100, dl-200..dl-N, dur-400 y clases estáticas del observador.
 */
class AnimationRule extends JitRuleBase
{
  public function processWord(string $word): ?string
  {
    // Clases estáticas base para el observador
    $statics = [
      'observer' => ".observer { position: relative; }\n.observer [class*=\"ob-\"]:not(.animated) { opacity: 0; will-change: transform, opacity; }\n",
      'animated' => ".animated { -webkit-animation-fill-mode: both; animation-fill-mode: both; }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // 1. Umbral de Observador: ob-10, ob-20, ob-30... ob-100 (con soporte responsive)
    if (preg_match('/^ob(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
      $media = $m[1] ?? '';
      $pct = (int)$m[2];
      $threshold = round($pct / 100, 2);

      $rule = ".{$word} { --ob-threshold: {$threshold}; }\n.{$word}:not(.animated) { opacity: 0; will-change: transform, opacity; }";
      return $this->wrapMediaQuery($media, $rule);
    }

    // 2. Retardo de Animación: dl-200, dl-300, dl-1s, dl-desk-200, etc.
    if (preg_match('/^dl(?:-(desk|mid|sml))?-?(\d+)(ms|s)?$/', $word, $m)) {
      $media = $m[1] ?? '';
      $num = $m[2];
      $unit = $m[3] ?? 'ms';
      $val = "{$num}{$unit}";

      $rule = ".{$word} { -webkit-animation-delay: {$val} !important; animation-delay: {$val} !important; --animate-delay: {$val}; }";
      return $this->wrapMediaQuery($media, $rule);
    }

    // 3. Duración de Animación: dur-400, dur-800, duration-500, etc.
    if (preg_match('/^(?:dur|duration)(?:-(desk|mid|sml))?-?(\d+)(ms|s)?$/', $word, $m)) {
      $media = $m[1] ?? '';
      $num = $m[2];
      $unit = $m[3] ?? 'ms';
      $val = "{$num}{$unit}";

      $rule = ".{$word} { -webkit-animation-duration: {$val} !important; animation-duration: {$val} !important; --animate-duration: {$val}; }";
      return $this->wrapMediaQuery($media, $rule);
    }

    return null;
  }
}
