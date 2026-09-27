<?php

namespace App\Providers;

use Base\Providers\ServiceProvider;
use Base\Module\Security\AccessBlocker;

/**
 * Clase SecurityProvider
 *
 * Service Provider que inicializa el subsistema de seguridad y verifica bloqueos de IP en cada petición.
 */
class SecurityProvider extends ServiceProvider
{
  /**
   * Registra servicios básicos en el contenedor.
   *
   * @return void
   */
  public function register(): void
  {
  }

  /**
   * Ejecuta la comprobación de acceso y bloqueos de seguridad.
   *
   * @return void
   */
  public function boot(): void
  {
    AccessBlocker::checkAndBlockRequest();
  }
}
