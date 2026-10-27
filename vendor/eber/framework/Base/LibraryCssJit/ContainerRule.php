<?php

namespace Base\LibraryCssJit;

/**
 * Regla JIT para contenedores (.container, .container-xl y variantes responsive) y espaciados gap (.gap-*, .gap-rem-*, .gap-em-*).
 */
class ContainerRule extends JitRuleBase
{
  /**
   * Procesa una palabra y devuelve la regla CSS correspondiente o null.
   *
   * @param string $word Palabra/clase detectada en el código fuente
   * @return string|null
   */
  public function processWord(string $word): ?string
  {
    // Contenedores Base
    if (preg_match('/^container-xl(?:-(desk|mid|sml))?$/', $word, $m)) {
      return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { width: 100%; margin: 0px; max-width: none; }\n");
    }
    if (preg_match('/^container(?:-(desk|mid|sml))?$/', $word, $m)) {
      $suffix = $m[1] ?? '';
      $width = match ($suffix) {
        'desk' => '90%',
        'mid' => '96%',
        'sml' => '95%',
        default => '90%',
      };
      return $this->wrapMediaQuery($suffix, ".{$word} { max-width: {$width}; width: var(--width-window); margin: 0 auto; }\n");
    }

    // Gaps
    if (preg_match('/^gap(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
      return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { gap: {$m[2]}px; }");
    }
    if (preg_match('/^gap-rem(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
      return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { gap: {$m[2]}rem; }");
    }
    if (preg_match('/^gap-em(?:-(desk|mid|sml))?-?(\d+)$/', $word, $m)) {
      return $this->wrapMediaQuery($m[1] ?? '', ".{$word} { gap: {$m[2]}em; }");
    }

    return null;
  }
}

