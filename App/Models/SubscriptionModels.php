<?php

namespace App\Models;

use Base\Builder\BuilderSqlite;

/**
 * Clase SubscriptionModels
 * 
 * Modelo encargado de la gestión, consulta y sincronización de suscripciones
 * y servicios acumulables (Free, Premium y futuros add-ons) en SQLite (user_subscriptions_cache).
 */
class SubscriptionModels extends BuilderSqlite {

  protected $table = "user_subscriptions_cache";

  // Identificadores de servicios del sistema
  public const SERVICE_FREE       = 1;
  public const SERVICE_PREMIUM    = 2;
  public const SERVICE_GIVEAWAYS  = 3; // Reservado para futura mejora
  public const SERVICE_GHOST_MODE = 4; // Reservado para futura mejora

  /**
   * Busca la fila de suscripción en caché por index_user, user_id o username.
   *
   * @param string $userOrIndex Identificador del usuario.
   * @return array|null Datos de la suscripción o null si no se encuentra.
   */
  public static function findSubscriptionRow(string $userOrIndex): ?array {
    try {
      $clean = trim($userOrIndex);
      $cleanLower = mb_strtolower($clean, "UTF-8");

      $row = (new self())
        ->where("index_user", "=", $clean)
        ->orWhere("user_id", "=", $clean)
        ->orWhere("username", "=", $cleanLower)
        ->get_one();

      return !empty($row[0]) ? $row[0] : null;
    } catch (\Throwable $e) {
      error_log("Error en SubscriptionModels::findSubscriptionRow: " . $e->getMessage());
      return null;
    }
  }

