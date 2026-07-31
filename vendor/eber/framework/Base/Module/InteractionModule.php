<?php

namespace Base\Module;

/**
 * InteractionModule - Generador de Payloads de Interacción
 * 
 * Este módulo está completamente desacoplado de la base de datos.
 * Su único propósito es recolectar, estructurar y devolver un array estandarizado
 * con todos los datos de la interacción (contexto del usuario, link, botón, hora, etc.)
 * para que el Controlador pueda enviarlo al Modelo encargado de guardarlo (en JSON, SQLite, MySQL, etc).
 */
class InteractionModule
{
    // Tipos de interacción estándar
    const TYPE_LIKE = 'like';
    const TYPE_FAVORITE = 'favorite';
    const TYPE_SAVE = 'save';
    const TYPE_SHARE = 'share';
    const TYPE_VIEW = 'view';
    const TYPE_CLICK = 'click';

    /**
     * Construye y agrupa todos los datos de una interacción en un array estructurado (payload).
     *
     * @param array $config Opciones configuradas de entrada (ej: tipo, id de entidad, enlace, usuario, etc).
     * @return array Array completo listo para ser procesado/guardado por el Modelo.
     */
    public static function build(array $config): array
    {
        // 1. Recolectar contexto automático
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $timestamp = date('Y-m-d H:i:s');
        
        // 2. Generar identificadores
        $guestId = hash('sha256', $ip . $userAgent);
        $interactionId = uniqid('int_', true); // ID único para rastreo cruzado

        // 3. Estructurar el payload final fusionando la configuración enviada con el contexto automático
        return [
            'interaction_id'   => $config['interaction_id'] ?? $interactionId,
            'type'             => $config['type'] ?? 'unknown',
            'entity_id'        => $config['entity_id'] ?? null,
            'entity_type'      => $config['entity_type'] ?? 'post', // Ej: post, comment, user, product
            'user_id'          => $config['user_id'] ?? null,
            'guest_identifier' => $guestId,
            'link_url'         => $config['link'] ?? null,
            'button_id'        => $config['button_id'] ?? null,
            'timestamp'        => $config['timestamp'] ?? $timestamp,
            'ip_address'       => $ip,
            'user_agent'       => $userAgent,
            'metadata'         => $config['metadata'] ?? [] // Array extra para datos arbitrarios (ej. coordenadas, tiempo en página)
        ];
    }

    /**
     * Helper: Construye rápidamente un payload para un LIKE
     */
    public static function buildLike($entityId, $userId = null, array $extraConfig = []): array
    {
        return self::build(array_merge([
            'type' => self::TYPE_LIKE,
            'entity_id' => $entityId,
            'user_id' => $userId
        ], $extraConfig));
    }

    /**
     * Helper: Construye rápidamente un payload para un FAVORITO
     */
    public static function buildFavorite($entityId, $userId = null, array $extraConfig = []): array
    {
        return self::build(array_merge([
            'type' => self::TYPE_FAVORITE,
            'entity_id' => $entityId,
            'user_id' => $userId
        ], $extraConfig));
    }

    /**
     * Helper: Construye rápidamente un payload para una VISTA (View)
     */
    public static function buildView($entityId, $userId = null, array $extraConfig = []): array
    {
        return self::build(array_merge([
            'type' => self::TYPE_VIEW,
            'entity_id' => $entityId,
            'user_id' => $userId
        ], $extraConfig));
    }

    /**
     * Helper: Construye rápidamente un payload para un CLIC general en enlace
     */
    public static function buildClick($link, $userId = null, array $extraConfig = []): array
    {
        return self::build(array_merge([
            'type' => self::TYPE_CLICK,
            'link' => $link,
            'user_id' => $userId
        ], $extraConfig));
    }
    
    /**
     * Helper: Construye rápidamente un payload para COMPARTIR
     */
    public static function buildShare($entityId, $userId = null, array $extraConfig = []): array
    {
        return self::build(array_merge([
            'type' => self::TYPE_SHARE,
            'entity_id' => $entityId,
            'user_id' => $userId
        ], $extraConfig));
    }
}
