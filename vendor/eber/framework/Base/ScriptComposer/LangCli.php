<?php

/**
 * Herramienta CLI de internacionalización (i18n) para Eber Framework.
 *
 * Comandos soportados:
 * - frame lang:make <locale> [--auto]  : Crea App/Lang/<locale>/ clonando claves del idioma por defecto y actualiza LangConfiguration.php
 * - frame lang:check                   : Compara todos los idiomas contra el idioma por defecto y reporta claves faltantes o sobrantes
 * - frame lang:scan                    : Escanea App/Views, App/Segment y App/Components buscando __('...') y reporta claves inexistentes
 * - frame lang:export [formato] [ruta] : Exporta todos los diccionarios a un archivo JSON o CSV unificado
 * - frame lang:import <archivo>        : Importa traducciones desde un archivo JSON o CSV hacia App/Lang/
 */

if (php_sapi_name() !== 'cli') {
  die("Este script solo puede ejecutarse desde la interfaz de comandos (CLI).\n");
}

class LangCli
{
  private string $basePath;
  private string $frameworkResources;
  private bool $isFrameworkRepo = false;
  private bool $isCustomPath = false;

  public function __construct(?string $basePath = null)
  {
    $this->isCustomPath = $basePath !== null;
    $this->basePath = $basePath ? str_replace('\\', '/', $basePath) : str_replace('\\', '/', getcwd());
    $this->frameworkResources = str_replace('\\', '/', dirname(__DIR__, 2) . '/Resources');

    if (file_exists($this->basePath . '/vendor/autoload.php')) {
      require_once $this->basePath . '/vendor/autoload.php';
    } elseif (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
      require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    }

    $composerJsonPath = $this->basePath . '/composer.json';
    if (file_exists($composerJsonPath)) {
      $composerData = json_decode((string)file_get_contents($composerJsonPath), true);
      if (($composerData['name'] ?? '') === 'eber/framework') {
        $this->isFrameworkRepo = true;
      }
    }
  }

  /**
   * Punto de entrada principal del comando CLI.
   *
   * @param array $args Argumentos de $_SERVER['argv'].
   * @return int Código de salida (0 = éxito, 1 = error/diferencias).
   */
  public function run(array $args): int
  {
    $action = strtolower(trim((string)($args[1] ?? '')));
    if (str_starts_with($action, 'lang:')) {
      $action = substr($action, 5);
    }

    if ($action === '') {
      $this->showUsage();
      return 0;
    }

    return match ($action) {
      'make'   => $this->commandMake(array_slice($args, 2)),
      'check'  => $this->commandCheck(),
      'scan'   => $this->commandScan(),
      'export' => $this->commandExport(array_slice($args, 2)),
      'import' => $this->commandImport(array_slice($args, 2)),
      default  => $this->unknownAction($action),
    };
  }

  /**
   * Crea un nuevo idioma en App/Lang/<locale>/ clonando la estructura de grupos y claves del idioma por defecto.
   * Soporta el flag --auto para pretraducir con GeminiModule si GEMINI_API_KEY está configurada.
   */
  private function commandMake(array $params): int
  {
    $locale = null;
    $autoTranslate = false;

    foreach ($params as $p) {
      if ($p === '--auto' || $p === '-a') {
        $autoTranslate = true;
      } elseif ($locale === null && !str_starts_with($p, '-')) {
        $locale = trim($p);
      }
    }

    if ($locale === null || $locale === '') {
      echo "Ingrese el código del nuevo idioma (ej: pt, fr, it, de, en_GB): ";
      $locale = trim((string)fgets(STDIN));
    }

    if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
      echo "❌ Error: Código de idioma inválido '{$locale}'. Use formato ISO (ej: 'pt', 'fr', 'pt_BR').\n";
      return 1;
    }

    $langBaseDir = $this->resolveLangDirectory();
    $defaultLocale = $this->resolveDefaultLocale($langBaseDir);
    $sourceDir = $langBaseDir . '/' . $defaultLocale;
    $targetDir = $langBaseDir . '/' . $locale;

    if (!is_dir($sourceDir)) {
      echo "❌ Error: No se encontró el directorio del idioma base en {$sourceDir}.\n";
      return 1;
    }

    if (!is_dir($targetDir)) {
      @mkdir($targetDir, 0755, true);
      echo "✅ Directorio creado: {$this->relPath($targetDir)}\n";
    } else {
      echo "ℹ️  El directorio {$this->relPath($targetDir)} ya existe. Se completarán los archivos o claves faltantes.\n";
    }

