<?php

namespace Base\LibraryCssJit;

interface JitRuleInterface
{
    /**
     * Procesa una palabra. Si la palabra corresponde a una regla manejada
     * por este traductor, devuelve el string de CSS generado. De lo contrario, devuelve null.
     */
    public function processWord(string $word): ?string;
}
