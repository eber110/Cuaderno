<?php

namespace Base\Module;

use Base\Module\Security\SafeHttpClient;
use DOMDocument;
use Exception;

class RequestMetaModule
{
    /**
     * Extrae las meta etiquetas principales (título, descripción, keywords, og:meta y twitter:meta) de un sitio web.
     * Utiliza SafeHttpClient con protección anti-SSRF (IPv4/IPv6 + CURLOPT_RESOLVE) y tope de tamaño.
     *
     * @param string $url La URL del sitio web a consultar.
     * @return array|false Retorna un arreglo con los metadatos estructurados o false en caso de error.
     */
    public static function requestMeta(string $url)
    {
        // Asegurarse de que la URL tenga el esquema http o https
        if (!preg_match("~^https?://~i", $url)) {
            $url = "https://" . $url;
        }

        $res = SafeHttpClient::fetch($url, [
            'timeout' => 4,
            'connectTimeout' => 3,
            'maxBytes' => 2097152, // 2 MB límite para parseo seguro de <head>
            'userAgent' => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
        ]);

        if (empty($res['success']) || empty($res['body'])) {
            return false;
        }

        $html = $res['body'];

        $metaTags = [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og' => [],
            'twitter' => []
        ];

        // Solución para parsear correctamente los caracteres UTF-8 en DOMDocument sin red externa
        $html = '<?xml encoding="UTF-8">' . $html;

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML($html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        // Extraer la etiqueta <title>
        $titles = $doc->getElementsByTagName('title');
        if ($titles->length > 0) {
            $metaTags['title'] = trim($titles->item(0)->textContent);
        }

        // Extraer las etiquetas <meta>
        $metas = $doc->getElementsByTagName('meta');
        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);
            
            $name = strtolower(trim($meta->getAttribute('name')));
            $property = strtolower(trim($meta->getAttribute('property')));
            $content = trim($meta->getAttribute('content'));

            if (empty($content)) {
                continue;
            }

            // Metas estándar
            if ($name === 'description' || $name === 'keywords') {
                $metaTags[$name] = $content;
            } 
            // Open Graph (og:) usando 'property'
            elseif (strpos($property, 'og:') === 0) {
                $key = substr($property, 3);
                $metaTags['og'][$key] = $content;
            } 
            // Twitter Cards usando 'name'
            elseif (strpos($name, 'twitter:') === 0) {
                $key = substr($name, 8);
                $metaTags['twitter'][$key] = $content;
            } 
            // Fallback para sitios que configuran og: usando 'name' en lugar de 'property'
            elseif (strpos($name, 'og:') === 0) {
                $key = substr($name, 3);
                $metaTags['og'][$key] = $content;
            }
        }

        // Si no hay un título principal, pero sí un og:title, lo asignamos como fallback
        if (empty($metaTags['title']) && !empty($metaTags['og']['title'])) {
            $metaTags['title'] = $metaTags['og']['title'];
        }
        
        // Lo mismo para description
        if (empty($metaTags['description']) && !empty($metaTags['og']['description'])) {
            $metaTags['description'] = $metaTags['og']['description'];
        }

        return $metaTags;
    }
}
