<?php
  
namespace App\Controllers;

use Base\Control\Control;
use Base\Module\SeoModule;
use Base\Module\Session;

class HomeControllers extends Control
{
  /**
   * Renderiza la página de inicio (Landing Page) de Clikhub.
   *
   * @return mixed Vista Home.index
   */
  public function home()
  {
    $siteName = defined('NAME_SITE') ? NAME_SITE : 'Clikhub';
    
    SeoModule::setTitle("{$siteName} — Tu bio link ultraligero y personalizable");
    SeoModule::setMetaDescription("Crea tu bio link gratis en segundos. Conecta tus redes sociales, vende productos, comparte videos y lanza campañas en una página limpia que carga en milisegundos.");
    SeoModule::setOpenGraph([
      "title"       => "{$siteName} — Todo lo que haces. En un solo link instantáneo.",
      "site_name"   => $siteName,
      "content"     => "Conecta tus productos, redes, eventos y campañas en una página limpia y ultraligera.",
      "image"       => DIR_UPLOAD_MEDIA_STATIC . "hero.webp",
      "image_width" => 1200,
      "image_height"=> 630,
      "link"        => defined('DOMAIN') ? DOMAIN : "/",
      "type"        => "website"
    ]);

    $data = [
      "connect"  => Session::session_active(),
      "username" => Session::session_data("username")
    ];

    return $this->view("Home.index", $data);
  }
}