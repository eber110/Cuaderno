<?php

namespace Base\Studio\Services;

use Base\Exec\FontCompressor;
use Base\Module\ThemeModule;

/**
 * Servicio de descubrimiento y catalogación de fuentes tipográficas para Frame Theme Studio.
 *
 * Lee exclusivamente las fuentes instaladas en el proyecto (App/Public/Css/font-project.css),
 * los archivos físicos presentes en App/Rsc/Fonts/ sin registrar y la fuente por defecto del navegador.
 * Descarta automáticamente bloques CSS huérfanos cuyos archivos no existan en disco.
 */
class FontCatalogService
{
  /**
   * Devuelve el descriptor canónico de la única fuente base del navegador en el framework.
   *
   * @return array<string, mixed>
   */
  public static function getDefaultBrowserFont(): array
  {
    return [
      'id' => 'browser-default',
      'name' => 'Navegador (Por defecto)',
      'fontFamily' => 'Sans-serif (Navegador)',
      'cssValue' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif",
      'previewCssValue' => "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif",
      'category' => 'default',
      'type' => 'default',
      'baseClass' => 'font',
      'weightClasses' => [],
      'weights' => [100, 200, 300, 400, 500, 600, 700, 800, 900],
      'weightRangeLabel' => 'Sistema (100–900)',
      'isVariable' => false,
      'styles' => ['normal', 'italic'],
      'files' => [],
      'totalSizeBytes' => 0,
      'preloaded' => false,
      'orphanIgnored' => false,
    ];
  }

