<?php

namespace Base\Module;

use Base\Module\TextModule;

/**
 * Módulo para generar botones de compartir en redes sociales.
 * 
 * Genera URLs de compartir optimizadas para móvil (apps nativas) y desktop (web).
 * 
 * @example
 * // Obtener todas las URLs de compartir
 * $shares = ShareButtonModule::share('https://example.com', 'Mi contenido');
 * echo $shares['whatsapp']; // URL para WhatsApp
 * 
 * // Solo algunas redes sociales (por ID)
 * $shares = ShareButtonModule::share($url, $text, [6, 8, 1]); // WhatsApp, Telegram, X
 */
class ShareButtonModule
{
  /**
   * Redes sociales disponibles con sus IDs.
   */
  private const NETWORKS = [
    1 => 'x',
    2 => 'facebook',
    3 => 'linkedin',
    4 => 'reddit',
    5 => 'tumblr',
    6 => 'whatsapp',
    7 => 'pinterest',
    8 => 'telegram',
    9 => 'skype',
    10 => 'email',
    11 => 'threads',
    12 => 'bluesky',
    13 => 'mastodon',
    14 => 'vk',
    15 => 'line',
    16 => 'viber',
    17 => 'pocket',
    18 => 'flipboard',
    19 => 'hackernews',
    20 => 'mix',
    21 => 'snapchat'
  ];

  /**
   * Configuración de URLs por red social.
   * Utiliza Universal Links (https://...) para máxima compatibilidad 
   * entre Desktop, Android, iOS y Tablets.
   */
  private static function getNetworkConfig(): array
  {
    return [
      'whatsapp' => [
        'mobile' => 'https://wa.me/?text={text}%20{url}',
        'desktop' => 'https://wa.me/?text={text}%20{url}'
      ],
      'facebook' => [
        'mobile' => 'https://www.facebook.com/sharer/sharer.php?u={url}',
        'desktop' => 'https://www.facebook.com/sharer/sharer.php?u={url}'
      ],
      'x' => [
        'mobile' => 'https://twitter.com/intent/tweet?url={url}&text={text}',
        'desktop' => 'https://twitter.com/intent/tweet?url={url}&text={text}'
      ],
      'linkedin' => [
        'mobile' => 'https://www.linkedin.com/sharing/share-offsite/?url={url}',
        'desktop' => 'https://www.linkedin.com/sharing/share-offsite/?url={url}'
      ],
      'pinterest' => [
        'mobile' => 'https://pinterest.com/pin/create/button/?url={url}&description={text}',
        'desktop' => 'https://pinterest.com/pin/create/button/?url={url}&description={text}'
      ],
      'reddit' => [
        'mobile' => 'https://www.reddit.com/submit?url={url}&title={text}',
        'desktop' => 'https://www.reddit.com/submit?url={url}&title={text}'
      ],
      'telegram' => [
        'mobile' => 'https://t.me/share/url?url={url}&text={text}',
        'desktop' => 'https://t.me/share/url?url={url}&text={text}'
      ],
      'email' => [
        'mobile' => 'mailto:?subject={text}&body={url}',
        'desktop' => 'mailto:?subject={text}&body={url}'
      ],
      'skype' => [
        'mobile' => 'https://web.skype.com/share?url={url}&text={text}',
        'desktop' => 'https://web.skype.com/share?url={url}&text={text}'
      ],
      'tumblr' => [
        'mobile' => 'https://www.tumblr.com/widgets/share/tool?canonicalUrl={url}&caption={text}',
        'desktop' => 'https://www.tumblr.com/widgets/share/tool?canonicalUrl={url}&caption={text}'
      ],
      // === NUEVAS REDES SOCIALES ===
      'threads' => [
        'mobile' => 'https://www.threads.net/intent/post?text={text}%20{url}',
        'desktop' => 'https://www.threads.net/intent/post?text={text}%20{url}'
      ],
      'bluesky' => [
        'mobile' => 'https://bsky.app/intent/compose?text={text}%20{url}',
        'desktop' => 'https://bsky.app/intent/compose?text={text}%20{url}'
      ],
      'mastodon' => [
        'mobile' => 'https://mastodon.social/share?text={text}%20{url}',
        'desktop' => 'https://mastodon.social/share?text={text}%20{url}'
      ],
      'vk' => [
        'mobile' => 'https://vk.com/share.php?url={url}&title={text}',
        'desktop' => 'https://vk.com/share.php?url={url}&title={text}'
      ],
      'line' => [
        'mobile' => 'https://social-plugins.line.me/lineit/share?url={url}',
        'desktop' => 'https://social-plugins.line.me/lineit/share?url={url}'
      ],
      'viber' => [
        // Viber es la rara excepción que aún depende fuertemente de viber://
        'mobile' => 'viber://forward?text={text}%20{url}',
        'desktop' => 'viber://forward?text={text}%20{url}'
      ],
      'pocket' => [
        'mobile' => 'https://getpocket.com/save?url={url}&title={text}',
        'desktop' => 'https://getpocket.com/save?url={url}&title={text}'
      ],
      'flipboard' => [
        'mobile' => 'https://share.flipboard.com/bookmarklet/popout?v=2&url={url}&title={text}',
        'desktop' => 'https://share.flipboard.com/bookmarklet/popout?v=2&url={url}&title={text}'
      ],
      'hackernews' => [
        'mobile' => 'https://news.ycombinator.com/submitlink?u={url}&t={text}',
        'desktop' => 'https://news.ycombinator.com/submitlink?u={url}&t={text}'
      ],
      'mix' => [
        'mobile' => 'https://mix.com/add?url={url}',
        'desktop' => 'https://mix.com/add?url={url}'
      ],
      'snapchat' => [
        'mobile' => 'https://www.snapchat.com/share?link={url}',
        'desktop' => 'https://www.snapchat.com/share?link={url}'
      ]
    ];
  }

