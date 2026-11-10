<?php

namespace App\Middleware;

use App\Middleware\MiddlewareInterface\MiddlewareInterface;
use Base\Module\LangModule;

/**
 * Middleware de localización e idioma (i18n).
 *
 * Puede asignarse globalmente o a grupos de rutas para forzar o detectar
 * el idioma de la solicitud antes de ejecutar la acción del controlador.
 */
class LocaleMiddleware implements MiddlewareInterface
{
  /**
   * Maneja la solicitud entrante verificando y aplicando la detección de idioma.
   *
   * @param mixed $requestData Datos de la solicitud actual ($_GET, $_POST, JSON).
   * @param callable $next Siguiente middleware o controlador final.
   * @return mixed
   */
  public function handle($requestData, callable $next)
  {
    if (class_exists(LangModule::class)) {
      LangModule::detect();
    }

    return $next($requestData);
  }
}