    $files = glob($sourceDir . '/*.php') ?: [];
    $createdFiles = 0;
    $updatedFiles = 0;

    foreach ($files as $srcFile) {
      $groupName = basename($srcFile, '.php');
      $destFile = $targetDir . '/' . $groupName . '.php';

      $srcData = include $srcFile;
      if (!is_array($srcData)) {
        continue;
      }

      $existingData = [];
      $fileExisted = is_file($destFile);
      if ($fileExisted) {
        $loaded = include $destFile;
        if (is_array($loaded)) {
          $existingData = $loaded;
        }
      }

      $newData = $this->buildSkeletonArray($srcData, $existingData, $defaultLocale, $locale, $autoTranslate);

      if (!$fileExisted || $newData !== $existingData) {
        $this->writePhpDictionary($destFile, $groupName, $locale, $newData);
        if ($fileExisted) {
          $updatedFiles++;
          echo "🔧 Actualizado: {$this->relPath($destFile)}\n";
        } else {
          $createdFiles++;
          echo "✅ Creado: {$this->relPath($destFile)}\n";
        }
      } else {
        echo "⏭️  Sin cambios: {$this->relPath($destFile)}\n";
      }
    }

    $this->registerLocaleInConfig($locale);

    echo "\n✨ Idioma '{$locale}' preparado ({$createdFiles} archivos nuevos, {$updatedFiles} actualizados).\n";
    return 0;
  }

  /**
   * Compara todos los idiomas presentes en el directorio Lang contra el idioma por defecto
   * y lista claves faltantes o sobrantes.
   */
  private function commandCheck(): int
  {
    $langBaseDir = $this->resolveLangDirectory();
    if (!is_dir($langBaseDir)) {
      echo "❌ No existe el directorio de idiomas: {$langBaseDir}\n";
      return 1;
    }

    $defaultLocale = $this->resolveDefaultLocale($langBaseDir);
    $locales = $this->listLocales($langBaseDir);

    if (!in_array($defaultLocale, $locales, true)) {
      echo "❌ El idioma por defecto '{$defaultLocale}' no existe en {$this->relPath($langBaseDir)}.\n";
      return 1;
    }

    echo "🔍 Verificando diccionarios en {$this->relPath($langBaseDir)} (idioma base: '{$defaultLocale}')...\n\n";

    $baseGroups = $this->loadAllGroupsForLocale($langBaseDir, $defaultLocale);
    $totalIssues = 0;

    foreach ($locales as $locale) {
      if ($locale === $defaultLocale) {
        continue;
      }

      $targetGroups = $this->loadAllGroupsForLocale($langBaseDir, $locale);
      $missingKeys = [];
      $extraKeys = [];
      $todoKeys = [];

      // Revisar grupos y claves de base contra target
      foreach ($baseGroups as $group => $baseFlat) {
        $targetFlat = $targetGroups[$group] ?? [];
        foreach ($baseFlat as $k => $v) {
          $fullKey = "{$group}.{$k}";
          if (!array_key_exists($k, $targetFlat)) {
            $missingKeys[] = $fullKey;
          } elseif (is_string($targetFlat[$k]) && (trim($targetFlat[$k]) === '' || str_starts_with($targetFlat[$k], 'TODO:'))) {
            $todoKeys[] = $fullKey;
          }
        }
      }

      // Revisar claves sobrantes en target que no existen en base
      foreach ($targetGroups as $group => $targetFlat) {
        $baseFlat = $baseGroups[$group] ?? [];
        foreach ($targetFlat as $k => $v) {
          if (!array_key_exists($k, $baseFlat)) {
            $extraKeys[] = "{$group}.{$k}";
          }
        }
      }

      $issueCount = count($missingKeys) + count($extraKeys) + count($todoKeys);
      $totalIssues += $issueCount;

      if ($issueCount === 0) {
        echo "✅ [{$locale}] Completo y sincronizado con '{$defaultLocale}'.\n";
      } else {
        echo "⚠️  [{$locale}] Se encontraron {$issueCount} diferencia(s):\n";
        foreach ($missingKeys as $mk) {
          echo "   ❌ Faltante: {$mk}\n";
        }
        foreach ($todoKeys as $tk) {
          echo "   📝 Pendiente (TODO/vacío): {$tk}\n";
        }
        foreach ($extraKeys as $ek) {
          echo "   ➕ Sobrante (no existe en {$defaultLocale}): {$ek}\n";
        }
      }
      echo "\n";
    }

    if ($totalIssues === 0) {
      echo "✨ Todos los idiomas están 100% sincronizados.\n";
      return 0;
    }

    echo "📊 Total de observaciones encontradas: {$totalIssues}\n";
    return 1;
  }

  /**
   * Escanea App/Views, App/Segment, App/Components y Base/Error buscando llamadas a __(), __n(), __raw() y t()
   * y reporta las claves utilizadas que no existen en los diccionarios.
   */
  private function commandScan(): int
  {
    $langBaseDir = $this->resolveLangDirectory();
    $defaultLocale = $this->resolveDefaultLocale($langBaseDir);
    $locales = $this->listLocales($langBaseDir);

    $scanDirs = [
      $this->basePath . '/App/Views',
      $this->basePath . '/App/Segment',
      $this->basePath . '/App/Components',
      $this->basePath . '/App/errorViews',
      $this->basePath . '/Base/Error',
    ];

    $foundKeys = [];
    $filesScanned = 0;

    $pattern = '/\b(?:__|__n|__raw|t)\(\s*[\'"]([a-zA-Z0-9_-]+\.[a-zA-Z0-9_.-]+)[\'"]/';

    foreach ($scanDirs as $dir) {
      if (!is_dir($dir)) {
        continue;
      }
      $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
      );
      foreach ($iterator as $file) {
        if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
          continue;
        }
        $filesScanned++;
        $content = (string)file_get_contents($file->getPathname());
        if (preg_match_all($pattern, $content, $matches)) {
          foreach ($matches[1] as $key) {
            $relFile = $this->relPath(str_replace('\\', '/', $file->getPathname()));
            $foundKeys[$key][$relFile] = true;
          }
        }
      }
    }

    echo "🔎 Escaneados {$filesScanned} archivos PHP. Claves únicas detectadas: " . count($foundKeys) . "\n\n";

    if (empty($foundKeys)) {
      echo "ℹ️  No se encontraron llamadas a helpers de traducción.\n";
      return 0;
    }

    // Cargar diccionarios combinados (Resources/Lang + App/Lang) por cada idioma
    $allDicts = [];
    foreach ($locales as $loc) {
      $allDicts[$loc] = $this->loadMergedFlatDictionary($loc);
    }

    $missingCount = 0;
    ksort($foundKeys);

    foreach ($foundKeys as $fullKey => $locations) {
      $missingInLocales = [];
      foreach ($locales as $loc) {
        if (!array_key_exists($fullKey, $allDicts[$loc])) {
          $missingInLocales[] = $loc;
        }
      }

      if (!empty($missingInLocales)) {
        $missingCount++;
        $locList = implode(', ', $missingInLocales);
        $where = implode(', ', array_keys($locations));
        echo "❌ '{$fullKey}' falta en [{$locList}] (usada en: {$where})\n";
      }
    }

    if ($missingCount === 0) {
      echo "✅ Todas las claves usadas en las vistas existen en los diccionarios (" . implode(', ', $locales) . ").\n";
      return 0;
    }

    echo "\n⚠️  Se encontraron {$missingCount} clave(s) sin definir en uno o más idiomas.\n";
    return 1;
  }

  /**
   * Exporta todos los diccionarios a un archivo JSON o CSV.
   * Uso: frame lang:export [json|csv] [ruta_destino]
   */
  private function commandExport(array $params): int
  {
    $format = strtolower($params[0] ?? 'json');
    if (!in_array($format, ['json', 'csv'], true)) {
      $outputPath = $params[0];
      $ext = strtolower(pathinfo($outputPath, PATHINFO_EXTENSION));
      $format = ($ext === 'csv') ? 'csv' : 'json';
    } else {
      $outputPath = $params[1] ?? ($this->basePath . '/lang_export.' . $format);
    }

    $langBaseDir = $this->resolveLangDirectory();
    $locales = $this->listLocales($langBaseDir);

    if (empty($locales)) {
      echo "❌ No se encontraron idiomas para exportar en {$this->relPath($langBaseDir)}.\n";
      return 1;
    }

    $matrix = [];
    foreach ($locales as $loc) {
      $groups = $this->loadAllGroupsForLocale($langBaseDir, $loc);
      foreach ($groups as $group => $flat) {
        foreach ($flat as $k => $v) {
          $matrix["{$group}.{$k}"][$loc] = (string)$v;
        }
      }
    }

    ksort($matrix);

    if ($format === 'csv') {
      $fp = fopen($outputPath, 'w');
      if (!$fp) {
        echo "❌ No se pudo abrir {$outputPath} para escritura.\n";
        return 1;
      }
      fputcsv($fp, array_merge(['key'], $locales));
      foreach ($matrix as $fullKey => $translations) {
        $row = [$fullKey];
        foreach ($locales as $loc) {
          $row[] = $translations[$loc] ?? '';
        }
        fputcsv($fp, $row);
      }
      fclose($fp);
    } else {
      $exportPayload = [
        'exported_at' => date('Y-m-d H:i:s'),
        'locales'     => $locales,
        'keys'        => $matrix,
      ];
      file_put_contents($outputPath, json_encode($exportPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    echo "✅ Exportadas " . count($matrix) . " claves (" . implode(', ', $locales) . ") en: {$this->relPath($outputPath)}\n";
    return 0;
  }

  /**
   * Importa traducciones desde un archivo JSON o CSV generado por lang:export hacia los diccionarios PHP.
   */
  private function commandImport(array $params): int
  {
    $inputFile = $params[0] ?? '';
    if ($inputFile === '' || !is_file($inputFile)) {
      echo "❌ Especifique un archivo válido para importar. Uso: frame lang:import <archivo.json|archivo.csv>\n";
      return 1;
    }

    $ext = strtolower(pathinfo($inputFile, PATHINFO_EXTENSION));
    $matrix = [];
    $locales = [];

    if ($ext === 'csv') {
      $fp = fopen($inputFile, 'r');
      if (!$fp) {
        echo "❌ No se pudo leer {$inputFile}.\n";
        return 1;
      }
      $header = fgetcsv($fp);
      if (!is_array($header) || count($header) < 2) {
        fclose($fp);
        echo "❌ Cabecera CSV inválida.\n";
        return 1;
      }
      $locales = array_slice($header, 1);
      while (($row = fgetcsv($fp)) !== false) {
        $fullKey = trim((string)($row[0] ?? ''));
        if ($fullKey === '' || !str_contains($fullKey, '.')) {
          continue;
        }
        foreach ($locales as $idx => $loc) {
          $val = $row[$idx + 1] ?? null;
          if ($val !== null) {
            $matrix[$loc][$fullKey] = (string)$val;
          }
        }
      }
      fclose($fp);
    } else {
      $decoded = json_decode((string)file_get_contents($inputFile), true);
      if (!is_array($decoded) || !isset($decoded['keys']) || !is_array($decoded['keys'])) {
        echo "❌ Estructura JSON inválida en {$inputFile}.\n";
        return 1;
      }
      foreach ($decoded['keys'] as $fullKey => $transByLoc) {
        if (!str_contains((string)$fullKey, '.') || !is_array($transByLoc)) {
          continue;
        }
        foreach ($transByLoc as $loc => $val) {
          $locales[$loc] = true;
          $matrix[$loc][$fullKey] = (string)$val;
        }
      }
      $locales = array_keys($locales);
    }

    $langBaseDir = $this->resolveLangDirectory();
    $writtenFiles = 0;

    foreach ($matrix as $loc => $flatEntries) {
      if (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', (string)$loc)) {
        continue;
      }
      $byGroup = [];
      foreach ($flatEntries as $fullKey => $val) {
        [$group, $itemKey] = explode('.', $fullKey, 2);
        $this->setNestedValue($byGroup[$group], $itemKey, $val);
      }

      $locDir = $langBaseDir . '/' . $loc;
      if (!is_dir($locDir)) {
        @mkdir($locDir, 0755, true);
      }

      foreach ($byGroup as $group => $nestedData) {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string)$group)) {
          continue;
        }
        $destFile = $locDir . '/' . $group . '.php';
        $existing = is_file($destFile) ? (include $destFile) : [];
        if (!is_array($existing)) {
          $existing = [];
        }
        $merged = array_replace_recursive($existing, $nestedData);
        $this->writePhpDictionary($destFile, (string)$group, (string)$loc, $merged);
        $writtenFiles++;
      }
    }

    echo "✅ Importación completada ({$writtenFiles} archivos de diccionario actualizados en {$this->relPath($langBaseDir)}).\n";
    return 0;
  }

  // ============================================
  // UTILIDADES INTERNAS
  // ============================================

  /**
   * Resuelve el directorio de idiomas activo (App/Lang en proyectos, o Resources/Lang en el repo del framework).
   */
  private function resolveLangDirectory(): string
  {
    $appLang = $this->basePath . '/App/Lang';
    if (is_dir($appLang) || !$this->isFrameworkRepo || $this->isCustomPath) {
      return $appLang;
    }
    return $this->frameworkResources . '/Lang';
  }

  private function resolveDefaultLocale(string $langBaseDir): string
  {
    $configFile = $this->basePath . '/App/Config/LangConfiguration.php';
    if (!is_file($configFile)) {
      $configFile = $this->frameworkResources . '/Config/LangConfiguration.php';
    }
    if (is_file($configFile)) {
      $cfg = include $configFile;
      if (is_array($cfg) && !empty($cfg['default']) && is_dir($langBaseDir . '/' . $cfg['default'])) {
        return (string)$cfg['default'];
      }
    }
    return 'es';
  }

  /**
   * Lista los subdirectorios de idiomas existentes.
   *
   * @return array<int, string>
   */
  private function listLocales(string $langBaseDir): array
  {
    if (!is_dir($langBaseDir)) {
      return [];
    }
    $items = scandir($langBaseDir) ?: [];
    $locales = [];
    foreach ($items as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }
      if (is_dir($langBaseDir . '/' . $item) && preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $item)) {
        $locales[] = $item;
      }
    }
    sort($locales);
    return $locales;
  }

  /**
   * Carga todos los archivos .php de un idioma y aplana sus claves.
   *
   * @return array<string, array<string, string>>
   */
  private function loadAllGroupsForLocale(string $langBaseDir, string $locale): array
  {
    $dir = $langBaseDir . '/' . $locale;
    if (!is_dir($dir)) {
      return [];
    }
    $files = glob($dir . '/*.php') ?: [];
    $result = [];
    foreach ($files as $file) {
      $group = basename($file, '.php');
      $data = include $file;
      if (is_array($data)) {
        $result[$group] = $this->flattenArray($data);
      }
    }
    return $result;
  }

  /**
   * Carga el diccionario plano combinado (Resources/Lang + App/Lang) para un idioma.
   *
   * @return array<string, string>
   */
  private function loadMergedFlatDictionary(string $locale): array
  {
    $flat = [];
    $dirs = [
      $this->frameworkResources . '/Lang/' . $locale,
      $this->basePath . '/App/Lang/' . $locale,
    ];

    foreach ($dirs as $dir) {
      if (!is_dir($dir)) {
        continue;
      }
      $files = glob($dir . '/*.php') ?: [];
      foreach ($files as $file) {
        $group = basename($file, '.php');
        $data = include $file;
        if (is_array($data)) {
          foreach ($this->flattenArray($data) as $k => $v) {
            $flat["{$group}.{$k}"] = (string)$v;
          }
        }
      }
    }

    return $flat;
  }

  /**
   * Aplana un array multidimensional usando notación de puntos.
   *
   * @return array<string, string>
   */
  private function flattenArray(array $array, string $prefix = ''): array
  {
    $result = [];
    foreach ($array as $key => $value) {
      $newKey = $prefix === '' ? (string)$key : $prefix . '.' . $key;
      if (is_array($value)) {
        $result = array_merge($result, $this->flattenArray($value, $newKey));
      } else {
        $result[$newKey] = (string)$value;
      }
    }
    return $result;
  }

  private function setNestedValue(?array &$array, string $dotKey, mixed $value): void
  {
    if ($array === null) {
      $array = [];
    }
    $keys = explode('.', $dotKey);
    $current = &$array;
    foreach ($keys as $k) {
      if (!isset($current[$k]) || !is_array($current[$k])) {
        $current[$k] = [];
      }
      $current = &$current[$k];
    }
    $current = $value;
  }

  /**
   * Construye el array de un nuevo idioma respetando valores existentes y marcando los nuevos con TODO
   * (o traduciéndolos con GeminiModule si $autoTranslate es true).
   */
  private function buildSkeletonArray(array $source, array $existing, string $fromLocale, string $toLocale, bool $autoTranslate): array
  {
    $result = [];
    foreach ($source as $k => $v) {
      if (is_array($v)) {
        $subExisting = (isset($existing[$k]) && is_array($existing[$k])) ? $existing[$k] : [];
        $result[$k] = $this->buildSkeletonArray($v, $subExisting, $fromLocale, $toLocale, $autoTranslate);
      } else {
        if (array_key_exists($k, $existing) && is_string($existing[$k]) && trim($existing[$k]) !== '' && !str_starts_with($existing[$k], 'TODO:')) {
          $result[$k] = $existing[$k];
        } else {
          $translated = null;
          if ($autoTranslate && class_exists('\\Base\\Module\\GeminiModule')) {
            $prompt = "Translate the following UI string from '{$fromLocale}' to '{$toLocale}'. Keep placeholders like :name, :count, {0}, {1}, [2,*] intact. Return ONLY the translated text without quotes:\n\n" . (string)$v;
            $res = \Base\Module\GeminiModule::prompt($prompt, ['temperature' => 0.2]);
            if ($res !== '') {
              $translated = $res;
            }
          }
          $result[$k] = $translated ?? ('TODO: ' . (string)$v);
        }
      }
    }
    return $result;
  }

  /**
   * Escribe un archivo de diccionario PHP con formato limpio e indentación de 2 espacios.
   */
  private function writePhpDictionary(string $filePath, string $group, string $locale, array $data): void
  {
    $exported = $this->exportArrayPhp($data, 1);
    $content = <<<PHP
<?php

/**
 * Diccionario '{$group}' ({$locale}).
 */

return {$exported};

PHP;
    file_put_contents($filePath, $content);
  }

  /**
   * Serializa un array PHP con sintaxis corta [...] e indentación de 2 espacios.
   */
  private function exportArrayPhp(array $data, int $depth = 1): string
  {
    if (empty($data)) {
      return '[]';
    }

    $indent = str_repeat('  ', $depth);
    $closingIndent = str_repeat('  ', $depth - 1);
    $lines = ["["];

    foreach ($data as $key => $val) {
      $escapedKey = "'" . addcslashes((string)$key, "'\\") . "'";
      if (is_array($val)) {
        $lines[] = "{$indent}{$escapedKey} => " . $this->exportArrayPhp($val, $depth + 1) . ",";
      } else {
        $escapedVal = "'" . addcslashes((string)$val, "'\\") . "'";
        $lines[] = "{$indent}{$escapedKey} => {$escapedVal},";
      }
    }

    $lines[] = "{$closingIndent}]";
    return implode("\n", $lines);
  }

  /**
   * Agrega el nuevo locale a App/Config/LangConfiguration.php si el archivo existe y aún no lo incluye.
   */
  private function registerLocaleInConfig(string $locale): void
  {
    $configFile = $this->basePath . '/App/Config/LangConfiguration.php';
    if (!is_file($configFile)) {
      return;
    }

    $content = (string)file_get_contents($configFile);
    if (preg_match('/[\'"]available[\'"]\s*=>\s*\[([^\]]*)\]/', $content, $m)) {
      if (!str_contains($m[1], "'{$locale}'") && !str_contains($m[1], "\"{$locale}\"")) {
        $trimmedList = rtrim(trim($m[1]), ',');
        $newList = $trimmedList === '' ? "'{$locale}'" : "{$trimmedList}, '{$locale}'";
        $updated = str_replace($m[0], "'available' => [{$newList}]", $content);
        if (file_put_contents($configFile, $updated)) {
          echo "🔧 Agregado '{$locale}' a App/Config/LangConfiguration.php\n";
        }
      }
    }
  }

  private function relPath(string $path): string
  {
    $norm = str_replace('\\', '/', $path);
    if (str_starts_with($norm, $this->basePath . '/')) {
      return substr($norm, strlen($this->basePath) + 1);
    }
    return $norm;
  }

  private function unknownAction(string $action): int
  {
    echo "❌ Acción desconocida '{$action}'.\n";
    $this->showUsage();
    return 1;
  }

  private function showUsage(): void
  {
    echo "\n🌐 Herramienta CLI de Idiomas (i18n):\n";
    echo "   frame lang:make <locale> [--auto]   Crea un nuevo idioma en App/Lang/<locale>/\n";
    echo "   frame lang:check                    Compara todos los idiomas contra el idioma por defecto\n";
    echo "   frame lang:scan                     Busca claves __('...') en vistas que no existan en diccionarios\n";
    echo "   frame lang:export [json|csv] [out]  Exporta todas las traducciones a JSON o CSV\n";
    echo "   frame lang:import <archivo>         Importa traducciones desde JSON o CSV\n\n";
  }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['argv'][0] ?? '')) {
  $cli = new LangCli();
  exit($cli->run($_SERVER['argv'] ?? []));
}
