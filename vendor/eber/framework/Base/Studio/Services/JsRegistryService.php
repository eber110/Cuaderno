<?php

namespace Base\Studio\Services;

use Base\Module\MinifyModule;

/**
 * Servicio de descubrimiento, auto-detección de uso y persistencia de módulos JS y librerías.
 *
 * Gestiona jsConfig.json y App/Config/loadLibraryJsConfiguration.php desde Frame Studio.
 */
class JsRegistryService
{
  private const CORE_FILES = [
    'aaSvgStore.js',
    'querie.js',
    'lang.js',
    'uiFormUtils.js',
  ];

  private const FILE_TO_MODULE_ALIASES = [
    'darkMode.js' => 'initDarkMode',
    'querie.js' => 'initResponsiveVisibility',
    'toast.js' => 'toastTrigger',
  ];

  /**
   * Señales conocidas para auto-detección precisa de componentes y librerías en vistas/segmentos.
   */
  private const COMPONENT_HINTS = [
    'initDarkMode' => ['dark-mode-toggle', 'data-theme', 'toggleTheme', 'initDarkMode'],
    'initResponsiveVisibility' => ['data-hide-', 'data-show-', 'hide-sm', 'hide-md', 'hide-lg'],
    'toUp' => ['toUp', '#toUp', 'to-up'],
    'notification' => ['notification', 'data-notification'],
    'modal' => ['modal-btn', 'data-modal'],
    'modalMenu' => ['modal-menu', 'modalMenu'],
    'accordion' => ['accordion', 'data-accordion'],
    'tabs' => ['tabs', 'data-tab'],
    'tooltip' => ['tooltip', 'data-tooltip'],
    'carousel' => ['carousel', 'data-carousel'],
    'inlineCarousel' => ['inline-carousel', 'inlineCarousel'],
    'dropdown' => ['dropdown', 'data-dropdown'],
    'copyToClipboard' => ['copy-btn', 'data-copy', 'copyToClipboard'],
    'toastTrigger' => ['toast', 'data-toast'],
    'imageGallery' => ['image-gallery', 'gallery-item'],
    'hamburgerMenu' => ['hamburger', 'menu-toggle'],
    'sidebar' => ['sidebar', 'data-sidebar'],
    'verticalMenu' => ['vertical-menu', 'verticalMenu'],
    'codeBlock' => ['code-block', 'pre code'],
    'cropImage' => ['crop-image', 'cropImage'],
    'cutPhrase' => ['cut-phrase', 'cutPhrase'],
    'textLimiter' => ['text-limiter', 'textLimiter'],
    'textAnimator' => ['text-animator', 'textAnimator'],
    'scrollObserver' => ['scroll-observer', 'data-animate', 'observe-'],
    'scrollMemory' => ['scrollMemory', 'scroll-memory'],
    'floatingScroll' => ['floating-scroll', 'floatingScroll'],
    'sortableContent' => ['sortable', 'sortableContent'],
    'autoSubmitForm' => ['auto-submit', 'autoSubmitForm'],
    'formComponents' => ['fme-input', 'fme-select', 'fme-checkbox', 'fme-radio', 'fme-switch', 'fme-field'],
    'multiStepForm' => ['multi-step', 'multiStepForm'],
    'repeat_camp' => ['repeat-camp', 'repeat_camp'],
    'datePicker' => ['fme-date', 'datePicker', 'data-datepicker'],
    'timePicker' => ['fme-time', 'timePicker', 'data-timepicker'],
    'rangeSlider' => ['fme-range', 'rangeSlider', 'data-range'],
    'passwordInput' => ['fme-password', 'passwordInput'],
    'otpCodeInput' => ['fme-otp', 'otpCodeInput'],
    'tagsInput' => ['fme-tags', 'tagsInput'],
    'stepperInput' => ['fme-stepper', 'stepperInput'],
    'ratingInput' => ['fme-rating', 'ratingInput'],
    'fileDropzone' => ['fme-dropzone', 'fileDropzone'],
    'remoteContent' => ['data-remote', 'remoteContent'],
    'backgroundMotionText' => ['background-motion-text', 'backgroundMotionText'],
    'loadImgModern' => ['data-src', 'lazy-img', 'loadImgModern'],
    'protectedMenuImage' => ['protected-img', 'protectedMenuImage'],
    'hideNavigationPath' => ['hideNavigationPath', 'data-hide-nav'],
    'animations' => ['animate-', 'data-anim'],
    'portfolioAnimation' => ['portfolioAnimation', 'portfolio-anim'],
    'contact' => ['contact-form', 'contactForm'],
    'savePost' => ['save-post', 'savePost'],
    'adjustSizeHeight' => ['adjustSizeHeight', 'adjust-height'],
  ];

  private const LIBRARY_HINTS = [
    'Gsap' => ['gsap', 'ScrollTrigger', 'data-gsap', 'animated'],
    'Charts' => ['GraphicsModule', 'fme-chart', 'data-chart', 'chart-container'],
  ];

  /**
   * Resuelve la ruta raíz del proyecto.
   */
  private static function resolveBasePath(?string $basePath): string
  {
    $raw = $basePath ?: (defined('ROOT_PATH') ? ROOT_PATH : getcwd());
    return rtrim(str_replace('\\', '/', (string)$raw), '/');
  }

