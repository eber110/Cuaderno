<?php

namespace Base\Module;

use PDO;
use Exception;

/**
 * Módulo de Analíticas e Infraestructura SQLite para Visitas y Clics.
 * Gestiona de forma síncrona y transaccional el almacenamiento en base de datos local
 * evitando la generación masiva de archivos JSON e inodos en el servidor.
 */
class AnalyticsModule
{
    private static ?PDO $pdo = null;

    /**
     * Obtiene la conexión a la base de datos SQLite.
     * Configurada con WAL (Write-Ahead Logging) para evitar bloqueos por concurrencia.
     */
    public static function getPdo(): PDO
    {
        if (self::$pdo === null) {
            $baseDir = defined('ROUTE_DATABASE') ? rtrim(ROUTE_DATABASE, '/\\') : (defined('ROOT_PATH') ? ROOT_PATH . '/Database' : getcwd() . '/Database');
            if (!is_dir($baseDir)) {
                @mkdir($baseDir, 0755, true);
            }

            if (defined('DB_DRIVER') && DB_DRIVER === 'sqlite' && defined('BD') && !empty(BD)) {
                $dbFile = BD;
                if (!str_starts_with($dbFile, '/') && !preg_match('/^[a-zA-Z]:[\\\\\/]/', $dbFile)) {
                    $dbFile = (defined('ROOT_PATH') ? rtrim(ROOT_PATH, '/\\') : getcwd()) . '/' . ltrim($dbFile, '/\\');
                }
            } else {
                $dbFile = $baseDir . '/clikhub.sqlite';
            }

            try {
                self::$pdo = new PDO("sqlite:" . $dbFile, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_TIMEOUT            => 5
                ]);

                // Activar Write-Ahead Logging (WAL), caché en RAM y mmap para máximo rendimiento en SQLite
                self::$pdo->exec("PRAGMA journal_mode = WAL;");
                self::$pdo->exec("PRAGMA synchronous = NORMAL;");
                self::$pdo->exec("PRAGMA busy_timeout = 5000;");
                self::$pdo->exec("PRAGMA cache_size = -64000;");
                self::$pdo->exec("PRAGMA mmap_size = 268435456;");
                self::$pdo->exec("PRAGMA temp_store = MEMORY;");

                self::initSchema();
            } catch (Exception $e) {
                error_log("AnalyticsModule PDO Connection Error: " . $e->getMessage());
                throw $e;
            }
        }

        return self::$pdo;
    }

    /**
     * Inicializa automáticamente el esquema de tablas e índices si no existen.
     */
    private static function initSchema(): void
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS profile_views (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                profile_id TEXT NOT NULL,
                ip_address TEXT,
                country_code TEXT,
                country_name TEXT,
                city_name TEXT,
                device_type TEXT,
                os TEXT,
                browser TEXT,
                referrer TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX IF NOT EXISTS idx_profile_views_profile ON profile_views(profile_id);
            CREATE INDEX IF NOT EXISTS idx_profile_views_created ON profile_views(created_at);

            CREATE TABLE IF NOT EXISTS link_clicks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                link_id TEXT,
                profile_id TEXT NOT NULL,
                country_code TEXT,
                device_type TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE INDEX IF NOT EXISTS idx_link_clicks_profile ON link_clicks(profile_id);
            CREATE INDEX IF NOT EXISTS idx_link_clicks_created ON link_clicks(created_at);
        ";

        self::$pdo->exec($sql);
    }

    /**
     * Registra una visita a un perfil en la tabla profile_views.
     *
     * @param string $profileId ID o username del perfil (ej: "eber")
     * @param array $data Datos adicionales (ip_address, country_code, country_name, city_name, device_type, os, browser, referrer)
     * @return bool True si se insertó con éxito.
     */
    public static function logProfileView(string $profileId, array $data = []): bool
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO profile_views 
                (profile_id, ip_address, country_code, country_name, city_name, device_type, os, browser, referrer, created_at)
                VALUES 
                (:profile_id, :ip_address, :country_code, :country_name, :city_name, :device_type, :os, :browser, :referrer, :created_at)
            ");

            $nowUtc = gmdate('Y-m-d H:i:s');

            return $stmt->execute([
                ':profile_id'   => mb_strtolower($profileId, 'UTF-8'),
                ':ip_address'   => $data['ip_address'] ?? '',
                ':country_code' => $data['country_code'] ?? 'N/A',
                ':country_name' => $data['country_name'] ?? 'Desconocido',
                ':city_name'    => $data['city_name'] ?? 'Desconocido',
                ':device_type'  => $data['device_type'] ?? 'desktop',
                ':os'           => $data['os'] ?? 'Desconocido',
                ':browser'      => $data['browser'] ?? 'Desconocido',
                ':referrer'     => $data['referrer'] ?? '',
                ':created_at'   => $data['created_at'] ?? $nowUtc
            ]);
        } catch (Exception $e) {
            error_log("AnalyticsModule logProfileView Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un clic en un enlace individual en la tabla link_clicks.
     *
     * @param string $profileId Username o ID del creador
     * @param string $linkId ID o identificador del enlace cliqueado
     * @param array $data Datos adicionales (country_code, device_type)
     * @return bool True si se insertó con éxito.
     */
    public static function logLinkClick(string $profileId, string $linkId, array $data = []): bool
    {
        try {
            $pdo = self::getPdo();
            $stmt = $pdo->prepare("
                INSERT INTO link_clicks 
                (link_id, profile_id, country_code, device_type, created_at)
                VALUES 
                (:link_id, :profile_id, :country_code, :device_type, :created_at)
            ");

            $nowUtc = gmdate('Y-m-d H:i:s');

            return $stmt->execute([
                ':link_id'      => (string)$linkId,
                ':profile_id'   => mb_strtolower($profileId, 'UTF-8'),
                ':country_code' => $data['country_code'] ?? 'N/A',
                ':device_type'  => $data['device_type'] ?? 'desktop',
                ':created_at'   => $data['created_at'] ?? $nowUtc
            ]);
        } catch (Exception $e) {
            error_log("AnalyticsModule logLinkClick Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene resumen de métricas clave (Total Visitas, Visitas Únicas, Clics Totales y CTR).
     */
    public static function getProfileSummary(string $profileId): array
    {
        try {
            $pdo = self::getPdo();
            $profile = mb_strtolower($profileId, 'UTF-8');

            $stmtViews = $pdo->prepare("SELECT COUNT(*) as total_views, COUNT(DISTINCT ip_address) as unique_views FROM profile_views WHERE profile_id = :profile");
            $stmtViews->execute([':profile' => $profile]);
            $views = $stmtViews->fetch() ?: [];

            $stmtClicks = $pdo->prepare("SELECT COUNT(*) as total_clicks FROM link_clicks WHERE profile_id = :profile");
            $stmtClicks->execute([':profile' => $profile]);
            $clicks = $stmtClicks->fetch() ?: [];

            $totalViews  = (int)($views['total_views'] ?? 0);
            $uniqueViews = (int)($views['unique_views'] ?? 0);
            $totalClicks = (int)($clicks['total_clicks'] ?? 0);
            $ctr         = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0.0;

            return [
                'total_views'  => $totalViews,
                'unique_views' => $uniqueViews,
                'total_clicks' => $totalClicks,
                'ctr'          => $ctr
            ];
        } catch (Exception $e) {
            error_log("AnalyticsModule getProfileSummary Error: " . $e->getMessage());
            return ['total_views' => 0, 'unique_views' => 0, 'total_clicks' => 0, 'ctr' => 0];
        }
    }
}
