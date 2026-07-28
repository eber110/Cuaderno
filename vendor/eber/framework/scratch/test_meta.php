<?php
$url = "www.ebersanchez.cl";
if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
    $url = "https://" . $url;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

$html = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error || empty($html)) {
    die("Error: $error\n");
}

libxml_use_internal_errors(true);
$doc = new DOMDocument();
$html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
$doc->loadHTML($html);
libxml_clear_errors();

$metaTags = [
    'title' => '',
    'description' => '',
    'keywords' => '',
    'og' => [],
    'twitter' => []
];

// Get Title
$titles = $doc->getElementsByTagName('title');
if ($titles->length > 0) {
    $metaTags['title'] = trim($titles->item(0)->textContent);
}

// Get Metas
$metas = $doc->getElementsByTagName('meta');
for ($i = 0; $i < $metas->length; $i++) {
    $meta = $metas->item($i);
    $name = strtolower($meta->getAttribute('name'));
    $property = strtolower($meta->getAttribute('property'));
    $content = trim($meta->getAttribute('content'));

    if (empty($content)) continue;

    if ($name === 'description' || $name === 'keywords') {
        $metaTags[$name] = $content;
    } elseif (strpos($property, 'og:') === 0) {
        $key = substr($property, 3);
        $metaTags['og'][$key] = $content;
    } elseif (strpos($name, 'twitter:') === 0) {
        $key = substr($name, 8);
        $metaTags['twitter'][$key] = $content;
    } elseif (strpos($name, 'og:') === 0) {
        $key = substr($name, 3);
        $metaTags['og'][$key] = $content;
    }
}

print_r($metaTags);