  /**
   * Lee y normaliza jsConfig.json del proyecto.
   */
  public static function readJsConfig(string $basePath): array
  {
    $path = $basePath . '/jsConfig.json';
    $default = [
      'priority' => self::CORE_FILES,
      'exclude' => [],
      'functions' => [
        'defer' => [],
        'async' => [],
      ],
    ];

    if (!is_file($path)) {
      return $default;
    }

    $decoded = json_decode((string)file_get_contents($path), true);
    if (!is_array($decoded)) {
      return $default;
    }

    return [
      'priority' => array_values(array_filter(array_map('strval', (array)($decoded['priority'] ?? self::CORE_FILES)))),
      'exclude' => array_values(array_filter(array_map('strval', (array)($decoded['exclude'] ?? [])))),
      'functions' => [
        'defer' => is_array($decoded['functions']['defer'] ?? null) ? $decoded['functions']['defer'] : [],
        'async' => is_array($decoded['functions']['async'] ?? null) ? $decoded['functions']['async'] : [],
      ],
    ];
  }

  /**
   * Lee las librerías configuradas en App/Config/loadLibraryJsConfiguration.php.
   *
   * @return array<int, string>
   */
  public static function readConfiguredLibraries(string $basePath): array
  {
    $candidates = [
      $basePath . '/App/Config/loadLibraryJsConfiguration.php',
      $basePath . '/Resources/Config/loadLibraryJsConfiguration.php',
    ];

    foreach ($candidates as $file) {
      if (!is_file($file)) {
        continue;
      }
      $loaded = require $file;
      if (!is_array($loaded)) {
        continue;
      }
      $active = [];
      foreach ($loaded as $key => $val) {
        if (is_string($val) && $val !== '') {
          $active[] = $val;
        } elseif (is_string($key) && ($val === true || is_array($val))) {
          $active[] = $key;
        }
      }
      return array_values(array_unique($active));
    }

    return [];
  }

  /**
   * Extrae el primer bloque JSDoc de cabecera antes de cualquier código ejecutable.
   */
  private static function extractHeaderJsDoc(string $content): string
  {
    $head = ltrim($content, "\xEF\xBB\xBF\r\n\t ");
    $head = substr($head, 0, 24576);

    $offset = 0;
    $len = strlen($head);
    while ($offset < $len) {
      // Saltar espacios en blanco y saltos de línea
      if (preg_match('/\G\s+/s', $head, $ws, 0, $offset)) {
        $offset += strlen($ws[0]);
        continue;
      }
      // Permitir comentarios de una línea // antes del bloque JSDoc principal
      if (preg_match('/\G\/\/[^\r\n]*(?:\r?\n|$)/', $head, $lc, 0, $offset)) {
        $offset += strlen($lc[0]);
        continue;
      }
      // Bloque JSDoc principal
      if (preg_match('/\G\/\*\*[\s\S]*?\*\//', $head, $jm, 0, $offset)) {
        return $jm[0];
      }
      // Cualquier otro token indica código ejecutable antes de JSDoc
      if (preg_match('/\G(?:export\s+|function\s+|class\s+|const\s+|let\s+|var\s+|document\.|window\.)/', $head, $codeMatch, 0, $offset)) {
        return '';
      }
      return '';
    }

    return '';
  }

  /**
   * Limpia un título introductorio redundante (nombre de archivo o línea con emoji inicial).
   */
  private static function cleanIntroParagraphs(string $intro): string
  {
    $lines = preg_split('/\r?\n/', trim($intro)) ?: [];
    $kept = [];
    foreach ($lines as $i => $line) {
      $trimmed = trim($line);
      if ($i === 0 && $trimmed !== '') {
        $isFilenameTitle = (bool)preg_match('/^(?:[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]\s*)?[a-zA-Z0-9_.-]+\.js$/u', $trimmed);
        $isShortTitleOnly = (bool)preg_match('/^[A-Z][A-Za-z0-9_.-]{2,32}$/', $trimmed);
        if ($isFilenameTitle || $isShortTitleOnly) {
          continue;
        }
      }
      $kept[] = $line;
    }

    return trim(implode("\n", $kept));
  }

