<?php

namespace Base\Studio;

use Base\Control\Control;
use Base\Module\SecurityModule;

/**
 * Núcleo modular de Frame Studio ("frame secundario").
 *
 * Centraliza la verificación de entorno local, protección CSRF/loopback
 * y el registro de rutas exclusivas de desarrollo bajo Base/Studio/.
 * Este directorio completo se excluye en los empaquetados a producción.
 */
class StudioKernel
{
  private static bool $routesRegistered = false;

  /**
   * Verifica si la ejecución actual se encuentra en un entorno de desarrollo seguro (fail-closed).
   *
   * @return bool True solo si ENVIRONMENT está definido explícitamente como local/dev/testing.
   */
  public static function isDevEnvironment(): bool
  {
    if (class_exists(SecurityModule::class)) {
      return SecurityModule::isDevelopment();
    }
    if (!defined('ENVIRONMENT')) {
      return false;
    }
    $env = strtolower(trim((string)ENVIRONMENT));
    return in_array($env, ['local', 'development', 'dev', 'testing', 'test'], true);
  }

  /**
   * Valida entorno dev, origen loopback en HTTP y Content-Type + CSRF en mutaciones.
   *
   * @param Control $controller Controlador activo para emitir respuestas JSON.
   * @param bool $isMutation True si la petición modifica estado vía POST JSON.
   * @return bool
   */
  public static function guardRequest(Control $controller, bool $isMutation = false): bool
  {
    if (!self::isDevEnvironment()) {
      if ($isMutation) {
        $controller->json(['success' => false, 'message' => 'Recurso no disponible.'], 404);
      } else {
        \Core\ErrorHandler::handleCode(404, 404, 'Página no encontrada');
      }
      return false;
    }

    if (php_sapi_name() !== 'cli') {
      $allowRemote = filter_var($_ENV['FRAME_STUDIO_ALLOW_REMOTE'] ?? $_SERVER['FRAME_STUDIO_ALLOW_REMOTE'] ?? false, FILTER_VALIDATE_BOOLEAN);
      $remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
      if (!$allowRemote && !in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
        if ($isMutation) {
          $controller->json(['success' => false, 'message' => 'Acceso restringido al host local.'], 403);
        } else {
          \Core\ErrorHandler::handleCode(403, 403, 'Frame Studio solo admite conexiones locales (127.0.0.1 / ::1).');
        }
        return false;
      }

      if ($isMutation) {
        $contentType = (string)($_SERVER['CONTENT_TYPE'] ?? '');
        if (stripos($contentType, 'application/json') === false) {
          $controller->json(['success' => false, 'message' => 'Content-Type debe ser application/json.'], 415);
          return false;
        }
        if (class_exists(SecurityModule::class) && !SecurityModule::verifyCsrf()) {
          $controller->json(['success' => false, 'message' => 'Token CSRF inválido o ausente.'], 403);
          return false;
        }
      }
    }

    return true;
  }

  /**
   * Registra todas las rutas de desarrollo de Frame Studio si aún no han sido registradas.
   *
   * @return void
   */
  public static function registerRoutes(): void
  {
    if (self::$routesRegistered || !self::isDevEnvironment()) {
      return;
    }
    self::$routesRegistered = true;

    $themeCtrl = \Base\Studio\Controllers\ThemeStudioController::class;
    \Core\Route::get('/_frame/studio', [$themeCtrl, 'index']);
    \Core\Route::get('/_frame/theme-studio', [$themeCtrl, 'index']);
    \Core\Route::post('/_frame/theme-studio/save', [$themeCtrl, 'save']);
    \Core\Route::post('/_frame/theme-studio/load-to-site', [$themeCtrl, 'loadToSite']);
    \Core\Route::post('/_frame/theme-studio/set-active', [$themeCtrl, 'setActive']);
    \Core\Route::post('/_frame/theme-studio/delete-theme', [$themeCtrl, 'deleteThemeEndpoint']);
    \Core\Route::post('/_frame/theme-studio/render-chart', [$themeCtrl, 'renderChart']);
    \Core\Route::post('/_frame/theme-studio/render-input', [$themeCtrl, 'renderInput']);
    \Core\Route::post('/_frame/theme-studio/render-form', [$themeCtrl, 'renderForm']);
    \Core\Route::get('/_frame/theme-studio/fonts', [$themeCtrl, 'fonts']);
    \Core\Route::get('/_frame/theme-studio/icons', [$themeCtrl, 'icons']);

    $jsCtrl = \Base\Studio\Controllers\JsModulesController::class;
    if (class_exists($jsCtrl)) {
      \Core\Route::get('/_frame/studio/js-catalog', [$jsCtrl, 'catalog']);
      \Core\Route::post('/_frame/studio/js-detect', [$jsCtrl, 'detect']);
      \Core\Route::post('/_frame/studio/js-save', [$jsCtrl, 'save']);
    }

    $cfgCtrl = \Base\Studio\Controllers\ConfigController::class;
    if (class_exists($cfgCtrl)) {
      \Core\Route::get('/_frame/studio/config-state', [$cfgCtrl, 'state']);
      \Core\Route::post('/_frame/studio/config-save', [$cfgCtrl, 'save']);
      \Core\Route::post('/_frame/studio/config-generate-key', [$cfgCtrl, 'generateKey']);
      \Core\Route::post('/_frame/studio/config-test-db', [$cfgCtrl, 'testDatabase']);
      \Core\Route::post('/_frame/studio/config-compile', [$cfgCtrl, 'compileProduction']);
    }
  }
}
