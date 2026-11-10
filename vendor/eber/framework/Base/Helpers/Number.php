<?php

/**
 * ============================================
 * HELPERS NUMÉRICOS Y FORMATEO DECIMAL
 * ============================================
 * 
 * Funciones globales para formatear cifras y decimales en vistas y controladores.
 * 
 * - nd():  Formato decimal con COMA (estándar español / latino: 1.250,50).
 * - ndp(): Formato decimal con PUNTO (estándar anglosajón / US: 1,250.50).
 * 
 * @package Base\Helpers
 */

if (!function_exists('_parseNumberInput')) {
  /**
   * Normaliza una entrada numérica (int, float o string numérico) a float estándar.
   * Tolera strings con comas decimales o separadores de miles europeos y anglosajones.
   *
   * @param int|float|string|null $number
   * @return float
   */
  function _parseNumberInput(int|float|string|null $number): float
  {
    if ($number === null || $number === '') {
      return 0.0;
    }

    if (is_int($number) || is_float($number)) {
      return (float)$number;
    }

    $str = trim((string)$number);

    // Si contiene punto y coma, detectar cuál es el separador decimal por posición
    if (str_contains($str, '.') && str_contains($str, ',')) {
      if (strrpos($str, ',') > strrpos($str, '.')) {
        // Formato europeo/latino: 1.250,50 -> 1250.50
        $str = str_replace('.', '', $str);
        $str = str_replace(',', '.', $str);
      } else {
        // Formato anglosajón: 1,250.50 -> 1250.50
        $str = str_replace(',', '', $str);
      }
    } elseif (str_contains($str, ',')) {
      // Solo tiene coma: 1250,50 -> 1250.50
      $str = str_replace(',', '.', $str);
    }

    return is_numeric($str) ? (float)$str : 0.0;
  }
}

if (!function_exists('nd')) {
  /**
   * Formatea un número usando COMA como separador decimal (Estándar español/latino).
   *
   * @param int|float|string|null $number Número a formatear
   * @param int $decimals Cantidad de decimales (por defecto 2)
   * @param bool $thousands Si incluye separador de miles con punto (por defecto true)
   * @return string
   * 
   * @example nd(1250.5)           => "1.250,50"
   * @example nd(1250.5, 0)        => "1.250"
   * @example nd(1250.5, 2, false) => "1250,50"
   * @example nd("1.250,50")       => "1.250,50"
   */
  function nd(int|float|string|null $number, int $decimals = 2, bool $thousands = true): string
  {
    if ($number === null || $number === '') {
      return '0' . ($decimals > 0 ? ',' . str_repeat('0', $decimals) : '');
    }

    $val = _parseNumberInput($number);
    return number_format($val, $decimals, ',', $thousands ? '.' : '');
  }
}

if (!function_exists('ndp')) {
  /**
   * Formatea un número usando PUNTO como separador decimal (Estándar anglosajón / US).
   *
   * @param int|float|string|null $number Número a formatear
   * @param int $decimals Cantidad de decimales (por defecto 2)
   * @param bool $thousands Si incluye separador de miles con coma (por defecto true)
   * @return string
   * 
   * @example ndp(1250.5)           => "1,250.50"
   * @example ndp(1250.5, 0)        => "1,250"
   * @example ndp(1250.5, 2, false) => "1250.50"
   * @example ndp("1,250.50")       => "1,250.50"
   */
  function ndp(int|float|string|null $number, int $decimals = 2, bool $thousands = true): string
  {
    if ($number === null || $number === '') {
      return '0' . ($decimals > 0 ? '.' . str_repeat('0', $decimals) : '');
    }

    $val = _parseNumberInput($number);
    return number_format($val, $decimals, '.', $thousands ? ',' : '');
  }
}
