<?php

namespace Base\Module;

/**
 * Módulo de eventos pub/sub.
 * 
 * Sistema simple de eventos para desacoplar componentes.
 * 
 * @example
 * // Registrar listener
 * EventModule::on('user.created', function($data) {
 *   sendWelcomeEmail($data['email']);
 * });
 * 
 * // Disparar evento
 * EventModule::trigger('user.created', ['email' => 'user@example.com']);
 */
class EventModule
{
  private static array $events = [];

  /**
   * Registra un listener para un evento.
   * 
   * @param string $event Nombre del evento.
   * @param callable $callback Función a ejecutar.
   * @param int $priority Prioridad (mayor = primero). Default: 10.
   * @return void
   */
  public static function on(string $event, callable $callback, int $priority = 10): void
  {
    if (!isset(self::$events[$event])) {
      self::$events[$event] = [];
    }

    self::$events[$event][] = [
      'callback' => $callback,
      'priority' => $priority
    ];

    // Ordenar por prioridad (mayor primero)
    usort(self::$events[$event], fn($a, $b) => $b['priority'] <=> $a['priority']);
  }

  /**
   * Dispara un evento.
   * 
   * @param string $event Nombre del evento.
   * @param array $data Datos a pasar a los listeners.
   * @return array Resultados de los callbacks.
   */
  public static function trigger(string $event, array $data = []): array
  {
    $results = [];

    if (isset(self::$events[$event])) {
      foreach (self::$events[$event] as $listener) {
        $results[] = call_user_func($listener['callback'], $data);
      }
    }

    return $results;
  }

  /**
   * Elimina un listener específico o todos los listeners de un evento.
   * 
   * @param string $event Nombre del evento.
   * @param callable|null $callback Callback específico a eliminar, o null para todos.
   * @return bool True si se eliminó algo.
   */
  public static function off(string $event, ?callable $callback = null): bool
  {
    if (!isset(self::$events[$event])) {
      return false;
    }

    if ($callback === null) {
      unset(self::$events[$event]);
      return true;
    }

    $initial = count(self::$events[$event]);

    self::$events[$event] = array_filter(
      self::$events[$event],
      fn($listener) => $listener['callback'] !== $callback
    );

    return count(self::$events[$event]) < $initial;
  }

  /**
   * Registra un listener que se ejecuta una sola vez.
   * 
   * @param string $event Nombre del evento.
   * @param callable $callback Función a ejecutar.
   * @return void
   */
  public static function once(string $event, callable $callback): void
  {
    $wrapper = function ($data) use ($event, $callback, &$wrapper) {
      self::off($event, $wrapper);
      return $callback($data);
    };

    self::on($event, $wrapper);
  }

  /**
   * Verifica si un evento tiene listeners registrados.
   * 
   * @param string $event Nombre del evento.
   * @return bool True si tiene listeners.
   */
  public static function has(string $event): bool
  {
    return isset(self::$events[$event]) && count(self::$events[$event]) > 0;
  }

  /**
   * Obtiene todos los eventos registrados.
   * 
   * @return array Lista de nombres de eventos.
   */
  public static function getEvents(): array
  {
    return array_keys(self::$events);
  }

  /**
   * Limpia todos los eventos.
   * 
   * @return void
   */
  public static function reset(): void
  {
    self::$events = [];
  }
}
