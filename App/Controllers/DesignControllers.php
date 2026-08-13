<?php

namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;

/**
 * Clase DesignControllers
 * 
 * Controlador delgado encargado de recibir peticiones HTTP de diseño y configuración de tarjetas.
 * Cumple con SRP delegando la lógica de imágenes, metadatos y guardado en archivos al modelo DesignModels.
 */
class DesignControllers extends Control {

  /**
   * Inicializa la tarjeta por defecto del usuario delegando al modelo.
   *
   * @param string $user Nombre de usuario.
   * @return void
   */
  public static function initialDesign(string $user): void {
    DesignModels::createInitialDesign($user);
  }

  /**
   * Procesa la solicitud de configuración de diseño (cambios visuales, imágenes, enlaces).
   *
   * @param string $user Nombre de usuario.
   * @param array|string $param Parámetros POST recibidos.
   * @return void Redirige al panel del usuario o responde en JSON si es AJAX.
   */
  public function configDesign(string $user, array|string $param): void {
    $userClean = mb_strtolower($user, "UTF-8");

    // Delegar el procesamiento completo de actualización al modelo DesignModels
    DesignModels::updateCustomDesign($userClean, $param);

    // Detectar solicitudes AJAX / Fetch
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isAjax) {
      $dataUser = DesignModels::dataUser($userClean);
      $cardData = (isset($dataUser["card"]) && is_array($dataUser["card"])) 
        ? UserModels::formatCardImages($dataUser["card"]) 
        : [];

      if (empty($cardData["profile"])) {
        $cardData["profile"] = $userClean;
      }

      $uri = [
        "formDesign"   => "/panel/{$userClean}/diseno",
        "saveDesign"   => "/panel/{$userClean}/guardar",
        "simularDatos" => "/panel/{$userClean}/simular-datos"
      ];

      $previewHtml = _componentToString("UserPreview.userPreview", ["data" => $cardData]);
      $formHtml    = _partToString("Dashboard.contentPanel", [
        "card" => $cardData,
        "uri"  => $uri
      ]);

      ResponseModule::json([
        "success"  => true,
        "html"     => $previewHtml,
        "formHtml" => $formHtml,
        "card"     => $cardData
      ]);
    }

    // Redirigir de vuelta al panel del usuario para envíos navegados sincrónicos
    ResponseModule::redirect("/panel/{$userClean}");
  }

  /**
   * Publica oficialmente el diseño guardado en UserCustom a UserData.
   *
   * @param string $user Nombre de usuario.
   * @return void Redirige al panel del usuario.
   */
  public function saveDesign(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");

    // Delegar la publicación oficial del diseño al modelo DesignModels
    DesignModels::publishDesign($userClean);

    // Detectar solicitudes AJAX / Fetch
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
      || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

    if ($isAjax) {
      $dataUser = DesignModels::dataUser($userClean);
      $cardData = (isset($dataUser["card"]) && is_array($dataUser["card"])) 
        ? UserModels::formatCardImages($dataUser["card"]) 
        : [];

      if (empty($cardData["profile"])) {
        $cardData["profile"] = $userClean;
      }

      $previewHtml = _componentToString("UserPreview.userPreview", ["data" => $cardData]);

      ResponseModule::json([
        "success" => true,
        "html"    => $previewHtml,
        "card"    => $cardData
      ]);
    }

    // Redirigir de vuelta al panel del usuario
    ResponseModule::redirect("/panel/{$userClean}");
  }

  public static function orderShare() : array{
    
    //redes aceptadas con card og:
    $acceptedLinks = [1,2,3,6,8,21,4,5,11,12,13,14,15,16,17,18,20,10];
    return $acceptedLinks;
  
  }

}