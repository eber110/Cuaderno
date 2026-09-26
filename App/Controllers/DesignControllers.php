<?php

namespace App\Controllers;

use App\Models\DesignModels;
use App\Models\LemonSqueezyModels;
use App\Models\StatisticsModels;
use App\Models\UserModels;
use Base\Control\Control;
use Base\Module\ResponseModule;
use Base\Module\Session;

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
    ob_start();
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
        "formDesign"    => "/panel/{$userClean}/diseno",
        "saveDesign"    => "/panel/{$userClean}/guardar",
        "discardDesign" => "/panel/{$userClean}/descartar",
        "simularDatos"  => "/panel/{$userClean}/simular-datos"
      ];

      $previewHtml       = _componentToString("UserPreview.userPreview", ["data" => $cardData]);
      $sessionData       = $_SESSION["user"] ?? [];
      $sidebarStatusHtml = _partToString("Dashboard.SideMenu.statusBanner", [
        "card"    => $cardData,
        "session" => $sessionData
      ]);

      $formHtml = _partToString("Dashboard.contentPanel", [
        "card"    => $cardData,
        "uri"     => $uri,
        "user"    => $userClean,
        "stats"   => [], // Optimización: no recalcular 20 queries de estadísticas al cambiar el diseño
        "session" => $sessionData
      ]);

      while (ob_get_level() > 0) {
        ob_end_clean();
      }

      ResponseModule::json([
        "success"           => true,
        "hasCustom"         => true,
        "html"              => $previewHtml,
        "formHtml"          => $formHtml,
        "sidebarStatusHtml" => $sidebarStatusHtml,
        "card"              => $cardData,
        "videoError"        => DesignModels::$videoUploadError,
        "videoSuccess"      => DesignModels::$videoUploadSuccess
      ]);
    }

    // Redirigir de vuelta al panel del usuario para envíos navegados sincrónicos
    ResponseModule::redirect("/panel/{$userClean}");
  }

  /**
   * Publica oficialmente el diseño guardado en borrador a la versión oficial en SQLite.
   *
   * @param string $user Nombre de usuario.
   * @return void Redirige al panel del usuario.
   */
  public function saveDesign(string $user, array|string $param = []): void {
    $userClean = mb_strtolower($user, "UTF-8");

    // Si recibimos parámetros POST con cambios acumulados del borrador, persistirlos en el modelo
    if (!empty($param) && is_array($param)) {
      DesignModels::updateCustomDesign($userClean, $param);
    }

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

      $sessionData       = $_SESSION["user"] ?? [];
      $sidebarStatusHtml = _partToString("Dashboard.SideMenu.statusBanner", [
        "card"    => $cardData,
        "session" => $sessionData
      ]);

      $previewHtml       = _componentToString("UserPreview.userPreview", ["data" => $cardData]);

      $uri = [
        "formDesign"    => "/panel/{$userClean}/diseno",
        "saveDesign"    => "/panel/{$userClean}/guardar",
        "discardDesign" => "/panel/{$userClean}/descartar",
        "simularDatos"  => "/panel/{$userClean}/simular-datos"
      ];

      $formHtml = _partToString("Dashboard.contentPanel", [
        "card"    => $cardData,
        "uri"     => $uri,
        "user"    => $userClean,
        "stats"   => [], // Optimización: no recalcular 20 queries de estadísticas al guardar
        "session" => $sessionData
      ]);

      while (ob_get_level() > 0) {
        ob_end_clean();
      }

      ResponseModule::json([
        "success"           => true,
        "hasCustom"         => false,
        "html"              => $previewHtml,
        "formHtml"          => $formHtml,
        "sidebarStatusHtml" => $sidebarStatusHtml,
        "card"              => $cardData
      ]);
    }

    // Redirigir de vuelta al panel del usuario
    ResponseModule::redirect("/panel/{$userClean}");
  }

  /**
   * Descarta los cambios del borrador y revierte a la versión oficial en SQLite.
   *
   * @param string $user Nombre de usuario.
   * @return void Redirige al panel del usuario o responde en JSON si es AJAX.
   */
  public function discardDesign(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");

    // Delegar la eliminación del borrador al modelo
    DesignModels::discardDesign($userClean);

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
        "formDesign"    => "/panel/{$userClean}/diseno",
        "saveDesign"    => "/panel/{$userClean}/guardar",
        "discardDesign" => "/panel/{$userClean}/descartar",
        "simularDatos"  => "/panel/{$userClean}/simular-datos"
      ];

      $previewHtml       = _componentToString("UserPreview.userPreview", ["data" => $cardData]);
      $sessionData       = $_SESSION["user"] ?? [];
      $sidebarStatusHtml = _partToString("Dashboard.SideMenu.statusBanner", [
        "card"    => $cardData,
        "session" => $sessionData
      ]);

      $formHtml = _partToString("Dashboard.contentPanel", [
        "card"    => $cardData,
        "uri"     => $uri,
        "user"    => $userClean,
        "stats"   => [], // Optimización: no recalcular 20 queries de estadísticas al descartar
        "session" => $sessionData
      ]);

      while (ob_get_level() > 0) {
        ob_end_clean();
      }

      ResponseModule::json([
        "success"           => true,
        "hasCustom"         => false,
        "html"              => $previewHtml,
        "formHtml"          => $formHtml,
        "sidebarStatusHtml" => $sidebarStatusHtml,
        "card"              => $cardData
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

  /**
   * Genera los parámetros de subida firmados para Cloudinary para subida en 2do plano sin bloquear PHP,
   * incluyendo recorte de inicio/fin y encuadre en aspecto de teléfono (9:16).
   *
   * @param string $user Nombre de usuario.
   * @return void
   */
  public function getCloudinarySignature(string $user): void
  {
    $userClean = mb_strtolower($user, "UTF-8");
    $folder    = "cuaderno/backgrounds/{$userClean}";

    $start    = max(0, floatval($_GET["start"] ?? $_POST["start"] ?? 0));
    $duration = floatval($_GET["duration"] ?? $_POST["duration"] ?? 20);
    if ($duration <= 0 || $duration > 20) {
      $duration = 20;
    }

    $startFormatted = round($start, 1);
    $durFormatted   = round($duration, 1);

    // Transformación: recorte de tiempo + encuadre vertical teléfono 9:16 + compresión nítida 720p
    $transParts = [
      "so_{$startFormatted}",
      "du_{$durFormatted}",
      "ar_9:16",
      "c_fill",
      "g_center",
      "w_720",
      "q_auto",
      "vc_auto",
      "ac_none",
      "f_auto"
    ];
    $transformation = implode(",", $transParts);

    $params = \App\Services\CloudinaryService::createSignedUploadParams($folder, $transformation);
    ResponseModule::json($params);
  }

  /**
   * Extrae metadatos en segundo plano para enlaces o productos pendientes.
   * Diseñado para ser llamado vía Fetch/AJAX desde el navegador sin bloquear
   * la carga del panel ni la navegación.
   *
   * @param string $user Nombre de usuario.
   * @return void Emite respuesta JSON.
   */
  public function extractMetadataBackground(string $user): void {
    $userClean = mb_strtolower($user, "UTF-8");

    // 1. Liberar cerrojo de sesión para que el usuario pueda seguir interactuando
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_write_close();
    }

    // 2. Procesar los metadatos pendientes
    $updated = DesignModels::processPendingMetadata($userClean);

    // 3. Preparar respuesta
    if ($updated) {
      $dataUser = DesignModels::dataUser($userClean);
      $cardData = (isset($dataUser["card"]) && is_array($dataUser["card"])) 
        ? UserModels::formatCardImages($dataUser["card"]) 
        : [];

      if (empty($cardData["profile"])) {
        $cardData["profile"] = $userClean;
      }

      $previewHtml = _componentToString("UserPreview.userPreview", ["data" => $cardData]);

      $sessionData = $_SESSION["user"] ?? [];
      $sidebarStatusHtml = _partToString("Dashboard.SideMenu.statusBanner", [
        "card"    => $cardData,
        "session" => $sessionData
      ]);

      $uri = [
        "formDesign"    => "/panel/{$userClean}/diseno",
        "saveDesign"    => "/panel/{$userClean}/guardar",
        "discardDesign" => "/panel/{$userClean}/descartar",
        "simularDatos"  => "/panel/{$userClean}/simular-datos"
      ];

      $formHtml = _partToString("Dashboard.contentPanel", [
        "card"    => $cardData,
        "uri"     => $uri,
        "user"    => $userClean,
        "stats"   => [],
        "session" => $sessionData
      ]);

      while (ob_get_level() > 0) {
        ob_end_clean();
      }

      ResponseModule::json([
        "success"           => true,
        "updated"           => true,
        "html"              => $previewHtml,
        "formHtml"          => $formHtml,
        "sidebarStatusHtml" => $sidebarStatusHtml,
        "card"              => $cardData
      ]);
    }

    while (ob_get_level() > 0) {
      ob_end_clean();
    }

    ResponseModule::json([
      "success" => true,
      "updated" => false
    ]);
  }

}