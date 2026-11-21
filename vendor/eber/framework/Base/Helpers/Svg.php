<?php

if (!function_exists('renderSvgSafeNode')) {
  /**
   * Serializa de forma segura un nodo vectorial SVG permitiendo únicamente etiquetas y atributos de dibujo válidos.
   *
   * @param \DOMElement $node Elemento DOM del SVG
   * @return string HTML seguro del nodo y sus descendientes
   */
  function renderSvgSafeNode(\DOMElement $node): string
  {
    $allowedTags = [
      'g', 'path', 'circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse',
      'defs', 'lineargradient', 'radialgradient', 'stop', 'clippath', 'mask', 'use'
    ];
    $tagName = strtolower($node->localName ?: $node->nodeName);
    if (!in_array($tagName, $allowedTags, true)) {
      return '';
    }

    $allowedAttrs = [
      'id', 'd', 'cx', 'cy', 'r', 'rx', 'ry', 'x', 'y', 'x1', 'y1', 'x2', 'y2',
      'width', 'height', 'points', 'transform', 'fill', 'fill-rule', 'fill-opacity',
      'clip-rule', 'clip-path', 'mask', 'stroke', 'stroke-width', 'stroke-linecap',
      'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset', 'stroke-miterlimit',
      'stroke-opacity', 'opacity', 'offset', 'stop-color', 'stop-opacity',
      'gradientunits', 'gradienttransform', 'href', 'xlink:href', 'class'
    ];

    $attrStr = '';
    if ($node->hasAttributes()) {
      foreach ($node->attributes as $attr) {
        $attrName = strtolower($attr->nodeName);
        if (str_starts_with($attrName, 'on') || !in_array($attrName, $allowedAttrs, true)) {
          continue;
        }
        $val = trim((string)$attr->nodeValue);
        if (($attrName === 'href' || $attrName === 'xlink:href') && !str_starts_with($val, '#')) {
          continue;
        }
        $attrStr .= ' ' . $attr->nodeName . '="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"';
      }
    }

    $childrenHtml = '';
    foreach ($node->childNodes as $child) {
      if ($child instanceof \DOMElement) {
        $childrenHtml .= renderSvgSafeNode($child);
      }
    }

    $canonicalTag = $node->localName ?: $node->nodeName;
    if ($childrenHtml === '') {
      return '<' . $canonicalTag . $attrStr . ' />';
    }

    return '<' . $canonicalTag . $attrStr . '>' . $childrenHtml . '</' . $canonicalTag . '>';
  }
}

