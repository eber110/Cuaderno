<?php

namespace App\Components\UserPreview;

use App\Models\DesignModels;
use App\Models\UserModels;

/**
 * Clase userPreviewComponent
 * 
 * Componente autocontenido para renderizar la vista previa en vivo de la tarjeta de usuario en el dashboard.
 * Invoca a DesignModels y UserModels para formatear la tarjeta.
 */
class userPreviewComponent {

  /**
   * Obtiene los datos formateados de la tarjeta para la vista previa.
   *
   * @param string $view Vista objetivo.
   * @param string $viewType Tipo de vista.
   * @param array $params Parámetros del componente.
   * @return array Datos formateados para la vista previa.
   */
  public static function data($view = "UserPreview.index", $viewType = "template", $params = []) {
    $card = $params["data"] ?? [];

    // Si ya viene una tarjeta formateada y estructurada con su contenido o estilo, usarla directamente
    if (is_array($card) && (isset($card["content"]) || isset($card["backCard"]))) {
      return ["card" => $card];
    }

    $user = is_string($params["data"] ?? null)
      ? $params["data"]
      : ($card["profile"] ?? ($params["id"] ?? ($params["user"] ?? "")));

    if (!empty($user)) {
      $dataUser = DesignModels::dataUser($user);
      if ($dataUser && isset($dataUser["card"])) {
        $card = UserModels::formatCardImages($dataUser["card"]);
      }
    }

    return ["card" => $card];
  }

}