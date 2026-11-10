<?php

/**
 * Configuraciones globales del sistema de internacionalización (i18n).
 *
 * Este archivo define los idiomas habilitados, la estrategia de enrutamiento,
 * el nombre de la cookie de persistencia y el mapeo de códigos de país ISO 3166-1 alpha-2
 * a idiomas soportados para la detección automática por GeoIP (MaxMind).
 */

return [
  // Idioma principal por defecto del proyecto
  'default' => defined('LANG_DEFAULT') ? LANG_DEFAULT : 'es',

  // Idioma de respaldo (fallback) cuando una clave no existe en el idioma activo
  'fallback' => defined('LANG_FALLBACK') ? LANG_FALLBACK : 'es',

  // Lista de idiomas habilitados en el proyecto
  'available' => defined('LANG_AVAILABLE') ? LANG_AVAILABLE : ['es', 'en'],

  // Etiquetas legibles y códigos regionales para OpenGraph / selectores de UI
  'locales_meta' => [
    'es' => [
      'name'      => 'Español',
      'native'    => 'Español',
      'og_locale' => 'es_CL',
      'dir'       => 'ltr',
    ],
    'en' => [
      'name'      => 'Inglés',
      'native'    => 'English',
      'og_locale' => 'en_US',
      'dir'       => 'ltr',
    ],
  ],

  // Estrategia de URL:
  // - true: Soporta prefijo de idioma en la URL (/en/contacto) además de ?lang= y /lang/{locale}
  // - false: URLs sin prefijo de idioma (transparente por cookie/sesión)
  'url_prefix' => true,

  // Ocultar el prefijo en la URL cuando el idioma es el idioma por defecto ('es' -> /contacto, 'en' -> /en/contacto)
  'hide_default_in_url' => true,

  // Configuración de la cookie de persistencia de idioma
  'cookie' => [
    'name'     => 'fme_lang',
    'lifetime' => defined('TIME_YEAR') ? TIME_YEAR : 31536000,
    'path'     => '/',
    'samesite' => 'Lax',
  ],

  // Mapa país (ISO 3166-1 alpha-2) => idioma para detección GeoIP local (MaxMind)
  'country_to_locale' => [
    // Hispanoamérica y España -> Español
    'CL' => 'es',
    'AR' => 'es',
    'MX' => 'es',
    'ES' => 'es',
    'CO' => 'es',
    'PE' => 'es',
    'VE' => 'es',
    'EC' => 'es',
    'UY' => 'es',
    'PY' => 'es',
    'BO' => 'es',
    'CR' => 'es',
    'PA' => 'es',
    'DO' => 'es',
    'GT' => 'es',
    'HN' => 'es',
    'SV' => 'es',
    'NI' => 'es',
    'PR' => 'es',
    'CU' => 'es',
    'GQ' => 'es',

    // Países angloparlantes -> Inglés
    'US' => 'en',
    'GB' => 'en',
    'CA' => 'en',
    'AU' => 'en',
    'NZ' => 'en',
    'IE' => 'en',
    'ZA' => 'en',
    'SG' => 'en',
    'IN' => 'en',
    'PH' => 'en',
  ],
];