if (!function_exists('renderSvgFile')) {
  /**
   * Renderiza un archivo SVG desde una ruta absoluta del sistema de archivos
   * heredando la tipografía y color actual vía CSS (`1em` y `currentColor`).
   *
   * @param string $route_svg Ruta absoluta del archivo .svg
   * @param string $class Clases CSS adicionales para el elemento <svg>
   * @param string $transform Transformación SVG opcional (ej. "rotate(90)")
   * @return string Markup <svg> procesado o '?!' si el archivo es inválido
   */
  function renderSvgFile(string $route_svg, string $class = '', string $transform = ''): string
  {
    if ($route_svg === '' || !is_file($route_svg)) {
      return '?!';
    }

    $rawSvg = @file_get_contents($route_svg);
    if ($rawSvg === false || trim($rawSvg) === '') {
      return '?!';
    }

    // Cargar XML silenciando errores de parseo
    libxml_use_internal_errors(true);
    $dom = new \DOMDocument();
    $loaded = $dom->loadXML($rawSvg, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    if (!$loaded) {
      return '?!';
    }

    $svgElement = $dom->getElementsByTagName('svg')->item(0);
    if (!$svgElement instanceof \DOMElement) {
      return '?!';
    }

    if ($svgElement->hasAttribute('viewBox')) {
      $viewBox = $svgElement->getAttribute('viewBox');
    } elseif (
      $svgElement->hasAttribute('width') &&
      $svgElement->hasAttribute('height') &&
      preg_match('/^\s*(\d+(?:\.\d+)?)\s*(?:px)?\s*$/i', $svgElement->getAttribute('width'), $mw) &&
      preg_match('/^\s*(\d+(?:\.\d+)?)\s*(?:px)?\s*$/i', $svgElement->getAttribute('height'), $mh)
    ) {
      $viewBox = '0 0 ' . $mw[1] . ' ' . $mh[1];
    } else {
      $viewBox = '0 0 24 24';
    }

    $safeViewBox = htmlspecialchars($viewBox, ENT_QUOTES, 'UTF-8');
    $safeClass = htmlspecialchars($class, ENT_QUOTES, 'UTF-8');
    $safeTransform = $transform !== '' ? htmlspecialchars($transform, ENT_QUOTES, 'UTF-8') : '';

    // Detectar si contiene otras primitivas vectoriales además de <path> o usa fill="none" (ej. iconos outline en Custom/)
    $extraShapeTags = ['circle', 'rect', 'line', 'polyline', 'polygon', 'ellipse', 'g', 'use', 'defs', 'linearGradient', 'radialGradient', 'clipPath', 'mask'];
    $hasComplexShapes = false;
    foreach ($extraShapeTags as $tag) {
      if ($svgElement->getElementsByTagName($tag)->length > 0) {
        $hasComplexShapes = true;
        break;
      }
    }

    $rootFill = $svgElement->hasAttribute('fill') ? trim($svgElement->getAttribute('fill')) : '';
    $isOutlineRoot = (strtolower($rootFill) === 'none');

    if ($hasComplexShapes || $isOutlineRoot) {
      $innerHtml = '';
      foreach ($svgElement->childNodes as $child) {
        if ($child instanceof \DOMElement) {
          $innerHtml .= renderSvgSafeNode($child);
        }
      }

      if ($innerHtml === '') {
        return '?!';
      }

      if ($safeTransform !== '') {
        $innerHtml = '<g transform="' . $safeTransform . '">' . $innerHtml . '</g>';
      }

      $rootAttrs = ' fill="' . htmlspecialchars($rootFill !== '' ? $rootFill : 'currentColor', ENT_QUOTES, 'UTF-8') . '"';
      foreach (['fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin'] as $rootAttr) {
        if ($svgElement->hasAttribute($rootAttr)) {
          $rootAttrs .= ' ' . $rootAttr . '="' . htmlspecialchars($svgElement->getAttribute($rootAttr), ENT_QUOTES, 'UTF-8') . '"';
        }
      }

      return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $safeViewBox . '"' . $rootAttrs . ' class="' . $safeClass . '" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">' . $innerHtml . '</svg>';
    }

    // Construir todos los paths preservando sus atributos originales
    $pathsHtml = '';
    $pathElements = $svgElement->getElementsByTagName('path');

    foreach ($pathElements as $path) {
      if (!$path->hasAttribute('d')) {
        continue;
      }

      $d = htmlspecialchars($path->getAttribute('d'), ENT_QUOTES, 'UTF-8');
      $attrs = '';

      // Preservar atributos importantes del path original para permitir control CSS
      $preserve = ['fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'opacity', 'fill'];
      foreach ($preserve as $attr) {
        if ($path->hasAttribute($attr)) {
          $attrs .= ' ' . $attr . '="' . htmlspecialchars($path->getAttribute($attr), ENT_QUOTES, 'UTF-8') . '"';
        }
      }

      // Aplicar transformación si existe
      $transformAttr = $safeTransform !== '' ? ' transform="' . $safeTransform . '"' : '';

      $pathsHtml .= '<path d="' . $d . '"' . $attrs . $transformAttr . ' />';
    }

    if ($pathsHtml === '') {
      return '?!';
    }

    // Estilos base inline:
    // - width: 1em y height: 1em: hereda y respeta estrictamente el font-size (del padre o el asignado vía clase)
    // - flex-shrink: 0: evita que contenedores flex compriman o redimensionen el icono
    // - vertical-align: middle y display: inline-block: alineación con el texto
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $safeViewBox . '" fill="currentColor" class="' . $safeClass . '" style="width: 1em;height: 1em;vertical-align: middle;display: inline-block;flex-shrink: 0;">' . $pathsHtml . '</svg>';
  }
}

if (!function_exists('svg')) {
  /**
   * Carga e imprime el markup inline de un icono SVG del directorio de iconos (/Ico).
   * Soporta subcarpetas (ej. "Separator/fire-solid-full", "Custom/logo") con resolución
   * segura en sistemas sensibles a mayúsculas y minúsculas.
   *
   * @param string $name_icon Nombre o ruta relativa del icono sin extensión .svg
   * @param string $class Clases CSS personalizadas para el icono SVG
   * @param string $transform Transformación SVG opcional para los trazos
   * @return string Markup <svg> inline o '?!' si no se encuentra
   */
  function svg($name_icon, $class = '', $transform = ''): string
  {
    $cleanName = str_replace('\\', '/', trim((string)$name_icon));
    $cleanName = ltrim($cleanName, '/');
    $cleanName = (string)preg_replace('/\.svg$/i', '', $cleanName);

    if ($cleanName === '' || str_contains($cleanName, '..')) {
      return '?!';
    }

    $lowerName = mb_strtolower($cleanName, 'UTF-8');
    $frameworkIco = (defined('FRAMEWORK_PATH') ? FRAMEWORK_PATH : dirname(__DIR__, 2) . '/') . 'Resources/Ico/';

    $candidates = [];
    if (defined('ROUTE_ICON')) {
      $candidates[] = ROUTE_ICON . $cleanName . '.svg';
      if ($lowerName !== $cleanName) {
        $candidates[] = ROUTE_ICON . $lowerName . '.svg';
      }
    }
    $candidates[] = $frameworkIco . $cleanName . '.svg';
    if ($lowerName !== $cleanName) {
      $candidates[] = $frameworkIco . $lowerName . '.svg';
    }

    $route_svg = null;
    foreach ($candidates as $candidate) {
      if (is_file($candidate)) {
        $route_svg = $candidate;
        break;
      }
    }

    if ($route_svg === null) {
      return '?!';
    }

    return renderSvgFile($route_svg, (string)$class, (string)$transform);
  }
}
