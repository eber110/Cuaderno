<?php

namespace Base\Module;

use Base\Builder\Builder;

/**
 * InteractionModule - Módulo para manejar interacciones de usuarios
 * 
 * Maneja likes, favoritos, guardados y shares de manera unificada.
 * Soporta tanto usuarios autenticados como visitantes anónimos.
 * 
 * @example
 * $interaction = new InteractionModule();
 * 
 * // Toggle like (añade si no existe, elimina si existe)
 * $interaction->toggleLike($postId, $userId);
 * 
 * // Añadir a favoritos
 * $interaction->addFavorite($postId, $userId);
 * 
 * // Verificar si usuario dio like
 * $hasLiked = $interaction->hasInteraction($postId, $userId, 'like');
 * 
 * // Contar likes de un post
 * $likeCount = $interaction->countInteractions($postId, 'like');
 */
class InteractionModule
{
  protected string $table = 'interactions';

  // Tipos de interacción válidos
  const TYPE_LIKE = 'like';
  const TYPE_FAVORITE = 'favorite';
  const TYPE_SAVE = 'save';
  const TYPE_SHARE = 'share';

  protected array $validTypes = [
    self::TYPE_LIKE,
    self::TYPE_FAVORITE,
    self::TYPE_SAVE,
    self::TYPE_SHARE
  ];

  /**
   * Crea una nueva instancia del Builder con la tabla de interacciones
   * 
   * @return Builder
   */
  protected function getBuilder(): Builder
  {
    return new Builder($this->table);
  }