  /**
   * Escanea las fuentes reales del proyecto y construye el catálogo unificado por familia.
   *
   * @param string|null $basePath Directorio raíz del proyecto (opcional)
   * @return array<string, mixed>
   */
  public static function getCatalog(?string $basePath = null): array
  {
    $base = ThemeModule::resolveBasePath($basePath);
    $preloadSet = self::loadPreloadSet($base . '/App/Config/preloadFonts.json');

    $fontsDirRel = 'App/Rsc/Fonts';
    $fontsDir = $base . '/App/Rsc/Fonts';
    if (!is_dir($fontsDir) && is_dir($base . '/App/Rsc/Font')) {
      $fontsDir = $base . '/App/Rsc/Font';
      $fontsDirRel = 'App/Rsc/Font';
    }

    $fontCssPath = $base . '/App/Public/Css/font-project.css';
    $familiesMap = [];
    $orphans = [];
    $registeredDiskPaths = [];
    $previewFontFacesCssParts = [];

    if (is_file($fontCssPath)) {
      $rawCss = str_replace("\r\n", "\n", (string)file_get_contents($fontCssPath));
      $cleanCss = (string)preg_replace('/\/\*[\s\S]*?\*\//', '', $rawCss);
      $classIndex = self::parseCssClassIndex($cleanCss);

      if (preg_match_all('/@font-face\s*\{([^}]*)\}/i', $cleanCss, $faceMatches)) {
        foreach ($faceMatches[1] as $faceBody) {
          $family = self::extractCssProperty($faceBody, 'font-family');
          $family = trim($family, " \t\n\r\0\x0B'\"");
          if ($family === '') {
            continue;
          }

          $weightRaw = self::extractCssProperty($faceBody, 'font-weight');
          if ($weightRaw === '') {
            $weightRaw = '400';
          }

          $styleRaw = strtolower(self::extractCssProperty($faceBody, 'font-style'));
          $style = $styleRaw !== '' ? $styleRaw : 'normal';

          $url = '';
          if (preg_match('/url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', $faceBody, $urlMatch)) {
            $url = trim($urlMatch[1]);
          }

          $format = '';
          if (preg_match('/format\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)/i', $faceBody, $fmtMatch)) {
            $format = strtolower(trim($fmtMatch[1]));
          }

          $diskPath = self::resolveProjectDiskPath($base, $url);
          if ($diskPath === null || !is_file($diskPath)) {
            $orphans[] = [
              'family' => $family,
              'url' => $url,
              'weight' => $weightRaw,
            ];
            continue;
          }

          $realDisk = realpath($diskPath);
          if ($realDisk !== false) {
            $registeredDiskPaths[$realDisk] = true;
          }

          $ext = strtolower(pathinfo($diskPath, PATHINFO_EXTENSION));
          if ($format === '') {
            $format = self::extensionToCssFormat($ext);
          }

          $normalizedUrl = '/' . ltrim(str_replace('\\', '/', $url), '/');
          $isPreloaded = isset($preloadSet[strtolower(ltrim($normalizedUrl, '/'))]);

          $parsedWeight = self::parseFontWeights($weightRaw, $diskPath);
          $familyKey = mb_strtolower($family, 'UTF-8');

          if (!isset($familiesMap[$familyKey])) {
            $meta = $classIndex[$familyKey] ?? ['baseClass' => '', 'weightClasses' => []];
            $baseClass = $meta['baseClass'] !== '' ? $meta['baseClass'] : self::suggestClassName($family);

            $familiesMap[$familyKey] = [
              'id' => 'project-' . self::slugify($family),
              'name' => $family,
              'fontFamily' => $family,
              'cssValue' => self::formatCssFamilyStack($family),
              'previewCssValue' => self::formatCssFamilyStack($family),
              'category' => 'project',
              'type' => $parsedWeight['isVariable'] ? 'variable' : 'static',
              'baseClass' => $baseClass,
              'weightClasses' => $meta['weightClasses'],
              'weights' => [],
              'weightRangeLabel' => '',
              'isVariable' => $parsedWeight['isVariable'],
              'styles' => [],
              'files' => [],
              'totalSizeBytes' => 0,
              'preloaded' => false,
              'orphanIgnored' => false,
            ];
          }

          if ($parsedWeight['isVariable']) {
            $familiesMap[$familyKey]['isVariable'] = true;
            $familiesMap[$familyKey]['type'] = 'variable';
          }

          foreach ($parsedWeight['weights'] as $w) {
            $familiesMap[$familyKey]['weights'][$w] = $w;
          }
          $familiesMap[$familyKey]['styles'][$style] = $style;

          $fileSize = (int)@filesize($diskPath);
          $familiesMap[$familyKey]['totalSizeBytes'] += $fileSize;
          if ($isPreloaded) {
            $familiesMap[$familyKey]['preloaded'] = true;
          }

          $familiesMap[$familyKey]['files'][] = [
            'url' => $normalizedUrl,
            'filename' => basename($diskPath),
            'format' => strtoupper($ext !== '' ? $ext : $format),
            'weight' => $weightRaw,
            'style' => $style,
            'sizeBytes' => $fileSize,
            'preloaded' => $isPreloaded,
          ];

          $previewFontFacesCssParts[] = self::buildFontFaceRule(
            $family,
            $normalizedUrl,
            $format,
            $weightRaw,
            $style
          );
        }
      }
    }

    // Escanear recursivamente App/Rsc/Fonts para detectar fuentes físicas sin registrar en font-project.css
    $unregisteredMap = [];
    if (is_dir($fontsDir)) {
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($fontsDir, \FilesystemIterator::SKIP_DOTS)
      );

      $baseNorm = rtrim(str_replace('\\', '/', $base), '/');
      $fontsDirNorm = rtrim(str_replace('\\', '/', $fontsDir), '/');

      foreach ($iterator as $entry) {
        if (!$entry->isFile()) {
          continue;
        }

        $filePath = $entry->getPathname();
        $ext = strtolower($entry->getExtension());
        if (!in_array($ext, ['woff2', 'woff', 'ttf', 'otf'], true)) {
          continue;
        }

        $entryNorm = str_replace('\\', '/', $filePath);
        $relInsideFonts = ltrim(substr($entryNorm, strlen($fontsDirNorm)), '/');
        if ($relInsideFonts === '' || self::hasHiddenSegment($relInsideFonts)) {
          continue;
        }

        $realFile = realpath($filePath);
        if ($realFile !== false && isset($registeredDiskPaths[$realFile])) {
          continue;
        }

        $inferredFamily = self::inferFamilyFromDiskPath($relInsideFonts);
        $familyKey = mb_strtolower($inferredFamily, 'UTF-8');

        $isVar = FontCompressor::isVariableFont($filePath);
        $weightRaw = $isVar ? '100 900' : (string)FontCompressor::detectWeightFromFilename($filePath);
        $parsedWeight = self::parseFontWeights($weightRaw, $filePath);

        $relProjectUrl = '/' . ltrim(substr($entryNorm, strlen($baseNorm)), '/');
        $format = self::extensionToCssFormat($ext);
        $previewFamily = 'FrameStudioPreview_' . self::slugify($inferredFamily);

        if (!isset($unregisteredMap[$familyKey])) {
          $unregisteredMap[$familyKey] = [
            'id' => 'unregistered-' . self::slugify($inferredFamily),
            'name' => $inferredFamily,
            'fontFamily' => $inferredFamily,
            'cssValue' => self::formatCssFamilyStack($inferredFamily),
            'previewCssValue' => "'{$previewFamily}', system-ui, sans-serif",
            'category' => 'unregistered',
            'type' => $isVar ? 'variable' : 'static',
            'baseClass' => self::suggestClassName($inferredFamily),
            'weightClasses' => [],
            'weights' => [],
            'weightRangeLabel' => '',
            'isVariable' => $isVar,
            'styles' => ['normal' => 'normal'],
            'files' => [],
            'totalSizeBytes' => 0,
            'preloaded' => false,
            'orphanIgnored' => false,
          ];
        }

        if ($isVar) {
          $unregisteredMap[$familyKey]['isVariable'] = true;
          $unregisteredMap[$familyKey]['type'] = 'variable';
        }

        foreach ($parsedWeight['weights'] as $w) {
          $unregisteredMap[$familyKey]['weights'][$w] = $w;
        }

        $fileSize = (int)$entry->getSize();
        $unregisteredMap[$familyKey]['totalSizeBytes'] += $fileSize;
        $unregisteredMap[$familyKey]['files'][] = [
          'url' => $relProjectUrl,
          'filename' => basename($filePath),
          'format' => strtoupper($ext),
          'weight' => $weightRaw,
          'style' => 'normal',
          'sizeBytes' => $fileSize,
          'preloaded' => false,
        ];

        $previewFontFacesCssParts[] = self::buildFontFaceRule(
          $previewFamily,
          $relProjectUrl,
          $format,
          $weightRaw,
          'normal'
        );
      }
    }

