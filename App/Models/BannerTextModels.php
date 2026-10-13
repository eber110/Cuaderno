<?php

namespace App\Models;

use Base\Module\CookieModule;

class BannerTextModels
{
  /**
   * Colección de 20 textos descriptivos que definen la aplicación.
   *
   * @return array<int, string> Lista de 20 textos.
   */
  public static function getTexts(): array
  {
    return [
      "Un solo link para todo lo que haces. Centraliza tus redes sociales, promociona tus productos y comparte tus proyectos en una página ultraligera, 100% personalizable y diseñada para cargar al instante.",
      "Un solo enlace para todo tu universo digital. Reúne tus redes sociales, enlaces destacados, campañas y productos en una biografía rápida, moderna y totalmente adaptable a tu estilo. Crea tu perfil en segundos y comparte todo tu trabajo desde un único lugar.",
      "Tu espacio digital en un solo enlace. Diseña tu perfil a tu medida, conecta a tu audiencia con todas tus plataformas y administra tus contenidos fácilmente: sin menús pesados, sin costo y con la máxima velocidad de carga.",
      "Todo lo que creas, compartes y vendes reunido en un link instantáneo. Olvídate de configuraciones complicadas: personaliza tu estilo, conecta con tu comunidad y gestiona tu presencia online de forma simple, rápida y gratuita.",
      "Conecta todo tu mundo con un solo clic. Agrupa tus perfiles sociales, tiendas online, contenidos recientes y datos de contacto en una sola página visualmente impecable que carga en milisegundos desde cualquier dispositivo.",
      "Lleva tu presencia online al siguiente nivel sin complicaciones técnicas. Crea una biografía interactiva con tus redes, catálogos y lanzamientos, manteniendo siempre el control total del diseño y los colores de tu marca.",
      "El punto de encuentro perfecto entre tú y tus seguidores. Organiza tus enlaces prioritarios, destaca tus mejores proyectos y dale a tu comunidad una experiencia fluida, moderna y sin distracciones.",
      "Tu carta de presentación digital en un único destino. Personaliza bloques para tus redes, ofertas temporales y contenidos destacados con una herramienta ágil pensada para potenciar tu alcance en internet.",
      "Simplifica cómo te encuentran en internet. Transforma el enlace de tu biografía en un centro de mandos interactivo donde tus seguidores acceden a tus redes, productos y novedades al instante.",
      "Diseña tu bio link con total libertad y sin barreras. Agrega botones personalizados, galerías, enlaces directos a WhatsApp y redes sociales en una plataforma liviana que no consume datos innecesarios.",
      "Una sola dirección web para mostrar todo tu talento y proyectos. Mantén a tu audiencia conectada con tus canales favoritos, eventos y publicaciones importantes a través de una experiencia limpia y profesional.",
      "Tu comunidad merece encontrarte sin rodeos. Reúne tu contenido multimedia, tus canales de comunicación y tus ventas en un enlace optimizado para la máxima velocidad y rendimiento en cualquier móvil.",
      "La forma más inteligente y veloz de compartir quién eres y qué haces. Elige tu combinación visual preferida, publica tus accesos directos y permite que tu audiencia navegue por tu contenido con total fluidez.",
      "Haz que cada visita a tu perfil cuente. Centraliza enlaces a tus proyectos, campañas activas, música, videos y tiendas en una sola página atractiva que responde de inmediato al toque del usuario.",
      "Potencia la visibilidad de tu marca personal o negocio. Conecta tus plataformas de mensajería, redes sociales y productos destacados en una herramienta intuitiva que puedes actualizar en cualquier momento y lugar.",
      "Todo tu ecosistema digital concentrado en una página de alto impacto. Sin código complejo ni tiempos de espera: personaliza tu diseño, comparte tu enlace y ofrece a tus seguidores una navegación rápida y elegante.",
      "El enlace definitivo para tu biografía en Instagram, TikTok y YouTube. Presenta tus novedades, servicios y redes sociales en un entorno moderno que refleja tu identidad y fideliza a tu comunidad.",
      "Crea una experiencia digital memorable para tu audiencia. Organiza tus enlaces de contacto, contenidos prioritarios y ofertas especiales en un perfil ultrarrápido creado para convertir visitas en conexiones reales.",
      "Tu presencia digital organizada exactamente como tú quieres. Actualiza tus bloques en tiempo real, añade enlaces a tus plataformas favoritas y ofrece una vía directa a todo lo que tienes para ofrecer.",
      "Un perfil dinámico y profesional al alcance de todos. Conecta tu trabajo, tus ideas y tus redes en un enlace optimizado que se adapta perfectamente a cualquier pantalla y no deja esperando a tus visitantes."
    ];
  }

  /**
   * Obtiene el texto rotativo actual persistido en cookie por 6 horas.
   *
   * @param int $hours Horas de duración de la cookie (por defecto 6).
   * @return string Texto descriptivo aleatorio.
   */
  public static function getRotatingText(int $hours = 6): string
  {
    $texts = self::getTexts();
    $total = count($texts);
    $cookieName = 'app_rotating_banner_idx';

    $savedIdx = CookieModule::get($cookieName);

    if ($savedIdx !== null && is_numeric($savedIdx)) {
      $idx = (int)$savedIdx;
      if ($idx >= 0 && $idx < $total) {
        return $texts[$idx];
      }
    }

    $newIdx = random_int(0, $total - 1);
    CookieModule::set($cookieName, [
      'value'    => (string)$newIdx,
      'expired'  => $hours * 3600,
      'httponly' => true,
      'samesite' => 'Lax'
    ]);

    return $texts[$newIdx];
  }
}
