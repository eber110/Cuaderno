<?php

namespace Base\Module;

/**
 * Módulo para procesamiento de texto.
 * 
 * Proporciona utilidades para:
 * - Eliminar acentos y caracteres especiales
 * - Generar slugs para URLs
 * - Sanitizar y limpiar texto HTML
 * - Truncar texto por palabras
 * - Formatear párrafos
 * 
 * @example
 * // Generar slug
 * $slug = TextModule::toSlug("Hóla Múndo!"); // "hola-mundo"
 * 
 * // Truncar texto
 * $excerpt = TextModule::truncate($text, 20); // primeras 20 palabras
 * 
 * // Limpiar HTML
 * $clean = TextModule::clean($htmlContent);
 */
class TextModule
{
  /**
   * Elimina acentos y caracteres especiales de un string.
   * 
   * @param string $string Texto a procesar.
   * @return string Texto sin acentos.
   */
  public static function removeAccents(string $string): string
  {
    // Reemplazar vocales acentuadas
    $replacements = [
      // A
      ['Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª'],
      ['A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a'],
      // E
      ['É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê'],
      ['E', 'E', 'E', 'E', 'e', 'e', 'e', 'e'],
      // I
      ['Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î'],
      ['I', 'I', 'I', 'I', 'i', 'i', 'i', 'i'],
      // O
      ['Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô'],
      ['O', 'O', 'O', 'O', 'o', 'o', 'o', 'o'],
      // U
      ['Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û'],
      ['U', 'U', 'U', 'U', 'u', 'u', 'u', 'u'],
      // N, C
      ['Ñ', 'ñ', 'Ç', 'ç'],
      ['N', 'n', 'C', 'c'],
      // Puntuación
      [',', '.', ':', ';', '_', '-'],
      ['', '', '', '', '', ''],
    ];

    for ($i = 0; $i < count($replacements); $i += 2) {
      $string = str_replace($replacements[$i], $replacements[$i + 1], $string);
    }

    return $string;
  }

  /**
   * Genera un slug URL-friendly a partir de un texto.
   * 
   * @param string $text Texto a convertir.
   * @return string Slug generado.
   */
  public static function toSlug(string $text, bool $distinct = true): string
  {
    $text = self::removeAccents($text);
    $text = preg_replace('([^A-Za-z0-9 ]+)', ' ', $text);
    $text = rtrim($text);
    $text = explode(' ', $text);
    $text = array_filter($text); // Eliminar elementos vacíos
    $text = implode('-', $text);

    if ($distinct) {
      $text = $text."-".random_int(1000000000, 999999999999999999);
    }

    return strtolower($text);
  }

  /**
   * Sanitiza texto eliminando HTML y decodificando entidades.
   * 
   * @param string $text Texto a limpiar.
   * @return string Texto limpio.
   */
  public static function clean(string $text): string
  {
    $text = strip_tags($text);
    $text = htmlspecialchars_decode($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    return $text;
  }

  /**
   * Procesa texto con opciones configurables.
   * 
   * @param string $text Texto a procesar.
   * @param int|null $wordCount Cantidad de palabras (opcional).
   * @param array $options Opciones de formato:
   *   - strip_tags: bool - Eliminar etiquetas HTML (default: false)
   *   - decode_html: bool - Decodificar entidades HTML (default: true)
   *   - nl2br: bool - Convertir saltos de línea (default: true)
   * @return string Texto procesado.
   */
  public static function process(string $text, ?int $wordCount = null, array $options = []): string
  {
    $options = array_merge([
      'strip_tags' => false,
      'decode_html' => true,
      'nl2br' => true
    ], $options);

    if ($options['strip_tags']) {
      $text = strip_tags($text);
    }

    if ($options['decode_html']) {
      $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    if ($wordCount !== null) {
      $words = explode(' ', $text);
      $text = implode(' ', array_slice($words, 0, min($wordCount, count($words))));
    }

    if ($options['nl2br']) {
      $text = nl2br($text);
    }

    return $text;
  }

  /**
   * Trunca texto a un número específico de palabras.
   * 
   * @param string $text Texto a truncar.
   * @param int|null $wordCount Cantidad de palabras.
   * @return string Texto truncado.
   */
  public static function truncate(string $text, ?int $wordCount = null): string
  {
    return self::process($text, $wordCount, [
      'decode_html' => true,
      'nl2br' => true
    ]);
  }

  /**
   * Trunca texto sin formato HTML.
   * 
   * @param string $text Texto a truncar.
   * @param int|null $wordCount Cantidad de palabras.
   * @return string Texto truncado sin formato.
   */
  public static function truncateRaw(string $text, ?int $wordCount = null): string
  {
    return self::process($text, $wordCount, [
      'strip_tags' => true,
      'decode_html' => false,
      'nl2br' => true
    ]);
  }

  /**
   * Formatea texto en párrafos HTML.
   * 
   * Convierte saltos de línea dobles en etiquetas <p> y
   * saltos simples en <br>.
   * 
   * @param string $text Texto a formatear.
   * @return string HTML con párrafos.
   */
  public static function formatParagraphs(string $text, string $class = ""): string
  {
    // Normalizar saltos de línea
    $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
    $text = str_replace("\r\n", "\n", $text);

    // Dividir por saltos de línea
    $paragraphs = explode("\n", $text);
    $html = '';

    foreach ($paragraphs as $paragraph) {
      $clean = trim($paragraph);

      if (!empty($clean)) {
        $withBr = nl2br($clean);
        $html .= "<p class='".$class."'>" . $withBr . "</p>\n";
      }
    }

    return $html;
  }
}
