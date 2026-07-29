<?php

namespace Base\Module;

use DOMDocument;
use Exception;

class RequestMetaModule
{
    /**
     * Extrae las meta etiquetas principales (título, descripción, keywords, og:meta y twitter:meta) de un sitio web.
     *
     * @param string $url La URL del sitio web a consultar.
     * @return array|false Retorna un arreglo con los metadatos estructurados o false en caso de error.
     */
    public static function requestMeta(string $url)
    {
        // Asegurarse de que la URL tenga el esquema http o https
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "https://" . $url;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        // Deshabilitar la verificación SSL estricta para evitar errores con certificados locales o autofirmados
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // Usar el User-Agent del crawler de Facebook. 
        // Sitios como LinkedIn, Twitter o Instagram bloquean peticiones automatizadas con User-Agents genéricos (HTTP 999),
        // pero tienen listas blancas (whitelist) para los bots de redes sociales y así poder mostrar las "cards" al compartir enlaces.
        curl_setopt($ch, CURLOPT_USERAGENT, 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)');

        $html = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || empty($html)) {
            return false;
        }

        $metaTags = [
            'title' => '',
            'description' => '',
            'keywords' => '',
            'og' => [],
            'twitter' => []
        ];

        // Solución para parsear correctamente los caracteres UTF-8 en DOMDocument
        $html = '<?xml encoding="UTF-8">' . $html;

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        // Cargar el HTML supresionando las advertencias (ya que el HTML moderno casi nunca es XML válido)
        $doc->loadHTML($html);
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