  /**
   * Añade una interacción
   * 
   * @param int $postId ID del post
   * @param int|null $userId ID del usuario (null si es anónimo)
   * @param string $type Tipo de interacción (like, favorite, save, share)
   * @param string|null $guestIdentifier Identificador para usuarios anónimos
   * @return bool
   */
  public function add(int $postId, ?int $userId, string $type, ?string $guestIdentifier = null): bool
  {
    if (!$this->isValidType($type)) {
      return false;
    }

    // Verificar si ya existe
    if ($this->hasInteraction($postId, $userId, $type, $guestIdentifier)) {
      return true; // Ya existe, no hacer nada
    }

    $data = [
      'post_id_i' => $postId,
      'interaction_type' => $type
    ];

    if ($userId !== null) {
      $data['user_id_i'] = $userId;
    } else if ($guestIdentifier !== null) {
      $data['guest_identifier_i'] = $guestIdentifier;
    } else {
      // Generar identificador basado en IP
      $data['guest_identifier_i'] = $this->generateGuestIdentifier();
    }

    try {
      $this->getBuilder()->create($data);
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Elimina una interacción
   * 
   * @param int $postId ID del post
   * @param int|null $userId ID del usuario
   * @param string $type Tipo de interacción
   * @param string|null $guestIdentifier Identificador para usuarios anónimos
   * @return bool
   */
  public function remove(int $postId, ?int $userId, string $type, ?string $guestIdentifier = null): bool
  {
    if (!$this->isValidType($type)) {
      return false;
    }

    // Buscar el ID de la interacción primero
    $query = $this->getBuilder()
      ->select('id_interaction')
      ->where('post_id_i', $postId)
      ->where('interaction_type', $type);

    if ($userId !== null) {
      $query->where('user_id_i', $userId);
    } else {
      $identifier = $guestIdentifier ?? $this->generateGuestIdentifier();
      $query->where('guest_identifier_i', $identifier);
    }

    $result = $query->get_one();

    if (empty($result) || !isset($result[0]['id_interaction'])) {
      return false; // No existe la interacción
    }

    try {
      $this->getBuilder()->delete('id_interaction', $result[0]['id_interaction']);
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Toggle: añade si no existe, elimina si existe
   * 
   * @param int $postId ID del post
   * @param int|null $userId ID del usuario
   * @param string $type Tipo de interacción
   * @param string|null $guestIdentifier Identificador para usuarios anónimos
   * @return array ['action' => 'added'|'removed', 'success' => bool]
   */
  public function toggle(int $postId, ?int $userId, string $type, ?string $guestIdentifier = null): array
  {
    if ($this->hasInteraction($postId, $userId, $type, $guestIdentifier)) {
      $success = $this->remove($postId, $userId, $type, $guestIdentifier);
      return ['action' => 'removed', 'success' => $success];
    } else {
      $success = $this->add($postId, $userId, $type, $guestIdentifier);
      return ['action' => 'added', 'success' => $success];
    }
  }

  /**
   * Métodos de acceso rápido para toggle
   */
  public function toggleLike(int $postId, ?int $userId, ?string $guestIdentifier = null): array
  {
    return $this->toggle($postId, $userId, self::TYPE_LIKE, $guestIdentifier);
  }

  public function toggleFavorite(int $postId, ?int $userId, ?string $guestIdentifier = null): array
  {
    return $this->toggle($postId, $userId, self::TYPE_FAVORITE, $guestIdentifier);
  }

  public function toggleSave(int $postId, ?int $userId, ?string $guestIdentifier = null): array
  {
    return $this->toggle($postId, $userId, self::TYPE_SAVE, $guestIdentifier);
  }

  /**
   * Métodos de acceso rápido para añadir
   */
  public function addLike(int $postId, ?int $userId, ?string $guestIdentifier = null): bool
  {
    return $this->add($postId, $userId, self::TYPE_LIKE, $guestIdentifier);
  }

  public function addFavorite(int $postId, ?int $userId, ?string $guestIdentifier = null): bool
  {
    return $this->add($postId, $userId, self::TYPE_FAVORITE, $guestIdentifier);
  }

  public function addSave(int $postId, ?int $userId, ?string $guestIdentifier = null): bool
  {
    return $this->add($postId, $userId, self::TYPE_SAVE, $guestIdentifier);
  }

  public function addShare(int $postId, ?int $userId, ?string $guestIdentifier = null): bool
  {
    return $this->add($postId, $userId, self::TYPE_SHARE, $guestIdentifier);
  }

  /**
   * Verifica si existe una interacción
   * 
   * @param int $postId ID del post
   * @param int|null $userId ID del usuario
   * @param string $type Tipo de interacción
   * @param string|null $guestIdentifier Identificador para usuarios anónimos
   * @return bool
   */
  public function hasInteraction(int $postId, ?int $userId, string $type, ?string $guestIdentifier = null): bool
  {
    $query = $this->getBuilder()
      ->where('post_id_i', $postId)
      ->where('interaction_type', $type);

    if ($userId !== null) {
      $query->where('user_id_i', $userId);
    } else {
      $identifier = $guestIdentifier ?? $this->generateGuestIdentifier();
      $query->where('guest_identifier_i', $identifier);
    }

    $result = $query->get_one();
    return !empty($result) && isset($result[0]);
  }

  /**
   * Métodos de verificación rápida
   */
  public function hasLiked(int $postId, ?int $userId, ?string $guestIdentifier = null): bool
  {
    return $this->hasInteraction($postId, $userId, self::TYPE_LIKE, $guestIdentifier);
  }

  public function hasFavorited(int $postId, ?int $userId, ?string $guestIdentifier = null): bool
  {
    return $this->hasInteraction($postId, $userId, self::TYPE_FAVORITE, $guestIdentifier);
  }

  public function hasSaved(int $postId, ?int $userId, ?string $guestIdentifier = null): bool
  {
    return $this->hasInteraction($postId, $userId, self::TYPE_SAVE, $guestIdentifier);
  }

  /**
   * Cuenta las interacciones de un post por tipo
   * 
   * @param int $postId ID del post
   * @param string|null $type Tipo de interacción (null para todas)
   * @return int
   */
  public function countInteractions(int $postId, ?string $type = null): int
  {
    $query = $this->getBuilder()
      ->where('post_id_i', $postId);

    if ($type !== null && $this->isValidType($type)) {
      $query->where('interaction_type', $type);
    }

    $result = $query->count('id_interaction', 'total')->get_one();
    return (int)($result[0]['total'] ?? 0);
  }

  /**
   * Métodos de conteo rápido
   */
  public function countLikes(int $postId): int
  {
    return $this->countInteractions($postId, self::TYPE_LIKE);
  }

  public function countFavorites(int $postId): int
  {
    return $this->countInteractions($postId, self::TYPE_FAVORITE);
  }

  public function countSaves(int $postId): int
  {
    return $this->countInteractions($postId, self::TYPE_SAVE);
  }

  public function countShares(int $postId): int
  {
    return $this->countInteractions($postId, self::TYPE_SHARE);
  }

  /**
   * Obtiene todas las interacciones de un usuario
   * 
   * @param int $userId ID del usuario
   * @param string|null $type Tipo de interacción (null para todas)
   * @param int $limit Límite de resultados
   * @param int $offset Offset para paginación
   * @return array
   */
  public function getUserInteractions(int $userId, ?string $type = null, int $limit = 20, int $offset = 0): array
  {
    $query = $this->getBuilder()
      ->where('user_id_i', $userId)
      ->order('created_at_interaction', 'DESC');

    if ($type !== null && $this->isValidType($type)) {
      $query->where('interaction_type', $type);
    }

    return $query->pag($limit, $offset) ?? [];
  }

  /**
   * Obtiene los posts favoritos de un usuario
   */
  public function getUserFavorites(int $userId, int $limit = 20, int $offset = 0): array
  {
    return $this->getUserInteractions($userId, self::TYPE_FAVORITE, $limit, $offset);
  }

  /**
   * Obtiene los posts guardados de un usuario
   */
  public function getUserSaved(int $userId, int $limit = 20, int $offset = 0): array
  {
    return $this->getUserInteractions($userId, self::TYPE_SAVE, $limit, $offset);
  }

  /**
   * Obtiene los posts que un usuario ha dado like
   */
  public function getUserLiked(int $userId, int $limit = 20, int $offset = 0): array
  {
    return $this->getUserInteractions($userId, self::TYPE_LIKE, $limit, $offset);
  }

  /**
   * Obtiene un resumen de interacciones para un post
   * 
   * @param int $postId ID del post
   * @param int|null $userId ID del usuario actual (para verificar si interactuó)
   * @param string|null $guestIdentifier Identificador del visitante
   * @return array
   */
  public function getPostSummary(int $postId, ?int $userId = null, ?string $guestIdentifier = null): array
  {
    $summary = [
      'likes' => $this->countLikes($postId),
      'favorites' => $this->countFavorites($postId),
      'saves' => $this->countSaves($postId),
      'shares' => $this->countShares($postId),
      'user_has_liked' => false,
      'user_has_favorited' => false,
      'user_has_saved' => false
    ];

    // Si hay usuario o identificador de visitante, verificar sus interacciones
    if ($userId !== null || $guestIdentifier !== null) {
      $summary['user_has_liked'] = $this->hasLiked($postId, $userId, $guestIdentifier);
      $summary['user_has_favorited'] = $this->hasFavorited($postId, $userId, $guestIdentifier);
      $summary['user_has_saved'] = $this->hasSaved($postId, $userId, $guestIdentifier);
    }

    return $summary;
  }

  /**
   * Elimina todas las interacciones de un post
   * 
   * @param int $postId ID del post
   * @return bool
   */
  public function removeAllForPost(int $postId): bool
  {
    try {
      // Buscar todos los IDs de interacciones del post
      $results = $this->getBuilder()
        ->select('id_interaction')
        ->where('post_id_i', $postId)
        ->get_all();

      if (empty($results)) {
        return true; // No hay nada que eliminar
      }

      $ids = array_column($results, 'id_interaction');
      $this->getBuilder()->delete('id_interaction', $ids);
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Elimina todas las interacciones de un usuario
   * 
   * @param int $userId ID del usuario
   * @return bool
   */
  public function removeAllForUser(int $userId): bool
  {
    try {
      // Buscar todos los IDs de interacciones del usuario
      $results = $this->getBuilder()
        ->select('id_interaction')
        ->where('user_id_i', $userId)
        ->get_all();

      if (empty($results)) {
        return true; // No hay nada que eliminar
      }

      $ids = array_column($results, 'id_interaction');
      $this->getBuilder()->delete('id_interaction', $ids);
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  /**
   * Verifica si el tipo de interacción es válido
   */
  protected function isValidType(string $type): bool
  {
    return in_array($type, $this->validTypes);
  }

  /**
   * Genera un identificador único para visitantes anónimos
   * Basado en IP + User Agent (hash)
   */
  protected function generateGuestIdentifier(): string
  {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    return hash('sha256', $ip . $userAgent);
  }

  /**
   * Obtiene los posts más populares por tipo de interacción
   * 
   * @param string $type Tipo de interacción
   * @param int $limit Límite de resultados
   * @return array
   */
  public function getMostPopular(string $type, int $limit = 10): array
  {
    if (!$this->isValidType($type)) {
      return [];
    }

    try {
      return $this->getBuilder()
        ->select('post_id_i')
        ->count('id_interaction', 'total')
        ->where('interaction_type', $type)
        ->group('post_id_i')
        ->order('total', 'DESC')
        ->pag($limit) ?? [];
    } catch (\Exception $e) {
      return [];
    }
  }
}