  /**
   * Inicializa la suscripción Free (1) por defecto para un nuevo usuario registrado.
   *
   * @param string $indexUser Identificador único index_user.
   * @param string $userId ID de usuario o identificador de registro.
   * @param string $username Nombre de usuario.
   * @return bool True si se inicializó correctamente, false en caso contrario.
   */
  public static function initializeFreeSubscription(string $indexUser, string $userId, string $username): bool {
    try {
      $cleanIndex = trim($indexUser);
      $cleanUser  = mb_strtolower(trim($username), "UTF-8");
      $cleanId    = trim($userId);

      // Comprobar si ya existe un registro previo
      $existing = self::findSubscriptionRow($cleanIndex) ?? self::findSubscriptionRow($cleanId);

      if (!empty($existing)) {
        // Si ya existe pero no tiene services asignado, asegurar '1'
        if (empty($existing["services"])) {
          (new self())->update("user_id", $existing["user_id"], ["services" => "1"]);
        }
        return true;
      }

      // Insertar nuevo registro con plan Free activo
      $inserted = (new self())->create([
        "user_id"    => !empty($cleanId) ? $cleanId : $cleanIndex,
        "index_user" => $cleanIndex,
        "username"   => $cleanUser,
        "is_premium" => 0,
        "services"   => "1",
        "status"     => "active"
      ]);

      return (bool)$inserted;
    } catch (\Throwable $e) {
      error_log("Error en SubscriptionModels::initializeFreeSubscription: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Obtiene la lista de IDs de servicios activos para el usuario.
   *
   * @param string $userOrIndex Nombre de usuario, index_user o user_id.
   * @return int[] Lista de IDs numéricos de servicios activos, ej. [1] o [1, 2].
   */
  public static function getServices(string $userOrIndex): array {
    try {
      $row = self::findSubscriptionRow($userOrIndex);

      if (!empty($row)) {
        $servicesStr = (string)($row["services"] ?? "");
        $isPremium   = (int)($row["is_premium"] ?? 0);

        if (!empty($servicesStr)) {
          $parts = array_map('intval', array_filter(explode(',', $servicesStr), 'is_numeric'));
          if (!in_array(self::SERVICE_FREE, $parts, true)) {
            $parts[] = self::SERVICE_FREE;
          }
          if ($isPremium === 1 && !in_array(self::SERVICE_PREMIUM, $parts, true)) {
            $parts[] = self::SERVICE_PREMIUM;
          }
          return array_values(array_unique($parts));
        }

        // Si services está vacío pero is_premium es 1
        return $isPremium === 1 ? [self::SERVICE_FREE, self::SERVICE_PREMIUM] : [self::SERVICE_FREE];
      }

      // Por defecto cualquier usuario registrado tiene el servicio Free
      return [self::SERVICE_FREE];
    } catch (\Throwable $e) {
      error_log("Error en SubscriptionModels::getServices: " . $e->getMessage());
      return [self::SERVICE_FREE];
    }
  }

  /**
   * Comprueba si el usuario tiene contratado o activo un servicio específico.
   *
   * @param string $userOrIndex Identificador del usuario.
   * @param int $serviceId ID del servicio (1: Free, 2: Premium, etc.).
   * @return bool True si el servicio está activo, false en caso contrario.
   */
  public static function hasService(string $userOrIndex, int $serviceId): bool {
    $services = self::getServices($userOrIndex);
    return in_array($serviceId, $services, true);
  }

  /**
   * Comprueba si el usuario cuenta con suscripción Premium activa.
   *
   * @param string $userOrIndex Identificador del usuario.
   * @return bool True si es premium, false en caso contrario.
   */
  public static function isPremium(string $userOrIndex): bool {
    return self::hasService($userOrIndex, self::SERVICE_PREMIUM);
  }

  /**
   * Añade un servicio al usuario de forma acumulable.
   *
   * @param string $userOrIndex Identificador del usuario.
   * @param int $serviceId ID del servicio a añadir.
   * @return bool True si se actualizó con éxito, false en caso contrario.
   */
  public static function addService(string $userOrIndex, int $serviceId): bool {
    try {
      $row = self::findSubscriptionRow($userOrIndex);
      $targetKey = !empty($row["user_id"]) ? $row["user_id"] : trim($userOrIndex);

      $current = self::getServices($userOrIndex);
      if (!in_array($serviceId, $current, true)) {
        $current[] = $serviceId;
      }
      sort($current);
      $newServicesStr = implode(',', $current);

      $updateData = ["services" => $newServicesStr];
      if ($serviceId === self::SERVICE_PREMIUM) {
        $updateData["is_premium"] = 1;
        $updateData["status"]     = "active";
      }

      (new self())->update("user_id", $targetKey, $updateData);

      return true;
    } catch (\Throwable $e) {
      error_log("Error en SubscriptionModels::addService: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Remueve un servicio del usuario manteniendo siempre el plan Free activo.
   *
   * @param string $userOrIndex Identificador del usuario.
   * @param int $serviceId ID del servicio a remover (no se puede remover el 1).
   * @return bool True si se removió con éxito, false en caso contrario.
   */
  public static function removeService(string $userOrIndex, int $serviceId): bool {
    if ($serviceId === self::SERVICE_FREE) {
      return false; // El plan Free no se puede remover
    }

    try {
      $row = self::findSubscriptionRow($userOrIndex);
      $targetKey = !empty($row["user_id"]) ? $row["user_id"] : trim($userOrIndex);

      $current = self::getServices($userOrIndex);
      $current = array_values(array_filter($current, fn($id) => $id !== $serviceId));
      if (!in_array(self::SERVICE_FREE, $current, true)) {
        $current[] = self::SERVICE_FREE;
      }
      sort($current);
      $newServicesStr = implode(',', $current);

      $updateData = ["services" => $newServicesStr];
      if ($serviceId === self::SERVICE_PREMIUM) {
        $updateData["is_premium"] = 0;
      }

      (new self())->update("user_id", $targetKey, $updateData);

      return true;
    } catch (\Throwable $e) {
      error_log("Error en SubscriptionModels::removeService: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Obtiene la fila completa del registro de suscripción del usuario.
   *
   * @param string $userOrIndex Identificador del usuario.
   * @return array|null Datos de suscripción o null si no existe.
   */
  public static function getUserSubscription(string $userOrIndex): ?array {
    return self::findSubscriptionRow($userOrIndex);
  }

  /**
   * Determina si el sistema de suscripciones y planes premium está activo en producción.
   *
   * @return bool True si está publicado en producción, false si debe permanecer oculto.
   */
  public static function isProductionEnabled(): bool {
    return StatisticsModels::canViewSubscriptions();
  }

}