    $projectFamilies = self::finalizeFamilyList(array_values($familiesMap));
    $unregisteredFamilies = self::finalizeFamilyList(array_values($unregisteredMap));

    usort($projectFamilies, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));
    usort($unregisteredFamilies, static fn(array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

    $families = array_merge($projectFamilies, $unregisteredFamilies, [self::getDefaultBrowserFont()]);

    return [
      'totalFonts' => count($families),
      'projectFontsCount' => count($projectFamilies),
      'unregisteredCount' => count($unregisteredFamilies),
      'orphanCssCount' => count($orphans),
      'orphans' => $orphans,
      'fontsDir' => $fontsDirRel,
      'families' => $families,
      'previewFontFacesCss' => implode("\n", $previewFontFacesCssParts),
    ];
  }

  /**
   * Devuelve las opciones para el selector <select id="fontSelect"> de la pestaña Layout.
   *
   * @param string|null $basePath Directorio raíz del proyecto (opcional)
   * @return array<int, array{name: string, value: string}>
   */
  public static function getSelectOptions(?string $basePath = null): array
  {
    $catalog = self::getCatalog($basePath);
    $options = [];

    foreach ($catalog['families'] as $fam) {
      if ($fam['category'] === 'project') {
        $options[] = [
          'name' => "⭐ {$fam['name']} (Instalada en proyecto)",
          'value' => $fam['cssValue'],
        ];
      } elseif ($fam['category'] === 'unregistered') {
        $options[] = [
          'name' => "📦 {$fam['name']} (Archivo sin registrar en CSS)",
          'value' => $fam['cssValue'],
        ];
      } else {
        $options[] = [
          'name' => 'Navegador (Sans-serif por defecto)',
          'value' => $fam['cssValue'],
        ];
      }
    }

    return $options;
  }

  /**
   * Carga las URLs normalizadas presentes en App/Config/preloadFonts.json.
   *
   * @param string $jsonPath Ruta al archivo preloadFonts.json
   * @return array<string, bool>
   */
  private static function loadPreloadSet(string $jsonPath): array
  {
    if (!is_file($jsonPath)) {
      return [];
    }

    $decoded = json_decode((string)file_get_contents($jsonPath), true);
    if (!is_array($decoded)) {
      return [];
    }

    $set = [];
    foreach ($decoded as $url) {
      if (!is_string($url) || trim($url) === '') {
        continue;
      }
      $clean = strtolower(ltrim(str_replace('\\', '/', trim($url)), '/'));
      $set[$clean] = true;
    }

    return $set;
  }

  /**
   * Construye un índice por familia con su clase base y clases específicas por peso desde font-project.css.
   *
   * @param string $cleanCss Contenido CSS sin comentarios
   * @return array<string, array{baseClass: string, weightClasses: array<int, string>}>
   */
  private static function parseCssClassIndex(string $cleanCss): array
  {
    $withoutFontFaces = (string)preg_replace('/@font-face\s*\{[^}]*\}/i', '', $cleanCss);
    $index = [];

    if (!preg_match_all('/\.([a-zA-Z0-9_-]+)\s*\{([^}]*)\}/', $withoutFontFaces, $matches, PREG_SET_ORDER)) {
      return $index;
    }

    foreach ($matches as $m) {
      $className = trim($m[1]);
      $body = $m[2];

      $family = self::extractCssProperty($body, 'font-family');
      $family = trim($family, " \t\n\r\0\x0B'\"");
      if ($family === '') {
        continue;
      }

      $key = mb_strtolower($family, 'UTF-8');
      if (!isset($index[$key])) {
        $index[$key] = [
          'baseClass' => '',
          'weightClasses' => [],
        ];
      }

      $weightStr = self::extractCssProperty($body, 'font-weight');
      if ($weightStr === '') {
        if ($index[$key]['baseClass'] === '') {
          $index[$key]['baseClass'] = $className;
        }
      } else {
        $w = self::normalizeSingleWeight($weightStr);
        if ($w !== null && !isset($index[$key]['weightClasses'][$w])) {
          $index[$key]['weightClasses'][$w] = $className;
        }
      }
    }

    return $index;
  }

  /**
   * Extrae el valor limpio (sin !important) de una propiedad CSS dentro de un bloque.
   *
   * @param string $block Cuerpo del bloque CSS
   * @param string $property Nombre de la propiedad CSS
   * @return string
   */
  private static function extractCssProperty(string $block, string $property): string
  {
    $pattern = '/(?:^|[;\s])' . preg_quote($property, '/') . '\s*:\s*([^;}\r\n]+)/i';
    if (!preg_match($pattern, $block, $m)) {
      return '';
    }

    $val = preg_replace('/\s*!important\s*$/i', '', trim($m[1]));
    return trim((string)$val);
  }

  /**
   * Resuelve una URL de fuente relativa al proyecto a una ruta física segura en disco.
   *
   * @param string $base Directorio raíz del proyecto
   * @param string $url URL extraída de @font-face
   * @return string|null Ruta física o null si es externa o inválida
   */
  private static function resolveProjectDiskPath(string $base, string $url): ?string
  {
    $url = trim($url);
    if ($url === '' || preg_match('/^(?:https?:)?\/\/|^data:/i', $url)) {
      return null;
    }

    $rel = ltrim(str_replace('\\', '/', $url), '/');
    if ($rel === '' || str_contains($rel, '..')) {
      return null;
    }

    return rtrim(str_replace('\\', '/', $base), '/') . '/' . $rel;
  }

  /**
   * Parsea el peso CSS de un @font-face y detecta si la fuente es variable.
   *
   * @param string $weightRaw Valor de font-weight en CSS
   * @param string $diskPath Ruta física del archivo
   * @return array{isVariable: bool, weights: int[]}
   */
  private static function parseFontWeights(string $weightRaw, string $diskPath): array
  {
    $weightClean = trim(preg_replace('/\s+/', ' ', $weightRaw));
    $isVariable = false;
    $weights = [];

    if (preg_match('/^(\d+)\s+(\d+)$/', $weightClean, $m)) {
      $isVariable = true;
      $min = max(100, (int)(ceil((int)$m[1] / 100) * 100));
      $max = min(900, (int)(floor((int)$m[2] / 100) * 100));
      if ($max >= $min) {
        for ($w = $min; $w <= $max; $w += 100) {
          $weights[] = $w;
        }
      }
    } else {
      $single = self::normalizeSingleWeight($weightClean);
      if ($single !== null) {
        $weights[] = $single;
      }
      if (FontCompressor::isVariableFont($diskPath)) {
        $isVariable = true;
        if (empty($weights)) {
          $weights = range(100, 900, 100);
        }
      }
    }

    if (empty($weights)) {
      $weights = [400];
    }

    return [
      'isVariable' => $isVariable,
      'weights' => $weights,
    ];
  }

  /**
   * Convierte un peso CSS simple a entero estándar (100-900).
   *
   * @param string $weight Valor textual o numérico
   * @return int|null
   */
  private static function normalizeSingleWeight(string $weight): ?int
  {
    $w = strtolower(trim($weight));
    if ($w === 'normal') {
      return 400;
    }
    if ($w === 'bold') {
      return 700;
    }
    if (is_numeric($w)) {
      $num = (int)$w;
      if ($num >= 1 && $num <= 1000) {
        return $num;
      }
    }

    return null;
  }

  /**
   * Normaliza pesos, ordena y calcula la etiqueta legible de rango para cada familia.
   *
   * @param array<int, array<string, mixed>> $families
   * @return array<int, array<string, mixed>>
   */
  private static function finalizeFamilyList(array $families): array
  {
    foreach ($families as &$fam) {
      $weights = array_values(array_unique(array_map('intval', $fam['weights'])));
      sort($weights, SORT_NUMERIC);
      if (empty($weights)) {
        $weights = [400];
      }
      $fam['weights'] = $weights;
      $fam['styles'] = array_values($fam['styles']);
      ksort($fam['weightClasses'], SORT_NUMERIC);

      if ($fam['isVariable'] && count($weights) > 1) {
        $fam['weightRangeLabel'] = $weights[0] . '–' . $weights[count($weights) - 1];
      } else {
        $fam['weightRangeLabel'] = implode(', ', $weights);
      }
    }
    unset($fam);

    return $families;
  }

  /**
   * Infiere el nombre de familia tipográfica desde la ruta relativa dentro de App/Rsc/Fonts.
   *
   * @param string $relInsideFonts Ruta relativa dentro de la carpeta Fonts
   * @return string
   */
  private static function inferFamilyFromDiskPath(string $relInsideFonts): string
  {
    $dir = dirname($relInsideFonts);
    $fileBase = pathinfo($relInsideFonts, PATHINFO_FILENAME);
    $parts = explode('-', $fileBase);
    $prefix = trim($parts[0]);

    $weightKeywords = ['thin', 'hairline', 'extralight', 'ultralight', 'light', 'regular', 'normal', 'book', 'medium', 'semibold', 'demibold', 'bold', 'extrabold', 'ultrabold', 'black', 'heavy'];
    if ($prefix !== '' && !in_array(strtolower($prefix), $weightKeywords, true)) {
      return $prefix;
    }

    if ($dir !== '.' && $dir !== '') {
      $segments = explode('/', $dir);
      $folderName = trim(end($segments));
      if ($folderName !== '') {
        return ucfirst($folderName);
      }
    }

    return $fileBase !== '' ? $fileBase : 'CustomFont';
  }

  /**
   * Construye una regla @font-face segura para vista previa en Theme Studio.
   *
   * @param string $family Nombre de familia CSS
   * @param string $url Ruta relativa al servidor
   * @param string $format Formato CSS (woff2, woff, truetype, opentype)
   * @param string $weight Peso o rango (ej. "400" o "100 900")
   * @param string $style Estilo (normal/italic)
   * @return string
   */
  private static function buildFontFaceRule(string $family, string $url, string $format, string $weight, string $style): string
  {
    $safeFamily = str_replace(["'", "\\", "\n", "\r"], ["\\'", "\\\\", '', ''], $family);
    $safeUrl = str_replace(["'", "\\", "\n", "\r"], ["\\'", "\\\\", '', ''], $url);
    $safeFormat = preg_replace('/[^a-z0-9-]/i', '', $format) ?: 'woff2';
    $safeWeight = preg_replace('/[^a-z0-9\s-]/i', '', $weight) ?: '400';
    $safeStyle = in_array($style, ['normal', 'italic', 'oblique'], true) ? $style : 'normal';

    return "@font-face { font-family: '{$safeFamily}'; src: url('{$safeUrl}') format('{$safeFormat}'); font-weight: {$safeWeight}; font-style: {$safeStyle}; font-display: swap; }";
  }

  /**
   * Devuelve el formato CSS correspondiente a la extensión del archivo de fuente.
   *
   * @param string $ext Extensión en minúsculas
   * @return string
   */
  private static function extensionToCssFormat(string $ext): string
  {
    return match ($ext) {
      'ttf' => 'truetype',
      'otf' => 'opentype',
      'woff' => 'woff',
      default => 'woff2',
    };
  }

  /**
   * Sugiere un nombre de clase CSS limpio a partir de la familia tipográfica.
   *
   * @param string $family Nombre de familia
   * @return string
   */
  private static function suggestClassName(string $family): string
  {
    $clean = lcfirst((string)preg_replace('/[^a-zA-Z0-9]/', '', $family));
    return $clean !== '' ? $clean : 'customFont';
  }

  /**
   * Genera una pila CSS estándar para una familia personalizada.
   *
   * @param string $family Nombre de la familia
   * @return string
   */
  private static function formatCssFamilyStack(string $family): string
  {
    $safe = str_replace("'", '', $family);
    return "'{$safe}', system-ui, -apple-system, sans-serif";
  }

  /**
   * Convierte una cadena a identificador slug seguro para IDs en HTML/JS.
   *
   * @param string $text Texto de entrada
   * @return string
   */
  private static function slugify(string $text): string
  {
    $slug = strtolower((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'font';
  }

  /**
   * Verifica si algún segmento de una ruta relativa comienza con punto (archivo/carpeta oculta).
   *
   * @param string $relPath Ruta relativa
   * @return bool
   */
  private static function hasHiddenSegment(string $relPath): bool
  {
    foreach (explode('/', $relPath) as $seg) {
      if ($seg === '' || str_starts_with($seg, '.')) {
        return true;
      }
    }
    return false;
  }
}
