<?php

namespace Base\Module\Security;

use Base\Module\SecurityModule;

/**
 * Cliente HTTP cURL endurecido contra SSRF, DNS Rebinding, redirecciones inseguras,
 * fugas TLS y agotamiento de memoria por respuestas gigantes.
 */
class SafeHttpClient
{
  /**
   * Rangos CIDR adicionales bloqueados más allá de FILTER_FLAG_NO_PRIV_RANGE | NO_RES_RANGE.
   */
  private const BLOCKED_CIDRS = [
    '0.0.0.0/8',
    '10.0.0.0/8',
    '100.64.0.0/10',
    '127.0.0.0/8',
    '169.254.0.0/16',
    '172.16.0.0/12',
    '192.0.0.0/24',
    '192.0.2.0/24',
    '192.168.0.0/16',
    '198.18.0.0/15',
    '198.51.100.0/24',
    '203.0.113.0/24',
    '224.0.0.0/4',
    '240.0.0.0/4',
    '::/128',
    '::1/128',
    'fc00::/7',
    'fe80::/10',
    'ff00::/8',
  ];

  /**
   * Verifica si una IP (IPv4 o IPv6, incluyendo mapeadas ::ffff:x.x.x.x) es pública y enrutable.
   */
  public static function isPublicIp(string $ip): bool
  {
    $clean = trim($ip, "[] \t\n\r\0\x0B");
    if (filter_var($clean, FILTER_VALIDATE_IP) === false) {
      return false;
    }

    // Extraer IPv4 si es dirección IPv6 mapeada ::ffff:x.x.x.x
    if (stripos($clean, '::ffff:') === 0) {
      $mapped = substr($clean, 7);
      if (filter_var($mapped, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $clean = $mapped;
      }
    }

    if (filter_var($clean, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
      return false;
    }

    foreach (self::BLOCKED_CIDRS as $cidr) {
      if (SecurityModule::isIpInCidr($clean, $cidr)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Resuelve todas las IPs (IPv4 e IPv6) de un host, valida que todas sean públicas
   * y prepara entradas para CURLOPT_RESOLVE evitando TOCTOU / DNS Rebinding.
   *
   * @return array{ok: bool, error?: string, status?: int, host?: string, port?: int, ip?: string, resolve?: array<int, string>}
   */
  public static function validateUrlAndResolve(string $url, array $allowedDomains = [], array $allowedPorts = [80, 443]): array
  {
    if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url)) {
      return ['ok' => false, 'status' => 400, 'error' => 'URL inválida'];
    }

    $parsed = parse_url($url);
    $scheme = strtolower((string)($parsed['scheme'] ?? ''));
    $host = strtolower(trim((string)($parsed['host'] ?? ''), '[]'));
    $port = isset($parsed['port']) ? (int)$parsed['port'] : ($scheme === 'https' ? 443 : 80);

    if ($host === '' || !in_array($scheme, ['http', 'https'], true)) {
      return ['ok' => false, 'status' => 400, 'error' => 'Protocolo o host no permitido'];
    }

    if (!in_array($port, $allowedPorts, true)) {
      return ['ok' => false, 'status' => 403, 'error' => 'Puerto destino no permitido'];
    }

    if (!empty($allowedDomains)) {
      $domainAllowed = false;
      foreach ($allowedDomains as $domain) {
        $d = strtolower(trim((string)$domain));
        if ($d !== '' && ($host === $d || str_ends_with($host, '.' . $d))) {
          $domainAllowed = true;
          break;
        }
      }
      if (!$domainAllowed) {
        return ['ok' => false, 'status' => 403, 'error' => 'Dominio no permitido'];
      }
    }

    $resolvedIps = [];
    if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
      $resolvedIps[] = $host;
    } else {
      $recordsA = @dns_get_record($host, DNS_A) ?: [];
      foreach ($recordsA as $rec) {
        if (!empty($rec['ip'])) {
          $resolvedIps[] = (string)$rec['ip'];
        }
      }

      $recordsAAAA = @dns_get_record($host, DNS_AAAA) ?: [];
      foreach ($recordsAAAA as $rec) {
        if (!empty($rec['ipv6'])) {
          $resolvedIps[] = (string)$rec['ipv6'];
        }
      }

      if (empty($resolvedIps)) {
        $fallback = @gethostbynamel($host);
        if (is_array($fallback)) {
          $resolvedIps = $fallback;
        }
      }
    }

    $resolvedIps = array_values(array_unique(array_filter($resolvedIps)));
    if (empty($resolvedIps)) {
      return ['ok' => false, 'status' => 404, 'error' => 'No se pudo resolver el host'];
    }

    foreach ($resolvedIps as $ip) {
      if (!self::isPublicIp($ip)) {
        return ['ok' => false, 'status' => 403, 'error' => 'Acceso a dirección IP privada o reservada denegado'];
      }
    }

    $pinnedIp = $resolvedIps[0];
    $formattedPin = str_contains($pinnedIp, ':') ? "[{$pinnedIp}]" : $pinnedIp;

    return [
      'ok' => true,
      'host' => $host,
      'port' => $port,
      'ip' => $pinnedIp,
      'resolve' => ["{$host}:{$port}:{$formattedPin}"],
    ];
  }

  /**
   * Realiza una petición GET segura con DNS pinning (CURLOPT_RESOLVE), TLS estricto y tope de bytes.
   *
   * @return array{success: bool, httpCode: int, contentType: string, body: string, error: string|null, status: int}
   */
  public static function fetch(string $url, array $options = []): array
  {
    $allowedDomains = (array)($options['allowedDomains'] ?? []);
    $allowedPorts = (array)($options['allowedPorts'] ?? [80, 443]);
    $timeout = (int)($options['timeout'] ?? 15);
    $connectTimeout = (int)($options['connectTimeout'] ?? 5);
    $maxBytes = (int)($options['maxBytes'] ?? 5242880); // 5 MB por defecto
    $userAgent = (string)($options['userAgent'] ?? 'Mozilla/5.0 (compatible; FrameSafeClient/1.0)');

    $validation = self::validateUrlAndResolve($url, $allowedDomains, $allowedPorts);
    if (empty($validation['ok'])) {
      return [
        'success' => false,
        'httpCode' => 0,
        'contentType' => '',
        'body' => '',
        'error' => (string)($validation['error'] ?? 'URL rechazada'),
        'status' => (int)($validation['status'] ?? 403),
      ];
    }

    $body = '';
    $sizeExceeded = false;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RESOLVE, $validation['resolve']);
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);

    if (defined('CURLOPT_MAXFILESIZE')) {
      curl_setopt($ch, CURLOPT_MAXFILESIZE, $maxBytes);
    }

    if (class_exists('\\Composer\\CaBundle\\CaBundle')) {
      $caBundle = \Composer\CaBundle\CaBundle::getSystemCaRootBundlePath();
      if ($caBundle && file_exists($caBundle)) {
        curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
      }
    }

    curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function ($curlHandle, string $chunk) use (&$body, &$sizeExceeded, $maxBytes): int {
      $len = strlen($chunk);
      if (strlen($body) + $len > $maxBytes) {
        $sizeExceeded = true;
        return 0; // Aborta descarga al superar el límite máximo de bytes
      }
      $body .= $chunk;
      return $len;
    });

    curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = (string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: '');
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($sizeExceeded) {
      return [
        'success' => false,
        'httpCode' => $httpCode,
        'contentType' => $contentType,
        'body' => '',
        'error' => 'El recurso remoto excede el tamaño máximo permitido',
        'status' => 413,
      ];
    }

    if ($curlError !== '' || $httpCode < 200 || $httpCode >= 300 || $body === '') {
      return [
        'success' => false,
        'httpCode' => $httpCode,
        'contentType' => $contentType,
        'body' => '',
        'error' => $curlError ?: 'No se pudo obtener el recurso remoto',
        'status' => $httpCode >= 400 ? $httpCode : 404,
      ];
    }

    return [
      'success' => true,
      'httpCode' => $httpCode,
      'contentType' => $contentType,
      'body' => $body,
      'error' => null,
      'status' => 200,
    ];
  }
}
