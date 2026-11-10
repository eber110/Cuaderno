<?php

/**
 * ============================================
 * HELPERS GLOBALES DE INTERNACIONALIZACIÓN (i18n)
 * ============================================
 *
 * Funciones globales para traducir textos, pluralizar y generar URLs localizadas
 * desde cualquier vista, componente o controlador.
 *
 * Funcionan tanto en modo monoidioma (APP_MULTILANG=false, resuelve en APP_LOCALE)
 * como en modo multiidioma (APP_MULTILANG=true, resuelve en el idioma detectado).
 *
 * @package Base\Helpers
 */

use Base\Module\LangModule;

if (!function_exists('__')) {
  /**
   * Traduce una clave de diccionario en formato 'grupo.clave' e interpola parámetros ':param'.
   * Los valores interpolados se escapan automáticamente para prevenir XSS.
   *
   * @param string $key Clave con notación de puntos (ej: 'global.welcome').
   * @param array<string, scalar> $params Parámetros a interpolar (ej: ['name' => 'Eber']).
   * @param string|null $locale Idioma opcional (por defecto usa el idioma activo).
   * @return string Texto traducido (o la propia clave si no existe).
   *
   * @example <?= e(__('global.welcome')) ?>
   * @example <?= e(__('auth.greeting', ['name' => $userName])) ?>
   */
  function __(string $key, array $params = [], ?string $locale = null): string
  {
    return LangModule::get($key, $params, $locale, true);
  }
}

if (!function_exists('t')) {
  /**
   * Alias corto de __() para traducir una clave de diccionario.
   *
   * @param string $key Clave con notación de puntos (ej: 'global.welcome').
   * @param array<string, scalar> $params Parámetros a interpolar.
   * @param string|null $locale Idioma opcional.
   * @return string
   */
  function t(string $key, array $params = [], ?string $locale = null): string
  {
    return LangModule::get($key, $params, $locale, true);
  }
}

if (!function_exists('__raw')) {
  /**
   * Traduce una clave de diccionario sin escapar los parámetros interpolados.
   * Usar únicamente cuando los parámetros contienen HTML seguro y controlado por el desarrollador.
   *
   * @param string $key Clave con notación de puntos.
   * @param array<string, scalar> $params Parámetros a interpolar sin escape.
   * @param string|null $locale Idioma opcional.
   * @return string
   */
  function __raw(string $key, array $params = [], ?string $locale = null): string
  {
    return LangModule::get($key, $params, $locale, false);
  }
}

if (!function_exists('__n')) {
  /**
   * Traduce una clave aplicando reglas de pluralización según $count.
   * Inyecta automáticamente el parámetro ':count'.
   *
   * @param string $key Clave de traducción con formato plural ('{0} Sin ítems|{1} Un ítem|[2,*] :count ítems').
   * @param int|float $count Cantidad numérica a evaluar.
   * @param array<string, scalar> $params Parámetros adicionales opcionales.
   * @param string|null $locale Idioma opcional.
   * @return string
   *
   * @example <?= e(__n('global.items_count', $total)) ?>
   */
  function __n(string $key, int|float $count, array $params = [], ?string $locale = null): string
  {
    return LangModule::choice($key, $count, $params, $locale, true);
  }
}

if (!function_exists('locale')) {
  /**
   * Obtiene el código del idioma activo actual (ej: 'es', 'en').
   * Si APP_MULTILANG=false, retorna siempre APP_LOCALE.
   *
   * @return string
   *
   * @example <html lang="<?= locale() ?>">
   */
  function locale(): string
  {
    return LangModule::getLocale();
  }
}

if (!function_exists('langUrl')) {
  /**
   * Genera una URL adaptada al idioma activo o al idioma especificado en $locale.
   *
   * @param string $path Ruta del enlace (ej: '/contacto').
   * @param string|null $locale Idioma destino opcional (por defecto el idioma activo).
   * @param bool $absolute Si es true, devuelve URL absoluta incluyendo el dominio.
   * @return string
   *
   * @example <a href="<?= eUrl(langUrl('/contacto')) ?>"><?= e(__('global.contact')) ?></a>
   * @example <a href="<?= eUrl(langUrl('/contacto', 'en')) ?>">English</a>
   */
  function langUrl(string $path = '/', ?string $locale = null, bool $absolute = false): string
  {
    return LangModule::localizedUrl($path, $locale, $absolute);
  }
}