  /**
   * Construye una descripción corta de 1-2 frases limpias para tarjetas.
   */
  private static function buildShortDescription(string $descriptionText, string $introText): string
  {
    $source = trim($descriptionText) !== '' ? trim($descriptionText) : self::cleanIntroParagraphs($introText);
    if ($source === '') {
      return '';
    }

    // Seguridad extra: cortar antes de cualquier etiqueta JSDoc residual
    $source = preg_replace('/@[a-zA-Z0-9_-]+\b[\s\S]*$/', '', $source) ?? $source;
    $singleLine = trim((string)preg_replace('/\s+/', ' ', $source));
    if ($singleLine === '') {
      return '';
    }

    // Limitar a 1-2 frases y ~190 caracteres
    if (preg_match('/^(.+?[.!?])(?:\s+(.+?[.!?]))?(?:\s|$)/u', $singleLine, $sm)) {
      $twoSentences = trim($sm[1] . (isset($sm[2]) ? ' ' . $sm[2] : ''));
      if (mb_strlen($twoSentences, 'UTF-8') <= 190) {
        return $twoSentences;
      }
      if (mb_strlen($sm[1], 'UTF-8') <= 190) {
        return trim($sm[1]);
      }
    }

    if (mb_strlen($singleLine, 'UTF-8') <= 190) {
      return $singleLine;
    }

    $cut = mb_substr($singleLine, 0, 187, 'UTF-8');
    $lastSpace = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > 110) {
      $cut = mb_substr($cut, 0, $lastSpace, 'UTF-8');
    }
    return rtrim($cut, " .,;:") . '…';
  }

  /**
   * Normaliza un bloque @example preservando saltos de línea, indentación relativa, título e idioma.
   */
  private static function normalizeExampleBlock(string $rawExample): array
  {
    $lines = preg_split('/\r?\n/', $rawExample) ?: [];

    // Recortar líneas vacías al inicio y final
    while (!empty($lines) && trim($lines[0]) === '') {
      array_shift($lines);
    }
    while (!empty($lines) && trim($lines[count($lines) - 1]) === '') {
      array_pop($lines);
    }

    if (empty($lines)) {
      return ['title' => 'Ejemplo', 'lang' => 'js', 'code' => ''];
    }

    // Dedent del margen común en líneas no vacías
    $minIndent = null;
    foreach ($lines as $line) {
      if (trim($line) === '') {
        continue;
      }
      if (preg_match('/^([ \t]*)/', $line, $im)) {
        $indentLen = strlen(str_replace("\t", '  ', $im[1]));
        if ($minIndent === null || $indentLen < $minIndent) {
          $minIndent = $indentLen;
        }
      }
    }

    if ($minIndent !== null && $minIndent > 0) {
      $lines = array_map(static function (string $line) use ($minIndent): string {
        if (trim($line) === '') {
          return '';
        }
        $expanded = preg_replace_callback('/^[ \t]+/', static fn(array $m): string => str_replace("\t", '  ', $m[0]), $line) ?? $line;
        return substr($expanded, $minIndent);
      }, $lines);
    }

    $title = '';
    $firstTrimmed = trim($lines[0]);
    if (count($lines) > 1) {
      if (preg_match('/^\/\/\s*(.+)$/', $firstTrimmed, $cm)) {
        $title = trim($cm[1]);
        array_shift($lines);
      } elseif (preg_match('/^<!--\s*(.+?)\s*-->$/', $firstTrimmed, $hm)) {
        $title = trim($hm[1]);
        array_shift($lines);
      }
    }

    while (!empty($lines) && trim($lines[0]) === '') {
      array_shift($lines);
    }

    $code = implode("\n", $lines);
    $trimmedCode = ltrim($code);

    $lang = 'js';
    if (stripos($title, 'jsConfig') !== false || stripos($title, 'json') !== false || preg_match('/^(?:\{|\[|"[a-zA-Z0-9_.-]+"\s*:)/', $trimmedCode)) {
      $lang = 'json';
    } elseif (preg_match('/<[a-zA-Z][a-zA-Z0-9-]*(?:\s|>|\/)/', $code)) {
      $lang = 'html';
    }

    if ($title === '') {
      $title = match ($lang) {
        'html' => 'Ejemplo en HTML',
        'json' => 'Configuración en jsConfig.json',
        default => 'Ejemplo en JavaScript',
      };
    }

    return [
      'title' => $title,
      'lang' => $lang,
      'code' => $code,
    ];
  }

  /**
   * Parsea una línea @param JSDoc en estructura uniforme.
   */
  private static function parseParamTag(string $rawLine): ?array
  {
    $line = trim($rawLine);
    if ($line === '') {
      return null;
    }

    $type = 'any';
    $rest = $line;
    if (preg_match('/^\{([^}]+)\}\s*(.*)$/', $line, $tm)) {
      $type = trim($tm[1]);
      $rest = trim($tm[2]);
    }

    $name = '';
    $default = '';
    $optional = false;
    $description = '';

    if (preg_match('/^\[([^\]]+)\]\s*(?:[-–—:]\s*)?(.*)$/', $rest, $bm)) {
      $optional = true;
      $inner = trim($bm[1]);
      $description = trim($bm[2]);
      if (str_contains($inner, '=')) {
        [$n, $d] = explode('=', $inner, 2);
        $name = trim($n);
        $default = trim($d);
      } else {
        $name = $inner;
      }
    } elseif (preg_match('/^([a-zA-Z0-9_$.]+)\s*(?:[-–—:]\s*)?(.*)$/', $rest, $nm)) {
      $name = trim($nm[1]);
      $description = trim($nm[2]);
    } else {
      return null;
    }

    if ($name === '') {
      return null;
    }

    return [
      'name' => $name,
      'type' => $type,
      'default' => $default,
      'optional' => $optional,
      'description' => $description,
    ];
  }

  /**
   * Parsea una línea @css en selector y descripción.
   */
  private static function parseCssTag(string $rawLine): ?array
  {
    $line = trim($rawLine);
    if ($line === '') {
      return null;
    }

    if (preg_match('/^(\S+(?:\s*\/\s*\S+)?)\s+[-–—:]\s+(.+)$/', $line, $m)) {
      return [
        'selector' => trim($m[1]),
        'description' => trim($m[2]),
      ];
    }
    if (preg_match('/^(\S+)\s+(.+)$/', $line, $m)) {
      return [
        'selector' => trim($m[1]),
        'description' => trim($m[2]),
      ];
    }

    return [
      'selector' => $line,
      'description' => '',
    ];
  }

  /**
   * Parsea una línea @attribute en nombre, opcionalidad y descripción.
   */
  private static function parseAttributeTag(string $rawLine): ?array
  {
    $line = trim($rawLine);
    if ($line === '') {
      return null;
    }

    $name = $line;
    $description = '';
    if (preg_match('/^([^\s]+)\s+[-–—:]\s+(.+)$/', $line, $m)) {
      $name = trim($m[1]);
      $description = trim($m[2]);
    } elseif (preg_match('/^([^\s]+)\s+(.+)$/', $line, $m)) {
      $name = trim($m[1]);
      $description = trim($m[2]);
    }

    $optional = stripos($description, 'opcional') !== false || str_contains($name, '=');
    return [
      'name' => $name,
      'optional' => $optional,
      'description' => $description,
    ];
  }

  /**
   * Detecta atributos data-* presentes en los ejemplos que no estén documentados explícitamente.
   */
  private static function extractDataAttributesFromExamples(array $examples, array $existingAttributes): array
  {
    $known = [];
    foreach ($existingAttributes as $attr) {
      $base = strtolower(trim(explode('=', (string)($attr['name'] ?? ''), 2)[0]));
      if ($base !== '') {
        $known[$base] = true;
      }
    }

    $result = $existingAttributes;
    foreach ($examples as $ex) {
      if (($ex['lang'] ?? '') !== 'html') {
        continue;
      }
      if (preg_match_all('/\b(data-[a-zA-Z0-9_-]+)\b/', (string)($ex['code'] ?? ''), $am)) {
        foreach ($am[1] as $attrName) {
          $key = strtolower($attrName);
          if (isset($known[$key])) {
            continue;
          }
          $known[$key] = true;
          $result[] = [
            'name' => $attrName,
            'optional' => true,
            'description' => 'Atributo HTML utilizado en los ejemplos del módulo.',
          ];
        }
      }
    }

    return $result;
  }

  /**
   * Parsea línea por línea el bloque JSDoc principal preservando indentación y ejemplos.
   */
  private static function parseHeaderJsDoc(string $rawBlock): array
  {
    if ($rawBlock === '') {
      return [
        'tagModule' => '',
        'shortDescription' => '',
        'fullDescription' => '',
        'examples' => [],
        'params' => [],
        'cssClasses' => [],
        'attributes' => [],
        'requires' => [],
      ];
    }

    $inner = preg_replace('/^\/\*\*\s*|\s*\*\/$/', '', $rawBlock) ?? '';
    $rawLines = preg_split('/\r?\n/', $inner) ?: [];

    $cleanLines = array_map(static function (string $line): string {
      return preg_replace('/^[ \t]*\* ?/', '', $line) ?? $line;
    }, $rawLines);

    $section = 'intro';
    $introLines = [];
    $descriptionLines = [];
    $examplesRaw = [];
    $currentExampleLines = [];
    $paramsRaw = [];
    $cssRaw = [];
    $attributesRaw = [];
    $requires = [];
    $tagModule = '';

    $flushExample = static function () use (&$currentExampleLines, &$examplesRaw): void {
      if (!empty($currentExampleLines)) {
        $examplesRaw[] = implode("\n", $currentExampleLines);
        $currentExampleLines = [];
      }
    };

    foreach ($cleanLines as $line) {
      if (preg_match('/^[ \t]*@([a-zA-Z0-9_-]+)(?:[ \t]+(.*))?$/', $line, $tm)) {
        $tag = strtolower($tm[1]);
        $rest = $tm[2] ?? '';

        if ($section === 'example') {
          $flushExample();
        }

        switch ($tag) {
          case 'function':
          case 'module':
            $section = 'other';
            if ($tagModule === '' && preg_match('/^([a-zA-Z0-9_]+)/', trim($rest), $mm)) {
              $tagModule = $mm[1];
            }
            break;

          case 'description':
            $section = 'description';
            if (trim($rest) !== '') {
              $descriptionLines[] = trim($rest);
            }
            break;

          case 'example':
            $section = 'example';
            $currentExampleLines = [];
            if (trim($rest) !== '') {
              $currentExampleLines[] = $rest;
            }
            break;

          case 'param':
            $section = 'param';
            if (trim($rest) !== '') {
              $paramsRaw[] = trim($rest);
            }
            break;

          case 'css':
            $section = 'css';
            if (trim($rest) !== '') {
              $cssRaw[] = trim($rest);
            }
            break;

          case 'attribute':
            $section = 'attribute';
            if (trim($rest) !== '') {
              $attributesRaw[] = trim($rest);
            }
            break;

          case 'requires':
            $section = 'requires';
            if (trim($rest) !== '') {
              $requires[] = trim(explode('-', $rest)[0]);
            }
            break;

          default:
            $section = 'other';
            break;
        }
        continue;
      }

      switch ($section) {
        case 'intro':
          $introLines[] = $line;
          break;
        case 'description':
          $descriptionLines[] = trim($line);
          break;
        case 'example':
          $currentExampleLines[] = $line;
          break;
        case 'param':
          if (trim($line) !== '' && !empty($paramsRaw)) {
            $lastIdx = count($paramsRaw) - 1;
            $paramsRaw[$lastIdx] .= ' ' . trim($line);
          }
          break;
        case 'css':
          if (trim($line) !== '' && !empty($cssRaw)) {
            $lastIdx = count($cssRaw) - 1;
            $cssRaw[$lastIdx] .= ' ' . trim($line);
          }
          break;
        case 'attribute':
          if (trim($line) !== '' && !empty($attributesRaw)) {
            $lastIdx = count($attributesRaw) - 1;
            $attributesRaw[$lastIdx] .= ' ' . trim($line);
          }
          break;
        default:
          break;
      }
    }

    if ($section === 'example') {
      $flushExample();
    }

    $introText = trim(implode("\n", $introLines));
    $cleanIntro = self::cleanIntroParagraphs($introText);
    $descriptionText = trim((string)preg_replace('/\n{3,}/', "\n\n", implode("\n", $descriptionLines)));

    $shortDescription = self::buildShortDescription($descriptionText, $introText);

    $fullParts = [];
    if ($descriptionText !== '') {
      $fullParts[] = trim((string)preg_replace('/[ \t]*\n[ \t]*/', ' ', str_replace("\n\n", "\x00", $descriptionText)));
      $fullParts[0] = str_replace("\x00", "\n\n", $fullParts[0]);
    }
    if ($cleanIntro !== '') {
      $normIntro = trim((string)preg_replace('/\s+/', ' ', $cleanIntro));
      $normDesc = trim((string)preg_replace('/\s+/', ' ', $descriptionText));
      if ($normDesc === '' || (!str_contains($normDesc, $normIntro) && $normIntro !== $normDesc)) {
        if ($normDesc === '') {
          $fullParts[] = $cleanIntro;
        } elseif (!str_contains($normIntro, $normDesc)) {
          array_unshift($fullParts, $cleanIntro);
        }
      }
    }
    $fullDescription = trim(implode("\n\n", array_filter($fullParts)));

    $examples = [];
    foreach ($examplesRaw as $rawEx) {
      $normalized = self::normalizeExampleBlock($rawEx);
      if ($normalized['code'] !== '') {
        $examples[] = $normalized;
      }
    }

    $params = [];
    foreach ($paramsRaw as $rawParam) {
      $parsed = self::parseParamTag($rawParam);
      if ($parsed !== null) {
        $params[] = $parsed;
      }
    }

    $cssClasses = [];
    foreach ($cssRaw as $rawCss) {
      $parsed = self::parseCssTag($rawCss);
      if ($parsed !== null) {
        $cssClasses[] = $parsed;
      }
    }

    $attributes = [];
    foreach ($attributesRaw as $rawAttr) {
      $parsed = self::parseAttributeTag($rawAttr);
      if ($parsed !== null) {
        $attributes[] = $parsed;
      }
    }
    $attributes = self::extractDataAttributesFromExamples($examples, $attributes);

    return [
      'tagModule' => $tagModule,
      'shortDescription' => $shortDescription,
      'fullDescription' => $fullDescription !== '' ? $fullDescription : $shortDescription,
      'examples' => $examples,
      'params' => $params,
      'cssClasses' => $cssClasses,
      'attributes' => $attributes,
      'requires' => array_values(array_unique(array_filter($requires))),
    ];
  }

  /**
   * Genera un fragmento JSON formateado listo para usar en jsConfig.json.
   */
  private static function buildJsConfigSnippet(
    string $basename,
    string $moduleName,
    string $mode,
    array $configuredArgs,
    array $params
  ): string {
    if (in_array($basename, self::CORE_FILES, true)) {
      return (string)json_encode(
        ['priority' => [$basename]],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
      );
    }

    $snippetArgs = [];
    if (!empty($configuredArgs)) {
      $snippetArgs = array_values($configuredArgs);
    } else {
      foreach ($params as $param) {
        $pName = (string)($param['name'] ?? '');
        if ($pName === '' || str_contains($pName, '.')) {
          continue;
        }
        $def = trim((string)($param['default'] ?? ''));
        if ($def !== '') {
          if (($def[0] === "'" && str_ends_with($def, "'")) || ($def[0] === '"' && str_ends_with($def, '"'))) {
            $snippetArgs[] = substr($def, 1, -1);
          } elseif ($def === 'true') {
            $snippetArgs[] = true;
          } elseif ($def === 'false') {
            $snippetArgs[] = false;
          } elseif (is_numeric($def)) {
            $snippetArgs[] = str_contains($def, '.') ? (float)$def : (int)$def;
          } elseif ($def === '{}') {
            $snippetArgs[] = new \stdClass();
          } elseif ($def === '[]') {
            $snippetArgs[] = [];
          } else {
            $snippetArgs[] = $def;
          }
        }
      }
    }

    $section = ($mode === 'async') ? 'async' : 'defer';
    $payload = [
      'functions' => [
        $section => [
          $moduleName => $snippetArgs,
        ],
      ],
    ];

    return (string)json_encode(
      $payload,
      JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
  }

  /**
   * Extrae metadatos JSDoc, nombre exportado y selectores de un archivo JS.
   */
  private static function inspectJsFile(string $filePath): array
  {
    $basename = basename($filePath);
    $stem = pathinfo($basename, PATHINFO_FILENAME);
    $content = (string)@file_get_contents($filePath);
    $headerBlock = self::extractHeaderJsDoc($content);
    $docs = self::parseHeaderJsDoc($headerBlock);

    if (isset(self::FILE_TO_MODULE_ALIASES[$basename])) {
      $moduleName = self::FILE_TO_MODULE_ALIASES[$basename];
    } elseif ($docs['tagModule'] !== '') {
      $moduleName = $docs['tagModule'];
    } elseif (preg_match('/export\s+(?:async\s+)?function\s+' . preg_quote($stem, '/') . '\s*\(/', $content)) {
      $moduleName = $stem;
    } elseif (preg_match('/export\s+(?:async\s+)?function\s+([a-zA-Z0-9_]+)\s*\(/', $content, $m)) {
      $moduleName = $m[1];
    } else {
      $moduleName = $stem;
    }

    // Extraer selectores consultados en el código para apoyar la auto-detección
    $selectors = [];
    if (preg_match_all('/(?:querySelector(?:All)?|getElementsByClassName|getElementById)\(\s*[\'"]([^\'"]+)[\'"]/', $content, $sm)) {
      foreach ($sm[1] as $rawSel) {
        if (preg_match_all('/[.#]?([a-zA-Z][a-zA-Z0-9_-]{2,})/', $rawSel, $parts)) {
          foreach ($parts[1] as $part) {
            if (!in_array($part, ['body', 'html', 'div', 'span', 'button', 'input', 'form', 'active', 'hidden', 'open', 'show'], true)) {
              $selectors[] = $part;
            }
          }
        }
      }
    }

    return [
      'module' => $moduleName,
      'description' => $docs['shortDescription'],
      'shortDescription' => $docs['shortDescription'],
      'docs' => [
        'fullDescription' => $docs['fullDescription'],
        'examples' => $docs['examples'],
        'params' => $docs['params'],
        'cssClasses' => $docs['cssClasses'],
        'attributes' => $docs['attributes'],
        'requires' => $docs['requires'],
      ],
      'requires' => $docs['requires'],
      'sizeBytes' => is_file($filePath) ? (int)filesize($filePath) : 0,
      'selectors' => array_values(array_unique($selectors)),
    ];
  }

  /**
   * Obtiene el catálogo completo de módulos JS, librerías y estado actual de jsConfig.json.
   */
  public static function getCatalog(?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $jsConfig = self::readJsConfig($root);
    $activeLibraries = self::readConfiguredLibraries($root);

    $jsDirs = [
      ['dir' => $root . '/App/Public/Js', 'source' => 'app', 'category' => 'custom'],
      ['dir' => $root . '/Resources/Js', 'source' => 'framework', 'category' => 'auto'],
      ['dir' => $root . '/vendor/eber/framework/Resources/Js', 'source' => 'framework', 'category' => 'auto'],
    ];

    $frameworkResourcesJs = str_replace('\\', '/', dirname(__DIR__, 3)) . '/Resources/Js';
    if (is_dir($frameworkResourcesJs)) {
      $jsDirs[] = ['dir' => $frameworkResourcesJs, 'source' => 'framework', 'category' => 'auto'];
    }

    $seenFiles = [];
    $modules = [];

    foreach ($jsDirs as $entry) {
      if (!is_dir($entry['dir'])) {
        continue;
      }
      $files = MinifyModule::getDirectoryFilesRecursive($entry['dir'], 'js');
      foreach ($files as $filePath) {
        $basename = basename($filePath);
        if (isset($seenFiles[$basename])) {
          continue;
        }
        $seenFiles[$basename] = true;

        $meta = self::inspectJsFile($filePath);
        $moduleName = $meta['module'];
        $isCore = in_array($basename, self::CORE_FILES, true);
        $isPriority = in_array($basename, $jsConfig['priority'], true);
        $isExcluded = in_array($basename, $jsConfig['exclude'], true);

        $deferKeys = $jsConfig['functions']['defer'];
        $asyncKeys = $jsConfig['functions']['async'];

        $mode = 'inactive';
        $args = [];
        if ($isExcluded) {
          $mode = 'excluded';
        } elseif ($isPriority) {
          $mode = 'priority';
        } elseif (array_key_exists($moduleName, $deferKeys)) {
          $mode = 'defer';
          $args = is_array($deferKeys[$moduleName]) ? array_values($deferKeys[$moduleName]) : [];
        } elseif (array_key_exists($moduleName, $asyncKeys)) {
          $mode = 'async';
          $args = is_array($asyncKeys[$moduleName]) ? array_values($asyncKeys[$moduleName]) : [];
        }

        $docs = $meta['docs'];
        $docs['jsConfigSnippet'] = self::buildJsConfigSnippet(
          $basename,
          $moduleName,
          $mode,
          $args,
          $docs['params'] ?? []
        );

        $modules[] = [
          'file' => $basename,
          'module' => $moduleName,
          'source' => $entry['source'],
          'category' => $isCore ? 'core' : ($entry['source'] === 'app' ? 'custom' : 'component'),
          'description' => $meta['shortDescription'],
          'shortDescription' => $meta['shortDescription'],
          'docs' => $docs,
          'requires' => $meta['requires'],
          'sizeBytes' => $meta['sizeBytes'],
          'mode' => $mode,
          'active' => in_array($mode, ['priority', 'defer', 'async'], true),
          'args' => $args,
          'selectors' => $meta['selectors'],
        ];
      }
    }

    usort($modules, static function (array $a, array $b): int {
      $order = ['core' => 0, 'custom' => 1, 'component' => 2];
      $ca = $order[$a['category']] ?? 9;
      $cb = $order[$b['category']] ?? 9;
      if ($ca !== $cb) {
        return $ca <=> $cb;
      }
      return strcasecmp($a['module'], $b['module']);
    });

    // Descubrir librerías disponibles
    $libDirs = [
      $root . '/App/Rsc/Library',
      $root . '/Resources/Library',
      $root . '/vendor/eber/framework/Resources/Library',
    ];
    $seenLibs = [];
    $libraries = [];

    foreach ($libDirs as $libRoot) {
      if (!is_dir($libRoot)) {
        continue;
      }
      foreach (scandir($libRoot) ?: [] as $item) {
        if ($item === '.' || $item === '..' || isset($seenLibs[$item])) {
          continue;
        }
        $fullItem = $libRoot . '/' . $item;
        if (!is_dir($fullItem)) {
          continue;
        }
        $seenLibs[$item] = true;
        $totalSize = 0;
        $filesList = [];
        $it = new \RecursiveIteratorIterator(
          new \RecursiveDirectoryIterator($fullItem, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($it as $f) {
          if ($f->isFile()) {
            $totalSize += (int)$f->getSize();
            $filesList[] = $f->getFilename();
          }
        }
        $libraries[] = [
          'name' => $item,
          'active' => in_array($item, $activeLibraries, true),
          'sizeBytes' => $totalSize,
          'filesCount' => count($filesList),
          'files' => array_slice($filesList, 0, 10),
        ];
      }
    }

    usort($libraries, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));

    $totalBytes = 0;
    $activeBytes = 0;
    $activeCount = 0;
    foreach ($modules as $mod) {
      $totalBytes += $mod['sizeBytes'];
      if ($mod['active']) {
        $activeBytes += $mod['sizeBytes'];
        $activeCount++;
      }
    }

    $minJsPath = $root . '/App/Public/Min/Js/js.min.js';

    return [
      'jsConfig' => $jsConfig,
      'modules' => $modules,
      'libraries' => $libraries,
      'stats' => [
        'totalModules' => count($modules),
        'activeModules' => $activeCount,
        'totalSourceBytes' => $totalBytes,
        'activeSourceBytes' => $activeBytes,
        'minifiedBytes' => is_file($minJsPath) ? (int)filesize($minJsPath) : 0,
      ],
    ];
  }

  /**
   * Escanea vistas, segmentos y código del proyecto para auto-detectar qué módulos JS y librerías se usan realmente.
   */
  public static function detectUsage(?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);
    $catalog = self::getCatalog($root);

    $scanDirs = [
      $root . '/App/Views',
      $root . '/App/Segment',
      $root . '/App/Components',
      $root . '/App/Controllers',
      $root . '/App/Public/Js',
    ];

    $projectFiles = [];
    foreach ($scanDirs as $dir) {
      if (!is_dir($dir)) {
        continue;
      }
      foreach (['php', 'html', 'js'] as $ext) {
        foreach (MinifyModule::getDirectoryFilesRecursive($dir, $ext) as $f) {
          $norm = str_replace('\\', '/', $f);
          $rel = ltrim(substr($norm, strlen($root)), '/');
          $projectFiles[$rel] = (string)@file_get_contents($f);
        }
      }
    }

    $componentsUsage = [];
    $estimatedSavingsBytes = 0;
    $recommendedActive = [];

    foreach ($catalog['modules'] as $mod) {
      $modName = $mod['module'];
      $isCore = ($mod['category'] === 'core');

      if ($isCore) {
        $componentsUsage[$modName] = [
          'used' => true,
          'reason' => 'Núcleo base requerido',
          'matches' => 1,
          'files' => [],
        ];
        $recommendedActive[] = $modName;
        continue;
      }

      $patterns = self::COMPONENT_HINTS[$modName] ?? [];
      foreach ($mod['selectors'] ?? [] as $sel) {
        if (!in_array($sel, $patterns, true)) {
          $patterns[] = $sel;
        }
      }
      if (empty($patterns)) {
        $patterns[] = $modName;
      }

      $matchedFiles = [];
      $matchCount = 0;
      foreach ($projectFiles as $relPath => $content) {
        foreach ($patterns as $pat) {
          if ($pat !== '' && stripos($content, $pat) !== false) {
            $matchedFiles[] = $relPath;
            $matchCount++;
            break;
          }
        }
      }

      $isUsed = $matchCount > 0;
      if ($isUsed) {
        $recommendedActive[] = $modName;
      } elseif ($mod['active']) {
        $estimatedSavingsBytes += $mod['sizeBytes'];
      }

      $componentsUsage[$modName] = [
        'used' => $isUsed,
        'reason' => $isUsed ? 'Detectado en ' . count($matchedFiles) . ' archivo(s)' : 'Sin referencias detectadas en App/',
        'matches' => $matchCount,
        'files' => array_slice($matchedFiles, 0, 5),
      ];
    }

    $librariesUsage = [];
    foreach ($catalog['libraries'] as $lib) {
      $libName = $lib['name'];
      $patterns = self::LIBRARY_HINTS[$libName] ?? [$libName];
      $matchedFiles = [];
      $matchCount = 0;

      foreach ($projectFiles as $relPath => $content) {
        foreach ($patterns as $pat) {
          if ($pat !== '' && stripos($content, $pat) !== false) {
            $matchedFiles[] = $relPath;
            $matchCount++;
            break;
          }
        }
      }

      $librariesUsage[$libName] = [
        'used' => $matchCount > 0,
        'matches' => $matchCount,
        'files' => array_slice($matchedFiles, 0, 5),
      ];
    }

    return [
      'scannedFiles' => count($projectFiles),
      'components' => $componentsUsage,
      'libraries' => $librariesUsage,
      'recommendedActive' => $recommendedActive,
      'estimatedSavingsBytes' => $estimatedSavingsBytes,
    ];
  }

  /**
   * Valida y guarda jsConfig.json y App/Config/loadLibraryJsConfiguration.php, y recompila js.min.js si existe /App.
   */
  public static function saveConfig(array $payload, ?string $basePath = null): array
  {
    $root = self::resolveBasePath($basePath);

    $sanitizeFile = static function (string $name): string {
      $clean = trim(basename(str_replace('\\', '/', $name)));
      return preg_match('/^[a-zA-Z0-9_.\-]+\.js$/', $clean) ? $clean : '';
    };

    $sanitizeModule = static function (string $name): string {
      $clean = trim($name);
      return preg_match('/^[a-zA-Z0-9_]+$/', $clean) ? $clean : '';
    };

    $priority = [];
    foreach ((array)($payload['priority'] ?? self::CORE_FILES) as $item) {
      $clean = $sanitizeFile((string)$item);
      if ($clean !== '' && !in_array($clean, $priority, true)) {
        $priority[] = $clean;
      }
    }
    if (!in_array('querie.js', $priority, true)) {
      array_unshift($priority, 'querie.js');
    }

    $exclude = [];
    foreach ((array)($payload['exclude'] ?? []) as $item) {
      $clean = $sanitizeFile((string)$item);
      if ($clean !== '' && !in_array($clean, $exclude, true)) {
        $exclude[] = $clean;
      }
    }

    $defer = [];
    foreach ((array)($payload['defer'] ?? []) as $modName => $args) {
      $cleanMod = $sanitizeModule((string)$modName);
      if ($cleanMod === '') {
        continue;
      }
      $cleanArgs = [];
      if (is_array($args)) {
        foreach ($args as $arg) {
          if (is_scalar($arg)) {
            $str = trim(str_replace(["\r", "\n"], '', (string)$arg));
            if ($str !== '') {
              $cleanArgs[] = $str;
            }
          }
        }
      }
      $defer[$cleanMod] = $cleanArgs;
    }

    $async = [];
    foreach ((array)($payload['async'] ?? []) as $modName => $args) {
      $cleanMod = $sanitizeModule((string)$modName);
      if ($cleanMod === '' || array_key_exists($cleanMod, $defer)) {
        continue;
      }
      $cleanArgs = [];
      if (is_array($args)) {
        foreach ($args as $arg) {
          if (is_scalar($arg)) {
            $str = trim(str_replace(["\r", "\n"], '', (string)$arg));
            if ($str !== '') {
              $cleanArgs[] = $str;
            }
          }
        }
      }
      $async[$cleanMod] = $cleanArgs;
    }

    $jsConfigData = [
      'priority' => $priority,
      'exclude' => $exclude,
      'functions' => [
        'defer' => (object)$defer,
        'async' => (object)$async,
      ],
    ];

    $jsConfigPath = $root . '/jsConfig.json';
    $jsonOut = json_encode($jsConfigData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    if (@file_put_contents($jsConfigPath, $jsonOut, LOCK_EX) === false) {
      return ['success' => false, 'message' => 'No se pudo escribir jsConfig.json'];
    }

    // Guardar App/Config/loadLibraryJsConfiguration.php
    $librariesInput = $payload['libraries'] ?? null;
    if (is_array($librariesInput)) {
      $cleanLibs = [];
      foreach ($librariesInput as $lib) {
        $libClean = trim((string)$lib);
        if (preg_match('/^[a-zA-Z0-9_\-]+$/', $libClean) && !in_array($libClean, $cleanLibs, true)) {
          $cleanLibs[] = $libClean;
        }
      }

      $configDir = $root . '/App/Config';
      if (!is_dir($configDir)) {
        @mkdir($configDir, 0755, true);
      }

      $libLines = array_map(
        static fn(string $lib): string => "    '" . addslashes($lib) . "',",
        $cleanLibs
      );
      $phpLibs = "<?php\n\n/**\n * Configuración de librerías JS y CSS para carga automática en el <head>.\n * Generado desde Frame Studio.\n */\n\nreturn [\n"
        . implode("\n", $libLines)
        . ($libLines ? "\n" : '')
        . "];\n";

      @file_put_contents($configDir . '/loadLibraryJsConfiguration.php', $phpLibs, LOCK_EX);
    }

    // Recompilar js.min.js si existe carpeta App/Public
    $minifiedBytes = 0;
    if (is_dir($root . '/App/Public') && class_exists(MinifyModule::class)) {
      $jsPaths = [];
      if (is_dir($root . '/App/Public/Js')) {
        $jsPaths[] = $root . '/App/Public/Js/';
      }
      if (is_dir($root . '/vendor/eber/framework/Resources/Js')) {
        $jsPaths[] = $root . '/vendor/eber/framework/Resources/Js';
      } elseif (is_dir($root . '/Resources/Js')) {
        $jsPaths[] = $root . '/Resources/Js';
      }

      if (!empty($jsPaths)) {
        $outJs = $root . '/App/Public/Min/Js/js.min.js';
        if (!is_dir(dirname($outJs))) {
          @mkdir(dirname($outJs), 0755, true);
        }
        ob_start();
        MinifyModule::minifyJs($jsPaths, $outJs);
        ob_end_clean();
        if (is_file($outJs)) {
          $minifiedBytes = (int)filesize($outJs);
        }
      }
    }

    return [
      'success' => true,
      'message' => 'Configuración de módulos JS y librerías guardada correctamente.',
      'minifiedBytes' => $minifiedBytes,
      'activeCount' => count($defer) + count($async) + count($priority),
    ];
  }
}
