<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para utilidades de formularios, foco accesible, acento nativo y tokens UI (--ui-*).
 * Soporta .accent1..N, .focus-ring1..N, .caret1..N, .ui-radius-X y .ui-touch-X.
 */
class InputRule extends JitRuleBase
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
      "accent-success"     => ".accent-success { accent-color: var(--success, #00b900) !important; }\n",
      "accent-caution"     => ".accent-caution { accent-color: var(--caution, #ff8c00) !important; }\n",
      "accent-danger"      => ".accent-danger { accent-color: var(--danger, #ff0000) !important; }\n",
      "focus-ring-success" => ".focus-ring-success:focus-visible { outline: 2px solid var(--success, #00b900); outline-offset: 2px; }\n",
      "focus-ring-caution" => ".focus-ring-caution:focus-visible { outline: 2px solid var(--caution, #ff8c00); outline-offset: 2px; }\n",
      "focus-ring-danger"  => ".focus-ring-danger:focus-visible { outline: 2px solid var(--danger, #ff0000); outline-offset: 2px; }\n",
      "focus-ring-none"    => ".focus-ring-none:focus-visible { outline: none; }\n",
    ];

    if (isset($statics[$word])) {
      return $statics[$word];
    }

    // Color de acento nativo (inputs range/checkbox/radio): .accent1..N
    if (preg_match('/^accent(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index >= 1) {
        $color = $this->getColorVar($index);
        return ".{$word} { accent-color: {$color} !important; }\n";
      }
    }

    // Color de cursor de texto en inputs: .caret1..N
    if (preg_match('/^caret(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index >= 1) {
        $color = $this->getColorVar($index);
        return ".{$word} { caret-color: {$color} !important; }\n";
      }
    }

    // Anillo de foco accesible: .focus-ring1..N o .ring1..N
    if (preg_match('/^(?:focus-)?ring(\d+)$/', $word, $m)) {
      $index = (int)$m[1];
      if ($index >= 1) {
        $color = $this->getColorVar($index);
        return ".{$word}:focus-visible, .{$word}:focus-within { outline: 2px solid {$color}; outline-offset: 2px; }\n";
      }
    }

    // Token de radio para componentes UI: .ui-radius-12, .ui-radius-20
    if (preg_match('/^ui-radius-(\d+)$/', $word, $m)) {
      $px = max(0, min(60, (int)$m[1]));
      return ".{$word} { --ui-radius: {$px}px !important; }\n";
    }

    // Token de área táctil para componentes UI: .ui-touch-44, .ui-touch-48
    if (preg_match('/^ui-touch-(\d+)$/', $word, $m)) {
      $px = max(32, min(72, (int)$m[1]));
      return ".{$word} { --ui-touch-size: {$px}px !important; }\n";
    }

    return null;
  }
}
