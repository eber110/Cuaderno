<?php

namespace Base\Providers;

/**
 * ServiceProvider
 * 
 * Clase base abstracta para todos los Service Providers.
 * Extiende esta clase para crear nuevos providers con una estructura consistente.
 * 
 * Ciclo de vida:
 * 1. register() - Registrar bindings y configuraciones básicas
 * 2. boot() - Ejecutar lógica después de que todos los providers se registraron
 */
abstract class ServiceProvider
{
  /**
   * Registra bindings básicos en el contenedor.
   * Se ejecuta ANTES de boot() de cualquier provider.
   * 
   * NO uses otros servicios aquí, podrían no estar disponibles.
   * 
   * @return void
   */
  public function register(): void
  {
    // Implementar en clases hijas si es necesario
  }

  /**
   * Ejecuta acciones de inicialización.
   * Se ejecuta DESPUÉS de que todos los providers llamaron register().
   * 
   * Aquí SÍ puedes usar otros servicios y cargar datos.
   * 
   * @return void
   */
  public function boot(): void
  {
    // Implementar en clases hijas si es necesario
  }
}
