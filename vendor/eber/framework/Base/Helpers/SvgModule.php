<?php

/**
 * @param name_icon -- es el nombre del icono en el directorio de icono svg
 * @param size -- es el tamaño en px o % del icono. Estos iconos están configurados para una relación de aspecto 1/1
 * @param class -- puede ingresar clases personalizadas a los iconos svg
 */
function svg($name_icon, $class = '', $transform = '')
{

  $name_icon_lower = mb_strtolower($name_icon, 'UTF-8');
  $route_svg = ROUTE_ICON . $name_icon_lower . '.svg';

  if (!file_exists($route_svg)) {
    return '?!';
  }

  // Cargar XML silenciando errores de parseo
  libxml_use_internal_errors(true);
  $dom = new DOMDocument();
  $dom->loadXML(file_get_contents($route_svg));
  libxml_clear_errors();

  $svgElement = $dom->getElementsByTagName('svg')->item(0);

  if (!$svgElement) {
    return '?!';
  }

  $viewBox = $svgElement->hasAttribute('viewBox') ? $svgElement->getAttribute('viewBox') : '0 0 24 24';

  // Construir todos los paths preservando sus atributos originales
  $pathsHtml = '';
  $pathElements = $svgElement->getElementsByTagName('path');

  foreach ($pathElements as $path) {

    if (!$path->hasAttribute('d')) {
      continue;
    }

    $d = $path->getAttribute('d');
    $attrs = '';

    // Preservar atributos importantes del path original para permitir control CSS
    $preserve = ['fill-rule', 'clip-rule', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin', 'opacity', 'fill'];
    foreach ($preserve as $attr) {
      if ($path->hasAttribute($attr)) {
        $attrs .= ' ' . $attr . '="' . $path->getAttribute($attr) . '"';
      }
    }

    // Aplicar transformación si existe
    $transformAttr = $transform ? ' transform="' . $transform . '"' : '';

    $pathsHtml .= '<path d="' . $d . '"' . $attrs . $transformAttr . ' />';
  }

  // Estilos base inline para retrocompatibilidad (width/height 1.1em, fill currentColor)
  return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="' . $viewBox . '" fill="currentColor" class="' . $class . '" style="width: 1.1em;height: 1.1em;vertical-align: middle;display: inline-block;">' . $pathsHtml . '</svg>';
}
