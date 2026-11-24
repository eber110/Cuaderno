<?php

namespace App\Models;

use Base\Builder\Builder;

/**
 * Clase HomeModels
 *
 * Modelo encargado de obtener y preparar los recursos dinámicos de la página de inicio.
 */
class HomeModels extends Builder
{
  /**
   * Extensiones de imagen permitidas para el carrusel del teléfono en el Hero.
   */
  private const ALLOWED_IMAGE_EXTENSIONS = ["webp", "png", "jpg", "jpeg", "avif", "gif"];

  /**
   * Obtiene dinámicamente la lista ordenada de imágenes disponibles en App/Public/Img/PhoneHero/.
   * Si la carpeta está vacía o no existe, utiliza como respaldo las imágenes hero*.webp raíz.
   *
   * @return array<int, array{src: string, alt: string}> Lista de slides listos para renderizar.
   */
  public static function getHeroPhoneSlides(): array
  {
    $rootPath   = defined("ROOT_PATH") ? rtrim(str_replace("\\", "/", ROOT_PATH), "/") : rtrim(str_replace("\\", "/", getcwd()), "/");
    $staticBase = defined("DIR_UPLOAD_MEDIA_STATIC") ? rtrim((string)DIR_UPLOAD_MEDIA_STATIC, "/") : "/App/Public/Img";

    $phoneHeroDir = $rootPath . "/App/Public/Img/PhoneHero";
    $slides       = [];

    if (is_dir($phoneHeroDir)) {
      $entries = scandir($phoneHeroDir);
      if (is_array($entries)) {
        $files = [];
        foreach ($entries as $entry) {
          if ($entry === "." || $entry === ".." || str_starts_with($entry, ".")) {
            continue;
          }

          $fullPath = $phoneHeroDir . "/" . $entry;
          if (!is_file($fullPath)) {
            continue;
          }

          $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
          if (in_array($ext, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            $files[] = $entry;
          }
        }

        usort($files, "strnatcasecmp");

        foreach ($files as $index => $file) {
          $slides[] = [
            "src" => $staticBase . "/PhoneHero/" . rawurlencode($file),
            "alt" => "Vista previa de perfil Clikhub " . ($index + 1)
          ];
        }
      }
    }

    if (!empty($slides)) {
      return $slides;
    }

    $fallbacks = ["hero.webp", "hero2.webp", "hero3.webp", "hero4.webp"];
    foreach ($fallbacks as $index => $fallback) {
      if (is_file($rootPath . "/App/Public/Img/" . $fallback)) {
        $slides[] = [
          "src" => $staticBase . "/" . $fallback,
          "alt" => "Vista previa de perfil Clikhub " . ($index + 1)
        ];
      }
    }

    return $slides;
  }
}
