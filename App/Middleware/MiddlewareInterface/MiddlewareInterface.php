<?php

namespace App\Middleware\MiddlewareInterface;

/**
 * Interfaz para todos los Middlewares de la aplicación.
 *
 * Define el contrato que toda clase de middleware debe seguir.
 * Obliga a la implementación de un método `handle` que procesa la solicitud.
 */
interface MiddlewareInterface{
  /**
   * Maneja una solicitud entrante.
   *
   * @param mixed $requestData Los datos de la solicitud actual (ej. $_GET, $_POST).
   * @param callable $next El siguiente middleware en la pila o el controlador final.
   * @return mixed La respuesta generada por el siguiente middleware o el controlador.
   */
  public function handle($requestData, callable $next);
  
}