  /**
   * Genera URLs de compartir para redes sociales.
   * 
   * @param string $url URL a compartir.
   * @param string $text Texto descriptivo.
   * @param array $networks IDs de redes a incluir (vacío = todas).
   *   IDs: 1=X, 2=Facebook, 3=LinkedIn, 4=Reddit, 5=Tumblr, 
   *        6=WhatsApp, 7=Pinterest, 8=Telegram, 9=Skype, 10=Email,
   *        11=Threads, 12=Bluesky, 13=Mastodon, 14=VK, 15=Line,
   *        16=Viber, 17=Pocket, 18=Flipboard, 19=HackerNews, 20=Mix,
   *        21=Snapchat
   * @return array URLs indexadas por nombre de red.
   */
  public static function share(string $url, string $text, array $networks = []): array
  {
    // Limpiar texto usando TextModule
    $cleanText = TextModule::clean($text);

    // URL encode
    $encodedUrl = urlencode($url);
    $encodedText = urlencode($cleanText);

    // Detectar si es móvil
    $isMobile = MovilDetectorModule::is_movil() === 1;

    // Determinar qué redes generar
    $networksToGenerate = empty($networks)
      ? self::NETWORKS
      : array_intersect_key(self::NETWORKS, array_flip($networks));

    $config = self::getNetworkConfig();
    $result = [];

    foreach ($networksToGenerate as $id => $name) {
      if (!isset($config[$name])) {
        continue;
      }

      $template = $isMobile ? $config[$name]['mobile'] : $config[$name]['desktop'];
      $result[$name] = self::buildUrl($template, $encodedUrl, $encodedText);
    }

    return $result;
  }

  /**
   * Genera URL de compartir para una red específica.
   * 
   * @param string $network Nombre de la red (whatsapp, facebook, x, etc).
   * @param string $url URL a compartir.
   * @param string $text Texto descriptivo.
   * @return string|null URL generada o null si no existe la red.
   */
  public static function shareOne(string $network, string $url, string $text): ?string
  {
    $network = strtolower($network);
    $config = self::getNetworkConfig();

    if (!isset($config[$network])) {
      return null;
    }

    $cleanText = TextModule::clean($text);
    $encodedUrl = urlencode($url);
    $encodedText = urlencode($cleanText);

    $isMobile = MovilDetectorModule::is_movil() === 1;
    $template = $isMobile ? $config[$network]['mobile'] : $config[$network]['desktop'];

    return self::buildUrl($template, $encodedUrl, $encodedText);
  }

  /**
   * Obtiene la URL para desktop (web) ignorando detección móvil.
   * 
   * @param string $network Nombre de la red.
   * @param string $url URL a compartir.
   * @param string $text Texto descriptivo.
   * @return string|null URL generada.
   */
  public static function getDesktopUrl(string $network, string $url, string $text): ?string
  {
    $network = strtolower($network);
    $config = self::getNetworkConfig();

    if (!isset($config[$network])) {
      return null;
    }

    $cleanText = TextModule::clean($text);
    return self::buildUrl(
      $config[$network]['desktop'],
      urlencode($url),
      urlencode($cleanText)
    );
  }

  /**
   * Obtiene la URL para móvil (app scheme) ignorando detección.
   * 
   * @param string $network Nombre de la red.
   * @param string $url URL a compartir.
   * @param string $text Texto descriptivo.
   * @return string|null URL generada.
   */
  public static function getMobileUrl(string $network, string $url, string $text): ?string
  {
    $network = strtolower($network);
    $config = self::getNetworkConfig();

    if (!isset($config[$network])) {
      return null;
    }

    $cleanText = TextModule::clean($text);
    return self::buildUrl(
      $config[$network]['mobile'],
      urlencode($url),
      urlencode($cleanText)
    );
  }

  /**
   * Obtiene lista de redes disponibles.
   * 
   * @return array IDs y nombres de redes.
   */
  public static function getAvailableNetworks(): array
  {
    return self::NETWORKS;
  }

  /**
   * Construye la URL final reemplazando placeholders.
   */
  private static function buildUrl(string $template, string $url, string $text): string
  {
    return str_replace(
      ['{url}', '{text}'],
      [$url, $text],
      $template
    );
  }
}
